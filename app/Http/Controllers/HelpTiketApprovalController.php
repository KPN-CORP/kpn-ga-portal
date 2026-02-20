<?php
// app/Http/Controllers/HelpTiketApprovalController.php

namespace App\Http\Controllers;

use App\Models\HelpTiket;
use App\Models\HelpKategori;
use App\Models\HelpLogStatus;
use App\Models\HelpKomentar;
use App\Models\HelpLampiran;
use App\Models\Pelanggan;
use App\Models\BisnisUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HelpTiketApprovalController extends Controller
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
     * Display the specified ticket.
     */
    public function show(HelpTiket $tiket)
    {
        $user = Auth::user();
        
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
        // Load relasi
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
        
        // Cek apakah user adalah penanggung jawab
        $isAssigned = $tiket->ditugaskan_ke == $user->pelanggan->id_pelanggan;
        
        // Klasifikasi lampiran untuk view
        $lampiran = $this->klasifikasiLampiran($tiket->lampiran, $tiket->pelapor_id);
        
        return view('help.proses.show', compact('tiket', 'isAssigned', 'lampiran'));
    }

    /**
     * Add comment to ticket (staff).
     */
    public function addKomentar(Request $request, HelpTiket $tiket)
    {
        $user = Auth::user();
        
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
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
     * Transfer ticket to GA Corp (ON_PROCESS/WAITING -> OPEN)
     */
    public function transferToCorp(Request $request, HelpTiket $tiket)
    {
        Log::info('========== TRANSFER TO CORP DIPANGGIL ==========', [
            'tiket_id' => $tiket->id,
            'nomor_tiket' => $tiket->nomor_tiket,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name ?? 'Unknown',
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'time' => now()->toDateTimeString()
        ]);
        
        // Validasi hanya penanggung jawab yang bisa mengalihkan
        $currentUser = Auth::user();
        $currentPelanggan = $currentUser->pelanggan;
        
        if (!$currentPelanggan) {
            Log::error('Transfer gagal: User tidak punya pelanggan', ['user_id' => $currentUser->id]);
            return redirect()->back()->with('error', 'Data pelanggan belum terhubung.');
        }
        
        if ($tiket->ditugaskan_ke != $currentPelanggan->id_pelanggan) {
            Log::warning('Transfer gagal: Bukan penanggung jawab', [
                'user_pelanggan_id' => $currentPelanggan->id_pelanggan,
                'ditugaskan_ke' => $tiket->ditugaskan_ke
            ]);
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk mengalihkan tiket ini.');
        }
        
        // Validasi hanya tiket dengan status ON_PROCESS atau WAITING yang bisa dialihkan
        if (!in_array($tiket->status, ['ON_PROCESS', 'WAITING'])) {
            Log::warning('Transfer gagal: Status tidak valid', [
                'status' => $tiket->status,
                'required' => 'ON_PROCESS atau WAITING'
            ]);
            return redirect()->back()->with('error', 'Tiket hanya dapat dialihkan saat status ON_PROCESS atau WAITING.');
        }
        
        // Validasi alasan wajib diisi
        $request->validate([
            'alasan_transfer' => 'required|string|min:5|max:500',
        ], [
            'alasan_transfer.required' => 'Alasan pengalihan harus diisi.',
            'alasan_transfer.min' => 'Alasan pengalihan minimal 5 karakter.',
            'alasan_transfer.max' => 'Alasan pengalihan maksimal 500 karakter.',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Catat status lama
            $statusLama = $tiket->status;
            
            // Update tiket:
            // 1. Ubah status menjadi OPEN
            // 2. Hapus penanggung jawab (ditugaskan_ke = null)
            // 3. Reset waktu diproses
            $tiket->status = 'OPEN';
            $tiket->ditugaskan_ke = null;
            $tiket->diproses_pada = null;
            $tiket->menunggu_pada = null;
            $tiket->save();
            
            // Catat log status
            HelpLogStatus::create([
                'tiket_id' => $tiket->id,
                'status_lama' => $statusLama,
                'status_baru' => 'OPEN',
                'pengguna_id' => $currentPelanggan->id_pelanggan,
                'catatan' => 'Penanganan Dilanjutkan ke GA Corp. Alasan: ' . $request->alasan_transfer,
            ]);
            
            // Tambahkan komentar otomatis tentang pengalihan
            HelpKomentar::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $currentPelanggan->id_pelanggan,
                'komentar' => '⚠️ **Penanganan Dilanjutkan ke GA Corp** ⚠️' . "\n\n" .
                              '**Alasan:** ' . $request->alasan_transfer . "\n\n" .
                              '_Tiket dikembalikan ke antrian untuk diproses._',
                'pesan_sistem' => true,
                'tipe_pesan_sistem' => 'STATUS_CHANGED',
            ]);
            
            DB::commit();
            
            Log::info('Tiket berhasil dialihkan ke GA Corp', [
                'tiket_id' => $tiket->id,
                'nomor_tiket' => $tiket->nomor_tiket,
                'petugas_id' => $currentPelanggan->id_pelanggan,
                'alasan' => $request->alasan_transfer
            ]);
            
            return redirect()->route('help.proses.index')
                ->with('success', '✅ Tiket berhasil dialihkan ke GA Corp. Tiket telah dikembalikan ke antrian OPEN.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Gagal mengalihkan tiket ke GA Corp', [
                'tiket_id' => $tiket->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->back()
                ->with('error', 'Gagal mengalihkan tiket: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Upload completion photos.
     */
    public function uploadFotoSelesai(Request $request, HelpTiket $tiket)
    {
        // ==================== WAJIB: LOGGING AWAL ====================
        Log::channel('daily')->info('========== UPLOAD FOTO SELESAI DIPANGGIL ==========', [
            'tiket_id' => $tiket->id,
            'nomor_tiket' => $tiket->nomor_tiket,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name ?? 'Unknown',
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'has_file' => $request->hasFile('foto_hasil'),
            'files_count' => $request->hasFile('foto_hasil') ? count($request->file('foto_hasil')) : 0,
            'files_info' => $request->hasFile('foto_hasil') ? array_map(function($file) {
                return [
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                    'error' => $file->getError(),
                    'is_valid' => $file->isValid(),
                    'tmp_path' => $file->getPathname(),
                ];
            }, $request->file('foto_hasil')) : [],
            'post_data' => $request->except('_token'),
            'session_token' => csrf_token(),
            'time' => now()->toDateTimeString()
        ]);
        // ==============================================================

        $user = Auth::user();
        
        // CEK USER
        if (!$user) {
            Log::error('Upload foto: User tidak terautentikasi');
            return back()->with('error', 'User tidak terautentikasi. Silakan login ulang.');
        }
        
        // CEK PELANGGAN
        if (!$user->pelanggan) {
            Log::error('Upload foto: User tidak punya pelanggan', ['user_id' => $user->id]);
            return back()->with('error', 'Data pelanggan belum terhubung. Silakan hubungi administrator.');
        }
        
        // CEK APAKAH ADA FILE
        if (!$request->hasFile('foto_hasil')) {
            Log::warning('Upload foto: Tidak ada file', [
                'files' => $_FILES,
                'request_all' => $request->all()
            ]);
            return back()->with('error', 'Tidak ada file yang dipilih. Silakan pilih foto terlebih dahulu.');
        }
        
        $files = $request->file('foto_hasil');
        if (count($files) === 0) {
            return back()->with('error', 'Tidak ada file yang valid.');
        }
        
        // CEK VALIDASI DASAR
        foreach ($files as $index => $file) {
            if (!$file->isValid()) {
                Log::error('Upload foto: File tidak valid', [
                    'index' => $index,
                    'name' => $file->getClientOriginalName(),
                    'error' => $file->getError(),
                    'error_message' => $this->getUploadErrorMessage($file->getError())
                ]);
                return back()->with('error', 'File ' . $file->getClientOriginalName() . ' tidak valid: ' . $this->getUploadErrorMessage($file->getError()));
            }
        }
        
        // Validasi: hanya penanggung jawab
        if ($tiket->ditugaskan_ke !== $user->pelanggan->id_pelanggan) {
            Log::warning('Upload foto: Bukan penanggung jawab', [
                'user_pelanggan_id' => $user->pelanggan->id_pelanggan,
                'ditugaskan_ke' => $tiket->ditugaskan_ke
            ]);
            return back()->with('error', 'Hanya penanggung jawab tiket yang dapat mengupload foto hasil pekerjaan.');
        }
        
        // Validasi: hanya saat ON_PROCESS atau DONE
        if (!in_array($tiket->status, [self::STATUS_TIKET['ON_PROCESS'], self::STATUS_TIKET['DONE']])) {
            return back()->with('error', 'Foto hasil pekerjaan hanya dapat diupload saat tiket sedang diproses (ON_PROCESS) atau sudah selesai (DONE).');
        }
        
        // Validasi input dengan try-catch
        try {
            $validated = $request->validate([
                'foto_hasil' => 'required|array|min:1',
                'foto_hasil.*' => 'required|file|max:5120|mimes:jpg,jpeg,png',
                'keterangan' => 'nullable|string|max:255'
            ]);
            Log::info('Upload foto: Validasi berhasil', ['count' => count($files)]);
        } catch (\Exception $e) {
            Log::error('Upload foto: Validasi gagal', [
                'error' => $e->getMessage(),
                'files' => $_FILES
            ]);
            return back()->with('error', 'File tidak valid. Pastikan format JPG/PNG dan maksimal 5MB per file.');
        }

        try {
            DB::beginTransaction();
            Log::info('Upload foto: Transaction dimulai');
            
            $uploadedCount = 0;
            $failedCount = 0;
            $uploadedFiles = [];
            $errors = [];
            
            // Upload semua foto dengan tipe COMPLETION
            foreach ($request->file('foto_hasil') as $index => $file) {
                try {
                    Log::info('Processing file', [
                        'index' => $index,
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'tmp' => $file->getPathname()
                    ]);
                    
                    $lampiran = $this->simpanLampiran(
                        $tiket,
                        $file,
                        self::LAMPIRAN_TIPE['COMPLETION'],
                        $user->pelanggan->id_pelanggan
                    );
                    
                    $uploadedCount++;
                    $uploadedFiles[] = $file->getClientOriginalName();
                    Log::info('File berhasil disimpan', [
                        'lampiran_id' => $lampiran->id,
                        'path' => $lampiran->path_file
                    ]);
                    
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = $file->getClientOriginalName() . ': ' . $e->getMessage();
                    Log::error('Gagal upload file', [
                        'index' => $index,
                        'file' => $file->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            Log::info('Upload foto: Ringkasan', [
                'uploaded' => $uploadedCount,
                'failed' => $failedCount,
                'total' => count($request->file('foto_hasil'))
            ]);
            
            // Buat komentar sistem JIKA ADA FILE YANG BERHASIL
            if ($uploadedCount > 0) {
                $keterangan = $request->filled('keterangan') 
                    ? "📸 **Foto Hasil Pekerjaan:** " . $request->keterangan
                    : "📸 **" . $uploadedCount . " foto hasil pekerjaan** telah diupload";
                    
                $komentar = HelpKomentar::create([
                    'tiket_id' => $tiket->id,
                    'pengguna_id' => $user->pelanggan->id_pelanggan,
                    'komentar' => $keterangan,
                    'pesan_sistem' => true,
                    'tipe_pesan_sistem' => 'PHOTO_COMPLETION_UPLOADED'
                ]);
                
                Log::info('Komentar sistem dibuat', ['komentar_id' => $komentar->id]);
            }
            
            DB::commit();
            Log::info('Upload foto: Transaction COMMIT');
            
            // RESPONSE BERHASIL
            if ($failedCount > 0) {
                return back()->with('warning', 
                    '<div class="text-center">' .
                    '<i class="fas fa-exclamation-triangle text-warning mb-2" style="font-size: 2rem;"></i>' .
                    '<h6 class="font-bold text-warning">Upload Sebagian Berhasil</h6>' .
                    '<p class="mb-1">' . $uploadedCount . ' foto berhasil, ' . $failedCount . ' foto gagal.</p>' .
                    '<p class="text-xs text-gray-600">Gagal: ' . implode(', ', $errors) . '</p>' .
                    '</div>'
                );
            }
            
            return back()->with('success', 
                '<div class="text-center">' .
                '<i class="fas fa-check-circle text-success mb-2" style="font-size: 2.5rem;"></i>' .
                '<h5 class="font-bold text-success mb-1">Berhasil!</h5>' .
                '<p class="text-base">' . $uploadedCount . ' foto hasil pekerjaan telah diupload.</p>' .
                '<p class="text-xs text-gray-500 mt-1">' . implode(', ', $uploadedFiles) . '</p>' .
                '</div>'
            );
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('========== UPLOAD FOTO SELESAI GAGAL TOTAL ==========');
            Log::error('Error: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile() . ':' . $e->getLine());
            Log::error('Trace: ' . $e->getTraceAsString());
            
            return back()->with('error', 
                '<div class="text-center">' .
                '<i class="fas fa-exclamation-triangle text-danger mb-2" style="font-size: 2.5rem;"></i>' .
                '<h5 class="font-bold text-danger mb-1">Gagal!</h5>' .
                '<p class="text-base">' . $e->getMessage() . '</p>' .
                '<p class="text-xs text-gray-500 mt-2">Silakan coba lagi atau hubungi administrator.</p>' .
                '</div>'
            );
        }
    }

    /**
     * Get upload error message
     */
    private function getUploadErrorMessage($error)
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File melebihi upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'File melebihi MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian',
            UPLOAD_ERR_NO_FILE => 'Tidak ada file yang diupload',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
            UPLOAD_ERR_EXTENSION => 'File upload dihentikan oleh extension',
        ];
        
        return $errors[$error] ?? 'Unknown error (' . $error . ')';
    }

    /**
     * Complete ticket (ON_PROCESS -> DONE).
     */
    public function complete(Request $request, HelpTiket $tiket)
    {
        $user = Auth::user();
        
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
        // Validasi: hanya penanggung jawab
        if ($tiket->ditugaskan_ke !== $user->pelanggan->id_pelanggan) {
            return back()->with('error', 'Hanya yang menangani tiket yang dapat menyelesaikan.');
        }
        
        // Validasi status
        if ($tiket->status !== self::STATUS_TIKET['ON_PROCESS']) {
            return back()->with('error', 'Hanya tiket dengan status ON_PROCESS yang dapat diselesaikan.');
        }

        try {
            DB::beginTransaction();
            
            $oldStatus = $tiket->status;
            
            // Update tiket
            $tiket->update([
                'status' => self::STATUS_TIKET['DONE'],
                'diselesaikan_pada' => now()
            ]);
            
            // Log status
            HelpLogStatus::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'status_lama' => $oldStatus,
                'status_baru' => self::STATUS_TIKET['DONE'],
                'catatan' => 'Tiket diselesaikan oleh ' . ($user->name ?? $user->username)
            ]);
            
            // Komentar sistem
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
            
            Log::error('Gagal menyelesaikan tiket', [
                'tiket_id' => $tiket->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Gagal menyelesaikan tiket: ' . $e->getMessage());
        }
    }

    /**
     * Set ticket to WAITING (ON_PROCESS -> WAITING).
     */
    public function waiting(Request $request, HelpTiket $tiket)
    {
        Log::info('=== SET WAITING METHOD ===', [
            'tiket_id' => $tiket->id,
            'nomor_tiket' => $tiket->nomor_tiket
        ]);
        
        $user = Auth::user();
        
        if (!$user->pelanggan) {
            return back()->with('error', 'Data pelanggan belum terhubung.');
        }
        
        // Validasi: hanya penanggung jawab
        if ($tiket->ditugaskan_ke !== $user->pelanggan->id_pelanggan) {
            $penanggungJawab = $this->getNamaPenanggungJawab($tiket);
            return back()->with('error', 'Hanya <strong>' . $penanggungJawab . '</strong> yang dapat mengubah status tiket ini menjadi WAITING.');
        }
        
        // Validasi status
        if ($tiket->status !== self::STATUS_TIKET['ON_PROCESS']) {
            $statusLabel = $this->getStatusLabel($tiket->status);
            return back()->with('error', 'Hanya tiket dengan status <strong>ON_PROCESS</strong> yang dapat diubah menjadi WAITING. Status saat ini: ' . $statusLabel);
        }
        
        // Validasi input
        $validated = $request->validate([
            'catatan' => 'required|string',
            'urgent' => 'nullable|boolean',
        ]);

        try {
            DB::beginTransaction();
            
            $oldStatus = $tiket->status;
            
            // Update tiket
            $tiket->update([
                'status' => self::STATUS_TIKET['WAITING'],
                'menunggu_pada' => now()
            ]);
            
            // Log status
            HelpLogStatus::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'status_lama' => $oldStatus,
                'status_baru' => self::STATUS_TIKET['WAITING'],
                'catatan' => $validated['catatan']
            ]);
            
            // Komentar user
            $komentarText = $validated['catatan'];
            if ($request->has('urgent')) {
                $komentarText = "**⚠️ PERMINTAAN URGENT:**\n" . $komentarText;
            }
            
            HelpKomentar::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'komentar' => $komentarText
            ]);
            
            // Komentar sistem
            HelpKomentar::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'komentar' => 'Status berubah menjadi WAITING - Menunggu respons',
                'pesan_sistem' => true,
                'tipe_pesan_sistem' => 'STATUS_CHANGED'
            ]);
            
            DB::commit();
            
            Log::info('Status berubah WAITING', [
                'tiket_id' => $tiket->id,
                'nomor_tiket' => $tiket->nomor_tiket
            ]);
            
            return back()->with('success', 
                '<div class="text-center">' .
                '<i class="fas fa-hourglass-half text-orange-500 mb-2" style="font-size: 2rem;"></i>' .
                '<h6 class="text-orange-600 mb-1">Status WAITING</h6>' .
                '<p class="mb-0">Tiket sedang menunggu respons dari pelapor.</p>' .
                '</div>'
            );
                
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Gagal set status WAITING', [
                'tiket_id' => $tiket->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Gagal mengubah status: ' . $e->getMessage());
        }
    }

    /**
     * Resume ticket (WAITING -> ON_PROCESS).
     */
    public function resume(Request $request, HelpTiket $tiket)
    {
        $user = Auth::user();
        
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
        // Validasi: hanya penanggung jawab
        if ($tiket->ditugaskan_ke !== $user->pelanggan->id_pelanggan) {
            return back()->with('error', 'Hanya yang menangani tiket yang dapat mengembalikan ke status ON_PROCESS.');
        }
        
        // Validasi status
        if ($tiket->status !== self::STATUS_TIKET['WAITING']) {
            return back()->with('error', 'Hanya tiket dengan status WAITING yang dapat dikembalikan ke ON_PROCESS.');
        }
        
        // Validasi input
        $validated = $request->validate([
            'catatan' => 'required|string',
        ]);

        try {
            DB::beginTransaction();
            
            $oldStatus = $tiket->status;
            
            // Update tiket
            $tiket->update([
                'status' => self::STATUS_TIKET['ON_PROCESS'],
                'menunggu_pada' => null
            ]);
            
            // Log status
            HelpLogStatus::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'status_lama' => $oldStatus,
                'status_baru' => self::STATUS_TIKET['ON_PROCESS'],
                'catatan' => $validated['catatan']
            ]);
            
            // Komentar sistem
            HelpKomentar::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'komentar' => 'Status berubah menjadi ON_PROCESS - Proses dilanjutkan',
                'pesan_sistem' => true,
                'tipe_pesan_sistem' => 'STATUS_CHANGED'
            ]);
            
            DB::commit();
            
            return back()->with('success', 'Tiket berhasil dikembalikan ke status ON_PROCESS!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Gagal resume tiket', [
                'tiket_id' => $tiket->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Gagal mengubah status: ' . $e->getMessage());
        }
    }

    /**
     * Close ticket (DONE -> CLOSED).
     */
    public function close(Request $request, HelpTiket $tiket)
    {
        $user = Auth::user();
        
        if (!$user->pelanggan) {
            abort(403, 'Data pelanggan belum terhubung.');
        }
        
        // Validasi: hanya penanggung jawab
        if ($tiket->ditugaskan_ke !== $user->pelanggan->id_pelanggan) {
            return back()->with('error', 'Hanya yang menangani tiket yang dapat menutup.');
        }
        
        // Validasi status
        if ($tiket->status !== self::STATUS_TIKET['DONE']) {
            return back()->with('error', 'Hanya tiket dengan status DONE yang dapat ditutup.');
        }

        try {
            DB::beginTransaction();
            
            $oldStatus = $tiket->status;
            
            // Update tiket
            $tiket->update([
                'status' => self::STATUS_TIKET['CLOSED'],
                'ditutup_pada' => now()
            ]);
            
            // Log status
            HelpLogStatus::create([
                'tiket_id' => $tiket->id,
                'pengguna_id' => $user->pelanggan->id_pelanggan,
                'status_lama' => $oldStatus,
                'status_baru' => self::STATUS_TIKET['CLOSED'],
                'catatan' => 'Tiket ditutup'
            ]);
            
            // Komentar sistem
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
            
            Log::error('Gagal menutup tiket', [
                'tiket_id' => $tiket->id,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', 'Gagal menutup tiket: ' . $e->getMessage());
        }
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
        
        // Cek apakah request thumbnail
        $isThumbnail = request()->has('thumb') || request()->has('thumbnail');
        
        if ($isThumbnail) {
            return $this->buatThumbnail($path, $lampiran);
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
     * ================ HELPER METHODS ================
     */

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
     * Buat thumbnail untuk gambar
     */
    private function buatThumbnail($path, $lampiran)
    {
        try {
            if (!class_exists('Intervention\Image\ImageManager')) {
                return response()->file($path, [
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
            
            return response()->file($path, [
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
            
            $item->type_label = $this->getTipeLabel($item->tipe);
            $item->type_badge_color = $this->getTipeBadgeColor($item->tipe);
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
     * Get label untuk tipe lampiran
     */
    private function getTipeLabel($tipe)
    {
        $labels = [
            self::LAMPIRAN_TIPE['INITIAL'] => 'Awal',
            self::LAMPIRAN_TIPE['FOLLOW_UP'] => 'Diskusi',
            self::LAMPIRAN_TIPE['COMPLETION'] => 'Hasil'
        ];
        
        return $labels[$tipe] ?? $tipe;
    }

    /**
     * Get badge color untuk tipe lampiran
     */
    private function getTipeBadgeColor($tipe)
    {
        $colors = [
            self::LAMPIRAN_TIPE['INITIAL'] => 'bg-blue-100 text-blue-800',
            self::LAMPIRAN_TIPE['FOLLOW_UP'] => 'bg-gray-100 text-gray-800',
            self::LAMPIRAN_TIPE['COMPLETION'] => 'bg-green-100 text-green-800'
        ];
        
        return $colors[$tipe] ?? 'bg-gray-100 text-gray-800';
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

    /**
     * Get nama penanggung jawab untuk pesan error
     */
    private function getNamaPenanggungJawab($tiket)
    {
        if ($tiket->ditugaskanKe) {
            if ($tiket->ditugaskanKe->user) {
                return $tiket->ditugaskanKe->user->name;
            }
            return $tiket->ditugaskanKe->nama ?? 'Staff GA';
        }
        return 'Staff GA';
    }

    /**
     * Get label status untuk pesan error
     */
    private function getStatusLabel($status)
    {
        $labels = [
            self::STATUS_TIKET['OPEN'] => '<span class="badge bg-warning">OPEN</span>',
            self::STATUS_TIKET['ON_PROCESS'] => '<span class="badge bg-info">ON_PROCESS</span>',
            self::STATUS_TIKET['WAITING'] => '<span class="badge bg-warning">WAITING</span>',
            self::STATUS_TIKET['DONE'] => '<span class="badge bg-success">DONE</span>',
            self::STATUS_TIKET['CLOSED'] => '<span class="badge bg-secondary">CLOSED</span>'
        ];
        
        return $labels[$status] ?? $status;
    }
}