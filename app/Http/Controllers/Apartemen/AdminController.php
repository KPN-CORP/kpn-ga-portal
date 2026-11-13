<?php
// app/Http/Controllers/Apartemen/AdminController.php

namespace App\Http\Controllers\Apartemen;

use App\Http\Controllers\Controller;
use App\Models\Apartemen\Apartemen;
use App\Models\Apartemen\ApartemenUnit;
use App\Models\Apartemen\ApartemenRequest;
use App\Models\Apartemen\ApartemenAssign;
use App\Models\Apartemen\ApartemenPenghuni;
use App\Models\Apartemen\ApartemenHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Apartemen\FasilitasBooking;
use App\Models\Apartemen\BisnisUnit;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Pagination\LengthAwarePaginator;
use Carbon\Carbon;
use App\Services\Apartemen\UnitService;

class AdminController extends Controller
{
    // DASHBOARD ADMIN
    public function dashboard()
    {
        // Status unit mengikuti penempatan yang benar-benar berjalan (bukan status basi).
        UnitService::sinkronSemua();

        $today = now()->startOfDay();

        // "Penghuni aktif" = penghuni yang sedang tinggal (penempatan sudah mulai) -> sama dengan halaman Monitoring.
        $aktifQuery = fn() => ApartemenPenghuni::where('status', 'AKTIF')
            ->whereHas('assign', fn($q) => $q->where('status', 'AKTIF')->whereDate('tanggal_mulai', '<=', $today));

        $stats = [
            'total_apartemen'    => Apartemen::count(),
            'total_unit'         => ApartemenUnit::count(),
            'unit_tersedia'      => ApartemenUnit::where('status', 'READY')->count(),
            'unit_terisi'        => ApartemenUnit::where('status', 'TERISI')->count(),
            'unit_maintenance'   => ApartemenUnit::where('status', 'MAINTENANCE')->count(),
            'permintaan_pending' => ApartemenRequest::where('status', 'PENDING')->count(),
            'permintaan_approved' => ApartemenRequest::where('status', 'APPROVED')->count(),
            'permintaan_rejected' => ApartemenRequest::where('status', 'REJECTED')->count(),
            'penghuni_aktif'     => $aktifQuery()->count(),
            'lewat_jadwal'       => $aktifQuery()
                ->whereHas('assign', fn($q) => $q->whereDate('tanggal_selesai', '<', $today))
                ->count(),
        ];

        $pendingRequests = ApartemenRequest::with(['user', 'penghuni'])
            ->where('status', 'PENDING')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Check-out hari ini s/d 7 hari ke depan (hari ini ikut dihitung).
        $upcomingCheckouts = ApartemenAssign::with(['unit.apartemen', 'penghuniAktif'])
            ->where('status', 'AKTIF')
            ->whereHas('penghuniAktif')
            ->whereDate('tanggal_selesai', '>=', $today)
            ->whereDate('tanggal_selesai', '<=', $today->copy()->addDays(7))
            ->orderBy('tanggal_selesai')
            ->get();

        // Perlu tindakan: sudah lewat jadwal tapi belum diselesaikan.
        $overdueAssigns = ApartemenAssign::with(['unit.apartemen', 'penghuniAktif'])
            ->where('status', 'AKTIF')
            ->whereHas('penghuniAktif')
            ->whereDate('tanggal_selesai', '<', $today)
            ->orderBy('tanggal_selesai')
            ->take(8)
            ->get();

        $maintenanceUnits = ApartemenUnit::with('apartemen')
            ->where('status', 'MAINTENANCE')
            ->get();

        return view('apartemen.admin.dashboard', compact(
            'stats',
            'pendingRequests',
            'upcomingCheckouts',
            'overdueAssigns',
            'maintenanceUnits'
        ));
    }

    public function calendarEvents()
    {
        $assignments = ApartemenAssign::with(['unit.apartemen', 'penghuniAktif'])
            ->where('status', 'AKTIF')
            ->whereHas('penghuniAktif')
            ->get();

        $events = [];

        foreach ($assignments as $assign) {
            if (!$assign->unit) {
                continue;
            }
            $events[] = [
                'title' => "Unit {$assign->unit->nomor_unit} (" . ($assign->unit->apartemen->nama_apartemen ?? '-') . ")",
                'start' => $assign->tanggal_mulai->toDateString(),
                'end'   => $assign->tanggal_selesai->addDay()->toDateString(),
                'color' => '#3b82f6',
                'extendedProps' => [
                    'type' => 'unit',
                    'unit_id' => $assign->unit_id,
                    'penghuni' => $assign->penghuniAktif->pluck('nama')->join(', ')
                ]
            ];
        }

        $bookings = FasilitasBooking::with('fasilitas', 'user')
            ->whereIn('status', ['APPROVED', 'CHECKED_IN'])
            ->get();

        foreach ($bookings as $booking) {
            $events[] = [
                'title' => "{$booking->fasilitas->nama_fasilitas} ({$booking->user->name})",
                'start' => "{$booking->tanggal_booking->toDateString()}T{$booking->jam_mulai}",
                'end'   => "{$booking->tanggal_booking->toDateString()}T{$booking->jam_selesai}",
                'color' => '#10b981',
                'extendedProps' => [
                    'type' => 'facility',
                    'booking_id' => $booking->id,
                    'jumlah_orang' => $booking->jumlah_orang
                ]
            ];
        }

        return response()->json($events);
    }

