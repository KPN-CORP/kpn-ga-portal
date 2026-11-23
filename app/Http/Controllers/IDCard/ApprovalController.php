<?php

namespace App\Http\Controllers\IDCard;

use App\Models\IDCard\RequestIdCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ApprovalController extends IDCardBaseController
{
    public function approve(Request $req, $id)
    {
        if (!$this->canProses() || !$this->canAccessRequest($id)) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        $item = RequestIdCard::findOrFail($id);
        if ($item->status != 'pending') {
            return back()->with('error', 'Request sudah diproses.');
        }

        // Validasi untuk magang/magang_extend/visitor (nomor kartu diisi saat approve)
        if (in_array($item->kategori, RequestIdCard::KATEGORI_PERIODE)) {
            $rules = [
                'nomor_kartu' => [
                    'required',
                    'string',
                    'max:50',
                    // Nomor kartu hanya bentrok dengan kartu yang SEDANG AKTIF. Kartu yang sudah
                    // dikembalikan / nonaktif boleh dipakai lagi (kartu fisik dipakai berulang).
                    function ($attribute, $value, $fail) use ($item, $id) {
                        $bentrok = RequestIdCard::where('id', '<>', $id)
                            ->where('status', 'approved')
                            ->where('is_active', 1)
                            ->where('nomor_kartu', $value);

                        // Extend / visitor: boleh memakai nomor kartu miliknya sendiri (NIK sama)
                        if ($item->kategori !== 'magang') {
                            $bentrok->where('nik', '<>', $item->nik);
                        }

                        if ($bentrok->exists()) {
                            $fail('Nomor kartu ini masih dipakai kartu aktif lain. Kembalikan / nonaktifkan kartu tersebut terlebih dahulu.');
                        }
                    },
                ],
            ];
            if ($req->has('sampai_tanggal') && !empty($req->sampai_tanggal)) {
                $rules['sampai_tanggal'] = $item->kategori === 'visitor'
                    ? 'date|after_or_equal:' . optional($item->masa_berlaku)->format('Y-m-d')
                    : 'date|after:masa_berlaku';
            }
            $validator = Validator::make($req->all(), $rules);
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
        }

        DB::beginTransaction();
        try {
            // Kunci baris supaya dua admin tidak memproses request yang sama bersamaan
            $item = RequestIdCard::lockForUpdate()->findOrFail($id);
            if ($item->status != 'pending') {
                DB::rollBack();
                return back()->with('error', 'Request sudah diproses.');
            }

            if (in_array($item->kategori, RequestIdCard::KATEGORI_PERIODE)) {
                if ($req->has('nomor_kartu') && !empty($req->nomor_kartu)) {
                    $item->nomor_kartu = $req->nomor_kartu;
                }
                if ($req->has('sampai_tanggal') && !empty($req->sampai_tanggal)) {
                    $item->sampai_tanggal = $req->sampai_tanggal;
                }
            }

            $item->status = 'approved';
            $item->approved_by = Auth::id();
            $item->approved_at = now();
            $item->rejected_by = null;
            $item->rejected_at = null;
            $item->rejection_reason = null;
            $item->result_email_sent = 0; // cron kirim email hasil ke pemohon
            $item->save();

            DB::table('request_idcard_logs')->insert([
                'request_id' => $id,
                'action' => 'approved',
                'action_by' => Auth::id(),
                'notes' => 'Request ID Card disetujui'
                    . (!empty($item->nomor_kartu) ? ' | Nomor kartu: ' . $item->nomor_kartu : ''),
                'created_at' => now()
            ]);
            DB::commit();
            return back()->with('success', 'Request telah disetujui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyetujui: ' . $e->getMessage());
        }
    }

    public function reject(Request $req, $id)
    {
        if (!$this->canProses() || !$this->canAccessRequest($id)) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        $item = RequestIdCard::findOrFail($id);
        if ($item->status != 'pending') {
            return back()->with('error', 'Request sudah diproses.');
        }

        $validator = Validator::make($req->all(), [
            'rejection_reason' => 'required|string|min:5|max:500'
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            $item = RequestIdCard::lockForUpdate()->findOrFail($id);
            if ($item->status != 'pending') {
                DB::rollBack();
                return back()->with('error', 'Request sudah diproses.');
            }

            $item->status = 'rejected';
            $item->rejection_reason = $req->rejection_reason;
            $item->rejected_by = Auth::id();
            $item->rejected_at = now();
            $item->approved_by = null;
            $item->approved_at = null;
            $item->result_email_sent = 0; // cron kirim email hasil ke pemohon
            $item->save();

            DB::table('request_idcard_logs')->insert([
                'request_id' => $id,
                'action' => 'rejected',
                'action_by' => Auth::id(),
                'notes' => 'Request ID Card ditolak: ' . $req->rejection_reason,
                'created_at' => now()
            ]);
            DB::commit();
            return back()->with('error', 'Request telah ditolak.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menolak: ' . $e->getMessage());
        }
    }
}
