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

class AdminController extends Controller
{
    // DASHBOARD ADMIN
    public function dashboard()
    {
        $stats = [
            'total_apartemen' => Apartemen::count(),
            'total_unit' => ApartemenUnit::count(),
            'unit_tersedia' => ApartemenUnit::where('status', 'READY')->count(),
            'unit_terisi' => ApartemenUnit::where('status', 'TERISI')->count(),
            'unit_maintenance' => ApartemenUnit::where('status', 'MAINTENANCE')->count(),
            'permintaan_pending' => ApartemenRequest::where('status', 'PENDING')->count(),
            'permintaan_approved' => ApartemenRequest::where('status', 'APPROVED')->count(),
            'permintaan_rejected' => ApartemenRequest::where('status', 'REJECTED')->count(),
            'penghuni_aktif' => ApartemenPenghuni::where('status', 'AKTIF')->count(),
        ];

        // Permintaan pending terbaru
        $pendingRequests = ApartemenRequest::with(['user', 'penghuni'])
            ->where('status', 'PENDING')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Penghuni yang akan selesai dalam 7 hari
        $upcomingCheckouts = ApartemenAssign::with(['unit.apartemen', 'penghuni'])
            ->where('status', 'AKTIF')
            ->whereBetween('tanggal_selesai', [now(), now()->addDays(7)])
            ->orderBy('tanggal_selesai')
            ->get();

        // Unit dalam maintenance
        $maintenanceUnits = ApartemenUnit::with('apartemen')
            ->where('status', 'MAINTENANCE')
            ->get();

        return view('apartemen.admin.dashboard', compact(
            'stats', 
            'pendingRequests', 
            'upcomingCheckouts', 
            'maintenanceUnits'
        ));
    }

    // INDEX - KHUSUS MENAMPILKAN DATA PENDING SAJA
    public function index(Request $request)
    {
        $query = ApartemenRequest::with(['user', 'penghuni'])
            ->where('status', 'PENDING')
            ->orderBy('created_at', 'desc');

        // Filter tanggal
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('created_at', '>=', $request->tanggal_mulai);
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('created_at', '<=', $request->tanggal_selesai);
        }

        // Search
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

        $requests = $query->paginate(10);