    public function index(Request $request)
    {
        $query = ApartemenRequest::with(['user', 'penghuni'])
            ->where('status', 'PENDING')
            ->orderBy('created_at', 'desc');

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('created_at', '>=', $request->tanggal_mulai);
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('created_at', '<=', $request->tanggal_selesai);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                })->orWhereHas('penghuni', function($q2) use ($search) {
                    $q2->where('nama', 'like', "%{$search}%")
                    ->orWhere('id_karyawan', 'like', "%{$search}%");
                });
            });
        }

        $requests = $query->paginate(10)->withQueryString();

        return view('apartemen.admin.index', compact('requests'));
    }

    public function approve($id)
    {
        $request = ApartemenRequest::with(['penghuni', 'user'])
            ->where('status', 'PENDING')
            ->findOrFail($id);

        $availableUnits = ApartemenUnit::with('apartemen')
            ->where('status', '!=', 'MAINTENANCE')
            ->get();

        return view('apartemen.admin.approve', compact('request', 'availableUnits'));
    }

    // =================================================================
    // PROSES APPROVAL
    // - Hanya permintaan berstatus PENDING yang bisa diproses (klik ganda / POST ulang ditolak).
    // - Bentrok jadwal dicek dari tanggal (bukan status unit); bersinggungan satu hari diperbolehkan.
    // - Satu penghuni tidak boleh ditempatkan di dua unit / bentrok dengan penempatan aktifnya.
    // - Semua cek dan tulis terjadi dalam satu transaksi.
    // =================================================================
    public function approveProcess(Request $request, $id)
    {
        $action = $request->input('action');

        // Validasi di LUAR try/catch supaya pesan per-kolom tampil normal.
        if ($action === 'approve') {
            $request->validate([
                'penempatan' => 'required|array|min:1',
                'penempatan.*.unit_id' => 'required|exists:tb_apartemen_unit,id',
                'penempatan.*.penghuni_ids' => 'required|array|min:1',
                'penempatan.*.tanggal_mulai' => 'required|date',
                'penempatan.*.tanggal_selesai' => 'required|date|after:penempatan.*.tanggal_mulai',
            ]);
        } elseif ($action === 'reject') {
            $request->validate([
                'reject_reason' => 'required|string|min:5|max:500',
            ]);
        } else {
            return back()->with('error', 'Pilih tindakan terlebih dahulu (Setujui atau Tolak).');
        }

        DB::beginTransaction();
        try {
            $apartemenRequest = ApartemenRequest::with('penghuni')->lockForUpdate()->findOrFail($id);

            if ($apartemenRequest->status !== 'PENDING') {
                throw new \DomainException('Permintaan ini sudah diproses (status: ' . $apartemenRequest->status . '). Muat ulang halaman.');
            }

            // ---------- TOLAK ----------
            if ($action === 'reject') {
                $apartemenRequest->update([
                    'status' => 'REJECTED',
                    'reject_reason' => $request->reject_reason,
                    'approved_by' => auth()->user()->name,
                    'approved_at' => now(),
                ]);

                DB::commit();
                return redirect()->route('apartemen.admin.index')
                    ->with('success', 'Permintaan berhasil ditolak.');
            }

            // ---------- SETUJUI ----------
            $items       = $request->penempatan;
            $penghuniReq = $apartemenRequest->penghuni->keyBy('id');
            $dipakai     = [];   // id penghuni request yang sudah ditempatkan
            $rentangUnit = [];   // unit_id => [[mulai, selesai], ...] untuk cek bentrok antar-baris

            foreach ($items as $item) {
                $unit = ApartemenUnit::whereNull('deleted_at')->lockForUpdate()->find($item['unit_id']);
                if (!$unit) {
                    throw new \DomainException('Unit tidak ditemukan.');
                }
                if ($unit->status === 'MAINTENANCE') {
                    throw new \DomainException("Unit {$unit->nomor_unit} sedang dalam maintenance!");
                }

                $ids = array_map('intval', $item['penghuni_ids']);
                if (count($ids) !== count(array_unique($ids))) {
                    throw new \DomainException("Ada penghuni yang dipilih dua kali pada unit {$unit->nomor_unit}.");
                }

                $kapasitas = (int) ($unit->kapasitas ?: 1);
                if ($kapasitas < count($ids)) {
                    throw new \DomainException("Unit {$unit->nomor_unit} kapasitas tidak mencukupi! (Kapasitas: {$kapasitas}, Ditempatkan: " . count($ids) . ")");
                }

                foreach ($ids as $pid) {
                    if (!$penghuniReq->has($pid)) {
                        throw new \DomainException('Ada penghuni yang tidak termasuk dalam permintaan ini.');
                    }
                    if (isset($dipakai[$pid])) {
                        throw new \DomainException("{$penghuniReq[$pid]->nama} tidak boleh ditempatkan di dua unit sekaligus.");
                    }
                    $dipakai[$pid] = true;

                    if (UnitService::penghuniBentrok($penghuniReq[$pid]->id_karyawan, $item['tanggal_mulai'], $item['tanggal_selesai'])) {
                        throw new \DomainException("{$penghuniReq[$pid]->nama} sudah punya penempatan aktif yang jadwalnya bertumpuk dengan periode ini.");
                    }
                }

                // Bentrok dengan penempatan yang sudah ada di unit tersebut
                if (UnitService::bentrok($unit->id, $item['tanggal_mulai'], $item['tanggal_selesai'])) {
                    throw new \DomainException("Unit {$unit->nomor_unit} sudah ditempati pada periode {$item['tanggal_mulai']} s/d {$item['tanggal_selesai']}!");
                }

                // Bentrok antar-baris dalam permintaan yang sama (unit sama, tanggal beririsan)
                foreach ($rentangUnit[$unit->id] ?? [] as [$m, $sel]) {
                    if ($item['tanggal_mulai'] < $sel && $item['tanggal_selesai'] > $m) {
                        throw new \DomainException("Unit {$unit->nomor_unit} dipilih dua kali dengan tanggal yang bertumpuk.");
                    }
                }
                $rentangUnit[$unit->id][] = [$item['tanggal_mulai'], $item['tanggal_selesai']];
            }

            $apartemenRequest->update([
                'status' => 'APPROVED',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                $assign = ApartemenAssign::create([
                    'request_id' => $apartemenRequest->id,
                    'unit_id' => $item['unit_id'],
                    'tanggal_mulai' => $item['tanggal_mulai'],
                    'tanggal_selesai' => $item['tanggal_selesai'],
                    'status' => 'AKTIF',
                    'assign_by' => auth()->id(),
                ]);

                foreach ($item['penghuni_ids'] as $pid) {
                    $rp = $penghuniReq[(int) $pid];
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
            }

            // Status unit mengikuti jadwal: booking yang belum mulai tidak membuat unit langsung TERISI.
            foreach (array_unique(array_column($items, 'unit_id')) as $unitId) {
                UnitService::sinkron((int) $unitId);
            }

            DB::commit();

            $total    = $penghuniReq->count();
            $ditempat = count($dipakai);
            $pesan    = "Permintaan berhasil disetujui. {$ditempat} dari {$total} penghuni telah ditempatkan.";
            $belum    = $penghuniReq->filter(fn($p) => !isset($dipakai[$p->id]))->pluck('nama');
            if ($belum->isNotEmpty()) {
                $pesan .= ' Belum ditempatkan: ' . $belum->implode(', ') . '. Ajukan permintaan baru untuk mereka.';
            }

            return redirect()->route('apartemen.admin.index')->with('success', $pesan);

        } catch (\DomainException $e) {
            DB::rollBack();
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error in approveProcess', ['request_id' => $id, 'message' => $e->getMessage()]);

            return back()->withInput()
                ->with('error', 'Gagal memproses permintaan. Silakan coba lagi; jika berulang, hubungi administrator sistem.');
        }
    }

    // CHECK-IN PENGHUNI
    public function checkin($id)
    {
        try {
            Log::info('=== CHECKIN START === ID: ' . $id);
            
            DB::beginTransaction();
            
            $penghuni = ApartemenPenghuni::with(['assign.unit.apartemen'])
                ->where('id', $id)
                ->where('status', 'AKTIF')
                ->lockForUpdate()
                ->firstOrFail();
            
            
            if (!$penghuni->assign) {
                throw new \Exception('Penghuni tidak memiliki data penempatan');
            }
            
            $assign = $penghuni->assign;
            
            if ($assign->status != 'AKTIF') {
                throw new \Exception('Penempatan tidak aktif');
            }
            
            if ($assign->checkin_at) {
                throw new \Exception('Penghuni sudah melakukan check-in pada ' . $assign->checkin_at->format('d/m/Y H:i'));
            }
            
            if ($assign->tanggal_mulai > now()) {
                throw new \Exception('Belum waktunya check-in. Tanggal mulai: ' . $assign->tanggal_mulai->format('d/m/Y'));
            }
            
            $assign->update([
                'checkin_at' => now()
            ]);
            
            // Check-in berlaku untuk seluruh penghuni aktif di penempatan ini, jadi riwayat Check In
            // dicatat untuk masing-masing (bukan hanya yang diklik) agar tiap orang punya catatan sendiri.
            $penghuniMasuk = $assign->penghuni()->where('status', 'AKTIF')->get();
            if ($penghuniMasuk->isEmpty()) {
                $penghuniMasuk = collect([$penghuni]);
            }

            $bisnisUnitMasuk = BisnisUnit::resolveNamaForUser($assign->request->user ?? null);

            foreach ($penghuniMasuk as $p) {
                ApartemenHistory::create([
                    'nama' => $p->nama,
                    'id_karyawan' => $p->id_karyawan,
                    'no_hp' => $p->no_hp ?? '-',
                    'unit_kerja' => $p->unit_kerja ?? '-',
                    'gol' => $p->gol ?? '-',
                    'apartemen' => $assign->unit->apartemen->nama_apartemen ?? '-',
                    'unit' => $assign->unit->nomor_unit ?? '-',
                    'periode' => $assign->tanggal_mulai->format('d/m/Y') . ' - ' . $assign->tanggal_selesai->format('d/m/Y'),
                    'status_selesai' => 'CHECKIN',
                    'bisnis_unit' => $bisnisUnitMasuk ?? $p->unit_kerja, // agar filter Bisnis unit di Riwayat ikut menemukan catatan Check-in
                    'created_at' => now()
                ]);
            }
            
            UnitService::sinkron((int) $assign->unit_id);

            DB::commit();

            Log::info('=== CHECKIN SUCCESS ===', ['assign_id' => $assign->id]);
            
            return redirect()->route('apartemen.admin.monitoring')
                ->with('success', 'Check-in berhasil untuk ' . $penghuni->nama . ' pada ' . now()->format('d/m/Y H:i'));
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('=== CHECKIN ERROR ===');
            Log::error('Error: ' . $e->getMessage());
            
            return redirect()->route('apartemen.admin.monitoring')
                ->with('error', 'Gagal check-in: ' . $e->getMessage());
        }
    }

    // =====================================================================
    // MONITORING PENGHUNI
    // Hanya penghuni yang sedang tinggal (penempatan sudah mulai dan masih AKTIF),
    // tanpa filter. Penghuni yang lewat tanggal selesai tapi belum check-out tetap
    // tampil (badge merah) supaya tombol Check-out masih bisa dipakai.
    // Booking yang belum mulai (tanggal_mulai > sekarang) tidak ditampilkan.
    // =====================================================================
    public function monitoring(Request $request)
    {
        UnitService::sinkronSemua();

        // Banner "perlu tindakan": semua penghuni yang sudah lewat jadwal (bukan hanya halaman ini).
        $lewatJadwal = ApartemenPenghuni::where('status', 'AKTIF')
            ->whereHas('assign', fn($q) => $q->where('status', 'AKTIF')->whereDate('tanggal_selesai', '<', now()->startOfDay()))
            ->count();

        $penghuni = ApartemenPenghuni::where('status', 'AKTIF')
            ->whereHas('assign', function ($q) {
                $q->where('status', 'AKTIF')
                  ->where('tanggal_mulai', '<=', now());
            })
            ->with(['assign' => function ($q) {
                // penghuniAktif dimuat supaya badge "N penghuni" bisa menampilkan siapa saja yang tinggal di unit itu.
                $q->withCount('penghuniAktif')->with(['unit.apartemen', 'penghuniAktif']);
            }])
            ->orderBy('nama', 'asc')
            ->paginate(10);

        // Unit kosong (READY) untuk opsi pindah unit, lengkap dengan nama apartemen
        $unitKosong = ApartemenUnit::active()
            ->ready()
            ->with('apartemen')
            ->orderBy('apartemen_id')
            ->orderBy('nomor_unit')
            ->get();

        return view('apartemen.admin.monitoring', compact('penghuni', 'unitKosong', 'lewatJadwal'));
    }

    // CHECKOUT PER ORANG
    public function checkoutPenghuni($id)
    {
        try {
            Log::info('=== CHECKOUT START ===', ['id' => $id]);
            
            DB::beginTransaction();
            
            $penghuni = ApartemenPenghuni::with(['assign.unit.apartemen', 'assign.penghuni', 'assign.request.user'])
                ->where('id', $id)
                ->where('status', 'AKTIF')
                ->lockForUpdate()
                ->first();
            
            if (!$penghuni) {
                throw new \Exception('Penghuni tidak ditemukan atau sudah tidak aktif');
            }
            
            
            if (!$penghuni->assign) {
                throw new \Exception('Penghuni tidak memiliki data penempatan');
            }
            
            $assign = $penghuni->assign;
            
            if ($assign->status != 'AKTIF') {
                throw new \Exception('Status penempatan tidak aktif');
            }
            
            if (!$assign->checkin_at) {
                throw new \Exception('Penghuni belum melakukan check-in. Tidak bisa check-out.');
            }
            
            $activeBefore = $assign->penghuni()
                ->where('status', 'AKTIF')
                ->count();
            
            Log::info('Active penghuni before checkout', ['count' => $activeBefore]);
            
            $penghuni->update(['status' => 'SELESAI']);
            
            $activeAfter = $assign->penghuni()
                ->where('status', 'AKTIF')
                ->count();
            
            Log::info('Active penghuni after checkout', ['count' => $activeAfter]);
            
            if ($activeAfter == 0) {
                Log::info('Last penghuni, updating assign and unit');
                
                $assign->forceFill(['status' => 'SELESAI', 'checkout_at' => now()])->save();

                UnitService::sinkron((int) $assign->unit_id);
            }
            
            $bisnisUnit = BisnisUnit::resolveNamaForUser($assign->request->user ?? null);

            $historyData = [
                'nama' => $penghuni->nama,
                'id_karyawan' => $penghuni->id_karyawan,
                'no_hp' => $penghuni->no_hp ?? '-',
                'unit_kerja' => $penghuni->unit_kerja ?? '-',
                'gol' => $penghuni->gol ?? '-',
                'apartemen' => $assign->unit->apartemen->nama_apartemen ?? '-',
                'unit' => $assign->unit->nomor_unit ?? '-',
                'periode' => ($assign->tanggal_mulai ? $assign->tanggal_mulai->format('d/m/Y') : '-') . ' - ' . ($assign->tanggal_selesai ? $assign->tanggal_selesai->format('d/m/Y') : '-'),
                'status_selesai' => 'SELESAI',
                'bisnis_unit' => $bisnisUnit ?? $penghuni->unit_kerja,
                'created_at' => now()
            ];
            
            
            $history = ApartemenHistory::create($historyData);
            
            Log::info('History created', ['history_id' => $history->id]);
            
            DB::commit();
            
            Log::info('=== CHECKOUT SUCCESS ===');
            
            return redirect()->route('apartemen.admin.monitoring')
                ->with('success', 'Check-out berhasil untuk ' . $penghuni->nama);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('=== CHECKOUT ERROR ===', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('apartemen.admin.monitoring')
                ->with('error', 'Gagal check-out: ' . $e->getMessage());
        }
    }

    // =====================================================================
    // HISTORY
    // =====================================================================
    // Rentang tanggal riwayat. Bila kedua tanggal kosong, otomatis bulan berjalan.
    // Return: [mulai Y-m-d|null, selesai Y-m-d|null, isDefault]. isDefault = rentang sama dengan bulan berjalan.
    private function historyRange(Request $request): array
    {
        // Tahun < 2000 diabaikan (mis. "0002" saat tanggal masih diketik di kolom date).
        $valid   = fn($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && (int) substr($v, 0, 4) >= 2000;
        $mulai   = $valid($request->input('tanggal_mulai'))   ? $request->input('tanggal_mulai')   : null;
        $selesai = $valid($request->input('tanggal_selesai')) ? $request->input('tanggal_selesai') : null;

        // Rentang terbalik (dari > sampai): tukar agar tidak menghasilkan data kosong.
        if ($mulai && $selesai && $mulai > $selesai) {
            [$mulai, $selesai] = [$selesai, $mulai];
        }

        $awalBulan  = now()->startOfMonth()->toDateString();
        $akhirBulan = now()->endOfMonth()->toDateString();

        if (!$mulai && !$selesai) {
            $mulai   = $awalBulan;
            $selesai = $akhirBulan;
        }

        return [$mulai, $selesai, ($mulai === $awalBulan && $selesai === $akhirBulan)];
    }

    // Label periode untuk kartu: "November 2026" / "12 – 13 November 2026" / "12 Nov 2026 – 3 Des 2026".
    private function historyPeriodeLabel(?string $mulai, ?string $selesai): string
    {
        $s = $mulai ? Carbon::parse($mulai)->locale('id') : null;
        $e = $selesai ? Carbon::parse($selesai)->locale('id') : null;

        if ($s && !$e) return 'mulai ' . $s->translatedFormat('j F Y');
        if (!$s && $e) return 'sampai ' . $e->translatedFormat('j F Y');

        if ($s->isSameMonth($e) && $s->day === 1 && $e->day === $e->daysInMonth) {
            return $s->translatedFormat('F Y');
        }
        if ($s->isSameDay($e)) return $s->translatedFormat('j F Y');
        if ($s->isSameMonth($e)) return $s->day . ' – ' . $e->translatedFormat('j F Y');

        return $s->translatedFormat('j M Y') . ' – ' . $e->translatedFormat('j M Y');
    }

    // Filter tanggal / bisnis unit / pencarian. Dipakai untuk builder Eloquent maupun subquery.
    private function applyHistoryFilters($query, Request $request)
    {
        // Rentang tanggal (default: bulan berjalan); boleh salah satu saja.
        [$mulai, $selesai] = $this->historyRange($request);
        if ($mulai) {
            $query->whereDate('created_at', '>=', $mulai);
        }
        if ($selesai) {
            $query->whereDate('created_at', '<=', $selesai);
        }

        if ($request->filled('bisnis_unit')) {
            $query->where('bisnis_unit', $request->bisnis_unit);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('id_karyawan', 'like', "%{$search}%")
                  ->orWhere('apartemen', 'like', "%{$search}%")
                  ->orWhere('unit', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    // Penghuni AKTIF yang tinggal pada rentang filter (jadwal penempatan beririsan dengan rentang,
    // atau sudah lewat jadwal tapi belum check-out). Dipakai agar penghuni aktif ikut tampil di
    // riwayat walau belum punya catatan riwayat pada rentang tersebut. Hasil: keyBy id_karyawan.
    private function historyActivePeople(Request $request)
    {
        [$mulai, $selesai] = $this->historyRange($request);
        $table = (new ApartemenHistory)->getTable();
        $now   = now();

        $query = ApartemenPenghuni::where('status', 'AKTIF')
            ->whereHas('assign', function ($a) use ($mulai, $selesai, $now) {
                $a->where('status', 'AKTIF');
                if ($selesai) {
                    $a->whereDate('tanggal_mulai', '<=', $selesai);
                }
                if ($mulai) {
                    $a->where(function ($w) use ($mulai, $now) {
                        $w->whereDate('tanggal_selesai', '>=', $mulai)
                          ->orWhere('tanggal_selesai', '<', $now);
                    });
                }
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('id_karyawan', 'like', "%{$search}%")
                  ->orWhere('unit_kerja', 'like', "%{$search}%")
                  ->orWhereHas('assign.unit', fn($u) => $u->where('nomor_unit', 'like', "%{$search}%"))
                  ->orWhereHas('assign.unit.apartemen', fn($ap) => $ap->where('nama_apartemen', 'like', "%{$search}%"));
            });
        }

        $people = $query->with('assign.unit.apartemen', 'assign.request.user')->get();

        // Bisnis unit: cocok bila ada catatan riwayat dengan bisnis unit itu, ATAU bisnis unit pemohon
        // penempatan sekarang sama (catatan Check-in lama tidak menyimpan bisnis unit, jadi tidak cukup lewat riwayat).
        if ($request->filled('bisnis_unit')) {
            $bu = $request->bisnis_unit;
            $viaHistory = ApartemenHistory::whereIn('id_karyawan', $people->pluck('id_karyawan')->all())
                ->where('bisnis_unit', $bu)
                ->pluck('id_karyawan')
                ->flip();
            $people = $people->filter(fn($pg) => $viaHistory->has($pg->id_karyawan)
                || BisnisUnit::resolveNamaForUser(optional(optional($pg->assign)->request)->user) === $bu);
        }

        return $people->keyBy('id_karyawan');
    }

    // Baris riwayat "virtual" untuk penghuni aktif yang belum punya catatan pada rentang filter.
    // Sudah check-in -> dianggap kejadian Masuk (waktu = checkin_at); belum -> "Jadwal disetujui".
    private function historyVirtualRow($pg): ApartemenHistory
    {
        $a   = $pg->assign;
        $row = new ApartemenHistory;
        $row->forceFill([
            'id_karyawan'    => $pg->id_karyawan,
            'nama'           => $pg->nama,
            'no_hp'          => $pg->no_hp,
            'unit_kerja'     => $pg->unit_kerja,
            'gol'            => $pg->gol,
            'apartemen'      => $a->unit->apartemen->nama_apartemen ?? '-',
            'unit'           => $a->unit->nomor_unit ?? '-',
            'periode'        => Carbon::parse($a->tanggal_mulai)->format('d/m/Y') . ' - ' . Carbon::parse($a->tanggal_selesai)->format('d/m/Y'),
            'status_selesai' => $a->checkin_at ? 'CHECKIN' : 'BOOKING',
            'catatan'        => null,
            'created_at'     => $a->checkin_at ?: ($a->created_at ?: $a->tanggal_mulai),
        ]);

        return $row;
    }

    // Batasi query ke penghuni yang punya catatan sesuai filter.
    private function historyScopePeople($query, Request $request)
    {
        $table = (new ApartemenHistory)->getTable();

        return $query->whereIn('id_karyawan', function ($q) use ($table, $request) {
            $q->from($table)->select('id_karyawan');
            $this->applyHistoryFilters($q, $request);
        });
    }

    // Satu baris TERAKHIR per penghuni (satu orang = satu baris), sesuai filter.
    private function historyLatestPerPerson(Request $request)
    {
        $table = (new ApartemenHistory)->getTable();

        $query = ApartemenHistory::query()->whereIn('id', function ($q) use ($table) {
            $q->from($table)->selectRaw('MAX(id)')->groupBy('id_karyawan');
        });

        return $this->historyScopePeople($query, $request);
    }

    // Pecah kejadian 1 orang (urut lama -> baru) menjadi "penempatan". Penempatan baru dimulai
    // setelah kejadian penutup (Check Out / Dibatalkan). Hasil: terbaru di indeks 0.
    private function splitHistoryStays($events): array
    {
        $stays = [];
        $cur   = [];
        foreach ($events as $e) {
            if ($cur && in_array(end($cur)->status_selesai, ['SELESAI', 'DIBATALKAN'], true)) {
                $stays[] = $cur;
                $cur = [];
            }
            $cur[] = $e;
        }
        if ($cur) {
            $stays[] = $cur;
        }

        return array_reverse($stays);
    }

    // Ringkasan satu penempatan: lama tinggal (malam) dan keterlambatan keluar dari jadwal yang disetujui.
    // Hanya dihitung bila ada catatan Masuk (CHECKIN) dan Keluar (SELESAI).
    // Tanggal jadwal diambil dari kolom "periode" ("dd/mm/yyyy - dd/mm/yyyy"); catatan "Dipindah"
    // tidak dipakai untuk tanggal selesai karena berisi tanggal pindah.
    private function historyStaySummary(array $stay): array
    {
        $evs = collect($stay);
        $in  = $evs->firstWhere('status_selesai', 'CHECKIN');
        $out = $evs->firstWhere('status_selesai', 'SELESAI');

        $re = '#^\d{2}/\d{2}/\d{4}$#';
        $selesai = null;
        foreach ($stay as $e) {
            $parts = array_map('trim', explode(' - ', (string) $e->periode));
            if ($e->status_selesai !== 'DIPINDAH' && isset($parts[1]) && preg_match($re, $parts[1])) {
                $selesai = Carbon::createFromFormat('d/m/Y', $parts[1])->startOfDay();
            }
        }

        $malam = null;
        $lewat = 0;
        if ($in && $out) {
            $masukHari  = $in->created_at->copy()->startOfDay();
            $keluarHari = $out->created_at->copy()->startOfDay();
            $malam = (int) abs($masukHari->diffInDays($keluarHari));
            if ($selesai && $keluarHari->gt($selesai)) {
                $lewat = (int) abs($selesai->diffInDays($keluarHari));
            }
        }

        return ['malam' => $malam, 'lewat' => $lewat];
    }

    public function history(Request $request)
    {
        [$rangeMulai, $rangeSelesai, $isDefaultRange] = $this->historyRange($request);
        $periodeLabel = $this->historyPeriodeLabel($rangeMulai, $rangeSelesai);

        // Daftar = penghuni yang punya catatan riwayat pada rentang filter
        //        + penghuni AKTIF yang tinggal pada rentang itu (walau belum ada catatan).
        $rows   = $this->historyLatestPerPerson($request)->get();
        $active = $this->historyActivePeople($request);

        $virtualIds = [];
        $virtual    = [];
        foreach ($active as $idKaryawan => $pg) {
            if (!$pg->assign || $rows->contains('id_karyawan', $idKaryawan)) {
                continue;
            }
            $virtual[$idKaryawan] = $this->historyVirtualRow($pg);
            $virtualIds[] = $idKaryawan;
        }

        // Urut: tanggal periode (jadwal mulai) terbaru di atas. Tanggal diambil dari kolom "periode"
        // ("dd/mm/yyyy - dd/mm/yyyy"); bila tidak terbaca, pakai waktu catatan. Tie-breaker: waktu catatan, lalu id.
        $periodeStart = function ($r) {
            $first = trim(explode(' - ', (string) $r->periode)[0]);
            if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $first)) {
                return Carbon::createFromFormat('d/m/Y', $first)->startOfDay()->timestamp;
            }
            return optional($r->created_at)->timestamp ?? 0;
        };
        $merged = $rows->concat(array_values($virtual))
            ->sortByDesc(fn($r) => [$periodeStart($r), optional($r->created_at)->timestamp ?? 0, (int) $r->id])
            ->values();

        $perPage   = 10;
        $page      = LengthAwarePaginator::resolveCurrentPage();
        $histories = new LengthAwarePaginator(
            $merged->forPage($page, $perPage)->values(),
            $merged->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Kartu ringkasan: total penghuni pada daftar (SAMA dengan jumlah baris).
        // Dibatalkan: penghuni yang pembatalannya tercatat pada periode/filter ini.
        $batal = $this->applyHistoryFilters(ApartemenHistory::query(), $request)
            ->where('status_selesai', 'DIBATALKAN')
            ->distinct()
            ->count('id_karyawan');

        $stats = ['total' => $merged->count(), 'batal' => $batal];

        // Perjalanan lengkap (semua kejadian) untuk penghuni yang tampil di halaman ini.
        $ids    = $histories->pluck('id_karyawan')->filter()->unique()->values()->all();
        $events = ApartemenHistory::whereIn('id_karyawan', $ids)
            ->orderBy('created_at')->orderBy('id')
            ->get()
            ->groupBy('id_karyawan');

        $journeys = [];
        $ringkas  = [];
        foreach ($histories as $h) {
            $isVirtual = in_array($h->id_karyawan, $virtualIds, true);
            $own = $events->get($h->id_karyawan) ?? ($isVirtual ? collect() : collect([$h]));
            $stays = $this->splitHistoryStays($own);

            // Penghuni aktif tanpa catatan pada penempatan sekarang: tambahkan sebagai penempatan terbaru.
            if ($isVirtual) {
                $last = $own->last();
                if (!$last || in_array($last->status_selesai, ['SELESAI', 'DIBATALKAN'], true)) {
                    array_unshift($stays, [$h]);
                }
            }

            $journeys[$h->id_karyawan] = $stays;
            $ringkas[$h->id_karyawan]  = $this->historyStaySummary($stays[0]);
        }

        // Penempatan yang sedang berjalan untuk tiap penghuni: jadwal yang disetujui (dari kapan ke kapan),
        // unit saat ini, dan waktu masuk. Riwayat saja tidak menyimpan semua ini (mis. setelah pindah).
        $live = ApartemenPenghuni::with('assign.unit.apartemen')
            ->whereIn('id_karyawan', $ids)
            ->where('status', 'AKTIF')
            ->get()
            ->keyBy('id_karyawan');

        // Penempatan pertama dalam satu permintaan = jadwal awal yang disetujui (sebelum pernah dipindah).
        $chains = ApartemenAssign::whereIn('request_id', $live->pluck('assign.request_id')->filter()->unique()->values()->all())
            ->orderBy('id')
            ->get()
            ->groupBy('request_id');

        $bookings = [];
        foreach ($live as $idKaryawan => $pg) {
            $a = $pg->assign;
            if (!$a) {
                continue;
            }
            $first = ($chains->get($a->request_id) ?? collect())->first() ?: $a;
            $bookings[$idKaryawan] = [
                'apartemen'  => $a->unit->apartemen->nama_apartemen ?? '-',
                'unit'       => $a->unit->nomor_unit ?? '-',
                'mulai'      => $first->tanggal_mulai ?: $a->tanggal_mulai,
                'selesai'    => $a->tanggal_selesai,
                'checkin_at' => $a->checkin_at,
            ];
        }

        $bisnisUnits = BisnisUnit::orderBy('nama_bisnis_unit')->get();

        return view('apartemen.admin.history', compact(
            'histories', 'journeys', 'ringkas', 'bookings', 'bisnisUnits', 'stats',
            'rangeMulai', 'rangeSelesai', 'isDefaultRange', 'periodeLabel'
        ));
    }

    // MANAJEMEN APARTEMEN & UNIT
    public function apartemen(Request $request)
    {
        UnitService::sinkronSemua();

        $query = Apartemen::withCount(['units', 'units as units_ready' => function($q) {
            $q->where('status', 'READY');
        }, 'units as units_terisi' => function($q) {
            $q->where('status', 'TERISI');
        }]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_apartemen', 'like', "%{$search}%")
                  ->orWhere('penanggung_jawab', 'like', "%{$search}%");
            });
        }

        $apartemen = $query->paginate(10)->withQueryString();

        // Kartu ringkasan unit ikut filter pencarian (sebelumnya selalu menghitung semua unit).
        $unitQuery = ApartemenUnit::query();
        if ($request->filled('search')) {
            $search = $request->search;
            $unitQuery->whereHas('apartemen', function ($q) use ($search) {
                $q->where('nama_apartemen', 'like', "%{$search}%")
                  ->orWhere('penanggung_jawab', 'like', "%{$search}%");
            });
        }
        $unitStats = [
            'ready'       => (clone $unitQuery)->where('status', 'READY')->count(),
            'terisi'      => (clone $unitQuery)->where('status', 'TERISI')->count(),
            'maintenance' => (clone $unitQuery)->where('status', 'MAINTENANCE')->count(),
        ];

        return view('apartemen.admin.apartemen', compact('apartemen', 'unitStats'));
    }

    public function apartemenDetail($id, Request $request)
    {
        UnitService::sinkronSemua();

        $apartemen = Apartemen::withCount([
            'units as units_count',
            'units as units_ready' => function ($query) {
                $query->where('status', 'READY');
            },
            'units as units_terisi' => function ($query) {
                $query->where('status', 'TERISI');
            },
            'units as units_maintenance' => function ($query) {
                $query->where('status', 'MAINTENANCE');
            }
        ])->findOrFail($id);

        $unitsQuery = ApartemenUnit::where('apartemen_id', $id)
            ->withCount(['assigns as active_assignments' => function($q) {
                $q->where('status', 'AKTIF');
            }]);

        if ($request->filled('search')) {
            $unitsQuery->where('nomor_unit', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            $unitsQuery->where('status', $request->status);
        }

        $units = $unitsQuery->orderBy('nomor_unit')->paginate(10)->withQueryString();
        
        // Ambil semua bisnis unit untuk dropdown
        $bisnisUnits = BisnisUnit::all();

        return view('apartemen.admin.apartemen-detail', compact('apartemen', 'units', 'bisnisUnits'));
    }

    public function detail($id)
    {
        $request = ApartemenRequest::with([
            'user', 
            'penghuni'
        ])->findOrFail($id);

        $units = collect();
        if ($request->status == 'APPROVED') {
            $units = ApartemenAssign::where('request_id', $request->id)
                ->with(['unit.apartemen', 'penghuni' => function($query) {
                    $query->select('id', 'assign_id', 'nama', 'id_karyawan', 'no_hp', 'unit_kerja', 'gol', 'status');
                }])
                ->get();
        }

        return view('apartemen.admin.detail', compact('request', 'units'));
    }
    
    public function storeApartemen(Request $request)
    {
        try {
            $validated = $request->validate([
                'nama_apartemen' => 'required|string|max:255',
                'alamat' => 'required|string|max:500',
                'penanggung_jawab' => 'nullable|string|max:100',
                'kontak_darurat' => 'nullable|string|max:50',
                'telepon' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:100',
            ]);

            Apartemen::create($validated);

            return redirect()->route('apartemen.admin.apartemen')
                ->with('success', 'Apartemen berhasil ditambahkan');
                
        } catch (\Exception $e) {
            Log::error('Error storing apartemen: ' . $e->getMessage());
            
            return back()->withInput()
                ->with('error', 'Gagal menambahkan apartemen: ' . $e->getMessage());
        }
    }

    public function storeUnit(Request $request)
    {
        try {
            $validated = $request->validate([
                'apartemen_id' => 'required|exists:tb_apartemen,id',
                'nomor_unit' => 'required|string|max:50|unique:tb_apartemen_unit,nomor_unit,NULL,id,apartemen_id,' . $request->apartemen_id,
                'kapasitas' => 'required|integer|min:1',
                'status' => 'required|in:READY,TERISI,MAINTENANCE',
                'bisnis_unit_id' => 'nullable|exists:tb_bisnis_unit,id_bisnis_unit',
                'gambar_360' => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
                'catatan' => 'nullable|string|max:500'
            ]);

            if ($request->hasFile('gambar_360')) {
                $path = $request->file('gambar_360')->store('unit_360', 'public');
                $validated['gambar_360'] = $path;
            }

            $unit = ApartemenUnit::create($validated);

            // Setelah create, langsung redirect ke halaman edit unit yang baru
            return redirect()->route('apartemen.admin.apartemen.detail', $unit->apartemen_id)
            ->with('success', 'Unit berhasil ditambahkan');
        } catch (\Exception $e) {
            Log::error('Error storing unit: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Gagal menambahkan unit: ' . $e->getMessage());
        }
    }

    public function deleteUnit(Request $request)
    {
        try {
            $validated = $request->validate([
                'unit_id' => 'required|exists:tb_apartemen_unit,id'
            ]);

            $unit = ApartemenUnit::find($validated['unit_id']);
            
            if (!$unit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit tidak ditemukan'
                ], 404);
            }
            
            $punyaPenempatan = DB::table('tb_apartemen_assign')
                ->where('unit_id', $unit->id)
                ->where('status', 'AKTIF')
                ->whereNull('deleted_at')
                ->exists();

            if ($punyaPenempatan) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit masih memiliki penempatan aktif atau booking mendatang, tidak dapat dihapus'
                ], 400);
            }

            $unit->delete();

            return response()->json([
                'success' => true,
                'message' => 'Unit berhasil dihapus'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error deleting unit: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function setMaintenance(Request $request)
    {
        try {
            $validated = $request->validate([
                'unit_id' => 'required|exists:tb_apartemen_unit,id',
                'status' => 'required|in:READY,MAINTENANCE',
                'catatan' => 'nullable|string|max:500'
            ]);

            $unit = ApartemenUnit::find($validated['unit_id']);

            if (!$unit) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit tidak ditemukan'
                ], 404);
            }

            if ($validated['status'] == 'MAINTENANCE') {
                // Tidak boleh ada penempatan aktif (sedang dihuni maupun booking yang akan datang).
                $adaPenempatan = DB::table('tb_apartemen_assign')
                    ->where('unit_id', $unit->id)
                    ->where('status', 'AKTIF')
                    ->whereNull('deleted_at')
                    ->whereDate('tanggal_selesai', '>=', now()->toDateString())
                    ->exists();

                if ($adaPenempatan || UnitService::terisiSekarang((int) $unit->id)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unit masih memiliki penempatan aktif atau booking mendatang, tidak dapat diubah ke maintenance'
                    ], 400);
                }
            }

            $unit->update([
                'status' => $validated['status'],
                'catatan' => $validated['catatan'] ?? $unit->catatan
            ]);

            // Keluar dari maintenance: status mengikuti kenyataan (READY / TERISI).
            UnitService::sinkron((int) $unit->id);

            return response()->json([
                'success' => true,
                'message' => 'Status unit berhasil diperbarui'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error setting maintenance: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    // Filter tambahan laporan: Request (pemohon) & Unit Kerja (penghuni)
    private function applyReportExtraFilters($query, Request $request)
    {
        // Filter Request (user yang mengajukan request)
        if ($request->filled('request_user')) {
            $requestUser = $request->request_user;
            $query->whereHas('assign.request', function($q) use ($requestUser) {
                $q->where('user_id', $requestUser);
            });
        }

        // Filter Unit Kerja (unit kerja penghuni)
        if ($request->filled('unit_kerja')) {
            $query->where('unit_kerja', $request->unit_kerja);
        }

        return $query;
    }

    // REPORT
    // Satu query dipakai oleh halaman laporan DAN export Excel, jadi isinya selalu sama.
    // Periode (default bulan berjalan) memakai logika irisan jadwal: penempatan masuk laporan bila
    // jadwalnya beririsan dengan periode, atau sudah lewat jadwal tapi belum check-out.
    private function reportQuery(Request $request)
    {
        [$mulai, $selesai] = $this->historyRange($request);
        $now = now();

        $query = ApartemenPenghuni::with([
                'assign.unit.apartemen',
                'assign.request.user'
            ])
            ->whereHas('assign', function ($a) use ($mulai, $selesai, $request, $now) {
                $a->whereIn('status', ['AKTIF', 'SELESAI']);

                if ($selesai) {
                    $a->whereDate('tanggal_mulai', '<=', $selesai);
                }
                if ($mulai) {
                    $a->where(function ($w) use ($mulai, $now) {
                        $w->whereDate('tanggal_selesai', '>=', $mulai)
                          ->orWhere(function ($x) use ($now) {
                              $x->where('status', 'AKTIF')->where('tanggal_selesai', '<', $now);
                          });
                    });
                }
                // Filter status: sama dengan status yang tampil di tabel (lihat reportStatus()).
                switch ($request->status) {
                    case 'AKTIF':   // semua yang masih tercatat aktif (menginap + belum aktif + belum check-out)
                    case 'SELESAI':
                        $a->where('status', $request->status);
                        break;
                    case 'BELUM_AKTIF':
                        $a->where('status', 'AKTIF')->where('tanggal_mulai', '>', $now);
                        break;
                    case 'MENGINAP':
                        $a->where('status', 'AKTIF')->where('tanggal_mulai', '<=', $now)->where('tanggal_selesai', '>=', $now);
                        break;
                    case 'BELUM_CHECKOUT':
                        $a->where('status', 'AKTIF')->where('tanggal_mulai', '<=', $now)->where('tanggal_selesai', '<', $now);
                        break;
                }
            })
            // Urut berdasarkan kolom "Periode" (tanggal mulai penempatan), terbaru di atas.
            ->orderByDesc(
                DB::table('tb_apartemen_assign')
                    ->select('tanggal_mulai')
                    ->whereColumn('tb_apartemen_assign.id', (new ApartemenPenghuni)->getTable() . '.assign_id')
                    ->limit(1)
            )
            ->orderByDesc(
                DB::table('tb_apartemen_assign')
                    ->select('tanggal_selesai')
                    ->whereColumn('tb_apartemen_assign.id', (new ApartemenPenghuni)->getTable() . '.assign_id')
                    ->limit(1)
            )
            ->orderByDesc('id'); // tie-breaker agar urutan stabil

        if (in_array($request->status, ['AKTIF', 'SELESAI'], true)) {
            $query->where('status', $request->status);
        } elseif (in_array($request->status, ['BELUM_AKTIF', 'MENGINAP', 'BELUM_CHECKOUT'], true)) {
            $query->where('status', 'AKTIF');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('id_karyawan', 'like', "%{$search}%")
                  ->orWhere('unit_kerja', 'like', "%{$search}%");
            });
        }

        return $this->applyReportExtraFilters($query, $request);
    }

    // Status tampilan satu penempatan: [kunci, teks, warna].
    private function reportStatus($assign): array
    {
        $now = now();
        if ($assign->status === 'SELESAI') {
            return ['selesai', 'Selesai', 'gray'];
        }
        if ($assign->status === 'AKTIF') {
            if ($assign->tanggal_mulai > $now) {
                return ['belum_aktif', 'Belum aktif', 'yellow'];
            }
            if ($assign->tanggal_selesai < $now) {
                return ['belum_checkout', 'Belum check-out', 'red'];
            }
            return ['aktif', 'Aktif (menginap)', 'green'];
        }
        return [strtolower((string) $assign->status), (string) $assign->status, 'gray'];
    }

    public function report(Request $request)
    {
        [$rangeMulai, $rangeSelesai, $isDefaultRange] = $this->historyRange($request);
        $periodeLabel = $this->historyPeriodeLabel($rangeMulai, $rangeSelesai);

        $list = $this->reportQuery($request)->get()
            ->filter(fn($p) => $p->assign)
            ->each(function ($p) {
                [$key, $text, $tone] = $this->reportStatus($p->assign);
                $p->laporan_status = ['key' => $key, 'text' => $text, 'tone' => $tone];
            })
            ->values();

        // Kartu ringkasan: dihitung dari SEMUA data yang cocok filter (bukan hanya halaman ini).
        $by = $list->countBy(fn($p) => $p->laporan_status['key']);
        $stats = [
            'total'          => $list->count(),
            'selesai'        => $by->get('selesai', 0),
            'aktif'          => $by->get('aktif', 0),
            'belum_aktif'    => $by->get('belum_aktif', 0),
            'belum_checkout' => $by->get('belum_checkout', 0),
        ];

        $perPage     = 10;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $histories   = new LengthAwarePaginator(
            $list->forPage($currentPage, $perPage)->values(),
            $list->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        // Data dropdown filter
        $requesters = \App\Models\User::whereIn('id', function($q) {
                $q->select('user_id')
                  ->from('tb_apartemen_request')
                  ->whereNull('deleted_at')
                  ->whereIn('id', function($q2) {
                      $q2->select('request_id')->from('tb_apartemen_assign')->whereNull('deleted_at');
                  });
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $unitKerjaList = ApartemenPenghuni::whereNotNull('unit_kerja')
            ->where('unit_kerja', '!=', '')
            ->distinct()
            ->orderBy('unit_kerja')
            ->pluck('unit_kerja');

        return view('apartemen.admin.report', compact(
            'histories', 'requesters', 'unitKerjaList', 'stats',
            'rangeMulai', 'rangeSelesai', 'isDefaultRange', 'periodeLabel'
        ));
    }

    public function exportReport(Request $request)
    {
        [$rangeMulai, $rangeSelesai] = $this->historyRange($request);
        $penghuniList = $this->reportQuery($request)->get();

        // Buat spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'Nama yang request', 'Tanggal', 'Alasan', 'Nama Penghuni', 'ID Karyawan',
            'Unit Kerja', 'Golongan', 'Apartemen', 'Unit', 'Periode', 'Status'
        ];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $sheet->getStyle($col . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE0E0E0');
            $col++;
        }

        $row = 2;
        foreach ($penghuniList as $penghuni) {
            $assign = $penghuni->assign;
            if (!$assign) continue;

            $unit        = $assign->unit;
            $apartemen   = $unit->apartemen ?? null;
            $requestData = $assign->request;
            $pemohon     = $requestData && $requestData->user ? $requestData->user->name : '-';
            $alasan      = $requestData ? $requestData->alasan : '-';
            $tanggalPenempatan = $assign->created_at ? $assign->created_at->format('d/m/Y H:i') : '-';
            $statusText  = $this->reportStatus($assign)[1];
            $periode     = $assign->tanggal_mulai->format('d/m/Y') . ' - ' . $assign->tanggal_selesai->format('d/m/Y');

            $sheet->setCellValue('A' . $row, $pemohon);
            $sheet->setCellValue('B' . $row, $tanggalPenempatan);
            $sheet->setCellValue('C' . $row, $alasan);
            $sheet->setCellValue('D' . $row, $penghuni->nama);
            $sheet->setCellValue('E' . $row, $penghuni->id_karyawan);
            $sheet->setCellValue('F' . $row, $penghuni->unit_kerja ?? '-');
            $sheet->setCellValue('G' . $row, $penghuni->gol ?? '-');
            $sheet->setCellValue('H' . $row, $apartemen ? $apartemen->nama_apartemen : '-');
            $sheet->setCellValue('I' . $row, $unit ? $unit->nomor_unit : '-');
            $sheet->setCellValue('J' . $row, $periode);
            $sheet->setCellValue('K' . $row, $statusText);
            $row++;
        }

        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Download
        $writer = new Xlsx($spreadsheet);
        $filename = 'laporan-apartemen-' . ($rangeMulai ?: 'awal') . '_sd_' . ($rangeSelesai ?: 'sekarang') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $writer->save('php://output');
        exit;
    }

    private function occupancyReport($request)
    {
        $apartemen = Apartemen::withCount(['units', 'units as units_ready', 'units as units_terisi'])
            ->get();

        return [
            'title' => 'Laporan Occupancy Apartemen',
            'data' => $apartemen,
            'total_units' => $apartemen->sum('units_count'),
            'total_ready' => $apartemen->sum('units_ready'),
            'total_terisi' => $apartemen->sum('units_terisi'),
        ];
    }

    private function utilizationReport($request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        $assignments = ApartemenAssign::with(['unit.apartemen'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return [
            'title' => 'Laporan Utilization Apartemen',
            'data' => $assignments,
            'total_assignments' => $assignments->count(),
            'total_days' => $assignments->sum(function($assign) {
                return $assign->tanggal_mulai->diffInDays($assign->tanggal_selesai);
            }),
        ];
    }

    private function maintenanceReport($request)
    {
        $units = ApartemenUnit::with('apartemen')
            ->where('status', 'MAINTENANCE')
            ->get();

        return [
            'title' => 'Laporan Maintenance Unit',
            'data' => $units,
            'total_maintenance' => $units->count(),
        ];
    }

    // =================================================================
    // METHOD UNTUK BISNIS UNIT & GAMBAR 360° (BARU)
    // =================================================================

    /**
     * Mengambil data unit untuk form edit (AJAX)
     */
    public function getUnitEditData($id)
    {
        $unit = ApartemenUnit::findOrFail($id);
        return response()->json($unit);
    }
    
    public function editUnitForm($id)
    {
        $unit = ApartemenUnit::with('apartemen')->findOrFail($id);
        $bisnisUnits = BisnisUnit::all();
        return view('apartemen.admin.unit-edit', compact('unit', 'bisnisUnits'));
    }

    /**
     * Memperbarui data unit (termasuk upload gambar 360)
     */
    public function updateUnit(Request $request, $id)
    {
        $unit = ApartemenUnit::findOrFail($id);

        $validated = $request->validate([
            'nomor_unit'   => 'required|string|max:50',
            'kapasitas'    => 'required|integer|min:1',
            'status'       => 'required|in:READY,TERISI,MAINTENANCE',
            'bisnis_unit_id' => 'nullable|exists:tb_bisnis_unit,id_bisnis_unit',
            'gambar_360'   => 'nullable|image|mimes:jpeg,png,jpg|max:10240',
            'catatan'      => 'nullable|string|max:500'
        ]);

        if ($request->hasFile('gambar_360')) {
            if ($unit->gambar_360 && Storage::disk('public')->exists($unit->gambar_360)) {
                Storage::disk('public')->delete($unit->gambar_360);
            }
            $path = $request->file('gambar_360')->store('unit_360', 'public');
            $validated['gambar_360'] = $path;
        } else {
            unset($validated['gambar_360']);
        }

        $unit->update($validated);

        // Setelah update, tetap di halaman edit yang sama
         return redirect()->route('apartemen.admin.apartemen.detail', $unit->apartemen_id)
        ->with('success', 'Unit berhasil diperbarui');
    }

    public function createUnitForm($apartemen_id)
    {
        $bisnisUnits = BisnisUnit::all();
        return view('apartemen.admin.unit-edit', compact('bisnisUnits', 'apartemen_id'));
    }
}