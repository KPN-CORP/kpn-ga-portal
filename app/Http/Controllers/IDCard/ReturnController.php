<?php

namespace App\Http\Controllers\IDCard;

use App\Models\IDCard\RequestIdCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

/**
 * Alur pengembalian kartu fisik (magang, magang_extend, visitor).
 *
 *   requested (diajukan pemohon) --[admin terima]--> returned  (is_active = 0)
 *                                \--[admin tolak ]--> rejected  (pemohon boleh mengajukan lagi)
 *
 * Admin juga bisa menerima kartu langsung (tanpa pengajuan) lewat approve().
 * Email ke admin / pemohon dikirim oleh cron_email_idcard.php (flag *_email_sent).
 */
class ReturnController extends IDCardBaseController
{
    // ---------------------------------------------------------------
    // PEMOHON: ajukan pengembalian
    // ---------------------------------------------------------------
    public function request(Request $req, $id)
    {
        $item = RequestIdCard::findOrFail($id);

        if (empty($item->user_id) || (int) $item->user_id !== (int) Auth::id()) {
            abort(403, 'Hanya pemohon kartu ini yang dapat mengajukan pengembalian.');
        }

        $validator = Validator::make($req->all(), [
            'return_kondisi' => 'required|in:baik,rusak,hilang',
            'return_note'    => 'nullable|string|max:500',
        ], [
            'return_kondisi.required' => 'Pilih kondisi kartu yang dikembalikan.',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if (!RequestIdCard::terkini()->whereKey($id)->exists()) {
            return back()->with('error', 'Kartu ini sudah digantikan oleh perpanjangan (extend). Ajukan pengembalian pada kartu terbaru.');
        }

        // Ada request lain (mis. extend) yang masih menunggu untuk NIK yang sama
        $adaPending = RequestIdCard::where('nik', $item->nik)
            ->where('status', 'pending')
            ->exists();
        if ($adaPending) {
            return back()->with('error', 'Masih ada request lain dengan NIK ini yang menunggu diproses. Selesaikan atau batalkan dulu sebelum mengembalikan kartu.');
        }

        DB::beginTransaction();
        try {
            $row = RequestIdCard::lockForUpdate()->findOrFail($id);
            if (!$row->can_return) {
                DB::rollBack();
                return back()->with('error', 'Kartu ini tidak dapat diajukan pengembalian (sudah diajukan, dikembalikan, atau tidak aktif).');
            }

            $row->return_status         = 'requested';
            $row->return_kondisi        = $req->return_kondisi;
            $row->return_note           = $req->return_note;
            $row->return_requested_by   = Auth::id();
            $row->return_requested_at   = now();
            $row->return_processed_by   = null;
            $row->return_processed_at   = null;
            $row->return_reject_reason  = null;
            $row->return_email_sent     = 0; // cron kirim email ke admin
            $row->save();

            DB::table('request_idcard')->where('id', $id)->update(['return_reminder_last' => null]);

            DB::table('request_idcard_logs')->insert([
                'request_id' => $id,
                'action'     => 'return_requested',
                'action_by'  => Auth::id(),
                'notes'      => 'Pengajuan pengembalian kartu. Kondisi: '
                    . (RequestIdCard::RETURN_KONDISI_LABELS[$req->return_kondisi] ?? $req->return_kondisi)
                    . (!empty($req->return_note) ? ' | Catatan: ' . $req->return_note : ''),
                'created_at' => now(),
            ]);

            DB::commit();
            return back()->with('success', 'Pengajuan pengembalian terkirim. Serahkan kartu fisik ke GA; status selesai setelah GA mengonfirmasi.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Return request error: ' . $e->getMessage());
            return back()->with('error', 'Gagal mengajukan pengembalian: ' . $e->getMessage());
        }
    }

    // ---------------------------------------------------------------
    // ADMIN: terima kartu (dari pengajuan, atau langsung di meja GA)
    // ---------------------------------------------------------------
    public function approve(Request $req, $id)
    {
        if (!$this->canProses() || !$this->canAccessRequest($id)) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        $validator = Validator::make($req->all(), [
            'nomor_kartu'    => 'nullable|string|max:50',
            'return_kondisi' => 'nullable|in:baik,rusak,hilang',
            'return_note'    => 'nullable|string|max:500',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $item = RequestIdCard::lockForUpdate()->findOrFail($id);

            if (!in_array($item->kategori, RequestIdCard::KATEGORI_KEMBALI, true)
                || $item->status !== 'approved'
                || !$item->is_active
                || $item->return_status === 'returned') {
                DB::rollBack();
                return back()->with('error', 'Kartu ini tidak bisa diproses sebagai pengembalian (bukan magang/visitor, tidak aktif, atau sudah dikembalikan).');
            }

            $kondisi = $req->return_kondisi ?: ($item->return_kondisi ?: 'baik');
            $nomor   = $req->filled('nomor_kartu') ? trim($req->nomor_kartu) : $item->nomor_kartu;
            $langsung = $item->return_status !== 'requested'; // tanpa pengajuan pemohon

            // Rantai extend: baris lain dengan NIK + nomor kartu sama yang masih aktif ikut dikembalikan
            $rantaiIds = collect();
            if (!empty($nomor)) {
                $rantaiIds = RequestIdCard::where('id', '<>', $item->id)
                    ->where('status', 'approved')
                    ->where('is_active', 1)
                    ->whereIn('kategori', RequestIdCard::KATEGORI_KEMBALI)
                    ->where('nik', $item->nik)
                    ->where('nomor_kartu', $nomor)
                    ->pluck('id');
            }

            $item->nomor_kartu          = $nomor;
            $item->is_active            = 0;
            $item->return_status        = 'returned';
            $item->return_kondisi       = $kondisi;
            $item->return_note          = $req->filled('return_note') ? $req->return_note : $item->return_note;
            $item->return_processed_by  = Auth::id();
            $item->return_processed_at  = now();
            $item->return_reject_reason = null;
            // Pemohon hanya dikabari bila ia sendiri yang mengajukan
            $item->return_result_email_sent = $langsung ? 1 : 0;
            $item->save();

            DB::table('request_idcard_logs')->insert([
                'request_id' => $item->id,
                'action'     => 'return_approved',
                'action_by'  => Auth::id(),
                'notes'      => ($langsung ? 'Kartu diterima langsung oleh GA. ' : 'Pengembalian kartu dikonfirmasi GA. ')
                    . 'Kondisi: ' . (RequestIdCard::RETURN_KONDISI_LABELS[$kondisi] ?? $kondisi)
                    . (!empty($nomor) ? ' | Nomor kartu: ' . $nomor : '')
                    . ($rantaiIds->isNotEmpty() ? ' | Ikut dinonaktifkan: #' . $rantaiIds->implode(', #') : ''),
                'created_at' => now(),
            ]);

            foreach ($rantaiIds as $rid) {
                DB::table('request_idcard')->where('id', $rid)->update([
                    'is_active'                => 0,
                    'return_status'            => 'returned',
                    'return_kondisi'           => $kondisi,
                    'return_processed_by'      => Auth::id(),
                    'return_processed_at'      => now(),
                    'return_result_email_sent' => 1,
                ]);
                DB::table('request_idcard_logs')->insert([
                    'request_id' => $rid,
                    'action'     => 'return_approved',
                    'action_by'  => Auth::id(),
                    'notes'      => 'Kartu fisik sudah dikembalikan lewat request #' . $item->id . ' (nomor kartu sama)',
                    'created_at' => now(),
                ]);
            }

            DB::commit();
            return back()->with('success', "Kartu {$item->nama} berhasil ditandai sudah dikembalikan.");
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Return approve error: ' . $e->getMessage());
            return back()->with('error', 'Gagal memproses pengembalian: ' . $e->getMessage());
        }
    }

    // ---------------------------------------------------------------
    // ADMIN: tolak pengajuan (mis. kartu fisik belum diterima)
    // ---------------------------------------------------------------
    public function reject(Request $req, $id)
    {
        if (!$this->canProses() || !$this->canAccessRequest($id)) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        $validator = Validator::make($req->all(), [
            'return_reject_reason' => 'required|string|min:5|max:500',
        ], [
            'return_reject_reason.required' => 'Alasan penolakan wajib diisi.',
            'return_reject_reason.min'      => 'Alasan penolakan minimal 5 karakter.',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $item = RequestIdCard::lockForUpdate()->findOrFail($id);

            if ($item->return_status !== 'requested') {
                DB::rollBack();
                return back()->with('error', 'Tidak ada pengajuan pengembalian yang menunggu untuk kartu ini.');
            }

            $item->return_status           = 'rejected';
            $item->return_processed_by     = Auth::id();
            $item->return_processed_at     = now();
            $item->return_reject_reason    = $req->return_reject_reason;
            $item->return_result_email_sent = 0; // cron kabari pemohon
            $item->save();

            DB::table('request_idcard_logs')->insert([
                'request_id' => $id,
                'action'     => 'return_rejected',
                'action_by'  => Auth::id(),
                'notes'      => 'Pengajuan pengembalian ditolak: ' . $req->return_reject_reason,
                'created_at' => now(),
            ]);

            DB::commit();
            return back()->with('error', 'Pengajuan pengembalian ditolak.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menolak pengajuan: ' . $e->getMessage());
        }
    }
}
