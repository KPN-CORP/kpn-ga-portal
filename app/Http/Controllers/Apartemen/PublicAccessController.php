<?php
// app/Http/Controllers/Apartemen/PublicAccessController.php

namespace App\Http\Controllers\Apartemen;

use App\Http\Controllers\Controller;
use App\Models\Apartemen\ApartemenAccessCode;
use App\Models\Apartemen\ApartemenPenghuni;
use App\Models\Apartemen\ApartemenAssign;
use App\Models\Apartemen\ApartemenUnit;
use App\Models\Apartemen\ApartemenHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PublicAccessController extends Controller
{
    /**
     * Halaman input kode akses
     */
    public function index()
    {
        return view('apartemen.public.index');
    }

    /**
     * Verifikasi kode akses
     */
    public function verify(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'kode_akses' => 'required|string|max:10'
        ]);

        if ($validator->fails()) {
            return back()->with('error', 'Kode akses harus diisi (maksimal 10 karakter).');
        }

        try {
            $kodeAkses = strtoupper(trim($request->kode_akses));

            $accessCode = ApartemenAccessCode::where('kode_akses', $kodeAkses)
                ->where('is_active', true)
                ->first();

            if (!$accessCode || !$accessCode->isValid()) {
                Log::warning('Invalid access code attempt', [
                    'code' => $kodeAkses,
                    'ip' => $request->ip()
                ]);
                return back()->with('error', 'Kode akses tidak valid atau sudah kadaluarsa!');
            }

            // Simpan data ke session
            session([
                'access_code' => encrypt($accessCode->kode_akses),
                'access_code_id' => encrypt($accessCode->id),
                'access_code_data' => $accessCode,
                'access_time' => now()->timestamp
            ]);

            session()->save();

            Log::info('Access code verified successfully', [
                'code' => $accessCode->kode_akses,
                'ip' => $request->ip()
            ]);

            return redirect()->route('apartemen.public.search');

        } catch (\Exception $e) {
            Log::error('Error verifying access code: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan sistem. Silakan coba lagi.');
        }
    }

    /**
     * Halaman pencarian penghuni
     */
    public function search(Request $request)
    {
        if (!$this->validateSession($request)) {
            return redirect()->route('apartemen.public.index')
                ->with('error', 'Sesi tidak valid. Silakan masukkan kode akses lagi.');
        }

        return view('apartemen.public.search');
    }

    /**
     * Proses pencarian penghuni (HANYA TAMPILKAN YANG AKTIF)
     */
    public function find(Request $request)
    {
        if (!$this->validateSession($request)) {
            return redirect()->route('apartemen.public.index')
                ->with('error', 'Sesi tidak valid.');
        }

        $validator = Validator::make($request->all(), [
            'search' => 'required|string|min:3|max:30'
        ]);

        if ($validator->fails()) {
            return back()->with('error', 'Pencarian minimal 3 karakter.');
        }

        try {
            $search = trim($request->search);

            // AMBIL DATA LENGKAP DENGAN SELECT SPESIFIK
            $penghuni = ApartemenPenghuni::with(['assign.unit.apartemen'])
                ->where('status', 'AKTIF')
                ->where(function($q) use ($search) {
                    $q->where('nama', 'LIKE', '%' . $search . '%')
                      ->orWhere('id_karyawan', 'LIKE', '%' . $search . '%');
                })
                ->select([
                    'id', 
                    'assign_id', 
                    'nama', 
                    'id_karyawan', 
                    'no_hp',
                    'unit_kerja',
                    'gol',
                    'status',
                    'tanggal_mulai',
                    'tanggal_selesai'
                ])
                ->orderBy('nama')
                ->limit(20)
                ->get();

            return view('apartemen.public.search-result', compact('penghuni', 'search'));

        } catch (\Exception $e) {
            Log::error('Search error: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan saat mencari data.');
        }
    }

    /**
     * Proses CHECK-IN mandiri (dengan penambahan history)
     */
    public function checkin(Request $request, $id)
    {
        // Validasi session
        if (!$this->validateSession($request)) {
            Log::error('Checkin failed: Session invalid');
            return redirect()->route('apartemen.public.index')
                ->with('error', 'Sesi tidak valid. Silakan login ulang.');
        }

        $accessCode = session('access_code_data');
        if (!$accessCode) {
            Log::error('Checkin failed: access_code_data not found in session');
            return redirect()->route('apartemen.public.index')
                ->with('error', 'Sesi tidak valid.');
        }

        // Cek tipe akses
        if (!in_array($accessCode->tipe, ['CHECKIN', 'BOTH'])) {
            Log::error('Checkin failed: Invalid access type', ['tipe' => $accessCode->tipe]);
            return redirect()->route('apartemen.public.search')
                ->with('error', 'Kode akses ini tidak memiliki izin untuk check-in!');
        }

        Log::info('Checkin process started', [
            'id' => $id,
            'ip' => $request->ip(),
            'access_code' => $accessCode->kode_akses
        ]);

        DB::beginTransaction();
        try {
            // Ambil data penghuni AKTIF
            $penghuni = ApartemenPenghuni::with('assign.unit.apartemen')
                ->where('id', $id)
                ->where('status', 'AKTIF')
                ->lockForUpdate()
                ->first();

            if (!$penghuni) {
                Log::error('Penghuni not found or not active', ['id' => $id]);
                return back()->with('error', 'Penghuni tidak ditemukan atau sudah tidak aktif.');
            }

            Log::info('Penghuni found', [
                'nama' => $penghuni->nama,
                'status' => $penghuni->status,
                'assign_id' => $penghuni->assign_id
            ]);

            $assign = $penghuni->assign;
            if (!$assign) {
                Log::error('Assignment not found', ['penghuni_id' => $penghuni->id]);
                throw new \Exception('Data assignment tidak ditemukan.');
            }

            // Cek apakah sudah check-in
            if ($assign->checkin_at) {
                Log::warning('Already checked in', ['checkin_at' => $assign->checkin_at]);
                throw new \Exception('Anda sudah melakukan check-in pada ' . $assign->checkin_at->format('d/m/Y H:i'));
            }

            // Cek tanggal mulai
            if ($assign->tanggal_mulai > now()) {
                Log::warning('Too early to check in', ['tanggal_mulai' => $assign->tanggal_mulai]);
                throw new \Exception('Belum waktunya check-in. Tanggal mulai: ' . $assign->tanggal_mulai->format('d/m/Y'));
            }

            // Update checkin_at
            $assign->update([
                'checkin_at' => now()
            ]);

            Log::info('Checkin successful', [
                'penghuni' => $penghuni->nama,
                'checkin_at' => $assign->fresh()->checkin_at
            ]);

            // === TAMBAHKAN HISTORY CHECK-IN ===
            $history = ApartemenHistory::create([
                'id_karyawan'    => $penghuni->id_karyawan,
                'nama'           => $penghuni->nama,
                'no_hp'          => $penghuni->no_hp ?? '-',
                'unit_kerja'     => $penghuni->unit_kerja ?? '-',
                'gol'            => $penghuni->gol ?? '-',
                'apartemen'      => $assign->unit->apartemen->nama_apartemen ?? '-',
                'unit'           => $assign->unit->nomor_unit ?? '-',
                'periode'        => $assign->tanggal_mulai->format('d/m/Y') . ' - ' . $assign->tanggal_selesai->format('d/m/Y'),
                'status_selesai' => 'CHECKIN',
                'created_at'     => now(),
            ]);

            Log::info('Public check-in history created', ['history_id' => $history->id]);

            // Update jumlah penggunaan kode akses
            $accessCode->incrementUsed();
            Log::info('Access code usage incremented');

            DB::commit();

            return redirect()->route('apartemen.public.success', [
                'action' => 'checkin',
                'nama' => $penghuni->nama
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('=== CHECKIN ERROR ===');
            Log::error('Error: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            return back()->with('error', 'Gagal check-in: ' . $e->getMessage());
        }
    }

    /**
     * Proses CHECK-OUT mandiri
     */
    public function checkout(Request $request, $id)
    {
        // Validasi session
        if (!$this->validateSession($request)) {
            Log::error('Checkout failed: Session invalid');
            return redirect()->route('apartemen.public.index')
                ->with('error', 'Sesi tidak valid. Silakan login ulang.');
        }

        $accessCode = session('access_code_data');
        if (!$accessCode) {
            Log::error('Checkout failed: access_code_data not found in session');
            return redirect()->route('apartemen.public.index')
                ->with('error', 'Sesi tidak valid.');
        }

        // Cek tipe akses
        if (!in_array($accessCode->tipe, ['CHECKOUT', 'BOTH'])) {
            Log::error('Checkout failed: Invalid access type', ['tipe' => $accessCode->tipe]);
            return redirect()->route('apartemen.public.search')
                ->with('error', 'Kode akses ini tidak memiliki izin untuk check-out!');
        }

        Log::info('Checkout process started', [
            'id' => $id,
            'ip' => $request->ip(),
            'access_code' => $accessCode->kode_akses
        ]);

        DB::beginTransaction();
        try {
            // Ambil data penghuni AKTIF dengan LOCK
            $penghuni = ApartemenPenghuni::with('assign.unit.apartemen')
                ->where('id', $id)
                ->where('status', 'AKTIF')
                ->lockForUpdate()
                ->first();

            if (!$penghuni) {
                Log::error('Penghuni not found or not active', ['id' => $id]);
                return back()->with('error', 'Penghuni tidak ditemukan atau sudah check-out.');
            }

            Log::info('Penghuni found', [
                'nama' => $penghuni->nama,
                'status' => $penghuni->status,
                'assign_id' => $penghuni->assign_id,
                'no_hp' => $penghuni->no_hp,
                'unit_kerja' => $penghuni->unit_kerja,
                'gol' => $penghuni->gol
            ]);

            $assign = $penghuni->assign;
            if (!$assign) {
                Log::error('Assignment not found', ['penghuni_id' => $penghuni->id]);
                throw new \Exception('Data assignment tidak ditemukan.');
            }

            // Cek duplikat (dalam 1 menit)
            $recentHistory = ApartemenHistory::where('id_karyawan', $penghuni->id_karyawan)
                ->where('status_selesai', 'SELESAI')
                ->where('created_at', '>=', now()->subMinute())
                ->exists();

            if ($recentHistory) {
                Log::warning('Duplicate checkout detected', ['id_karyawan' => $penghuni->id_karyawan]);
                throw new \Exception('Check-out sudah diproses dalam 1 menit terakhir.');
            }

            // Hitung penghuni aktif SEBELUM checkout
            $activeBefore = ApartemenPenghuni::where('assign_id', $assign->id)
                ->where('status', 'AKTIF')
                ->count();

            Log::info('Active before checkout', ['count' => $activeBefore]);

            // UPDATE STATUS PENGHUNI MENJADI SELESAI
            $update = $penghuni->update(['status' => 'SELESAI']);
            Log::info('Penghuni status updated', ['success' => $update]);

            // Hitung penghuni aktif SETELAH checkout
            $activeAfter = ApartemenPenghuni::where('assign_id', $assign->id)
                ->where('status', 'AKTIF')
                ->count();

            Log::info('Active after checkout', ['count' => $activeAfter]);

            // Jika tidak ada penghuni aktif lagi, update assign dan unit
            if ($activeAfter == 0) {
                Log::info('Last penghuni, updating assign and unit to READY');
                
                $assignUpdate = $assign->update(['status' => 'SELESAI']);
                Log::info('Assign updated', ['success' => $assignUpdate]);
                
                if ($assign->unit) {
                    $unitUpdate = $assign->unit->update(['status' => 'READY']);
                    Log::info('Unit updated to READY', [
                        'unit' => $assign->unit->nomor_unit,
                        'success' => $unitUpdate
                    ]);
                }
            }

            // FORMAT PERIODE DENGAN LENGKAP
            $periode = '';
            if ($assign && $assign->tanggal_mulai && $assign->tanggal_selesai) {
                $periode = $assign->tanggal_mulai->format('d/m/Y') . ' - ' . $assign->tanggal_selesai->format('d/m/Y');
            }

            // Catat HISTORY dengan DATA LENGKAP
            $historyData = [
                'id_karyawan' => $penghuni->id_karyawan,
                'nama' => $penghuni->nama,
                'no_hp' => $penghuni->no_hp ?? '-',
                'unit_kerja' => $penghuni->unit_kerja ?? '-',
                'gol' => $penghuni->gol ?? '-',
                'apartemen' => $assign->unit->apartemen->nama_apartemen ?? '-',
                'unit' => $assign->unit->nomor_unit ?? '-',
                'periode' => $periode,
                'status_selesai' => 'SELESAI',
                'created_at' => now()
            ];

            Log::info('Mencoba insert history dengan data lengkap:', $historyData);

            $history = ApartemenHistory::create($historyData);

            Log::info('History created', [
                'history_id' => $history->id,
                'no_hp' => $history->no_hp,
                'periode' => $history->periode
            ]);

            // Update jumlah penggunaan kode akses
            $accessCode->incrementUsed();
            Log::info('Access code usage incremented');

            DB::commit();

            Log::info('=== CHECKOUT SUCCESS ===', [
                'penghuni' => $penghuni->nama,
                'unit' => $assign->unit->nomor_unit ?? '-',
                'no_hp' => $penghuni->no_hp
            ]);

            return redirect()->route('apartemen.public.success', [
                'action' => 'checkout',
                'nama' => $penghuni->nama
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('=== CHECKOUT ERROR ===');
            Log::error('Error: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            return back()->with('error', 'Gagal check-out: ' . $e->getMessage());
        }
    }

    /**
     * Halaman sukses setelah check-in/out
     */
    public function success(Request $request)
    {
        if (!session('access_code')) {
            return redirect()->route('apartemen.public.index');
        }

        return view('apartemen.public.success', [
            'action' => $request->action,
            'nama' => $request->nama
        ]);
    }

    /**
     * Logout dari public access
     */
    public function logout()
    {
        session()->forget(['access_code', 'access_code_id', 'access_code_data', 'access_time']);
        return redirect()->route('apartemen.public.index')
            ->with('success', 'Anda telah keluar dari sistem.');
    }

    /**
     * Validasi session
     */
    private function validateSession(Request $request)
    {
        if (!session('access_code') || !session('access_code_id')) {
            return false;
        }

        try {
            $decrypted = decrypt(session('access_code'));
            $decryptedId = decrypt(session('access_code_id'));

            $accessCode = ApartemenAccessCode::find($decryptedId);

            if (!$accessCode || $accessCode->kode_akses !== $decrypted) {
                return false;
            }

            // Set access_code_data jika belum ada
            if (!session('access_code_data') && $accessCode) {
                session(['access_code_data' => $accessCode]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Session validation error: ' . $e->getMessage());
            return false;
        }
    }
}