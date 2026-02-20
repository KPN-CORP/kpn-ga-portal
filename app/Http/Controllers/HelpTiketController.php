<?php

namespace App\Http\Controllers;

use App\Models\HelpTiket;
use App\Models\HelpKategori;
use App\Models\HelpLampiran;
use App\Models\HelpKomentar;
use App\Models\HelpLogStatus;
use App\Models\User;
use App\Models\BisnisUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HelpTiketController extends Controller
{
    /**
     * Tipe lampiran yang tersedia
     */
    const LAMPIRAN_TIPE = [
        'INITIAL' => 'INITIAL',
        'FOLLOW_UP' => 'FOLLOW_UP',
        'COMPLETION' => 'COMPLETION'
    ];

    /**
     * Status tiket yang tersedia
     */
    const STATUS_TIKET = [
        'OPEN' => 'OPEN',
        'ON_PROCESS' => 'ON_PROCESS',
        'WAITING' => 'WAITING',
        'DONE' => 'DONE',
        'CLOSED' => 'CLOSED'
    ];

    /**
     * Display a listing of user's tickets.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Validasi pelanggan
        if (!$user->pelanggan) {
            return back()->with('error', 'Data pelanggan belum terhubung. Silakan hubungi administrator.');
        }
        
        // Query dasar - hanya tiket milik pelapor
        $query = HelpTiket::where('pelapor_id', $user->pelanggan->id_pelanggan);
        
        // Filter pencarian
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('judul', 'like', "%{$search}%")
                  ->orWhere('nomor_tiket', 'like', "%{$search}%")
                  ->orWhere('deskripsi', 'like', "%{$search}%");
            });
        }
        
        // Filter status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Filter prioritas
        if ($request->filled('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }
        
        // Filter tanggal
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }
        
        // Eksekusi query dengan pagination
        $tiket = $query->with(['kategori', 'bisnisUnit'])
                      ->latest()
                      ->paginate(20)
                      ->withQueryString();
        
        return view('help.tiket.index', compact('tiket'));
    }

    /**
     * Show the form for creating a new ticket.
     */
    public function create()
    {
        $kategori = HelpKategori::where('aktif', true)->orderBy('nama')->get();
        $bisnisUnits = BisnisUnit::orderBy('nama_bisnis_unit')->get();
        
        return view('help.tiket.create', compact('kategori', 'bisnisUnits'));
    }

    /**
     * Store a newly created ticket in storage.
     */

    public function store(Request $request)
    {
        Log::info('=== MEMBUAT TIKET BARU ===', ['user_id' => Auth::id()]);
        
        // Validasi input
        $validated = $request->validate([
            'judul' => 'required|string|max:255',
            'deskripsi' => 'required|string',
            'kategori_id' => 'required|exists:db_help_kategori,id',
            'bisnis_unit_id' => 'nullable|exists:tb_bisnis_unit,id_bisnis_unit',
            'prioritas' => 'required|in:LOW,MEDIUM,HIGH,URGENT',
            'lampiran.*' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx'
        ]);

        try {
            DB::beginTransaction();
            
            $user = Auth::user();
            
            // Validasi pelanggan
            if (!$user->pelanggan) {
                throw new \Exception('Data pelanggan tidak ditemukan');
            }
            
            // Generate nomor tiket
            $nomorTiket = $this->generateNomorTiket();
            
            // Buat tiket
            $tiket = HelpTiket::create([
                'nomor_tiket' => $nomorTiket,
                'judul' => $validated['judul'],
                'deskripsi' => $validated['deskripsi'],
                'kategori_id' => $validated['kategori_id'],
                'pelapor_id' => $user->id,
                'bisnis_unit_id' => $validated['bisnis_unit_id'] ?? null,
                'prioritas' => $validated['prioritas'],
                'status' => self::STATUS_TIKET['OPEN'],
                'ditugaskan_ke' => null
            ]);
            
            Log::info('Tiket berhasil dibuat', [
                'tiket_id' => $tiket->id,
                'nomor_tiket' => $tiket->nomor_tiket
            ]);
            
            // Upload lampiran
            if ($request->hasFile('lampiran')) {
                foreach ($request->file('lampiran') as $file) {
                    $this->simpanLampiran(
                        $tiket,
                        $file,
                        self::LAMPIRAN_TIPE['INITIAL'],
                        $user->pelanggan->id_pelanggan
                    );
                }
            }
            
            // Komentar sistem
            HelpKomentar::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'komentar' => 'Tiket berhasil dibuat dengan nomor: ' . $tiket->nomor_tiket,
                'pesan_sistem' => true,
                'tipe_pesan_sistem' => 'TICKET_CREATED'
            ]);

            // === TAMBAHKAN LOG STATUS ===
            try {
                $logData = [
                    'tiket_id' => $tiket->id,
                    'pengguna_id' => $user->pelanggan->id_pelanggan,
                    'status_lama' => 'OPEN',
                    'status_baru' => 'OPEN',
                    'catatan' => 'Tiket berhasil dibuat dengan nomor: ' . $tiket->nomor_tiket,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                
                Log::info('Mencoba menyimpan log status', $logData);
                
                $log = HelpLogStatus::create($logData);
                
                Log::info('Log status berhasil disimpan', ['log_id' => $log->id]);
                
            } catch (\Exception $e) {
                Log::error('Gagal menyimpan log status', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'tiket_id' => $tiket->id
                ]);
            }
            // === SELESAI LOG STATUS ===
            
            DB::commit();
            
            Log::info('=== TIKET BERHASIL DIBUAT ===', [
                'nomor_tiket' => $tiket->nomor_tiket
            ]);
            
            return redirect()
                ->route('help.tiket.show', $tiket)
                ->with('success', 'Tiket berhasil dibuat! Nomor tiket: ' . $tiket->nomor_tiket);
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('=== GAGAL MEMBUAT TIKET ===', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', 'Gagal membuat tiket: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified ticket.
     */
    public function show(HelpTiket $tiket)
    {
        $user = Auth::user();
        
        // Validasi pelanggan
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
        // Authorization - hanya pelapor yang bisa melihat
        if ($tiket->pelapor_id !== $user->pelanggan->id_pelanggan) {
            abort(403, 'Anda tidak memiliki akses ke tiket ini.');
        }
        
        // Load relasi dengan eager loading
        $tiket->load([
            'kategori',
            'bisnisUnit',
            'pelapor.user',
            'ditugaskanKe.user',
            'komentar' => function($query) {
                $query->with(['pengguna.user'])
                      ->orderBy('created_at', 'asc');
            },
            'lampiran' => function($query) {
                $query->with(['pengguna.user'])
                      ->orderBy('created_at', 'asc');
            },
            'logStatus' => function($query) {
                $query->with(['pengguna.user'])
                      ->orderBy('created_at', 'asc');
            }
        ]);
        
        // Klasifikasi lampiran untuk view
        $lampiran = $this->klasifikasiLampiran($tiket->lampiran, $tiket->pelapor_id);
        
        return view('help.tiket.show', compact('tiket', 'lampiran'));
    }

    /**
     * Add comment to ticket.
     */
    public function addKomentar(Request $request, HelpTiket $tiket)
    {
        $user = Auth::user();
        
        // Validasi pelanggan
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
        // Authorization
        if ($tiket->status === self::STATUS_TIKET['CLOSED']) {
            abort(403, 'Tiket yang sudah ditutup tidak dapat dikomentari.');
        }
        
        if ($tiket->pelapor_id !== $user->pelanggan->id_pelanggan) {
            abort(403, 'Anda tidak memiliki akses untuk mengomentari tiket ini.');
        }
        
        // Validasi input
        $validated = $request->validate([
            'komentar' => 'required|string',
            'lampiran.*' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx'
        ]);

        try {
            DB::beginTransaction();
            
            // Buat komentar
            $komentar = HelpKomentar::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'komentar' => $validated['komentar']
            ]);
            
            // Upload lampiran follow-up
            if ($request->hasFile('lampiran')) {
                foreach ($request->file('lampiran') as $file) {
                    $this->simpanLampiran(
                        $tiket,
                        $file,
                        self::LAMPIRAN_TIPE['FOLLOW_UP'],
                        $user->pelanggan->id_pelanggan
                    );
                }
            }
            
            // Jika status WAITING, tambahkan komentar sistem bahwa pelapor merespons
            if ($tiket->status === self::STATUS_TIKET['WAITING']) {
                HelpKomentar::create([
                    'tiket_id' => $tiket->id,
                    'pengguna_id' => $user->pelanggan->id_pelanggan,
                    'komentar' => 'Pelapor memberikan respons tambahan',
                    'pesan_sistem' => true,
                    'tipe_pesan_sistem' => 'USER_RESPONSE'
                ]);
                
                // Status TETAP WAITING, tidak berubah
            }
            
            DB::commit();
            
            return back()->with('success', 'Komentar berhasil ditambahkan!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Gagal menambahkan komentar', [
                'error' => $e->getMessage(),
                'tiket_id' => $tiket->id
            ]);
            
            return back()->with('error', 'Gagal menambahkan komentar: ' . $e->getMessage());
        }
    }

    /**
     * Download attachment.
     */
    public function downloadLampiran(HelpLampiran $lampiran)
    {
        $user = Auth::user();
        
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
        $tiket = $lampiran->tiket;
        
        // Cek akses - pelapor atau staff GA
        $isPelapor = $tiket->pelapor_id == $user->pelanggan->id_pelanggan;
        $isStaff = DB::table('tb_access_menu')
                    ->where('username', $user->username)
                    ->where('ga_help_proses', 1)
                    ->exists();
        
        if (!$isPelapor && !$isStaff) {
            Log::warning('Akses download lampiran ditolak', [
                'user_id' => $user->id,
                'lampiran_id' => $lampiran->id
            ]);
            abort(403, 'Anda tidak memiliki akses ke file ini.');
        }
        
        // Cari file
        $path = $this->cariPathFile($lampiran);
        
        if (!$path || !file_exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }
        
        Log::info('Lampiran di-download', [
            'user_id' => $user->id,
            'lampiran_id' => $lampiran->id,
            'tipe' => $lampiran->tipe
        ]);
        
        return response()->download($path, $lampiran->nama_file);
    }

    /**
     * Preview attachment.
     */
    public function previewLampiran(HelpLampiran $lampiran)
    {
        $user = Auth::user();
        
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
        $tiket = $lampiran->tiket;
        
        // Strict authorization
        $isPelapor = $tiket->pelapor_id == $user->pelanggan->id_pelanggan;
        $isUploader = $lampiran->pengguna_id == $user->pelanggan->id_pelanggan;
        
        if (!$isPelapor && !$isUploader) {
            abort(403, 'Anda tidak memiliki akses ke file ini.');
        }
        
        // Cek status tiket
        if ($tiket->status === self::STATUS_TIKET['CLOSED'] && !$isPelapor) {
            abort(403, 'Tiket sudah ditutup, hanya pelapor yang bisa melihat file.');
        }
        
        // Cek apakah file gambar
        if (!str_contains($lampiran->tipe_file, 'image')) {
            return $this->downloadLampiran($lampiran);
        }
        
        // Cari file
        $path = $this->cariPathFile($lampiran);
        
        if (!$path || !file_exists($path)) {
            abort(404, 'File tidak ditemukan.');
        }
        
        // Cek apakah request thumbnail
        $isThumbnail = request()->has('thumb') || request()->has('thumbnail');
        
        if ($isThumbnail) {
            return $this->buatThumbnail($path, $lampiran);
        }
        
        // Return file asli
        $fileContent = file_get_contents($path);
        
        $headers = [
            'Content-Type' => $lampiran->tipe_file,
            'Content-Disposition' => 'inline; filename="' . $lampiran->nama_file . '"',
            'Content-Length' => strlen($fileContent),
            'Cache-Control' => 'private, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ];
        
        Log::info('File preview', [
            'user_id' => $user->id,
            'lampiran_id' => $lampiran->id
        ]);
        
        return response($fileContent, 200, $headers);
    }

    /**
     * ================ HELPER METHODS ================
     */

    /**
     * Simpan file lampiran
     */
    private function simpanLampiran($tiket, $file, $tipe, $penggunaId)
    {
        try {
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $safeName = pathinfo($originalName, PATHINFO_FILENAME);
            $safeName = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $safeName);
            
            // Format nama file
            $prefix = strtolower($tipe) . '_';
            $fileName = $prefix . time() . '_' . uniqid() . '_' . $safeName . '.' . $extension;
            
            // Direktori penyimpanan
            $directory = 'help/tiket/' . $tiket->id;
            $path = $directory . '/' . $fileName;
            
            // Simpan file
            Storage::disk('private')->put($path, file_get_contents($file));
            
            // Simpan ke database
            $lampiran = HelpLampiran::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $penggunaId,
                'path_file' => $path,
                'nama_file' => $originalName,
                'tipe_file' => $file->getMimeType(),
                'ukuran_file' => $file->getSize(),
                'tipe' => $tipe
            ]);
            
            Log::info('Lampiran tersimpan', [
                'tiket_id' => $tiket->id,
                'tipe' => $tipe,
                'file' => $originalName
            ]);
            
            return $lampiran;
            
        } catch (\Exception $e) {
            Log::error('Gagal simpan lampiran', [
                'tiket_id' => $tiket->id,
                'tipe' => $tipe,
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Cari path file dari berbagai kemungkinan lokasi
     */
    private function cariPathFile($lampiran)
    {
        $possiblePaths = [
            storage_path('app/private/' . $lampiran->path_file),
            storage_path('app/' . $lampiran->path_file),
            storage_path('app/help/tiket/' . $lampiran->tiket_id . '/' . basename($lampiran->path_file)),
            storage_path('app/private/help/tiket/' . $lampiran->tiket_id . '/' . basename($lampiran->path_file)),
            storage_path('app/private/help/tiket/' . $lampiran->tiket_id . '/' . $lampiran->nama_file),
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        // Cek via Storage facade
        if (Storage::disk('private')->exists($lampiran->path_file)) {
            return Storage::disk('private')->path($lampiran->path_file);
        }
        
        if (Storage::disk('private')->exists('help/tiket/' . $lampiran->tiket_id . '/' . basename($lampiran->path_file))) {
            return Storage::disk('private')->path('help/tiket/' . $lampiran->tiket_id . '/' . basename($lampiran->path_file));
        }
        
        return null;
    }

    /**
     * Buat thumbnail untuk gambar
     */
    private function buatThumbnail($path, $lampiran)
    {
        try {
            if (!class_exists('Intervention\Image\ImageManager')) {
                // Fallback jika Intervention tidak terinstall
                $fileContent = file_get_contents($path);
                return response($fileContent, 200, [
                    'Content-Type' => $lampiran->tipe_file,
                    'Content-Disposition' => 'inline; filename="' . $lampiran->nama_file . '"',
                ]);
            }
            
            $img = \Intervention\Image\Facades\Image::make($path);
            $img->resize(200, 200, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            
            return $img->response($lampiran->tipe_file)
                      ->header('Cache-Control', 'public, max-age=86400');
                      
        } catch (\Exception $e) {
            Log::error('Gagal buat thumbnail', ['error' => $e->getMessage()]);
            
            // Fallback
            $fileContent = file_get_contents($path);
            return response($fileContent, 200, [
                'Content-Type' => $lampiran->tipe_file,
                'Content-Disposition' => 'inline; filename="' . $lampiran->nama_file . '"',
            ]);
        }
    }

    /**
     * Klasifikasi lampiran untuk view
     */
    private function klasifikasiLampiran($lampiran, $pelaporId)
    {
        $result = [
            'initial' => [
                'photos' => collect(),
                'documents' => collect()
            ],
            'follow_up' => [
                'photos' => collect(),
                'documents' => collect()
            ],
            'completion' => [
                'photos' => collect(),
                'documents' => collect()
            ],
            'all_photos' => collect()
        ];
        
        foreach ($lampiran as $item) {
            $isImage = str_contains($item->tipe_file, 'image');
            
            // Klasifikasi berdasarkan tipe
            if ($item->tipe === self::LAMPIRAN_TIPE['INITIAL']) {
                if ($isImage) {
                    $result['initial']['photos']->push($item);
                } else {
                    $result['initial']['documents']->push($item);
                }
            } elseif ($item->tipe === self::LAMPIRAN_TIPE['FOLLOW_UP']) {
                if ($isImage) {
                    $item->badge_text = $item->pengguna_id == $pelaporId ? 'Pelapor' : 'Petugas';
                    $item->badge_color = $item->pengguna_id == $pelaporId ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800';
                    $result['follow_up']['photos']->push($item);
                } else {
                    $result['follow_up']['documents']->push($item);
                }
            } elseif ($item->tipe === self::LAMPIRAN_TIPE['COMPLETION']) {
                if ($isImage) {
                    $item->badge_text = 'Sesudah';
                    $item->badge_color = 'bg-green-100 text-green-800';
                    $result['completion']['photos']->push($item);
                } else {
                    $result['completion']['documents']->push($item);
                }
            }
            
            // Koleksi semua foto
            if ($isImage) {
                $result['all_photos']->push($item);
            }
        }
        
        // Sort all photos by created_at
        $result['all_photos'] = $result['all_photos']->sortBy('created_at')->values();
        
        return $result;
    }

    /**
     * Generate nomor tiket
     */
    private function generateNomorTiket()
    {
        $prefix = 'GA';
        $year = date('Y');
        $month = date('m');
        
        Log::info('=== GENERATE NOMOR TIKET ===');
        
        // Cari nomor tertinggi bulan ini
        $existingNumbers = HelpTiket::withTrashed()
            ->where('nomor_tiket', 'like', $prefix . '-' . $year . $month . '-%')
            ->pluck('nomor_tiket')
            ->toArray();
        
        $maxSequence = 0;
        foreach ($existingNumbers as $number) {
            if (preg_match('/-(\d{4})$/', $number, $matches)) {
                $seq = intval($matches[1]);
                if ($seq > $maxSequence) {
                    $maxSequence = $seq;
                }
            }
        }
        
        $newSequence = $maxSequence + 1;
        $newNumber = $prefix . '-' . $year . $month . '-' . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
        
        // Cek duplikat
        $attempt = 1;
        while (in_array($newNumber, $existingNumbers) && $attempt <= 100) {
            $newSequence++;
            $newNumber = $prefix . '-' . $year . $month . '-' . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
            $attempt++;
        }
        
        Log::info('Nomor tiket generated', ['nomor' => $newNumber]);
        
        return $newNumber;
    }
}