        return view('apartemen.admin.index', compact('requests'));
    }

    // APPROVE/REVIEW REQUEST - UNTUK MULTIPLE UNITS
    public function approve($id)
    {
        $request = ApartemenRequest::with(['penghuni', 'user'])
            ->where('status', 'PENDING')
            ->findOrFail($id);

        // Ambil rentang tanggal dari request (min tanggal_mulai dan max tanggal_selesai)
        $tanggalMulai = $request->penghuni->min('tanggal_mulai');
        $tanggalSelesai = $request->penghuni->max('tanggal_selesai');

        // Unit yang TIDAK memiliki assign AKTIF dalam rentang tanggal
        // dan tidak dalam status MAINTENANCE
        $availableUnits = ApartemenUnit::with('apartemen')
            ->where('status', '!=', 'MAINTENANCE')
            ->whereDoesntHave('assigns', function($query) use ($tanggalMulai, $tanggalSelesai) {
                $query->where('status', 'AKTIF')
                      ->where(function($q) use ($tanggalMulai, $tanggalSelesai) {
                          // Cek apakah ada assign yang bentrok tanggalnya
                          $q->whereBetween('tanggal_mulai', [$tanggalMulai, $tanggalSelesai])
                            ->orWhereBetween('tanggal_selesai', [$tanggalMulai, $tanggalSelesai])
                            ->orWhere(function($q2) use ($tanggalMulai, $tanggalSelesai) {
                                $q2->where('tanggal_mulai', '<=', $tanggalMulai)
                                   ->where('tanggal_selesai', '>=', $tanggalSelesai);
                            });
                      });
            })
            ->get();

        return view('apartemen.admin.approve', compact('request', 'availableUnits'));
    }

    // PROCESS APPROVAL - VERSI MULTIPLE UNITS
    public function approveProcess(Request $request, $id)
    {
        Log::info('=== APPROVE PROCESS START ===');
        Log::info('Request ID: ' . $id);
        Log::info('All request data:', $request->all());
        Log::info('Action: ' . $request->action);
        Log::info('Penempatan: ', $request->penempatan ?? []);
        
        $apartemenRequest = ApartemenRequest::with(['penghuni'])->findOrFail($id);

        // Validasi action
        $action = $request->action;
        
        DB::beginTransaction();
        try {
            if ($action === 'approve') {
                // Validasi untuk approve dengan multiple units
                $request->validate([
                    'penempatan' => 'required|array|min:1',
                    'penempatan.*.unit_id' => 'required|exists:tb_apartemen_unit,id',
                    'penempatan.*.penghuni_ids' => 'required|array|min:1',
                    'tanggal_mulai' => 'required|date',
                    'tanggal_selesai' => 'required|date|after:tanggal_mulai'
                ]);

                // Debug: Log data yang diterima
                Log::info('Penempatan data received:', $request->penempatan);
                
                // Validasi bahwa semua penghuni tercover
                $penghuniTercover = collect();
                foreach ($request->penempatan as $item) {
                    $penghuniTercover = $penghuniTercover->merge($item['penghuni_ids']);
                }
                
                $semuaPenghuniIds = $apartemenRequest->penghuni->pluck('id')->toArray();
                $selisih = array_diff($semuaPenghuniIds, $penghuniTercover->toArray());
                
                if (!empty($selisih)) {
                    return back()->with('error', 'Ada penghuni yang belum ditetapkan ke unit!');
                }
                
                // Validasi kapasitas unit dan cek bentrok tanggal
                foreach ($request->penempatan as $item) {
                    $unit = ApartemenUnit::find($item['unit_id']);
                    $jumlahPenghuni = count($item['penghuni_ids']);
                    
                    // Validasi kapasitas
                    if ($unit->kapasitas < $jumlahPenghuni) {
                        return back()->with('error', "Unit {$unit->nomor_unit} kapasitas tidak mencukupi! (Kapasitas: {$unit->kapasitas}, Ditempatkan: {$jumlahPenghuni})");
                    }
                    
                    // CEK BENTROK TANGGAL - TAMBAHAN BARU
                    $bentrok = ApartemenAssign::where('unit_id', $unit->id)
                        ->where('status', 'AKTIF')
                        ->where(function($query) use ($request) {
                            $query->whereBetween('tanggal_mulai', [$request->tanggal_mulai, $request->tanggal_selesai])
                                  ->orWhereBetween('tanggal_selesai', [$request->tanggal_mulai, $request->tanggal_selesai])
                                  ->orWhere(function($q) use ($request) {
                                      $q->where('tanggal_mulai', '<=', $request->tanggal_mulai)
                                        ->where('tanggal_selesai', '>=', $request->tanggal_selesai);
                                  });
                        })
                        ->exists();

                    if ($bentrok) {
                        return back()->with('error', "Unit {$unit->nomor_unit} sudah ditempati pada periode {$request->tanggal_mulai} s/d {$request->tanggal_selesai}!");
                    }
                    
                    // Unit status tetap perlu dicek untuk memastikan tidak maintenance
                    if ($unit->status == 'MAINTENANCE') {
                        return back()->with('error', "Unit {$unit->nomor_unit} sedang dalam maintenance!");
                    }
                }
                
                // Update status request menjadi APPROVED
                $apartemenRequest->update([
                    'status' => 'APPROVED',
                    'approved_at' => now(),
                    'approved_by' => auth()->id()
                ]);
                
                // Proses penempatan ke masing-masing unit
                foreach ($request->penempatan as $item) {
                    $unit = ApartemenUnit::find($item['unit_id']);
                    
                    // Update status unit menjadi TERISI
                    $unit->update(['status' => 'TERISI']);
                    
                    // Buat assign untuk unit ini
                    $assign = ApartemenAssign::create([
                        'request_id' => $apartemenRequest->id,
                        'unit_id' => $unit->id,
                        'tanggal_mulai' => $request->tanggal_mulai,
                        'tanggal_selesai' => $request->tanggal_selesai,
                        'status' => 'AKTIF',
                        'assign_by' => auth()->id()
                    ]);
                    
                    // Buat penghuni untuk setiap ID yang dipilih
                    foreach ($item['penghuni_ids'] as $penghuniId) {
                        // Ambil data penghuni dari request
                        $reqPenghuni = $apartemenRequest->penghuni->where('id', $penghuniId)->first();
                        
                        if ($reqPenghuni) {
                            ApartemenPenghuni::create([
                                'assign_id' => $assign->id,
                                'nama' => $reqPenghuni->nama,
                                'id_karyawan' => $reqPenghuni->id_karyawan,
                                'no_hp' => $reqPenghuni->no_hp,
                                'unit_kerja' => $reqPenghuni->unit_kerja,
                                'gol' => $reqPenghuni->gol,
                                'tanggal_mulai' => $reqPenghuni->tanggal_mulai,
                                'tanggal_selesai' => $reqPenghuni->tanggal_selesai,
                                'status' => 'AKTIF',
                            ]);
                        }
                    }
                }
                
                DB::commit();
                
                return redirect()->route('apartemen.admin.index')
                    ->with('success', 'Permintaan berhasil disetujui dan penghuni telah ditempatkan!');
                
            } elseif ($action === 'reject') {
                // Validasi untuk reject
                $request->validate([
                    'reject_reason' => 'required|string|min:5|max:500'
                ]);

                // Reject request
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
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in approveProcess: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return back()->withInput()
                ->with('error', 'Gagal memproses penempatan: ' . $e->getMessage());
        }
    }

    /**
     * CHECK-IN PENGHUNI
     */
    public function checkin($id)
    {
        try {
            Log::info('=== CHECKIN START === ID: ' . $id);
            
            DB::beginTransaction();
            
            // Ambil data penghuni dengan assign
            $penghuni = ApartemenPenghuni::with(['assign.unit.apartemen'])
                ->where('id', $id)
                ->where('status', 'AKTIF')
                ->lockForUpdate()
                ->firstOrFail();
            
            Log::info('Penghuni ditemukan: ' . $penghuni->nama);
            
            // Validasi assign
            if (!$penghuni->assign) {
                throw new \Exception('Penghuni tidak memiliki data penempatan');
            }
            
            $assign = $penghuni->assign;
            
            // Validasi status assign
            if ($assign->status != 'AKTIF') {
                throw new \Exception('Penempatan tidak aktif');
            }
            
            // Validasi apakah sudah check-in
            if ($assign->checkin_at) {
                throw new \Exception('Penghuni sudah melakukan check-in pada ' . $assign->checkin_at->format('d/m/Y H:i'));
            }
            
            // Validasi tanggal mulai
            if ($assign->tanggal_mulai > now()) {
                throw new \Exception('Belum waktunya check-in. Tanggal mulai: ' . $assign->tanggal_mulai->format('d/m/Y'));
            }
            
            // Update checkin_at
            $assign->update([
                'checkin_at' => now()
            ]);
            
            // Catat history
            ApartemenHistory::create([
                'nama' => $penghuni->nama,
                'id_karyawan' => $penghuni->id_karyawan,
                'no_hp' => $penghuni->no_hp ?? '-',
                'unit_kerja' => $penghuni->unit_kerja ?? '-',
                'gol' => $penghuni->gol ?? '-',
                'apartemen' => $assign->unit->apartemen->nama_apartemen ?? '-',
                'unit' => $assign->unit->nomor_unit ?? '-',
                'periode' => $assign->tanggal_mulai->format('d/m/Y') . ' - ' . $assign->tanggal_selesai->format('d/m/Y'),
                'status_selesai' => 'CHECKIN',
                'created_at' => now()
            ]);
            
            DB::commit();
            
            Log::info('=== CHECKIN SUCCESS ===');
            
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

    // MONITORING PENGHUNI
    public function monitoring(Request $request)
    {
        $query = ApartemenPenghuni::with(['assign.unit.apartemen'])
            ->where('status', 'AKTIF');

        // Filter search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('id_karyawan', 'like', "%{$search}%")
                  ->orWhere('unit_kerja', 'like', "%{$search}%");
            });
        }

        // Filter apartemen
        if ($request->filled('apartemen_id')) {
            $query->whereHas('assign.unit', function($q) use ($request) {
                $q->where('apartemen_id', $request->apartemen_id);
            });
        }

        // Filter berdasarkan status check-in
        if ($request->filled('checkin_status')) {
            if ($request->checkin_status == 'sudah_checkin') {
                $query->whereHas('assign', function($q) {
                    $q->whereNotNull('checkin_at');
                });
            } elseif ($request->checkin_status == 'belum_checkin') {
                $query->whereHas('assign', function($q) {
                    $q->whereNull('checkin_at');
                });
            }
        }

        // Filter berdasarkan status
        if ($request->filled('status')) {
            if ($request->status == 'belum_aktif') {
                $query->whereHas('assign', function($q) {
                    $q->where('tanggal_mulai', '>', now());
                });
            } elseif ($request->status == 'aktif') {
                $query->whereHas('assign', function($q) {
                    $q->where('tanggal_mulai', '<=', now())
                      ->where('tanggal_selesai', '>=', now());
                });
            } elseif ($request->status == 'belum_checkout') {
                $query->whereHas('assign', function($q) {
                    $q->where('tanggal_selesai', '<', now());
                });
            }
        }

        // Sort
        $sort = $request->filled('sort') ? $request->sort : 'nama_asc';
        switch ($sort) {
            case 'nama_desc':
                $query->orderBy('nama', 'desc');
                break;
            case 'tanggal_mulai':
                $query->orderBy('tanggal_mulai', 'desc');
                break;
            case 'tanggal_selesai':
                $query->orderBy('tanggal_selesai', 'asc');
                break;
            default:
                $query->orderBy('nama', 'asc');
        }

        $penghuni = $query->paginate(10);
        $apartemen = Apartemen::all();

        return view('apartemen.admin.monitoring', compact('penghuni', 'apartemen'));
    }

    // CHECKOUT PER ORANG
    public function checkoutPenghuni($id)
    {
        try {
            Log::info('=== CHECKOUT START ===', ['id' => $id]);
            
            DB::beginTransaction();
            
            // 1. Cari penghuni dengan LOCK
            $penghuni = ApartemenPenghuni::with(['assign.unit.apartemen', 'assign.penghuni'])
                ->where('id', $id)
                ->where('status', 'AKTIF')
                ->lockForUpdate()
                ->first();
            
            if (!$penghuni) {
                throw new \Exception('Penghuni tidak ditemukan atau sudah tidak aktif');
            }
            
            Log::info('Penghuni ditemukan', [
                'nama' => $penghuni->nama,
                'assign_id' => $penghuni->assign_id
            ]);
            
            // 2. Cek assign
            if (!$penghuni->assign) {
                throw new \Exception('Penghuni tidak memiliki data penempatan');
            }
            
            $assign = $penghuni->assign;
            
            // 3. Validasi status assign
            if ($assign->status != 'AKTIF') {
                throw new \Exception('Status penempatan tidak aktif');
            }
            
            // 4. Validasi apakah sudah check-in
            if (!$assign->checkin_at) {
                throw new \Exception('Penghuni belum melakukan check-in. Tidak bisa check-out.');
            }
            
            // 5. Hitung penghuni aktif SEBELUM checkout
            $activeBefore = $assign->penghuni()
                ->where('status', 'AKTIF')
                ->count();
            
            Log::info('Active penghuni before checkout', ['count' => $activeBefore]);
            
            // 6. Update status penghuni
            $penghuni->update(['status' => 'SELESAI']);
            
            // 7. Hitung penghuni aktif SETELAH checkout
            $activeAfter = $assign->penghuni()
                ->where('status', 'AKTIF')
                ->count();
            
            Log::info('Active penghuni after checkout', ['count' => $activeAfter]);
            
            // 8. Jika ini penghuni TERAKHIR, update assign ke SELESAI dan unit ke READY
            if ($activeAfter == 0) {
                Log::info('Last penghuni, updating assign and unit');
                
                $assign->update(['status' => 'SELESAI']);
                
                if ($assign->unit) {
                    $assign->unit->update(['status' => 'READY']);
                    Log::info('Unit updated to READY', ['unit' => $assign->unit->nomor_unit]);
                }
            }
            
            // 9. Catat history dengan nilai ENUM yang valid
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
                'created_at' => now()
            ];
            
            Log::info('Mencatat history', $historyData);
            
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

    // HISTORY
    public function history(Request $request)
    {
        $query = ApartemenHistory::orderBy('created_at', 'desc');

        // Filter tanggal
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('created_at', [
                $request->tanggal_mulai,
                $request->tanggal_selesai
            ]);
        }

        // Filter status
        if ($request->filled('status_selesai')) {
            $query->where('status_selesai', $request->status_selesai);
        }

        // Tambahkan search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                ->orWhere('id_karyawan', 'like', "%{$search}%")
                ->orWhere('apartemen', 'like', "%{$search}%")
                ->orWhere('unit', 'like', "%{$search}%");
            });
        }

        $histories = $query->paginate(10);

        return view('apartemen.admin.history', compact('histories'));
    }

    // MANAJEMEN APARTEMEN & UNIT
    public function apartemen(Request $request)
    {
        $query = Apartemen::withCount(['units', 'units as units_ready' => function($q) {
            $q->where('status', 'READY');
        }, 'units as units_terisi' => function($q) {
            $q->where('status', 'TERISI');
        }]);

        if ($request->filled('search')) {
            $query->where('nama_apartemen', 'like', "%{$request->search}%")
                  ->orWhere('penanggung_jawab', 'like', "%{$request->search}%");
        }

        $apartemen = $query->paginate(10);

        return view('apartemen.admin.apartemen', compact('apartemen'));
    }

    // APARTEMEN DETAIL
    public function apartemenDetail($id, Request $request)
    {
        // Load apartemen dengan count unit berdasarkan status
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

        // Query units dengan pencarian
        $unitsQuery = ApartemenUnit::where('apartemen_id', $id)
            ->withCount(['assigns as active_assignments' => function($q) {
                $q->where('status', 'AKTIF');
            }]);

        // Filter search jika ada
        if ($request->filled('search')) {
            $unitsQuery->where('nomor_unit', 'like', '%' . $request->search . '%');
        }

        // Filter status jika ada
        if ($request->filled('status')) {
            $unitsQuery->where('status', $request->status);
        }

        $units = $unitsQuery->orderBy('nomor_unit')->paginate(10);

        return view('apartemen.admin.apartemen-detail', compact('apartemen', 'units'));
    }

    // DETAIL REQUEST
    public function detail($id)
    {
        $request = ApartemenRequest::with([
            'user', 
            'penghuni'
        ])->findOrFail($id);

        // Jika sudah di-approve, ambil unit yang ditempati
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
    
    // STORE APARTEMEN
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

    // STORE UNIT
    public function storeUnit(Request $request)
    {
        try {
            $validated = $request->validate([
                'apartemen_id' => 'required|exists:tb_apartemen,id',
                'nomor_unit' => 'required|string|max:20',
                'kapasitas' => 'required|integer|min:1',
                'status' => 'required|in:READY,MAINTENANCE'
            ]);

            // Cek nomor unit duplikat dalam apartemen yang sama
            $existingUnit = ApartemenUnit::where('apartemen_id', $validated['apartemen_id'])
                ->where('nomor_unit', $validated['nomor_unit'])
                ->first();

            if ($existingUnit) {
                return back()->withInput()
                    ->with('error', 'Nomor unit sudah digunakan di apartemen ini');
            }

            ApartemenUnit::create($validated);

            return back()->with('success', 'Unit berhasil ditambahkan');
            
        } catch (\Exception $e) {
            Log::error('Error storing unit: ' . $e->getMessage());
            
            return back()->withInput()
                ->with('error', 'Gagal menambahkan unit: ' . $e->getMessage());
        }
    }

    // DELETE UNIT
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
            
            // Cek apakah unit sedang digunakan
            if ($unit->status == 'TERISI') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit sedang terisi, tidak dapat dihapus'
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

    // SET MAINTENANCE
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

            // Cek jika unit sedang terisi, tidak bisa diubah ke maintenance
            if ($validated['status'] == 'MAINTENANCE' && $unit->status == 'TERISI') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unit sedang terisi, tidak dapat diubah ke maintenance'
                ], 400);
            }

            $unit->update([
                'status' => $validated['status'],
                'catatan' => $validated['catatan'] ?? $unit->catatan
            ]);

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

    // REPORT
    public function report(Request $request)
    {
        $reportData = [];
        $type = $request->get('type', 'occupancy');

        switch ($type) {
            case 'occupancy':
                $reportData = $this->occupancyReport($request);
                break;
            case 'utilization':
                $reportData = $this->utilizationReport($request);
                break;
            case 'maintenance':
                $reportData = $this->maintenanceReport($request);
                break;
        }

        return view('apartemen.admin.report', compact('reportData', 'type'));
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
}