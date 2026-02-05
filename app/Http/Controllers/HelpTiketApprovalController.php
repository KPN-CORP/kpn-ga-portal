<?php

namespace App\Http\Controllers;

use App\Models\HelpTiket;
use App\Models\HelpKategori;
use App\Models\HelpLogStatus;
use App\Models\HelpKomentar;
use App\Models\HelpLampiran;
use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class HelpTiketApprovalController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        // Pastikan user punya pelanggan
        if (!$user->pelanggan) {
            return back()->withErrors(['error' => 'Data pelanggan belum terhubung.']);
        }
        
        // Query untuk SEMUA tiket termasuk milik sendiri
        $query = HelpTiket::query();
        
        // Apply filters
        if ($search = request('search')) {
            $query->where(function($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                ->orWhere('nomor_tiket', 'like', "%{$search}%");
            });
        }
        
        if ($status = request('status')) {
            $query->where('status', $status);
        }
        
        if ($prioritas = request('prioritas')) {
            $query->where('prioritas', $prioritas);
        }
        
        if ($kategori_id = request('kategori_id')) {
            $query->where('kategori_id', $kategori_id);
        }
        
        // Get kategori untuk filter dropdown
        $kategori = HelpKategori::where('aktif', true)->get();
        
        // PERBAIKAN: Tambahkan eager loading untuk pelapor dan pelanggan
        $tiket = $query->with([
                'kategori',
                'pelapor',  // Ini load User model
                'ditugaskanKe.user'
            ])
            ->latest()
            ->paginate(20)
            ->withQueryString();
        
        return view('help.proses.index', compact('tiket', 'kategori'));
    }
    
    public function show(HelpTiket $tiket)
    {
        $user = Auth::user();
        
        // Pastikan user punya pelanggan
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
        // Load relations dengan relationship yang benar
        $tiket->load([
            'kategori',
            'komentar' => function($query) {
                $query->with(['pengguna.user'])->orderBy('created_at', 'asc');
            },
            'lampiran' => function($query) {
                $query->orderBy('created_at', 'desc');
            },
            'logStatus' => function($query) {
                $query->with(['pengguna.user'])->orderBy('created_at', 'asc');
            },
            'pelapor',              // Relasi pelapor ke User
            'ditugaskanKe.user'     // Relasi ditugaskan ke Pelanggan lalu ke User
        ]);
        
        return view('help.proses.show', compact('tiket'));
    }
    
    public function take(Request $request, HelpTiket $tiket)
    {
        $user = Auth::user();
        
        // Pastikan user punya pelanggan
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
        if ($tiket->status !== 'OPEN') {
            return back()->with('error', 'Hanya tiket dengan status OPEN yang dapat diambil.');
        }
        
        // Cek apakah tiket sudah diambil orang lain
        if ($tiket->status === 'ON_PROCESS' && $tiket->ditugaskan_ke && $tiket->ditugaskan_ke !== $user->pelanggan->id_pelanggan) {
            return back()->with('error', 'Tiket ini sudah diambil oleh orang lain.');
        }
        
        // Jika tiket sudah diambil oleh diri sendiri, tetap bisa diproses
        if ($tiket->status === 'ON_PROCESS' && $tiket->ditugaskan_ke === $user->pelanggan->id_pelanggan) {
            return redirect()->route('help.proses.show', $tiket)
                ->with('info', 'Anda sudah mengambil tiket ini sebelumnya.');
        }
        
        try {
            DB::beginTransaction();
            
            // Update tiket
            $tiket->update([
                'status' => 'ON_PROCESS',
                'diproses_pada' => now(),
                'ditugaskan_ke' => $user->pelanggan->id_pelanggan
            ]);
            
            // Log status change
            HelpLogStatus::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'status_lama' => 'OPEN',
                'status_baru' => 'ON_PROCESS',
                'catatan' => 'Tiket diambil untuk diproses'
            ]);
            
            // Add system komentar
            HelpKomentar::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'komentar' => 'Tiket sedang diproses',
                'pesan_sistem' => true,
                'tipe_pesan_sistem' => 'STATUS_CHANGED'
            ]);
            
            DB::commit();
            
            return redirect()->route('help.proses.show', $tiket)
                ->with('success', 'Tiket berhasil diambil untuk diproses!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal mengambil tiket: ' . $e->getMessage());
        }
    }
    
    public function addKomentar(Request $request, HelpTiket $tiket)
    {
        $user = Auth::user();
        
        // Pastikan user punya pelanggan
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
        // Authorization check - hanya yang menangani tiket yang bisa mengomentari
        if ($tiket->ditugaskan_ke !== $user->pelanggan->id_pelanggan) {
            return back()->with('error', 'Hanya yang menangani tiket yang dapat mengomentari.');
        }
        
        if ($tiket->status === 'CLOSED') {
            return back()->with('error', 'Tiket yang sudah ditutup tidak dapat dikomentari.');
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
            
            // Handle lampiran
            if ($request->hasFile('lampiran')) {
                foreach ($request->file('lampiran') as $file) {
                    $this->saveLampiranFile($tiket, $file, 'FOLLOW_UP', $user->pelanggan->id_pelanggan);
                }
            }
            
            // Jika status WAITING dan ini adalah respons dari penanggung jawab, ubah ke ON_PROCESS
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
                    'catatan' => 'Penanggung jawab memberikan respons'
                ]);
                
                // Add system komentar
                HelpKomentar::create([
                    'tiket_id' => $tiket->id,
                    'pengguna_id' => $user->pelanggan->id_pelanggan,
                    'komentar' => 'Status otomatis berubah menjadi ON_PROCESS',
                    'pesan_sistem' => true,
                    'tipe_pesan_sistem' => 'STATUS_CHANGED'
                ]);
            }
            
            DB::commit();
            
            return back()->with('success', 'Komentar berhasil ditambahkan!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menambahkan komentar: ' . $e->getMessage());
        }
    }
    
    public function complete(Request $request, HelpTiket $tiket)
    {
        $user = Auth::user();
        
        // Pastikan user punya pelanggan
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
        // Cek apakah user adalah yang menangani tiket
        if ($tiket->ditugaskan_ke !== $user->pelanggan->id_pelanggan) {
            return back()->with('error', 'Hanya yang menangani tiket yang dapat menyelesaikan.');
        }
        
        if ($tiket->status !== 'ON_PROCESS') {
            return back()->with('error', 'Hanya tiket dengan status ON_PROCESS yang dapat diselesaikan.');
        }
        
        $validated = $request->validate([
            'catatan' => 'required|string',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Update tiket status
            $tiket->update([
                'status' => 'DONE',
                'diselesaikan_pada' => now()
            ]);
            
            // Log status change
            HelpLogStatus::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'status_lama' => 'ON_PROCESS',
                'status_baru' => 'DONE',
                'catatan' => $validated['catatan']
            ]);
            
            // Add komentar dari user sebagai catatan penyelesaian
            HelpKomentar::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'komentar' => "**CATATAN PENYELESAIAN:**\n" . $validated['catatan']
            ]);
            
            // Add system komentar
            HelpKomentar::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'komentar' => 'Tiket telah diselesaikan',
                'pesan_sistem' => true,
                'tipe_pesan_sistem' => 'STATUS_CHANGED'
            ]);
            
            DB::commit();
            
            return back()->with('success', 'Tiket berhasil diselesaikan!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyelesaikan tiket: ' . $e->getMessage());
        }
    }
    
    public function close(Request $request, HelpTiket $tiket)
    {
        $user = Auth::user();
        
        // Pastikan user punya pelanggan
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
        // Cek apakah user adalah yang menangani tiket
        if ($tiket->ditugaskan_ke !== $user->pelanggan->id_pelanggan) {
            return back()->with('error', 'Hanya yang menangani tiket yang dapat menutup.');
        }
        
        if ($tiket->status !== 'DONE') {
            return back()->with('error', 'Hanya tiket dengan status DONE yang dapat ditutup.');
        }
        
        try {
            DB::beginTransaction();
            
            // Update tiket status
            $tiket->update([
                'status' => 'CLOSED',
                'ditutup_pada' => now()
            ]);
            
            // Log status change
            HelpLogStatus::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'status_lama' => 'DONE',
                'status_baru' => 'CLOSED',
                'catatan' => 'Tiket ditutup'
            ]);
            
            // Add system komentar
            HelpKomentar::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'komentar' => 'Tiket telah ditutup',
                'pesan_sistem' => true,
                'tipe_pesan_sistem' => 'STATUS_CHANGED'
            ]);
            
            DB::commit();
            
            return back()->with('success', 'Tiket berhasil ditutup!');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menutup tiket: ' . $e->getMessage());
        }
    }
    
