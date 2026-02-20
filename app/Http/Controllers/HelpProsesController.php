<?php
// app/Http/Controllers/HelpProsesController.php

namespace App\Http\Controllers;

use App\Models\HelpTiket;
use App\Models\HelpKategori;
use App\Models\BisnisUnit;
use App\Models\HelpLogStatus;
use App\Models\HelpKomentar;
use App\Models\HelpLampiran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HelpProsesController extends Controller
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
     * Display a listing of tickets for processing.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Ambil data untuk dropdown filter
        $bisnisUnits = BisnisUnit::orderBy('nama_bisnis_unit')->get();
        $kategori = HelpKategori::where('aktif', true)->orderBy('nama')->get();
        
        // Query dasar - tampilkan tiket yang bisa diproses
        $query = HelpTiket::with([
                'kategori',
                'bisnisUnit',
                'pelapor.user',
                'ditugaskanKe.user'
            ]);
        
        // FILTER PENCARIAN (nomor tiket atau judul)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor_tiket', 'LIKE', "%{$search}%")
                  ->orWhere('judul', 'LIKE', "%{$search}%")
                  ->orWhere('deskripsi', 'LIKE', "%{$search}%");
            });
        }
        
        // FILTER STATUS
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // FILTER BISNIS UNIT
        if ($request->filled('bisnis_unit_id')) {
            $query->where('bisnis_unit_id', $request->bisnis_unit_id);
        }
        
        // FILTER PRIORITAS
        if ($request->filled('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }
        
        // FILTER KATEGORI
        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }
        
        // FILTER TANGGAL (created_at)
        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $query->where('created_at', '>=', $startDate);
        }
        
        if ($request->filled('end_date')) {
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->where('created_at', '<=', $endDate);
        }
        
        // Urutkan dari yang terbaru
        $query->orderBy('created_at', 'desc');
        
        // Pagination
        $tiket = $query->paginate(15)->withQueryString();
        
        return view('help.proses.index', compact('tiket', 'bisnisUnits', 'kategori'));
    }

    /**
     * Display the specified ticket.
     */
    public function show($id)
    {
        $user = Auth::user();
        
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
        $tiket = HelpTiket::with([
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
            ])
            ->findOrFail($id);
        
        // Cek apakah user adalah penanggung jawab
        $isAssigned = $tiket->ditugaskan_ke == $user->pelanggan->id_pelanggan;
        
        // Klasifikasi lampiran untuk view
        $lampiran = $this->klasifikasiLampiran($tiket->lampiran, $tiket->pelapor_id);
        
        return view('help.proses.show', compact('tiket', 'isAssigned', 'lampiran'));
    }

    /**
     * Take/assign ticket to current user.
     */
    public function take(Request $request, $id)
    {
        $user = Auth::user();
        
        if (!$user->pelanggan) {
            return redirect()->back()->with('error', 'Data pelanggan belum terhubung.');
        }
        
        $tiket = HelpTiket::findOrFail($id);
        
        if ($tiket->status !== self::STATUS_TIKET['OPEN']) {
            return redirect()->back()->with('error', 'Hanya tiket dengan status OPEN yang dapat diambil.');
        }
        
        try {
            DB::beginTransaction();
            
            // Update tiket
            $tiket->update([
                'status' => self::STATUS_TIKET['ON_PROCESS'],
                'ditugaskan_ke' => $user->pelanggan->id_pelanggan,
                'diproses_pada' => now()
            ]);
            
            // Log status
            HelpLogStatus::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'status_lama' => self::STATUS_TIKET['OPEN'],
                'status_baru' => self::STATUS_TIKET['ON_PROCESS'],
                'catatan' => 'Tiket diambil oleh ' . ($user->name ?? $user->username)
            ]);
            
            // Komentar sistem
            HelpKomentar::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'komentar' => 'Tiket sedang diproses',
                'pesan_sistem' => true,
                'tipe_pesan_sistem' => 'STATUS_CHANGED'
            ]);
            
            DB::commit();
            
            Log::info('Tiket diambil', [
                'tiket_id' => $tiket->id,
                'petugas_id' => $user->pelanggan->id_pelanggan
            ]);
            
            return redirect()->route('help.proses.show', $tiket->id)
                ->with('success', 'Tiket berhasil diambil dan sedang diproses!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Gagal mengambil tiket', [
                'tiket_id' => $tiket->id,
                'error' => $e->getMessage()
            ]);
            
            return redirect()->back()->with('error', 'Gagal mengambil tiket: ' . $e->getMessage());
        }
    }

    /**
     * Add comment to ticket.
     */
    public function addKomentar(Request $request, $id)
    {
        $user = Auth::user();
        
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
        $tiket = HelpTiket::findOrFail($id);
        
        if ($tiket->status === self::STATUS_TIKET['CLOSED']) {
            return back()->with('error', 'Tiket yang sudah ditutup tidak dapat dikomentari.');
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
            
            DB::commit();
            
            return back()->with('success', 'Komentar berhasil ditambahkan!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Gagal menambahkan komentar', [
                'tiket_id' => $tiket->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Gagal menambahkan komentar: ' . $e->getMessage());
        }
    }

    /**
     * Download report as CSV.
     */
    public function download(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->pelanggan) {
            return redirect()->route('help.proses.index')
                ->with('error', 'Data pelanggan belum terhubung.');
        }
        
        // Query dasar
        $query = HelpTiket::with([
            'pelapor.user', 
            'kategori', 
            'bisnisUnit', 
            'ditugaskanKe.user'
        ]);
        
        // FILTER
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nomor_tiket', 'LIKE', "%{$search}%")
                  ->orWhere('judul', 'LIKE', "%{$search}%");
            });
        }
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->filled('bisnis_unit_id')) {
            $query->where('bisnis_unit_id', $request->bisnis_unit_id);
        }
        
        if ($request->filled('prioritas')) {
            $query->where('prioritas', $request->prioritas);
        }
        
        if ($request->filled('kategori_id')) {
            $query->where('kategori_id', $request->kategori_id);
        }
        
        if ($request->filled('start_date')) {
            $startDate = Carbon::parse($request->start_date)->startOfDay();
            $query->where('created_at', '>=', $startDate);
        }
        
        if ($request->filled('end_date')) {
            $endDate = Carbon::parse($request->end_date)->endOfDay();
            $query->where('created_at', '<=', $endDate);
        }
        
        // Cek opsi ignore filters
        if ($request->has('ignore_filters') && $request->ignore_filters == '1') {
            // Reset query tanpa filter
            $query = HelpTiket::with([
                'pelapor.user', 
                'kategori', 
                'bisnisUnit', 
                'ditugaskanKe.user'
            ]);
        }
        
        $query->orderBy('created_at', 'desc');
        
        $tiket = $query->get();
        
        if ($tiket->isEmpty()) {
            return redirect()->route('help.proses.index')
                ->with('error', 'Tidak ada data tiket untuk didownload');
        }
        
        // Generate filename
        $filename = 'tiket_proses_' . date('Y-m-d_His') . '.csv';
        
        // Set headers untuk download CSV
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];
        
        // Buat callback untuk stream CSV
        $callback = function() use ($tiket) {
            $file = fopen('php://output', 'w');
            
            // UTF-8 BOM untuk Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Header CSV
            fputcsv($file, [
                'No',
                'Nomor Tiket',
                'Judul',
                'Pelapor',
                'Bisnis Unit',
                'Kategori',
                'Prioritas',
                'Status',
                'Penanggung Jawab',
                'Tanggal Dibuat',
                'Tanggal Diproses',
                'Tanggal Selesai',
                'Deskripsi'
            ]);
            
            // Data
            foreach ($tiket as $index => $item) {
                $pelaporName = optional(optional($item->pelapor)->user)->name 
                    ?? optional($item->pelapor)->nama 
                    ?? '-';
                
                $penanggungJawab = optional(optional($item->ditugaskanKe)->user)->name 
                    ?? optional($item->ditugaskanKe)->nama 
                    ?? 'Belum ditugaskan';
                
                $bisnisUnit = optional($item->bisnisUnit)->nama_bisnis_unit ?? '-';
                $kategori = optional($item->kategori)->nama ?? '-';
                
                fputcsv($file, [
                    $index + 1,
                    $item->nomor_tiket,
                    $item->judul,
                    $pelaporName,
                    $bisnisUnit,
                    $kategori,
                    $item->prioritas,
                    $item->status,
                    $penanggungJawab,
                    $item->created_at ? $item->created_at->format('d/m/Y H:i') : '-',
                    $item->diproses_pada ? Carbon::parse($item->diproses_pada)->format('d/m/Y H:i') : '-',
                    $item->diselesaikan_pada ? Carbon::parse($item->diselesaikan_pada)->format('d/m/Y H:i') : '-',
                    $item->deskripsi
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Preview attachment.
     */
    public function previewLampiran(HelpLampiran $lampiran)
    {
        Log::info('Preview lampiran', ['lampiran_id' => $lampiran->id]);
        
        $user = Auth::user();
        
        if (!$user) {
            abort(403, 'Unauthorized');
        }
        
        // Cek apakah file gambar
        if (!str_contains($lampiran->tipe_file, 'image')) {
            return $this->downloadLampiran($lampiran);
        }
        
        // Cari file
        $path = $this->cariPathFile($lampiran);
        
        if (!$path || !file_exists($path)) {
            abort(404, 'File tidak ditemukan');
        }
        
        // Return file asli
        return response()->file($path, [
            'Content-Type' => $lampiran->tipe_file,
            'Content-Disposition' => 'inline; filename="' . $lampiran->nama_file . '"',
        ]);
    }

    /**
     * Download attachment.
     */
    public function downloadLampiran(HelpLampiran $lampiran)
    {
        $user = Auth::user();
        
        if (!$user) {
            abort(403, 'Unauthorized');
        }
        
        // Cari file
        $path = $this->cariPathFile($lampiran);
        
        if (!$path || !file_exists($path)) {
            abort(404, 'File tidak ditemukan');
        }
        
        Log::info('Download lampiran', [
            'user_id' => $user->id,
            'lampiran_id' => $lampiran->id
        ]);
        
        return response()->download($path, $lampiran->nama_file);
    }

    /**
     * ==================== FILTER HELPER METHODS ====================
     */

    /**
     * Get active filters from request
     */
    public function getActiveFilters(Request $request)
    {
        $filters = [];
        
        if ($request->filled('search')) {
            $filters['search'] = ['label' => 'Pencarian', 'value' => $request->search];
        }
        
        if ($request->filled('status')) {
            $filters['status'] = ['label' => 'Status', 'value' => $request->status];
        }
        
        if ($request->filled('bisnis_unit_id')) {
            $unit = BisnisUnit::find($request->bisnis_unit_id);
            $filters['bisnis_unit_id'] = [
                'label' => 'Bisnis Unit', 
                'value' => $unit ? $unit->nama_bisnis_unit : $request->bisnis_unit_id
            ];
        }
        
        if ($request->filled('prioritas')) {
            $filters['prioritas'] = ['label' => 'Prioritas', 'value' => $request->prioritas];
        }
        
        if ($request->filled('kategori_id')) {
            $kat = HelpKategori::find($request->kategori_id);
            $filters['kategori_id'] = [
                'label' => 'Kategori', 
                'value' => $kat ? $kat->nama : $request->kategori_id
            ];
        }
        
        if ($request->filled('start_date')) {
            $filters['start_date'] = [
                'label' => 'Tanggal Mulai', 
                'value' => Carbon::parse($request->start_date)->format('d/m/Y')
            ];
        }
        
        if ($request->filled('end_date')) {
            $filters['end_date'] = [
                'label' => 'Tanggal Akhir', 
                'value' => Carbon::parse($request->end_date)->format('d/m/Y')
            ];
        }
        
        return $filters;
    }

    /**
     * Check if any filter is active
     */
    public function hasActiveFilters(Request $request)
    {
        return $request->anyFilled([
            'search', 
            'status', 
            'bisnis_unit_id', 
            'prioritas', 
            'kategori_id', 
            'start_date', 
            'end_date'
        ]);
    }

    /**
     * Get filter summary text
     */
    public function getFilterSummary(Request $request)
    {
        $parts = [];
        
        if ($request->filled('search')) {
            $parts[] = "Pencarian: '{$request->search}'";
        }
        
        if ($request->filled('status')) {
            $parts[] = "Status: {$request->status}";
        }
        
        if ($request->filled('bisnis_unit_id')) {
            $unit = BisnisUnit::find($request->bisnis_unit_id);
            if ($unit) {
                $parts[] = "Unit: {$unit->nama_bisnis_unit}";
            }
        }
        
        if ($request->filled('prioritas')) {
            $parts[] = "Prioritas: {$request->prioritas}";
        }
        
        if ($request->filled('kategori_id')) {
            $kat = HelpKategori::find($request->kategori_id);
            if ($kat) {
                $parts[] = "Kategori: {$kat->nama}";
            }
        }
        
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $start = Carbon::parse($request->start_date)->format('d/m/Y');
            $end = Carbon::parse($request->end_date)->format('d/m/Y');
            $parts[] = "Tanggal: {$start} - {$end}";
        } elseif ($request->filled('start_date')) {
            $start = Carbon::parse($request->start_date)->format('d/m/Y');
            $parts[] = "Dari tanggal: {$start}";
        } elseif ($request->filled('end_date')) {
            $end = Carbon::parse($request->end_date)->format('d/m/Y');
            $parts[] = "Sampai tanggal: {$end}";
        }
        
        return implode(' • ', $parts);
    }

    /**
     * Get date range presets
     */
    public function getDatePresets()
    {
        return [
            'today' => [
                'label' => 'Hari Ini',
                'start' => Carbon::today()->format('Y-m-d'),
                'end' => Carbon::today()->format('Y-m-d')  // ← HAPUS KURUNG SETELAH INI
            ],
            'yesterday' => [
                'label' => 'Kemarin',
                'start' => Carbon::yesterday()->format('Y-m-d'),
                'end' => Carbon::yesterday()->format('Y-m-d')
            ],
            'this_week' => [
                'label' => 'Minggu Ini',
                'start' => Carbon::now()->startOfWeek()->format('Y-m-d'),
                'end' => Carbon::now()->endOfWeek()->format('Y-m-d')
            ],
            'last_week' => [
                'label' => 'Minggu Lalu',
                'start' => Carbon::now()->subWeek()->startOfWeek()->format('Y-m-d'),
                'end' => Carbon::now()->subWeek()->endOfWeek()->format('Y-m-d')
            ],
            'this_month' => [
                'label' => 'Bulan Ini',
                'start' => Carbon::now()->startOfMonth()->format('Y-m-d'),
                'end' => Carbon::now()->endOfMonth()->format('Y-m-d')
            ],
            'last_month' => [
                'label' => 'Bulan Lalu',
                'start' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
                'end' => Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d')
            ]
        ];
    }

    /**
     * ==================== HELPER METHODS ====================
     */

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
            
            $item->formatted_size = $this->formatBytes($item->ukuran_file);
            
            if ($item->tipe === self::LAMPIRAN_TIPE['INITIAL']) {
                if ($isImage) {
                    $item->badge_text = 'Awal';
                    $item->badge_color = 'bg-blue-100 text-blue-800';
                    $result['initial']['photos']->push($item);
                } else {
                    $result['initial']['documents']->push($item);
                }
            } elseif ($item->tipe === self::LAMPIRAN_TIPE['FOLLOW_UP']) {
                if ($isImage) {
                    $item->badge_text = $item->pengguna_id == $pelaporId ? 'Pelapor' : 'Petugas';
                    $item->badge_color = $item->pengguna_id == $pelaporId 
                        ? 'bg-blue-100 text-blue-800' 
                        : 'bg-gray-100 text-gray-800';
                    $result['follow_up']['photos']->push($item);
                } else {
                    $result['follow_up']['documents']->push($item);
                }
            } elseif ($item->tipe === self::LAMPIRAN_TIPE['COMPLETION']) {
                if ($isImage) {
                    $item->badge_text = 'Selesai';
                    $item->badge_color = 'bg-green-100 text-green-800';
                    $result['completion']['photos']->push($item);
                } else {
                    $result['completion']['documents']->push($item);
                }
            }
            
            if ($isImage) {
                $result['all_photos']->push($item);
            }
        }
        
        $result['all_photos'] = $result['all_photos']->sortBy('created_at')->values();
        
        return $result;
    }

    /**
     * Simpan file lampiran
     */
    private function simpanLampiran($tiket, $file, $tipe, $penggunaId)
    {
        try {
            // VALIDASI FILE
            if (!$file || !$file->isValid()) {
                throw new \Exception('File tidak valid atau corrupt');
            }
            
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            
            // CEK EKSTENSI
            if (!in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'])) {
                throw new \Exception('Format file tidak diizinkan');
            }
            
            $safeName = pathinfo($originalName, PATHINFO_FILENAME);
            $safeName = preg_replace('/[^a-zA-Z0-9\-_]/', '_', $safeName);
            $safeName = substr($safeName, 0, 50);
            
            // Format nama file
            $prefix = strtolower($tipe) . '_';
            $fileName = $prefix . time() . '_' . uniqid() . '_' . $safeName . '.' . $extension;
            
            // Direktori penyimpanan
            $directory = 'help/tiket/' . $tiket->id;
            $path = $directory . '/' . $fileName;
            
            Log::info('Menyimpan file', [
                'directory' => $directory,
                'filename' => $fileName,
                'path' => $path
            ]);
            
            // PASTIKAN FOLDER ADA
            if (!Storage::disk('private')->exists($directory)) {
                Storage::disk('private')->makeDirectory($directory);
                Log::info('Folder dibuat: ' . $directory);
            }
            
            // BACA FILE
            $fileContent = file_get_contents($file->getRealPath());
            if ($fileContent === false) {
                throw new \Exception('Gagal membaca file');
            }
            
            // SIMPAN FILE
            $result = Storage::disk('private')->put($path, $fileContent);
            
            if (!$result) {
                throw new \Exception('Gagal menyimpan file ke storage');
            }
            
            // VERIFIKASI FILE TERSIMPAN
            if (!Storage::disk('private')->exists($path)) {
                throw new \Exception('File tidak ditemukan setelah disimpan');
            }
            
            $fileSize = Storage::disk('private')->size($path);
            Log::info('File tersimpan', [
                'path' => $path,
                'size' => $fileSize,
                'exists' => 'YES'
            ]);
            
            // SIMPAN KE DATABASE
            $lampiran = HelpLampiran::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $penggunaId,
                'path_file' => $path,
                'nama_file' => $originalName,
                'tipe_file' => $file->getMimeType(),
                'ukuran_file' => $file->getSize(),
                'tipe' => $tipe
            ]);
            
            Log::info('Lampiran tersimpan di database', [
                'id' => $lampiran->id,
                'tiket_id' => $tiket->id,
                'tipe' => $tipe,
                'file' => $originalName
            ]);
            
            return $lampiran;
            
        } catch (\Exception $e) {
            Log::error('========== GAGAL SIMPAN LAMPIRAN ==========');
            Log::error('Tiket ID: ' . $tiket->id);
            Log::error('Tipe: ' . $tipe);
            Log::error('File: ' . ($file ? $file->getClientOriginalName() : 'NULL'));
            Log::error('Error: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
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
            storage_path('app/private/help/tiket/' . $lampiran->tiket_id . '/' . basename($lampiran->path_file)),
            storage_path('app/help/tiket/' . $lampiran->tiket_id . '/' . basename($lampiran->path_file)),
            storage_path('app/private/help/tiket/' . $lampiran->tiket_id . '/' . $lampiran->nama_file),
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }
        
        if (Storage::disk('private')->exists($lampiran->path_file)) {
            return Storage::disk('private')->path($lampiran->path_file);
        }
        
        if (Storage::disk('private')->exists('help/tiket/' . $lampiran->tiket_id . '/' . basename($lampiran->path_file))) {
            return Storage::disk('private')->path('help/tiket/' . $lampiran->tiket_id . '/' . basename($lampiran->path_file));
        }
        
        return null;
    }

    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 1)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}