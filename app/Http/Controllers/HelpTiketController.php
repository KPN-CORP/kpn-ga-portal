<?php
namespace App\Http\Controllers;

use App\Models\HelpTiket;
use App\Models\HelpKategori;
use App\Models\HelpLampiran;
use App\Models\HelpKomentar;
use App\Models\HelpLogStatus;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class HelpTiketController extends Controller
{
    // ================ VIEW METHODS ================
    
    public function index()
    {
        $user = Auth::user();
        
        // Pastikan user punya pelanggan
        if (!$user->pelanggan) {
            return back()->withErrors(['error' => 'Data pelanggan belum terhubung. Silakan hubungi administrator.']);
        }
        
        // SEMUA USER HANYA MELIHAT TIKET MILIKNYA SENDIRI
        $query = HelpTiket::where('pelapor_id', $user->id);
        
        // Apply filters
        if ($search = request('search')) {
            $query->where(function($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                ->orWhere('nomor_tiket', 'like', "%{$search}%")
                ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }
        
        if ($status = request('status')) {
            $query->where('status', $status);
        }
        
        if ($prioritas = request('prioritas')) {
            $query->where('prioritas', $prioritas);
        }
        
        // Apply date filter if exists
        if ($date = request('date')) {
            $query->whereDate('created_at', $date);
        }
        
        // Paginate results with relations
        $tiket = $query->with(['kategori'])
                    ->latest()
                    ->paginate(20)
                    ->withQueryString();
        
        return view('help.tiket.index', compact('tiket'));
    }
    
    public function create()
    {
        $kategori = HelpKategori::where('aktif', true)->get();
        return view('help.tiket.create', compact('kategori'));
    }
    
    public function show(HelpTiket $tiket)
    {
        $user = Auth::user();
        
        // Pastikan user punya pelanggan
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
        // Authorization check - hanya pelapor yang bisa melihat
        if ($tiket->pelapor_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses ke tiket ini.');
        }
        
        // Load relasi dengan eager loading
        $tiket->load([
            'kategori',
            'komentar' => function($query) {
                $query->with(['pengguna.user'])->orderBy('created_at', 'asc');
            },
            'lampiran',
            'logStatus' => function($query) {
                $query->with(['pengguna.user'])->orderBy('created_at', 'asc');
            },
            'pelapor',      // Relasi pelapor ke User
            'ditugaskanKe'  // Relasi penanggung jawab ke User
        ]);
        
        return view('help.tiket.show', compact('tiket'));
    }
    
    // ================ ACTION METHODS ================
    
public function store(Request $request)
{
    \Log::info('Store method called', ['user_id' => Auth::id()]);
    
    $validated = $request->validate([
        'judul' => 'required|string|max:255',
        'deskripsi' => 'required|string',
        'kategori_id' => 'required|exists:db_help_kategori,id',
        'prioritas' => 'required|in:LOW,MEDIUM,HIGH,URGENT',
        'lampiran.*' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx'
    ]);
    
    \Log::info('Validation passed', $validated);

    try {
        $user = Auth::user();
        
        DB::beginTransaction();
        
        // GENERATE NOMOR TIKET
        $nomorTiket = $this->generateNomorTiket();
        \Log::info('Generated ticket number', ['nomor_tiket' => $nomorTiket]);
        
        // BUAT TIKET
        $tiketData = [
            'nomor_tiket' => $nomorTiket,
            'judul' => $validated['judul'],
            'deskripsi' => $validated['deskripsi'],
            'kategori_id' => $validated['kategori_id'],
            'pelapor_id' => $user->id,
            'prioritas' => $validated['prioritas'],
            'status' => 'OPEN',
            'ditugaskan_ke' => null
        ];
        
        \Log::info('Creating ticket with data', $tiketData);
        
        $tiket = HelpTiket::create($tiketData);
        
        \Log::info('Ticket created', ['tiket_id' => $tiket->id]);
        
        // Handle lampiran
        $penggunaId = null;
        if ($user->pelanggan) {
            $penggunaId = $user->pelanggan->id_pelanggan;
            \Log::info('User has pelanggan', ['pengguna_id' => $penggunaId]);
        } else {
            // Fallback: gunakan user ID sebagai pengguna_id
            $penggunaId = $user->id;
            \Log::warning('User has no pelanggan, using user_id as fallback', ['pengguna_id' => $penggunaId]);
        }
        
        // Handle file uploads
        if ($request->hasFile('lampiran')) {
            \Log::info('Processing attachments', ['count' => count($request->file('lampiran'))]);
            
            foreach ($request->file('lampiran') as $index => $file) {
                try {
                    $this->saveLampiranFile($tiket, $file, 'INITIAL', $penggunaId);
                    \Log::info('Attachment saved', ['index' => $index, 'file' => $file->getClientOriginalName()]);
                } catch (\Exception $e) {
                    \Log::error('Failed to save attachment', [
                        'index' => $index,
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        // Add system comment
        if ($penggunaId) {
            HelpKomentar::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $penggunaId,
                'komentar' => 'Tiket berhasil dibuat',
                'pesan_sistem' => true,
                'tipe_pesan_sistem' => 'TICKET_CREATED'
            ]);
        }
        
        DB::commit();
        
        \Log::info('Ticket creation successful', [
            'tiket_id' => $tiket->id,
            'nomor_tiket' => $tiket->nomor_tiket
        ]);
        
        return redirect()->route('help.tiket.show', $tiket)
            ->with('success', 'Tiket berhasil dibuat! Nomor: ' . $tiket->nomor_tiket);
            
    } catch (\Exception $e) {
        DB::rollBack();
        
        \Log::error('Ticket creation failed', [
            'user_id' => Auth::id(),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return back()
            ->withInput()
            ->withErrors(['error' => 'Gagal membuat tiket: ' . $e->getMessage()]);
    }
}
    
    public function addKomentar(Request $request, HelpTiket $tiket)
    {
        $user = Auth::user();
        
        // Pastikan user punya pelanggan
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
        // Authorization check
        if ($tiket->status === 'CLOSED') {
            abort(403, 'Tiket yang sudah ditutup tidak dapat dikomentari.');
        }
        
        // Hanya pelapor yang bisa mengomentari
        if ($tiket->pelapor_id !== $user->id) {
            abort(403, 'Anda tidak memiliki akses untuk mengomentari tiket ini.');
        }
        
        $validated = $request->validate([
            'komentar' => 'required|string',
            'lampiran.*' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx'
        ]);
        
        try {
            DB::beginTransaction();
            
            $komentar = HelpKomentar::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'komentar' => $validated['komentar']
            ]);
            
            // Handle lampiran - SIMPAN DI STORAGE PRIVATE
            if ($request->hasFile('lampiran')) {
                foreach ($request->file('lampiran') as $file) {
                    $this->saveLampiranFile($tiket, $file, 'FOLLOW_UP', $user->pelanggan->id_pelanggan);
                }
            }
            
            // If tiket was in WAITING and user is pelapor, change status back to ON_PROCESS
            if ($tiket->status === 'WAITING') {
                $tiket->update([
                    'status' => 'ON_PROCESS',
                    'menunggu_pada' => null
                ]);
                
                HelpLogStatus::create([
                    'tiket_id' => $tiket->id,
                    'pengguna_id' => $user->pelanggan->id_pelanggan,
                    'status_lama' => 'WAITING',
                    'status_baru' => 'ON_PROCESS',
                    'catatan' => 'Pelapor memberikan respons'
                ]);
                
                HelpKomentar::create([
                    'tiket_id' => $tiket->id,
                    'pengguna_id' => $user->pelanggan->id_pelanggan,
                    'komentar' => 'Status otomatis berubah menjadi ON_PROCESS karena pelapor memberikan respons',
                    'pesan_sistem' => true,
                    'tipe_pesan_sistem' => 'STATUS_AUTO_CHANGED'
                ]);
            }
            
            DB::commit();
            
            return back()->with('success', 'Komentar berhasil ditambahkan!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal menambahkan komentar: ' . $e->getMessage()]);
        }
    }
    
// app/Http/Controllers/HelpTiketController.php

    public function downloadLampiran(HelpLampiran $lampiran)
    {
        $user = Auth::user();
        
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
        $tiket = $lampiran->tiket;
        
        // ============================================
        // LOGIKA SUPER SIMPEL:
        // 1. PELAPOR tiket ini -> BOLEH
        // 2. User dengan ga_help_proses = 1 -> BOLEH (STAFF/GA)
        // ============================================
        
        // 1. Cek apakah user adalah PELAPOR tiket ini
        $isPelapor = $tiket->pelapor_id == $user->pelanggan->id_pelanggan;
        
        // 2. Cek apakah user punya akses ga_help_proses = 1
        $isStaff = DB::table('tb_access_menu')
                    ->where('username', $user->username)
                    ->where('ga_help_proses', 1)
                    ->exists();
        
        // Jika bukan pelapor DAN bukan staff, TOLAK
        if (!$isPelapor && !$isStaff) {
            \Log::warning('Access denied for lampiran download', [
                'user_id' => $user->id,
                'username' => $user->username,
                'lampiran_id' => $lampiran->id,
                'tiket_id' => $tiket->id,
                'is_pelapor' => $isPelapor,
                'is_staff' => $isStaff
            ]);
            abort(403, 'Anda tidak memiliki akses ke file ini.');
        }
        
        // Lanjutkan proses download
        $path = str_replace('private/', '', $lampiran->path_file);
        
        if (!Storage::disk('private')->exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }
        
        \Log::info('Lampiran downloaded', [
            'user_id' => $user->id,
            'lampiran_id' => $lampiran->id,
            'file_name' => $lampiran->nama_file,
            'access_type' => $isPelapor ? 'pelapor' : 'staff'
        ]);
        
        return Storage::disk('private')->download($path, $lampiran->nama_file);
    }

    public function previewLampiran(HelpLampiran $lampiran)
    {
        $user = Auth::user();
        
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
        $tiket = $lampiran->tiket;
        
        // STRICT AUTHORIZATION: Hanya pelapor atau yang mengupload yang bisa akses
        $isPelapor = $tiket->pelapor_id === $user->pelanggan->id_pelanggan;
        $isUploader = $lampiran->pengguna_id === $user->pelanggan->id_pelanggan;
        
        if (!$isPelapor && !$isUploader) {
            abort(403, 'Anda tidak memiliki akses ke file ini.');
        }
        
        // Cek status tiket - jika CLOSED, hanya pelapor yang bisa preview
        if ($tiket->status === 'CLOSED' && !$isPelapor) {
            abort(403, 'Tiket sudah ditutup, hanya pelapor yang bisa melihat file.');
        }
        
        // Hapus prefix 'private/' jika ada
        $path = str_replace('private/', '', $lampiran->path_file);
        
        if (!Storage::disk('private')->exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }
        
        // Cek apakah file adalah gambar
        if (!str_contains($lampiran->tipe_file, 'image')) {
            return $this->downloadLampiran($lampiran);
        }
        
        // Gunakan stream untuk response file dari private disk
        $file = Storage::disk('private')->get($path);
        $type = $lampiran->tipe_file;
        
        // Tambahkan header security
        $headers = [
            'Content-Type' => $type,
            'Content-Disposition' => 'inline; filename="' . $lampiran->nama_file . '"',
            'Content-Length' => strlen($file),
            'Cache-Control' => 'private, max-age=3600', // Cache private
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY', // Prevent iframe embedding
        ];
        
        // Tambahkan log untuk audit
        \Log::info('File previewed', [
            'user_id' => $user->id,
            'file_id' => $lampiran->id,
            'file_name' => $lampiran->nama_file,
            'tiket_id' => $tiket->id,
            'ip' => request()->ip()
        ]);
        
        return response($file, 200, $headers);
    }
    
    public function logSistem()
    {
        // Hanya untuk pelapor yang sudah login
        if (!Auth::check()) {
            abort(403, 'Anda harus login untuk melihat log sistem.');
        }
        
        // Untuk sementara, return view kosong
        return view('help.log.index', ['log' => collect()]);
    }
    
    // ================ HELPER METHODS ================
    
private function saveLampiranFile($tiket, $file, $tipe, $penggunaId)
{
    try {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $safeName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $safeName);
        $fileName = time() . '_' . uniqid() . '_' . $safeName . '.' . $extension;
        
        // Pastikan directory ada
        $directory = 'help/tiket/' . $tiket->id;
        if (!Storage::disk('private')->exists($directory)) {
            Storage::disk('private')->makeDirectory($directory);
        }
        
        // Path penyimpanan
        $path = $directory . '/' . $fileName;
        
        // Simpan file
        Storage::disk('private')->put($path, file_get_contents($file));
        
        // Simpan ke database
        HelpLampiran::create([
            'tiket_id' => $tiket->id,
            'pengguna_id' => $penggunaId,
            'path_file' => $path,
            'nama_file' => $originalName,
            'tipe_file' => $file->getMimeType(),
            'ukuran_file' => $file->getSize(),
            'tipe' => $tipe
        ]);
        
        \Log::info('Attachment saved successfully', [
            'tiket_id' => $tiket->id,
            'file_name' => $originalName,
            'path' => $path
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Failed to save attachment file', [
            'tiket_id' => $tiket->id,
            'file_name' => $file->getClientOriginalName(),
            'error' => $e->getMessage()
        ]);
        throw $e; // Re-throw agar bisa ditangkap di method store
    }
}
    
private function generateNomorTiket()
{
    $prefix = 'GA';
    $year = date('Y');
    $month = date('m');
    
    // LOG 1: Debug
    \Log::info('=== GENERATE TICKET NUMBER START ===');
    
    // AMBIL SEMUA NOMOR YANG SUDAH ADA (termasuk soft deleted)
    $existingNumbers = HelpTiket::withTrashed()
        ->where('nomor_tiket', 'like', $prefix . '-' . $year . $month . '-%')
        ->pluck('nomor_tiket')
        ->toArray();
    
    \Log::info('Existing ticket numbers this month:', $existingNumbers);
    
    // CARI SEQUENCE TERTINGGI YANG SUDAH DIGUNAKAN
    $maxSequence = 0;
    foreach ($existingNumbers as $number) {
        // Extract 4 digit terakhir
        if (preg_match('/-(\d{4})$/', $number, $matches)) {
            $seq = intval($matches[1]);
            if ($seq > $maxSequence) {
                $maxSequence = $seq;
            }
        }
    }
    
    \Log::info('Max sequence found: ' . $maxSequence);
    
    // GENERATE NOMOR BARU (sequence + 1)
    $newSequence = $maxSequence + 1;
    $newNumber = $prefix . '-' . $year . $month . '-' . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    
    // DOUBLE CHECK: Pastikan benar-benar belum ada
    $attempt = 1;
    while (in_array($newNumber, $existingNumbers)) {
        \Log::warning('Duplicate detected! ' . $newNumber . ' already exists. Trying next...');
        $newSequence++;
        $newNumber = $prefix . '-' . $year . $month . '-' . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
        $attempt++;
        
        // Safety break
        if ($attempt > 100) {
            \Log::error('Too many duplicates, using timestamp fallback');
            $newNumber = $prefix . '-' . $year . $month . '-' . time();
            break;
        }
    }
    
    \Log::info('Final generated number: ' . $newNumber);
    \Log::info('=== GENERATE TICKET NUMBER END ===');
    
    return $newNumber;
}
}