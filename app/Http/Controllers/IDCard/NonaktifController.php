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