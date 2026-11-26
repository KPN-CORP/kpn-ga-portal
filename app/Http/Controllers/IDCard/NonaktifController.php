<?php

namespace App\Http\Controllers\IDCard;

use App\Models\IDCard\RequestIdCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class NonaktifController extends IDCardBaseController
{
    // Nonaktifkan satu kartu (admin bisa, dengan edit nomor kartu opsional)
    public function nonaktifkanSatu(Request $req, $id)
    {
        if (!$this->canProses() || !$this->canAccessRequest($id)) {
            return redirect()->route('no-access')->with('error', 'Anda tidak memiliki akses.');
        }

        $item = RequestIdCard::findOrFail($id);

        if ($item->status !== 'approved') {
            return back()->with('error', 'Hanya kartu yang sudah disetujui yang bisa dinonaktifkan.');
        }

        if ($item->is_active == 0) {
            return back()->with('error', 'Kartu ini sudah tidak aktif.');
        }

        // Ada pengajuan pengembalian dari pemohon: selesaikan lewat Terima Kartu / Tolak di halaman detail
        if ($item->return_status === 'requested') {
            return back()->with('error', 'Ada pengajuan pengembalian yang menunggu. Proses lewat tombol Terima Kartu atau Tolak di halaman detail.');
        }

        // Kartu pinjam (magang / magang_extend / visitor): lewat alur Terima Kartu, bukan edit nomor.
        // Nomor kartu TIDAK boleh diubah di sini (dulu dipakai untuk menulis "NN Done"); status
        // kembali + kondisi kartu dicatat di kolom return_*, jadi nomor tetap utuh sebagai riwayat.
        if (in_array($item->kategori, RequestIdCard::KATEGORI_KEMBALI, true)) {
            $req->request->remove('nomor_kartu');
            return app(ReturnController::class)->approve($req, $id);
        }

        // Validasi nomor kartu (opsional)
        $rules = [];
        if ($req->has('nomor_kartu') && !empty($req->nomor_kartu)) {
            $rules['nomor_kartu'] = 'string|max:50';
        }
        $validator = Validator::make($req->all(), $rules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Update nomor kartu jika diisi
            if ($req->has('nomor_kartu') && !empty($req->nomor_kartu)) {
                $item->nomor_kartu = $req->nomor_kartu;
            }

            $item->is_active = 0;
            $item->save();

            DB::table('request_idcard_logs')->insert([
                'request_id' => $id,
                'action' => 'nonaktifkan_satu',
                'action_by' => Auth::id(),
                'notes' => "Kartu dinonaktifkan oleh admin. Nama: {$item->nama}" . 
                           (!empty($req->nomor_kartu) ? " | Nomor Kartu baru: {$req->nomor_kartu}" : ""),
                'created_at' => now()
            ]);

            DB::commit();
            return back()->with('success', "Kartu {$item->nama} berhasil dinonaktifkan" . 
                               (!empty($req->nomor_kartu) ? " (Nomor Kartu diupdate)" : ""));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menonaktifkan: ' . $e->getMessage());
        }
    }

    // Aktifkan satu kartu (admin bisa, dengan edit nomor kartu opsional)
    public function aktifkanSatu(Request $req, $id)
    {
        if (!$this->canProses() || !$this->canAccessRequest($id)) {
            return redirect()->route('no-access')->with('error', 'Anda tidak memiliki akses.');
        }

        $item = RequestIdCard::findOrFail($id);

        if ($item->status !== 'approved') {
            return back()->with('error', 'Hanya kartu yang sudah disetujui yang bisa diaktifkan.');
        }

        if ($item->is_active == 1) {
            return back()->with('error', 'Kartu ini sudah aktif.');
        }

        // Validasi nomor kartu (opsional)
        $rules = [];
        if ($req->has('nomor_kartu') && !empty($req->nomor_kartu)) {
            $rules['nomor_kartu'] = 'string|max:50';
        }
        $validator = Validator::make($req->all(), $rules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::beginTransaction();
        try {
            // Update nomor kartu jika diisi
            if ($req->has('nomor_kartu') && !empty($req->nomor_kartu)) {
                $item->nomor_kartu = $req->nomor_kartu;
            }

            $item->is_active = 1;

            // Kartu yang pernah ditandai dikembalikan lalu diaktifkan lagi: reset status pengembalian
            // supaya bisa diajukan pengembalian lagi nanti.
            if ($item->return_status === 'returned') {
                $item->return_status        = 'none';
                $item->return_kondisi       = null;
                $item->return_note          = null;
                $item->return_requested_by  = null;
                $item->return_requested_at  = null;
                $item->return_processed_by  = null;
                $item->return_processed_at  = null;
                $item->return_reject_reason = null;
            }
            $item->save();

            DB::table('request_idcard_logs')->insert([
                'request_id' => $id,
                'action' => 'aktifkan_satu',
                'action_by' => Auth::id(),
                'notes' => "Kartu diaktifkan oleh admin. Nama: {$item->nama}" . 
                           (!empty($req->nomor_kartu) ? " | Nomor Kartu baru: {$req->nomor_kartu}" : ""),
                'created_at' => now()
            ]);

            DB::commit();
            return back()->with('success', "Kartu {$item->nama} berhasil diaktifkan" . 
                               (!empty($req->nomor_kartu) ? " (Nomor Kartu diupdate)" : ""));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengaktifkan: ' . $e->getMessage());
        }
    }
}