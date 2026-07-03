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

        // Validasi untuk magang/magang_extend
        if (in_array($item->kategori, ['magang', 'magang_extend'])) {
            $rules = [];
            if ($item->kategori === 'magang') {
                $rules['nomor_kartu'] = 'required|string|max:50|unique:request_idcard,nomor_kartu,' . $id;
            } else {
                $rules['nomor_kartu'] = 'required|string|max:50';
            }
            if ($req->has('sampai_tanggal') && !empty($req->sampai_tanggal)) {
                $rules['sampai_tanggal'] = 'date|after:masa_berlaku';
            }
            $validator = Validator::make($req->all(), $rules);
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
        }

        DB::beginTransaction();
        try {
            if (in_array($item->kategori, ['magang', 'magang_extend'])) {
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
            $item->save();

            DB::table('request_idcard_logs')->insert([
                'request_id' => $id,
                'action' => 'approved',
                'action_by' => Auth::id(),
                'notes' => 'Request ID Card disetujui',
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
            $item->status = 'rejected';
            $item->rejection_reason = $req->rejection_reason;
            $item->rejected_by = Auth::id();
            $item->rejected_at = now();
            $item->approved_by = null;
            $item->approved_at = null;
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