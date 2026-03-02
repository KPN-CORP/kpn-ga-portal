<?php

namespace App\Http\Controllers\Apartemen;

use App\Http\Controllers\Controller;
use App\Models\Apartemen\ApartemenRequest;
use App\Models\Apartemen\ApartemenAssign;
use App\Models\Apartemen\ApartemenPenghuni;
use App\Models\Apartemen\ApartemenHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display user's active status (Halaman Status Aktif)
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        
        // Status Aktif
        $activeAssignments = ApartemenAssign::with(['unit.apartemen', 'penghuni'])
            ->whereHas('request', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('status', 'AKTIF')
            ->get();

        // Count for badges
        $requestCount = ApartemenRequest::where('user_id', $userId)->count();
        $historyCount = ApartemenHistory::where('id_karyawan', Auth::user()->employee_id)->count();

        return view('apartemen.user.index', compact('activeAssignments', 'requestCount', 'historyCount'));
    }

    /**
     * Display user's request history (Halaman Riwayat Permintaan)
     */
    public function requests(Request $request)
    {
        $userId = Auth::id();
        
        // Get active assignments count for badge
        $activeCount = ApartemenAssign::with(['unit.apartemen', 'penghuni'])
            ->whereHas('request', function($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('status', 'AKTIF')
            ->count();

        // Riwayat Permintaan - Data terbaru di atas
        $requestsQuery = ApartemenRequest::with(['penghuni', 'assign.unit.apartemen'])
            ->where('user_id', $userId);

        // Apply filters
        if ($request->filled('search')) {
            $requestsQuery->where(function($q) use ($request) {
                $q->where('alasan', 'like', '%' . $request->search . '%')
                  ->orWhereHas('assign.unit.apartemen', function($q) use ($request) {
                      $q->where('nama_apartemen', 'like', '%' . $request->search . '%');
                  });
            });
        }

        if ($request->filled('status')) {
            $requestsQuery->where('status', $request->status);
        }

        if ($request->filled('tanggal_mulai')) {
            $requestsQuery->whereDate('tanggal_pengajuan', '>=', $request->tanggal_mulai);
        }

        if ($request->filled('tanggal_selesai')) {
            $requestsQuery->whereDate('tanggal_pengajuan', '<=', $request->tanggal_selesai);
        }

        // ORDER BY: Data terbaru di atas (created_at DESC, kemudian id DESC)
        $requests = $requestsQuery->orderBy('created_at', 'desc')
                                  ->orderBy('id', 'desc')
                                  ->paginate(10);

        // Add computed properties
        $requests->getCollection()->transform(function($item) {
            $item->status_text = $this->getStatusText($item->status);
            $item->status_color = $this->getStatusColor($item->status);
            $item->jenis_text = $this->determineJenisText($item);
            return $item;
        });

        return view('apartemen.user.requests', compact('requests', 'activeCount'));
    }

    /**
     * Determine jenis text based on available data
     */
    private function determineJenisText($request)
    {
        // Jika sudah ada assign dan unit
        if ($request->assign && $request->assign->unit) {
            // Cek apakah ini perpanjangan dari assignment sebelumnya
            $previousAssignment = ApartemenAssign::whereHas('request', function($q) use ($request) {
                $q->where('user_id', $request->user_id)
                  ->where('id', '<', $request->id);
            })
            ->whereHas('unit', function($q) use ($request) {
                if ($request->assign->unit) {
                    $q->where('apartemen_id', $request->assign->unit->apartemen_id);
                }
            })
            ->whereIn('status', ['SELESAI', 'AKTIF'])
            ->first();

            if ($previousAssignment) {
                return 'Penempatan';
            }
            
            return 'Penempatan';
        }
        
        return 'Permintaan Baru';
    }

    /**
     * Helper method for status text
     */
    private function getStatusText($status)
    {
        return match($status) {
            'APPROVED' => 'Disetujui',
            'REJECTED' => 'Ditolak',
            'PENDING' => 'Tertunda',
            'AKTIF' => 'Aktif',
            'SELESAI' => 'Selesai',
            default => $status
        };
    }

    /**
     * Helper method for status color
     */
    private function getStatusColor($status)
    {
        return match($status) {
            'APPROVED', 'AKTIF' => 'approved',
            'REJECTED' => 'rejected',
            'PENDING' => 'pending',
            'SELESAI' => 'completed',
            default => 'gray'
        };
    }

    /**
     * Show the form for creating a new request
     */
    public function create()
    {
        return view('apartemen.user.create');
    }

    /**
     * Store a newly created request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'alasan' => 'required|string|max:500',
            'penghuni' => 'required|array|min:1|max:15',
            'penghuni.*.nama' => 'required|string|max:100',
            'penghuni.*.id_karyawan' => 'required|string|max:50',
            'penghuni.*.no_hp' => [
                'required',
                'string',
                'max:30',
                'regex:/^[0-9+\-\s]+$/',
            ],
            'penghuni.*.unit_kerja' => 'nullable|string|max:100',
            'penghuni.*.gol' => 'nullable|string|max:5',
            'penghuni.*.tanggal_mulai' => 'required|date',
            'penghuni.*.tanggal_selesai' => 'required|date|after:penghuni.*.tanggal_mulai'
        ], [
            'penghuni.*.no_hp.required' => 'Nomor HP wajib diisi untuk semua penghuni',
            'penghuni.*.no_hp.regex' => 'Nomor HP hanya boleh berisi angka, tanda plus (+), tanda minus (-), dan spasi',
            'penghuni.*.tanggal_selesai.after' => 'Tanggal selesai harus setelah tanggal mulai',
        ]);

        DB::beginTransaction();
        try {
            $requestData = ApartemenRequest::create([
                'user_id' => Auth::id(),
                'tanggal_pengajuan' => now(),
                'status' => 'PENDING',
                'alasan' => $validated['alasan'],
            ]);

            foreach ($validated['penghuni'] as $penghuniData) {
                // Format nomor HP: selalu tambahkan +62 di depan
                $no_hp = $this->formatPhoneNumberForDatabase($penghuniData['no_hp']);
                
                // Validasi nomor HP yang sudah diformat
                if (!$this->validatePhoneNumber($no_hp)) {
                    throw new \Exception('Nomor HP tidak valid untuk penghuni: ' . $penghuniData['nama']);
                }

                // Hitung jumlah hari
                $tanggal_mulai = \Carbon\Carbon::parse($penghuniData['tanggal_mulai']);
                $tanggal_selesai = \Carbon\Carbon::parse($penghuniData['tanggal_selesai']);
                $jumlah_hari = $tanggal_mulai->diffInDays($tanggal_selesai);

                $requestData->penghuni()->create([
                    'nama' => $penghuniData['nama'],
                    'id_karyawan' => $penghuniData['id_karyawan'],
                    'no_hp' => $no_hp, // Format: +6281234567890
                    'unit_kerja' => $penghuniData['unit_kerja'] ?? null,
                    'gol' => $penghuniData['gol'] ?? null,
                    'tanggal_mulai' => $penghuniData['tanggal_mulai'],
                    'tanggal_selesai' => $penghuniData['tanggal_selesai'],
                    'jumlah_hari' => $jumlah_hari,
                ]);
            }

            DB::commit();
            
            return redirect()->route('apartemen.user.requests')
                ->with('success', 'Pengajuan berhasil dikirim! Nomor HP telah disimpan dengan format +62.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal menyimpan pengajuan apartemen:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => Auth::id(),
            ]);
            
            return back()->withInput()
                ->with('error', 'Gagal menyimpan pengajuan: ' . $e->getMessage());
        }
    }

/**
 * Format nomor HP untuk database (selalu tambahkan +62)
 */
private function formatPhoneNumberForDatabase($phone)
{
    // Hapus semua karakter selain angka
    $clean_phone = preg_replace('/[^0-9]/', '', trim($phone));
    
    // Jika kosong, return as-is
    if (empty($clean_phone)) {
        return $phone;
    }
    
    // Hilangkan prefix 0 atau 62
    if (str_starts_with($clean_phone, '0')) {
        $clean_phone = substr($clean_phone, 1);
    } elseif (str_starts_with($clean_phone, '62')) {
        $clean_phone = substr($clean_phone, 2);
    }
    
    // Validasi panjang
    if (strlen($clean_phone) < 9 || strlen($clean_phone) > 13) {
        return $phone; // Return original if invalid
    }
    
    // Tambahkan +62 di depan
    return '+62' . $clean_phone;
}

/**
 * Validasi nomor HP
 */
private function validatePhoneNumber($phone)
{
    // Format harus +6281234567890
    if (!preg_match('/^\+62[0-9]{9,13}$/', $phone)) {
        return false;
    }
    
    // Ambil angka setelah +62
    $number = substr($phone, 3);
    
    // Validasi prefix (81-89)
    $prefix = substr($number, 0, 2);
    $validPrefixes = ['81', '82', '83', '84', '85', '86', '87', '88', '89'];
    
    return in_array($prefix, $validPrefixes);
}

    /**
     * Display the specified request
     */
    public function show($id)
    {
        $request = ApartemenRequest::with([
            'penghuni',
            'assign.unit.apartemen',
            'assign.penghuni'
        ])
        ->where('user_id', Auth::id())
        ->findOrFail($id);

        return view('apartemen.user.show', compact('request'));
    }

    /**
     * Display user dashboard
     */
    public function dashboard()
    {
        $userId = Auth::id();
        
        $pendingRequests = ApartemenRequest::where('user_id', $userId)
            ->where('status', 'PENDING')
            ->count();

        $activeAssignments = ApartemenAssign::whereHas('request', function($query) use ($userId) {
            $query->where('user_id', $userId);
        })->where('status', 'AKTIF')->count();

        $totalRequests = ApartemenRequest::where('user_id', $userId)->count();

        $recentRequests = ApartemenRequest::with('penghuni')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('apartemen.user.dashboard', compact(
            'pendingRequests',
            'activeAssignments',
            'totalRequests',
            'recentRequests'
        ));
    }

    /**
     * Get filtered data for AJAX requests
     */
    public function getFilteredData(Request $request)
    {
        $userId = Auth::id();
        
        $query = ApartemenRequest::where('user_id', $userId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tanggal_pengajuan', [
                $request->tanggal_mulai,
                $request->tanggal_selesai
            ]);
        }

        $data = $query->orderBy('created_at', 'desc')
                      ->orderBy('id', 'desc')
                      ->paginate($request->per_page ?? 10);

        return response()->json([
            'data' => $data,
            'total' => $data->total(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage()
        ]);
    }

    /**
     * Alternative: Simple method untuk requests jika ada error
     */
    public function requestsSimple(Request $request)
    {
        $userId = Auth::id();
        
        // Get active assignments count for badge
        $activeCount = ApartemenAssign::whereHas('request', function($query) use ($userId) {
            $query->where('user_id', $userId);
        })->where('status', 'AKTIF')->count();

        // Simple query tanpa filter kompleks
        $requests = ApartemenRequest::with(['penghuni'])
            ->where('user_id', $userId)
            ->orderBy('id', 'desc') // Pastikan ID terbaru di atas
            ->paginate(10);

        // Simple computed properties
        $requests->getCollection()->transform(function($item) {
            $item->status_text = $this->getStatusText($item->status);
            $item->status_color = $this->getStatusColor($item->status);
            $item->jenis_text = 'Permintaan Baru'; // Default
            return $item;
        });

        return view('apartemen.user.requests', compact('requests', 'activeCount'));
    }
}