public function previewLampiran(HelpLampiran $lampiran)
{
    \Log::info('Preview Lampiran Dipanggil', [
        'lampiran_id' => $lampiran->id,
        'tipe_file' => $lampiran->tipe_file,
        'path_file' => $lampiran->path_file,
        'is_thumbnail' => request()->has('thumb') || request()->has('thumbnail'),
        'query_params' => request()->all()
    ]);
    
    $user = Auth::user();
    
    if (!$user) {
        abort(403, 'Unauthorized');
    }
    
    // Cek akses ke tiket
    $tiket = $lampiran->tiket;
    
    // Validasi akses
    $canAccess = false;
    
    if ($user->pelanggan && $tiket->ditugaskan_ke === $user->pelanggan->id_pelanggan) {
        $canAccess = true;
    }
    
    if ($user->pelanggan && $tiket->pelapor_id === $user->pelanggan->id_pelanggan) {
        $canAccess = true;
    }
    
    if ($user->username === 'admin' || $user->is_admin == 1 || $user->role === 'admin') {
        $canAccess = true;
    }
    
    if (!$canAccess) {
        abort(403, 'Anda tidak memiliki akses untuk melihat lampiran ini');
    }
    
    // Cek apakah file adalah gambar
    if (!str_contains($lampiran->tipe_file, 'image')) {
        abort(404, 'File ini bukan gambar');
    }
    
    // Cari file
    $path = null;
    $possiblePaths = [
        storage_path('app/help/tiket/' . $tiket->id . '/' . basename($lampiran->path_file)),
        storage_path('app/' . $lampiran->path_file),
        storage_path('app/private/' . $lampiran->path_file),
        storage_path('app/public/' . $lampiran->path_file),
        storage_path('app/help/tiket/' . $tiket->id . '/' . $lampiran->nama_file),
    ];
    
    \Log::info('Mencari file di paths:', $possiblePaths);
    
    foreach ($possiblePaths as $possiblePath) {
        if (file_exists($possiblePath)) {
            $path = $possiblePath;
            \Log::info('File ditemukan di:', ['path' => $path]);
            break;
        }
    }
    
    if (!$path) {
        \Log::error('File tidak ditemukan di semua lokasi');
        abort(404, 'File tidak ditemukan');
    }
    
    // Tentukan apakah permintaan untuk thumbnail
    $isThumbnail = request()->has('thumb') || request()->has('thumbnail');
    
    \Log::info('Processing image', [
        'is_thumbnail' => $isThumbnail,
        'file_size' => filesize($path),
        'file_exists' => file_exists($path)
    ]);
    
    if ($isThumbnail) {
        try {
            // Pastikan Intervention Image terinstall
            if (!class_exists('Intervention\Image\ImageManager')) {
                \Log::error('Intervention Image tidak terinstall');
                // Return file asli
                return response()->file($path, [
                    'Content-Type' => $lampiran->tipe_file,
                    'Content-Disposition' => 'inline; filename="' . $lampiran->nama_file . '"',
                ]);
            }
            
            $img = Image::make($path);
            
            \Log::info('Original image size', [
                'width' => $img->width(),
                'height' => $img->height()
            ]);
            
            // Resize untuk thumbnail
            $img->resize(200, 200, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            \Log::info('Resized image size', [
                'width' => $img->width(),
                'height' => $img->height()
            ]);
            
            return $img->response($lampiran->tipe_file)
                ->header('Cache-Control', 'public, max-age=86400');
                
        } catch (\Exception $e) {
            \Log::error('Error creating thumbnail: ' . $e->getMessage());
            
            // Fallback: return file asli
            return response()->file($path, [
                'Content-Type' => $lampiran->tipe_file,
                'Content-Disposition' => 'inline; filename="' . $lampiran->nama_file . '"',
            ]);
        }
    }
    
    // Return file as-is untuk full size
    return response()->file($path, [
        'Content-Type' => $lampiran->tipe_file,
        'Content-Disposition' => 'inline; filename="' . $lampiran->nama_file . '"',
    ]);
}

/**
 * DOWNLOAD LAMPIRAN
 */
public function downloadLampiran(HelpLampiran $lampiran)
{
    $user = Auth::user();
    
    if (!$user) {
        abort(403, 'Unauthorized');
    }
    
    // Cek akses ke tiket
    $tiket = $lampiran->tiket;
    
    // Validasi akses - PERBAIKAN: Hapus hasRole()
    $canAccess = false;
    
    // 1. Cek jika user adalah penanggung jawab tiket
    if ($user->pelanggan && $tiket->ditugaskan_ke === $user->pelanggan->id_pelanggan) {
        $canAccess = true;
    }
    
    // 2. Cek jika user adalah pelapor tiket
    if ($user->pelanggan && $tiket->pelapor_id === $user->pelanggan->id_pelanggan) {
        $canAccess = true;
    }
    
    // 3. Admin/superuser selalu bisa akses
    // PERBAIKAN: Ganti hasRole() dengan cek langsung
    if ($user->username === 'admin' || $user->is_admin == 1 || $user->role === 'admin') {
        $canAccess = true;
    }
    
    if (!$canAccess) {
        abort(403, 'Anda tidak memiliki akses untuk mendownload lampiran ini');
    }
    
    // Cari file di storage
    $path = null;
    $possiblePaths = [
        storage_path('app/help/tiket/' . $tiket->id . '/' . basename($lampiran->path_file)),
        storage_path('app/' . $lampiran->path_file),
        storage_path('app/private/' . $lampiran->path_file),
        storage_path('app/public/' . $lampiran->path_file),
        storage_path('app/help/tiket/' . $tiket->id . '/' . $lampiran->nama_file),
        storage_path('app/private/help/tiket/' . $tiket->id . '/' . $lampiran->nama_file),
        storage_path('app/public/help/tiket/' . $tiket->id . '/' . $lampiran->nama_file), // TAMBAHKAN INI
    ];
    
    foreach ($possiblePaths as $possiblePath) {
        if (file_exists($possiblePath)) {
            $path = $possiblePath;
            break;
        }
    }
    
    if (!$path) {
        // Coba cari file dengan Storage facade
        $storagePath = 'help/tiket/' . $tiket->id . '/' . basename($lampiran->path_file);
        if (Storage::exists($storagePath)) {
            $path = Storage::path($storagePath);
        } else if (Storage::exists($lampiran->path_file)) {
            $path = Storage::path($lampiran->path_file);
        } else {
            abort(404, 'File tidak ditemukan di storage');
        }
    }
    
    // Return file untuk download
    return response()->download($path, $lampiran->nama_file);
}
    
    /**
     * Helper method untuk menyimpan file lampiran
     */
    private function saveLampiranFile($tiket, $file, $tipe, $penggunaId)
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $safeName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeName = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $safeName);
        $fileName = time() . '_' . uniqid() . '_' . $safeName . '.' . $extension;
        
        // Path penyimpanan
        $path = 'help/tiket/' . $tiket->id . '/' . $fileName;
        
        // Simpan ke storage private
        $file->storeAs('help/tiket/' . $tiket->id, $fileName, 'private');
        
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
    }
}