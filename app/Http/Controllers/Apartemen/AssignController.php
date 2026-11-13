<?php
// app/Http/Controllers/Apartemen/AssignController.php

namespace App\Http\Controllers\Apartemen;

use App\Http\Controllers\Controller;
use App\Models\Apartemen\ApartemenAssign;
use App\Models\Apartemen\ApartemenRequest;
use App\Models\Apartemen\ApartemenUnit;
use App\Models\Apartemen\ApartemenPenghuni;
use App\Models\Apartemen\ApartemenHistory;
use App\Models\Apartemen\BisnisUnit;
use App\Services\Apartemen\UnitService;
use Carbon\Carbon;
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
            $apartemenRequest = ApartemenRequest::with('penghuni')->lockForUpdate()->findOrFail($validated['request_id']);

            if ($apartemenRequest->status != 'APPROVED') {
                throw new \DomainException('Hanya request yang sudah disetujui yang dapat ditempatkan.');
            }

            // Satu permintaan hanya boleh punya satu penempatan manual aktif (hindari duplikat).
            if (ApartemenAssign::where('request_id', $apartemenRequest->id)->where('status', 'AKTIF')->exists()) {
                throw new \DomainException('Permintaan ini sudah memiliki penempatan aktif.');
            }

            $unit = ApartemenUnit::whereNull('deleted_at')->lockForUpdate()->findOrFail($validated['unit_id']);
            if ($unit->status == 'MAINTENANCE') {
                throw new \DomainException('Unit sedang dalam maintenance.');
            }

            if ((int) ($unit->kapasitas ?: 1) < $apartemenRequest->penghuni->count()) {
                throw new \DomainException('Kapasitas unit tidak mencukupi.');
            }

            if (UnitService::bentrok($unit->id, $validated['tanggal_mulai'], $validated['tanggal_selesai'])) {
                throw new \DomainException('Unit sudah ditempati pada periode tersebut.');
            }

            foreach ($apartemenRequest->penghuni as $rp) {
                if (UnitService::penghuniBentrok($rp->id_karyawan, $validated['tanggal_mulai'], $validated['tanggal_selesai'])) {
                    throw new \DomainException("{$rp->nama} sudah punya penempatan aktif yang jadwalnya bertumpuk.");
                }
            }

            $assign = ApartemenAssign::create([
                'request_id' => $validated['request_id'],
                'unit_id' => $validated['unit_id'],
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
                'status' => 'AKTIF',
                'assign_by' => Auth::id(),
            ]);

            foreach ($apartemenRequest->penghuni as $rp) {
                ApartemenPenghuni::create([
                    'assign_id' => $assign->id,
                    'nama' => $rp->nama,
                    'id_karyawan' => $rp->id_karyawan,
                    'no_hp' => $rp->no_hp,
                    'unit_kerja' => $rp->unit_kerja,
                    'gol' => $rp->gol,
                    'tanggal_mulai' => $rp->tanggal_mulai,
                    'tanggal_selesai' => $rp->tanggal_selesai,
                    'status' => 'AKTIF',
                ]);
            }

            UnitService::sinkron((int) $unit->id);

            DB::commit();
            return redirect()->route('apartemen.admin.monitoring')
                ->with('success', 'Penempatan unit berhasil dilakukan.');
        } catch (\DomainException $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Assign store gagal', ['message' => $e->getMessage()]);
            return back()->with('error', 'Terjadi kesalahan saat menyimpan penempatan.');
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
            $assign = ApartemenAssign::lockForUpdate()->findOrFail($id);

            if ($assign->status != 'AKTIF') {
                throw new \DomainException('Penempatan ini sudah tidak aktif.');
            }

            $newUnit = ApartemenUnit::whereNull('deleted_at')->lockForUpdate()->findOrFail($validated['unit_id']);
            if ($newUnit->status == 'MAINTENANCE') {
                throw new \DomainException('Unit tujuan sedang dalam maintenance.');
            }

            $jumlah = $assign->penghuni()->where('status', 'AKTIF')->count();
            if ((int) ($newUnit->kapasitas ?: 1) < $jumlah) {
                throw new \DomainException('Kapasitas unit tidak mencukupi.');
            }

            if (UnitService::bentrok($newUnit->id, $validated['tanggal_mulai'], $validated['tanggal_selesai'], (int) $assign->id)) {
                throw new \DomainException('Unit tujuan sudah ditempati pada periode tersebut.');
            }

            $oldUnitId = (int) $assign->unit_id;

            $assign->update([
                'unit_id' => $validated['unit_id'],
                'tanggal_mulai' => $validated['tanggal_mulai'],
                'tanggal_selesai' => $validated['tanggal_selesai'],
            ]);

            UnitService::sinkron($oldUnitId);
            UnitService::sinkron((int) $newUnit->id);

            DB::commit();
            return back()->with('success', 'Penempatan berhasil diperbarui.');
        } catch (\DomainException $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Assign update gagal', ['assign_id' => $id, 'message' => $e->getMessage()]);
            return back()->with('error', 'Terjadi kesalahan saat memperbarui penempatan.');
        }
    }

    // TRANSFER PENGHUNI (pindah unit): pindahkan seluruh penghuni aktif pada 1 penempatan ke unit lain,
    // tercatat di history. Alur: check-in -> pindah -> check-out.
    public function transfer(Request $request, $id)
    {
        $validated = $request->validate([
            'unit_id' => 'required|exists:tb_apartemen_unit,id',
            'tanggal_transfer' => 'required|date',
            'alasan' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $assign = ApartemenAssign::with(['penghuni', 'unit.apartemen', 'request.user'])
                ->lockForUpdate()
                ->findOrFail($id);

            if ($assign->status != 'AKTIF') {
                throw new \DomainException('Penempatan ini sudah tidak aktif.');
            }
            if (!$assign->unit) {
                throw new \DomainException('Unit saat ini tidak ditemukan.');
            }
            if (!$assign->checkin_at) {
                throw new \DomainException('Penghuni belum check-in, belum bisa dipindahkan. Lakukan check-in terlebih dahulu, atau batalkan penempatan bila tidak jadi menginap.');
            }
            if ($assign->unit->id == $validated['unit_id']) {
                throw new \DomainException('Unit tujuan sama dengan unit saat ini.');
            }

            $tglPindah = Carbon::parse($validated['tanggal_transfer'])->startOfDay();
            if ($tglPindah->lt(Carbon::parse($assign->tanggal_mulai)->startOfDay())) {
                throw new \DomainException('Tanggal pindah tidak boleh sebelum tanggal mulai penempatan.');
            }
            if ($tglPindah->gte(Carbon::parse($assign->tanggal_selesai)->startOfDay())) {
                // Penempatan sudah/akan berakhir: tidak ada sisa masa tinggal untuk dipindahkan.
                throw new \DomainException('Tanggal pindah harus sebelum tanggal selesai penempatan (' . Carbon::parse($assign->tanggal_selesai)->format('d/m/Y') . ').');
            }

            $newUnit = ApartemenUnit::whereNull('deleted_at')
                ->lockForUpdate()
                ->findOrFail($validated['unit_id']);

            if ($newUnit->status == 'MAINTENANCE') {
                throw new \DomainException('Unit tujuan sedang dalam maintenance.');
            }

            // Unit tujuan harus bebas selama sisa masa tinggal (dicek dari tanggal, bukan dari status unit).
            if (UnitService::bentrok($newUnit->id, $tglPindah, $assign->tanggal_selesai)) {
                throw new \DomainException('Unit tujuan tidak kosong pada sisa periode tinggal (ada penghuni/booking lain).');
            }

            $penghuniAktif = $assign->penghuni->where('status', 'AKTIF');
            if ($penghuniAktif->isEmpty()) {
                throw new \DomainException('Tidak ada penghuni aktif pada penempatan ini.');
            }
            if ((int) ($newUnit->kapasitas ?: 1) < $penghuniAktif->count()) {
                throw new \DomainException('Kapasitas unit tidak mencukupi.');
            }

            $newUnit->loadMissing('apartemen');
            $tujuan = ($newUnit->apartemen->nama_apartemen ?? '-') . ' · Unit ' . ($newUnit->nomor_unit ?? '-');

            $apartemenLama = $assign->unit->apartemen->nama_apartemen ?? '-';
            $unitLama = $assign->unit->nomor_unit ?? '-';
            $unitLamaId = (int) $assign->unit_id;
            $mulaiLama = $assign->tanggal_mulai ? $assign->tanggal_mulai->format('d/m/Y') : '-';
            $bisnisUnitLama = BisnisUnit::resolveNamaForUser($assign->request->user ?? null);

            // Tutup penempatan lama
            $assign->forceFill(['status' => 'SELESAI', 'checkout_at' => now()])->save();
            $assign->penghuni()->where('status', 'AKTIF')->update(['status' => 'SELESAI']);

            // Penempatan baru di unit tujuan (check-in tetap berlaku, tinggal check-out)
            $newAssign = ApartemenAssign::create([
                'request_id' => $assign->request_id,
                'unit_id' => $validated['unit_id'],
                'tanggal_mulai' => $validated['tanggal_transfer'],
                'tanggal_selesai' => $assign->tanggal_selesai,
                'status' => 'AKTIF',
                'assign_by' => Auth::id(),
                'checkin_at' => $assign->checkin_at,
            ]);

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
                    'periode' => $mulaiLama . ' - ' . $tglPindah->format('d/m/Y'),
                    'status_selesai' => 'DIPINDAH',
                    'catatan' => 'Pindah ke ' . $tujuan . ' — ' . $validated['alasan'],
                    'bisnis_unit' => $bisnisUnitLama ?? $penghuni->unit_kerja,
                    'created_at' => now(),
                ]);
            }

            UnitService::sinkron($unitLamaId);
            UnitService::sinkron((int) $newUnit->id);

            Log::info('Transfer unit berhasil', [
                'assign_lama' => $assign->id,
                'assign_baru' => $newAssign->id,
                'unit_lama' => $unitLama,
                'unit_baru' => $newUnit->nomor_unit,
            ]);

            DB::commit();
            return redirect()->route('apartemen.admin.monitoring')
                ->with('success', 'Transfer penghuni ke unit ' . $newUnit->nomor_unit . ' berhasil dilakukan.');
        } catch (\DomainException $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Transfer unit gagal', ['assign_id' => $id, 'message' => $e->getMessage()]);
            return back()->with('error', 'Terjadi kesalahan saat memindahkan penghuni.');
        }
    }

    // BATALKAN PENEMPATAN: hanya untuk penghuni yang BELUM check-in (tidak jadi menginap).
    // Status penempatan menjadi BATAL (bukan SELESAI) supaya laporan tidak menghitungnya sebagai masa tinggal.
    public function cancel(Request $request, $id)
    {
        $validated = $request->validate([
            'alasan' => 'required|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $assign = ApartemenAssign::with(['penghuni', 'unit.apartemen', 'request.user'])
                ->lockForUpdate()
                ->findOrFail($id);

            if ($assign->status != 'AKTIF') {
                throw new \DomainException('Penempatan ini sudah tidak aktif.');
            }
            if ($assign->checkin_at) {
                throw new \DomainException('Penghuni sudah check-in, tidak bisa dibatalkan. Gunakan Check-out atau Selesaikan.');
            }

            $penghuniAktif = $assign->penghuni->where('status', 'AKTIF');
            if ($penghuniAktif->isEmpty()) {
                throw new \DomainException('Tidak ada penghuni aktif pada penempatan ini.');
            }

            $apartemenNama = $assign->unit->apartemen->nama_apartemen ?? '-';
            $unitNomor = $assign->unit->nomor_unit ?? '-';
            $periode = ($assign->tanggal_mulai ? $assign->tanggal_mulai->format('d/m/Y') : '-')
                . ' - ' . ($assign->tanggal_selesai ? $assign->tanggal_selesai->format('d/m/Y') : '-');
            $bisnisUnit = BisnisUnit::resolveNamaForUser($assign->request->user ?? null);

            foreach ($penghuniAktif as $penghuni) {
                ApartemenHistory::create([
                    'nama' => $penghuni->nama,
                    'id_karyawan' => $penghuni->id_karyawan,
                    'no_hp' => $penghuni->no_hp ?? '-',
                    'unit_kerja' => $penghuni->unit_kerja ?? '-',
                    'gol' => $penghuni->gol ?? '-',
                    'apartemen' => $apartemenNama,
                    'unit' => $unitNomor,
                    'periode' => $periode,
                    'status_selesai' => 'DIBATALKAN',
                    'catatan' => $validated['alasan'],
                    'bisnis_unit' => $bisnisUnit ?? $penghuni->unit_kerja,
                    'created_at' => now(),
                ]);
            }

            $assign->forceFill(['status' => 'BATAL'])->save();
            $assign->penghuni()->where('status', 'AKTIF')->update(['status' => 'SELESAI']);

            UnitService::sinkron((int) $assign->unit_id);

            Log::info('Pembatalan penempatan berhasil', ['assign_id' => $assign->id, 'unit' => $unitNomor]);

            DB::commit();
            return redirect()->route('apartemen.admin.monitoring')
                ->with('success', 'Penempatan berhasil dibatalkan.');
        } catch (\DomainException $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Pembatalan penempatan gagal', ['assign_id' => $id, 'message' => $e->getMessage()]);
            return back()->with('error', 'Terjadi kesalahan saat membatalkan penempatan.');
        }
    }

    // SELESAIKAN PENEMPATAN (untuk yang sudah lewat jadwal): admin mengisi tanggal masuk/keluar sebenarnya.
    // Dipakai bila penghuni lupa check-in / check-out. Riwayat dicatat sebagai "Backfill" (jam tidak ditampilkan).
    public function selesaikan(Request $request, $id)
    {
        $validated = $request->validate([
            'tanggal_masuk' => 'nullable|date',
            'tanggal_keluar' => 'required|date',
            'catatan' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $assign = ApartemenAssign::with(['penghuni', 'unit.apartemen', 'request.user'])
                ->lockForUpdate()
                ->findOrFail($id);

            if ($assign->status != 'AKTIF') {
                throw new \DomainException('Penempatan ini sudah tidak aktif.');
            }

            $penghuniAktif = $assign->penghuni->where('status', 'AKTIF');
            if ($penghuniAktif->isEmpty()) {
                throw new \DomainException('Tidak ada penghuni aktif pada penempatan ini.');
            }

            $hariIni = now()->startOfDay();
            $mulai   = Carbon::parse($assign->tanggal_mulai)->startOfDay();
            $keluar  = Carbon::parse($validated['tanggal_keluar'])->startOfDay();

            if ($keluar->gt($hariIni)) {
                throw new \DomainException('Tanggal keluar tidak boleh di masa depan.');
            }

            $perluMasuk = !$assign->checkin_at;
            if ($perluMasuk) {
                $masuk = Carbon::parse($validated['tanggal_masuk'] ?? $assign->tanggal_mulai)->startOfDay();
                if ($masuk->lt($mulai)) {
                    throw new \DomainException('Tanggal masuk tidak boleh sebelum jadwal mulai (' . $mulai->format('d/m/Y') . ').');
                }
            } else {
                $masuk = Carbon::parse($assign->checkin_at)->startOfDay();
            }

            if ($keluar->lt($masuk)) {
                throw new \DomainException('Tanggal keluar tidak boleh sebelum tanggal masuk.');
            }

            // Jam 12:00 sebagai penanda tanggal (jam asli tidak diketahui); tidak boleh melewati sekarang.
            $at = fn(Carbon $d) => $d->copy()->setTime(12, 0)->min(now());
            $atMasuk  = $at($masuk);
            $atKeluar = $at($keluar);

            $apartemenNama = $assign->unit->apartemen->nama_apartemen ?? '-';
            $unitNomor = $assign->unit->nomor_unit ?? '-';
            $periode = Carbon::parse($assign->tanggal_mulai)->format('d/m/Y') . ' - ' . Carbon::parse($assign->tanggal_selesai)->format('d/m/Y');
            $bisnisUnit = BisnisUnit::resolveNamaForUser($assign->request->user ?? null);
            $ket = trim((string) ($validated['catatan'] ?? ''));

            foreach ($penghuniAktif as $p) {
                $dasar = [
                    'nama' => $p->nama,
                    'id_karyawan' => $p->id_karyawan,
                    'no_hp' => $p->no_hp ?? '-',
                    'unit_kerja' => $p->unit_kerja ?? '-',
                    'gol' => $p->gol ?? '-',
                    'apartemen' => $apartemenNama,
                    'unit' => $unitNomor,
                    'periode' => $periode,
                    'bisnis_unit' => $bisnisUnit ?? $p->unit_kerja,
                ];

                if ($perluMasuk) {
                    ApartemenHistory::create($dasar + [
                        'status_selesai' => 'CHECKIN',
                        'catatan' => 'Backfill: check-in dicatat admin',
                        'created_at' => $atMasuk,
                    ]);
                }

                ApartemenHistory::create($dasar + [
                    'status_selesai' => 'SELESAI',
                    'catatan' => 'Backfill: diselesaikan admin' . ($ket !== '' ? ' — ' . $ket : ''),
                    'created_at' => $atKeluar,
                ]);
            }

            $assign->forceFill([
                'checkin_at' => $perluMasuk ? $atMasuk : $assign->checkin_at,
                'checkout_at' => $atKeluar,
                'status' => 'SELESAI',
            ])->save();
            $assign->penghuni()->where('status', 'AKTIF')->update(['status' => 'SELESAI']);

            UnitService::sinkron((int) $assign->unit_id);

            Log::info('Penempatan diselesaikan admin', ['assign_id' => $assign->id, 'user_id' => Auth::id()]);

            DB::commit();
            return redirect()->route('apartemen.admin.monitoring')
                ->with('success', 'Penempatan berhasil diselesaikan (' . $penghuniAktif->count() . ' penghuni).');
        } catch (\DomainException $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Selesaikan penempatan gagal', ['assign_id' => $id, 'message' => $e->getMessage()]);
            return back()->with('error', 'Terjadi kesalahan saat menyelesaikan penempatan.');
        }
    }
}
