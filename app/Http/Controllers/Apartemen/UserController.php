<?php

namespace App\Http\Controllers\Apartemen;

use App\Http\Controllers\Controller;
use App\Models\Apartemen\ApartemenRequest;
use App\Models\Apartemen\ApartemenRequestPenghuni;
use App\Models\Apartemen\ApartemenAssign;
use App\Models\Apartemen\ApartemenUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UserController extends Controller
{
    /**
     * Display user's active status
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        
        $activeAssignments = ApartemenAssign::with([
            'unit.apartemen', 
            'penghuni'
        ])
        ->whereHas('request', function($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->where('status', 'AKTIF')
        ->get();

        $requestCount = ApartemenRequest::where('user_id', $userId)->count();

        return view('apartemen.user.index', compact('activeAssignments', 'requestCount'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        // Ambil semua unit yang READY
        $availableUnits = ApartemenUnit::with('apartemen')
            ->where('status', 'READY')
            ->orderBy('apartemen_id')
            ->orderBy('nomor_unit')
            ->get()
            ->groupBy(function($unit) {
                return $unit->apartemen->nama_apartemen;
            });

        return view('apartemen.user.create', compact('availableUnits'));
    }

    /**
     * Store new request
     */
    public function store(Request $request)
    {
        // Validasi dasar
        $validated = $request->validate([
            'unit_id' => 'required|exists:tb_apartemen_unit,id',
            'alasan' => 'required|string|min:10|max:500',
            'penghuni' => 'required|array|min:1|max:5',
            'penghuni.*.nama' => 'required|string|max:100',
            'penghuni.*.id_karyawan' => 'required|string|max:50',
            'penghuni.*.no_hp' => 'required|string|max:20',
            'penghuni.*.unit_kerja' => 'nullable|string|max:100',
            'penghuni.*.gol' => 'nullable|string|max:5',
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'required|date|after:tanggal_mulai',
        ], [
            'tanggal_mulai.after_or_equal' => 'Tanggal mulai tidak boleh sebelum hari ini',
            'tanggal_selesai.after' => 'Tanggal selesai harus setelah tanggal mulai',
        ]);

        // CEK BENTROK TANGGAL
        $unit = ApartemenUnit::find($validated['unit_id']);
        
        $bentrok = ApartemenAssign::where('unit_id', $validated['unit_id'])
            ->where('status', 'AKTIF')
            ->where(function($q) use ($validated) {
                $q->whereBetween('tanggal_mulai', [$validated['tanggal_mulai'], $validated['tanggal_selesai']])
                  ->orWhereBetween('tanggal_selesai', [$validated['tanggal_mulai'], $validated['tanggal_selesai']])
                  ->orWhere(function($query) use ($validated) {
                      $query->where('tanggal_mulai', '<=', $validated['tanggal_mulai'])
                            ->where('tanggal_selesai', '>=', $validated['tanggal_selesai']);
                  });
            })
            ->exists();

        if ($bentrok) {
            return back()->withInput()
                ->with('error', "Unit {$unit->nomor_unit} sudah ditempati pada periode " . 
                       Carbon::parse($validated['tanggal_mulai'])->format('d/m/Y') . ' - ' . 
                       Carbon::parse($validated['tanggal_selesai'])->format('d/m/Y'));
        }

        // CEK KAPASITAS
        $jumlahPenghuni = count($validated['penghuni']);
        if ($unit->kapasitas < $jumlahPenghuni) {
            return back()->withInput()
                ->with('error', "Kapasitas unit tidak mencukupi. Maksimal {$unit->kapasitas} orang.");
        }

        DB::beginTransaction();
        try {
            // Buat request
            $apartemenRequest = ApartemenRequest::create([
                'user_id' => Auth::id(),
                'tanggal_pengajuan' => now(),
                'status' => 'PENDING',
                'alasan' => $validated['alasan'],
            ]);

            // Simpan data unit yang dipilih (sebagai catatan, bukan assignment)
            // Kita simpan di session atau tabel temporary? Lebih baik simpan di request_penghuni dengan unit_id
            // TAPI tabel request_penghuni tidak punya unit_id. Jadi kita simpan di notes?

            foreach ($validated['penghuni'] as $penghuniData) {
                // Format nomor HP
                $no_hp = $this->formatPhoneNumber($penghuniData['no_hp']);
                
                // Hitung jumlah hari
                $tanggalMulai = Carbon::parse($validated['tanggal_mulai']);
                $tanggalSelesai = Carbon::parse($validated['tanggal_selesai']);
                $jumlahHari = $tanggalMulai->diffInDays($tanggalSelesai) + 1;

                $apartemenRequest->penghuni()->create([
                    'nama' => $penghuniData['nama'],
                    'id_karyawan' => $penghuniData['id_karyawan'],
                    'no_hp' => $no_hp,
                    'unit_kerja' => $penghuniData['unit_kerja'] ?? null,
                    'gol' => $penghuniData['gol'] ?? null,
                    'tanggal_mulai' => $validated['tanggal_mulai'],
                    'tanggal_selesai' => $validated['tanggal_selesai'],
                    'jumlah_hari' => $jumlahHari,
                ]);
            }

            // Simpan unit_id yang dipilih di session untuk digunakan di approve
            session(['last_selected_unit' => $validated['unit_id']]);

            DB::commit();
            
            return redirect()->route('apartemen.user.requests')
                ->with('success', 'Pengajuan berhasil dikirim! Menunggu persetujuan admin.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error store request: ' . $e->getMessage());
            
            return back()->withInput()
                ->with('error', 'Gagal mengirim pengajuan: ' . $e->getMessage());
        }
    }

    /**
     * Format phone number
     */
    private function formatPhoneNumber($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (substr($phone, 0, 1) == '0') {
            $phone = '62' . substr($phone, 1);
        } elseif (substr($phone, 0, 2) != '62') {
            $phone = '62' . $phone;
        }
        
        return '+' . $phone;
    }

    /**
     * Show request detail
     */
    public function show($id)
    {
        $request = ApartemenRequest::with([
            'user',
            'penghuni',
            'assign.unit.apartemen',
            'assign.penghuni'
        ])
        ->where('user_id', Auth::id())
        ->findOrFail($id);

        return view('apartemen.user.show', compact('request'));
    }

    /**
     * User requests history
     */
    public function requests(Request $request)
    {
        $userId = Auth::id();
        
        $activeCount = ApartemenAssign::whereHas('request', fn($q) => $q->where('user_id', $userId))
            ->where('status', 'AKTIF')
            ->count();

        $requests = ApartemenRequest::with(['penghuni', 'assign.unit.apartemen'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('apartemen.user.requests', compact('requests', 'activeCount'));
    }
}