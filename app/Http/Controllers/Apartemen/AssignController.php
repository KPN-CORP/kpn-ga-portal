<?php
// app/Http/Controllers/Apartemen/AssignController.php

namespace App\Http\Controllers\Apartemen;

use App\Http\Controllers\Controller;
use App\Models\Apartemen\ApartemenAssign;
use App\Models\Apartemen\ApartemenRequest;
use App\Models\Apartemen\ApartemenUnit;
use App\Models\Apartemen\ApartemenPenghuni;
use App\Models\Apartemen\ApartemenHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AssignController extends Controller
{
    // STORE ASSIGNMENT (Manual)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'request_id' => 'required|exists:tb_apartemen_request,id',
            'unit_id' => 'required|exists:tb_apartemen_unit,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
        ]);

        DB::beginTransaction();
        try {
            $apartemenRequest = ApartemenRequest::findOrFail($validated['request_id']);
            
            // Cek apakah request sudah APPROVED
            if ($apartemenRequest->status != 'APPROVED') {
                return back()->with('error', 'Hanya request yang sudah disetujui yang dapat ditempatkan.');
            }

            // Cek apakah unit tersedia
            $unit = ApartemenUnit::findOrFail($validated['unit_id']);
            if ($unit->status != 'READY') {
                return back()->with('error', 'Unit tidak tersedia untuk ditempati.');
            }

            // Cek kapasitas unit
            if ($unit->kapasitas < $apartemenRequest->penghuni->count()) {
                return back()->with('error', 'Kapasitas unit tidak mencukupi.');
            }

            // Buat assignment
            $assign = ApartemenAssign::create([
                'request_id' => $validated['request_id'],
                'unit_id' => $validated['unit_id'],
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
                'status' => 'AKTIF',
            ]);

            // Update status unit
            $unit->update(['status' => 'TERISI']);

            // Create penghuni dari request
            foreach ($apartemenRequest->penghuni as $reqPenghuni) {
                ApartemenPenghuni::create([
                    'assign_id' => $assign->id,
                    'nama' => $reqPenghuni->nama,
                    'id_karyawan' => $reqPenghuni->id_karyawan,
                    'unit_kerja' => $reqPenghuni->unit_kerja,
                    'gol' => $reqPenghuni->gol,
                    'tanggal_mulai' => $reqPenghuni->tanggal_mulai,
                    'tanggal_selesai' => $reqPenghuni->tanggal_selesai,
                    'status' => 'AKTIF',
                ]);
            }

            DB::commit();
            return redirect()->route('apartemen.admin.monitoring')
                ->with('success', 'Penempatan unit berhasil dilakukan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // UPDATE ASSIGNMENT
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'unit_id' => 'required|exists:tb_apartemen_unit,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
        ]);

        DB::beginTransaction();
        try {
            $assign = ApartemenAssign::findOrFail($id);
            
            // Kembalikan status unit lama
            $oldUnit = $assign->unit;
            $oldUnit->update(['status' => 'READY']);

            // Update assignment
            $assign->update([
                'unit_id' => $validated['unit_id'],
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
            ]);

            // Update status unit baru
            $newUnit = ApartemenUnit::find($validated['unit_id']);
            $newUnit->update(['status' => 'TERISI']);

            DB::commit();
            return back()->with('success', 'Penempatan berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    // TRANSFER PENGHUNI (pindah unit) - pindahkan seluruh penghuni pada 1 assignment ke unit lain yang kosong, tercatat di history
    public function transfer(Request $request, $id)
    {
        $validated = $request->validate([
            'unit_id' => 'required|exists:tb_apartemen_unit,id',
            'tanggal_transfer' => 'required|date',
            'alasan' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $assign = ApartemenAssign::with(['penghuni', 'unit.apartemen'])
                ->lockForUpdate()
                ->findOrFail($id);

            if ($assign->status != 'AKTIF') {
                return back()->with('error', 'Penempatan ini sudah tidak aktif.');
            }

            if (!$assign->unit) {
                return back()->with('error', 'Unit saat ini tidak ditemukan.');
            }

            if ($assign->unit->id == $validated['unit_id']) {
                return back()->with('error', 'Unit tujuan sama dengan unit saat ini.');
            }

            $newUnit = ApartemenUnit::whereNull('deleted_at')
                ->lockForUpdate()
                ->findOrFail($validated['unit_id']);

            // Unit tujuan harus kosong (READY)
            if ($newUnit->status != 'READY') {
                return back()->with('error', 'Unit tujuan tidak kosong / tidak tersedia.');
            }

            $penghuniAktif = $assign->penghuni->where('status', 'AKTIF');

            if ($penghuniAktif->isEmpty()) {
                return back()->with('error', 'Tidak ada penghuni aktif pada penempatan ini.');
            }

            // Cek kapasitas unit baru
            if ($newUnit->kapasitas < $penghuniAktif->count()) {
                return back()->with('error', 'Kapasitas unit tidak mencukupi.');
            }

            $apartemenLama = $assign->unit->apartemen->nama_apartemen ?? '-';
            $unitLama = $assign->unit->nomor_unit ?? '-';
            $mulaiLama = $assign->tanggal_mulai ? $assign->tanggal_mulai->format('d/m/Y') : '-';

            // Update assignment lama
            $assign->update(['status' => 'SELESAI']);
            $assign->penghuni()->update(['status' => 'SELESAI']);

            // Buat assignment baru
            $newAssign = ApartemenAssign::create([
                'request_id' => $assign->request_id,
                'unit_id' => $validated['unit_id'],
                'tanggal_mulai' => $validated['tanggal_transfer'],
                'tanggal_selesai' => $assign->tanggal_selesai,
                'status' => 'AKTIF',
                'assign_by' => Auth::id(),
            ]);

            // Copy penghuni aktif ke assignment baru + catat history per penghuni
            foreach ($penghuniAktif as $penghuni) {
                ApartemenPenghuni::create([
                    'assign_id' => $newAssign->id,
                    'nama' => $penghuni->nama,
                    'id_karyawan' => $penghuni->id_karyawan,
                    'no_hp' => $penghuni->no_hp,
                    'unit_kerja' => $penghuni->unit_kerja,
                    'gol' => $penghuni->gol,
                    'tanggal_mulai' => $validated['tanggal_transfer'],
                    'tanggal_selesai' => $assign->tanggal_selesai,
                    'status' => 'AKTIF',
                ]);

                ApartemenHistory::create([
                    'nama' => $penghuni->nama,
                    'id_karyawan' => $penghuni->id_karyawan,
                    'no_hp' => $penghuni->no_hp ?? '-',
                    'unit_kerja' => $penghuni->unit_kerja ?? '-',
                    'gol' => $penghuni->gol ?? '-',
                    'apartemen' => $apartemenLama,
                    'unit' => $unitLama,
                    'periode' => $mulaiLama . ' - ' . \Carbon\Carbon::parse($validated['tanggal_transfer'])->format('d/m/Y'),
                    'status_selesai' => 'DIPINDAH',
                    'created_at' => now(),
                ]);
            }

            // Update status unit
            $assign->unit->update(['status' => 'READY']);
            $newUnit->update(['status' => 'TERISI']);

            Log::info('Transfer unit berhasil', [
                'assign_lama' => $assign->id,
                'assign_baru' => $newAssign->id,
                'unit_lama' => $unitLama,
                'unit_baru' => $newUnit->nomor_unit,
                'alasan' => $validated['alasan'],
            ]);

            DB::commit();
            return redirect()->route('apartemen.admin.monitoring')
                ->with('success', 'Transfer penghuni ke unit ' . $newUnit->nomor_unit . ' berhasil dilakukan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}