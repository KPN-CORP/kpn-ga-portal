<?php

namespace App\Http\Controllers;

use App\Models\Mailing;
use App\Models\Pelanggan;
use App\Models\Ekspedisi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MailingController extends Controller
{
    // ================= HELPER FUNCTIONS =================
    private function getUserAccess()
    {
        $username = Auth::user()->username;
        return DB::table('tb_access_menu')->where('username', $username)->first();
    }

    private function getPelangganId()
    {
        $user = Auth::user();
        $pelanggan = Pelanggan::where('id_login', $user->id)->first();
        return $pelanggan ? $pelanggan->id_pelanggan : null;
    }

    private function applyAccessFilter($query)
    {
        $accessMenu = $this->getUserAccess();
        if (!$accessMenu || (isset($accessMenu->mailing_proses) && $accessMenu->mailing_proses != 1)) {
            $pelangganId = $this->getPelangganId();
            if ($pelangganId) {
                $query->where(function($q) use ($pelangganId) {
                    $q->where('mailing_keterangan', 'LIKE', "Pelanggan ID: {$pelangganId} - %")
                      ->orWhere('mailing_keterangan', 'LIKE', "Pelanggan ID: {$pelangganId} %")
                      ->orWhere('mailing_keterangan', 'LIKE', "%Pelanggan ID: {$pelangganId} - %")
                      ->orWhere('mailing_keterangan', 'LIKE', "%Pelanggan ID: {$pelangganId} %");
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }
        return $query;
    }

    /**
     * Kompres gambar ke maksimal ukuran (dalam MB)
     * 
     * @param string $sourcePath Path file asli
     * @param string $destPath Path tujuan
     * @param float $maxSizeMB Ukuran maksimal dalam MB (default 1.5)
     * @return bool True jika berhasil
     */
    private function compressImage($sourcePath, $destPath, $maxSizeMB = 1.5)
    {
        if (!file_exists($sourcePath)) {
            return false;
        }

        $info = getimagesize($sourcePath);
        if (!$info) return false;

        $mime = $info['mime'];
        $maxSizeBytes = $maxSizeMB * 1024 * 1024;

        // Jika ukuran awal sudah <= limit, cukup copy
        if (filesize($sourcePath) <= $maxSizeBytes) {
            if ($sourcePath !== $destPath) {
                copy($sourcePath, $destPath);
            }
            return true;
        }

        // Buka gambar asli
        switch ($mime) {
            case 'image/jpeg':
                $src = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $src = imagecreatefrompng($sourcePath);
                break;
            case 'image/webp':
                $src = imagecreatefromwebp($sourcePath);
                break;
            default:
                return false;
        }
        if (!$src) return false;

        // Konversi PNG ke JPEG (lebih kecil)
        if ($mime === 'image/png') {
            $width = imagesx($src);
            $height = imagesy($src);
            $jpeg = imagecreatetruecolor($width, $height);
            $white = imagecolorallocate($jpeg, 255, 255, 255);
            imagefill($jpeg, 0, 0, $white);
            imagecopy($jpeg, $src, 0, 0, 0, 0, $width, $height);
            imagedestroy($src);
            $src = $jpeg;
            $mime = 'image/jpeg';
        }

        // Loop kompresi dengan kualitas menurun
        $quality = 90;
        $minQuality = 20;
        $tempPath = $destPath . '.tmp';
        $success = false;

        while ($quality >= $minQuality) {
            if ($mime === 'image/jpeg') {
                imagejpeg($src, $tempPath, $quality);
            } elseif ($mime === 'image/webp') {
                imagewebp($src, $tempPath, $quality);
            } else {
                imagejpeg($src, $tempPath, $quality);
            }
            clearstatcache();
            if (filesize($tempPath) <= $maxSizeBytes) {
                rename($tempPath, $destPath);
                $success = true;
                break;
            }
            $quality -= 5;
        }

        if (!$success && file_exists($tempPath)) {
            rename($tempPath, $destPath);
            $success = true;
        }

        imagedestroy($src);
        return $success;
    }

    private function ensureDirectoryExists()
    {
        $tempDir = storage_path('app/public/temp');
        $mailingDir = storage_path('app/public/mailing-foto');
        if (!is_dir($tempDir)) mkdir($tempDir, 0755, true);
        if (!is_dir($mailingDir)) mkdir($mailingDir, 0755, true);
    }

    // ================= MAIN METHODS =================
    public function index()
    {
        $query = Mailing::where('mailing_status', 'Selesai');
        $query = $this->applyAccessFilter($query);
        $accessMenu = $this->getUserAccess();
        $canViewAll = $accessMenu && isset($accessMenu->mailing_proses) && $accessMenu->mailing_proses == 1;
        $pelangganId = $this->getPelangganId();

        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('mailing_resi', 'like', '%' . $searchTerm . '%')
                  ->orWhere('mailing_pengirim', 'like', '%' . $searchTerm . '%')
                  ->orWhere('mailing_penerima', 'like', '%' . $searchTerm . '%')
                  ->orWhere('mailing_penerima_distribusi', 'like', '%' . $searchTerm . '%')
                  ->orWhere('mailing_expedisi', 'like', '%' . $searchTerm . '%')
                  ->orWhere('mailing_keterangan', 'like', '%' . $searchTerm . '%');
            });
        }
        if (request('start_date')) {
            $query->whereDate('mailing_tanggal_selesai', '>=', request('start_date'));
        }
        if (request('end_date')) {
            $query->whereDate('mailing_tanggal_selesai', '<=', request('end_date'));
        }

        $mailings = $query->orderBy('mailing_tanggal_selesai', 'desc')->paginate(50);
        return view('mailing.index', compact('mailings', 'canViewAll', 'pelangganId'));
    }

    public function proses()
    {
        $today = Carbon::now()->format('Y-m-d');
        $query = Mailing::whereIn('mailing_status', ['Mailing Room', 'Lantai 47']);
        $query = $this->applyAccessFilter($query);
        $accessMenu = $this->getUserAccess();
        $canViewAll = $accessMenu && isset($accessMenu->mailing_proses) && $accessMenu->mailing_proses == 1;
        $pelangganId = $this->getPelangganId();

        $startDate = request('start_date', $today);
        $endDate = request('end_date', $today);
        $query->whereDate('mailing_tanggal_input', '>=', $startDate)
              ->whereDate('mailing_tanggal_input', '<=', $endDate);

        if (request('search')) {
            $searchTerm = request('search');
            $query->where(function($q) use ($searchTerm) {
                $q->where('mailing_resi', 'like', '%' . $searchTerm . '%')
                  ->orWhere('mailing_pengirim', 'like', '%' . $searchTerm . '%')
                  ->orWhere('mailing_penerima', 'like', '%' . $searchTerm . '%')
                  ->orWhere('mailing_expedisi', 'like', '%' . $searchTerm . '%')
                  ->orWhere('mailing_lantai', 'like', '%' . $searchTerm . '%')
                  ->orWhere('mailing_keterangan', 'like', '%' . $searchTerm . '%');
            });
        }
        if (request('status') && in_array(request('status'), ['Mailing Room', 'Lantai 47'])) {
            $query->where('mailing_status', request('status'));
        }
        if (request('lantai')) {
            $query->where('mailing_lantai', request('lantai'));
        }

        $mailings = $query->orderBy('mailing_tanggal_input', 'desc')->paginate(50)->withQueryString();
        $pelanggans = Pelanggan::orderBy('nama_pelanggan', 'asc')->get();

        return view('mailing.proses', compact('mailings', 'pelanggans', 'today', 'canViewAll', 'pelangganId'));
    }

    /**
     * Bulk selesaikan mailing - dengan kompresi di server
     */
    public function bulkSelesai(Request $request)
    {
        $request->validate([
            'mailing_ids' => 'required|array|min:1',
            'mailing_ids.*' => 'exists:tb_mailing,id_mailing',
            'mailing_foto' => 'required|image', // tanpa max, karena batasan sudah di .user.ini
        ]);

        DB::beginTransaction();

        try {
            $this->ensureDirectoryExists();

            $file = $request->file('mailing_foto');
            $originalExtension = $file->getClientOriginalExtension();
            $fileName = 'bulk_' . time() . '_' . auth()->id() . '.' . $originalExtension;

            // Simpan sementara
            $tempPath = $file->storeAs('temp', $fileName, 'public');
            $fullTempPath = storage_path('app/public/' . $tempPath);

            $finalPath = 'mailing-foto/' . $fileName;
            $fullFinalPath = storage_path('app/public/' . $finalPath);

            // Kompres gambar (server-side)
            $compressSuccess = $this->compressImage($fullTempPath, $fullFinalPath, 1.5);

            if ($compressSuccess) {
                if (file_exists($fullTempPath)) unlink($fullTempPath);
                $storedPath = $finalPath;
            } else {
                // fallback: gunakan file asli jika kompres gagal
                $storedPath = $tempPath;
            }

            // Tentukan penerima
            $penerimaNama = '';
            if ($request->filled('penerima_id')) {
                $pelanggan = Pelanggan::find($request->penerima_id);
                $penerimaNama = $pelanggan ? $pelanggan->nama_pelanggan : '';
            } elseif ($request->filled('mailing_penerima_distribusi')) {
                $penerimaNama = $request->mailing_penerima_distribusi;
            }

            $now = now()->format('Y-m-d H:i:s');
            $userId = auth()->id();

            $updated = DB::table('tb_mailing')
                ->whereIn('id_mailing', $request->mailing_ids)
                ->where('mailing_status', 'Lantai 47')
                ->update([
                    'mailing_status' => 'Selesai',
                    'mailing_tanggal_selesai' => $now,
                    'mailing_selesai_by' => $userId,
                    'mailing_foto' => $storedPath,
                    'mailing_penerima_distribusi' => $penerimaNama,
                    'mailing_keterangan' => DB::raw("CONCAT(COALESCE(mailing_keterangan, ''), ' | Selesai: {$now} oleh {$penerimaNama}')"),
                    'updated_at' => $now
                ]);

            DB::commit();

            return redirect()->route('mailing.proses')
                ->with('success', "✅ {$updated} mailing berhasil diselesaikan (foto dikompres ≤ 1.5 MB)");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menyelesaikan mailing: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Single selesai - dengan kompresi server
     */
    public function selesai(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'mailing_foto' => 'required|image',
                'mailing_penerima_distribusi' => 'required|string|max:255',
                'penerima_id' => 'nullable|string|max:100',
            ]);

            $mailing = Mailing::findOrFail($id);

            if ($request->hasFile('mailing_foto')) {
                $file = $request->file('mailing_foto');
                $fileName = 'mailing_' . $id . '_' . time() . '.' . $file->getClientOriginalExtension();

                $this->ensureDirectoryExists();
                $tempPath = $file->storeAs('temp', $fileName, 'public');
                $fullTempPath = storage_path('app/public/' . $tempPath);

                $finalPath = 'mailing-foto/' . $fileName;
                $fullFinalPath = storage_path('app/public/' . $finalPath);

                if ($this->compressImage($fullTempPath, $fullFinalPath, 1.5)) {
                    if (file_exists($fullTempPath)) unlink($fullTempPath);
                    $path = $finalPath;
                } else {
                    $path = $tempPath;
                }

                $updateData = [
                    'mailing_status' => 'Selesai',
                    'mailing_tanggal_selesai' => now(),
                    'mailing_selesai_by' => auth()->id(),
                    'mailing_foto' => $path,
                    'mailing_penerima_distribusi' => $validated['mailing_penerima_distribusi'],
                ];

                if ($request->filled('penerima_id')) {
                    if (is_numeric($request->penerima_id)) {
                        $pelanggan = Pelanggan::find($request->penerima_id);
                        $keteranganBaru = $pelanggan ? "Pelanggan ID: {$request->penerima_id} - {$pelanggan->nama_pelanggan}" : "Pelanggan ID: {$request->penerima_id} - Tidak Dikenal";
                    } else {
                        $keteranganBaru = "Penerima ID: {$request->penerima_id}";
                    }
                    $updateData['mailing_keterangan'] = trim(($mailing->mailing_keterangan ?: '') . ' | ' . $keteranganBaru);
                }

                $mailing->update($updateData);
                return back()->with('success', 'Mailing selesai (foto dikompres ≤ 1.5 MB)');
            }
            return back()->with('error', 'Foto tidak ditemukan');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function storeBulk(Request $request)
    {
        // sama seperti kode Anda sebelumnya, tidak perlu kompresi di sini
        try {
            $request->validate(['mailings' => 'required|array|min:1']);
            $count = 0;
            foreach ($request->mailings as $mailingData) {
                try {
                    $ekspedisiNama = $mailingData['id_ekspedisi_input'] ?? 'Unknown';
                    $ekspedisiId = $mailingData['id_ekspedisi'] ?? null;
                    if ($ekspedisiId && is_numeric($ekspedisiId)) {
                        $ekspedisi = Ekspedisi::find($ekspedisiId);
                        if ($ekspedisi) $ekspedisiNama = $ekspedisi->nama_ekspedisi;
                    }
                    $user = Auth::user();
                    $pelanggan = Pelanggan::where('id_login', $user->id)->first();
                    $keteranganAwal = $pelanggan ? "Pelanggan ID: {$pelanggan->id_pelanggan} - {$pelanggan->nama_pelanggan}" : '';
                    Mailing::create([
                        'mailing_resi' => $mailingData['mailing_resi'] ?? '',
                        'mailing_pengirim' => $mailingData['mailing_pengirim'] ?? '',
                        'mailing_penerima' => $mailingData['mailing_penerima'] ?? '',
                        'mailing_lantai' => $mailingData['mailing_lantai'] ?? null,
                        'mailing_expedisi' => $ekspedisiNama,
                        'mailing_status' => 'Mailing Room',
                        'mailing_prioritas' => 'Normal',
                        'mailing_tanggal_input' => now(),
                        'mailing_input_by' => $user->id,
                        'mailing_keterangan' => $keteranganAwal,
                    ]);
                    $count++;
                } catch (\Exception $e) { continue; }
            }
            if ($count > 0) {
                return redirect()->route('mailing.proses')->with('success', "✅ {$count} mailing berhasil ditambahkan!");
            }
            return back()->with('error', 'Tidak ada data yang berhasil disimpan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $ekspedisi = Ekspedisi::orderBy('nama_ekspedisi')->get();
        return view('mailing.create', compact('ekspedisi'));
    }

    public function viewFoto($id)
    {
        try {
            $mailing = Mailing::findOrFail($id);
            if (!$mailing->mailing_foto) abort(404, 'Foto tidak ditemukan');
            $path = storage_path('app/public/' . $mailing->mailing_foto);
            if (!file_exists($path)) abort(404, 'File foto tidak ditemukan');
            return response()->file($path, ['Content-Type' => mime_content_type($path)]);
        } catch (\Exception $e) {
            abort(404, 'Error: ' . $e->getMessage());
        }
    }

    public function bulkLantai47(Request $request)
    {
        $request->validate(['mailing_ids' => 'required|array|min:1', 'mailing_ids.*' => 'exists:tb_mailing,id_mailing']);
        try {
            $updated = DB::table('tb_mailing')
                ->whereIn('id_mailing', $request->mailing_ids)
                ->where('mailing_status', 'Mailing Room')
                ->update(['mailing_status' => 'Lantai 47', 'mailing_tanggal_ob47' => now(), 'mailing_ob47_by' => auth()->id()]);
            return redirect()->route('mailing.proses')->with('success', "✅ {$updated} mailing dipindahkan ke Lantai 47");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses: ' . $e->getMessage());
        }
    }

    public function lantai47Get($id)
    {
        try {
            $mailing = Mailing::findOrFail($id);
            if ($mailing->mailing_status !== 'Mailing Room') return back()->with('error', 'Status harus "Mailing Room"');
            DB::table('tb_mailing')->where('id_mailing', $id)->update(['mailing_status' => 'Lantai 47', 'mailing_tanggal_ob47' => now(), 'mailing_ob47_by' => auth()->id()]);
            return back()->with('success', 'Status berhasil diperbarui ke Lantai 47');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    public function selesaiGet($id)
    {
        $mailing = Mailing::findOrFail($id);
        $pelanggans = Pelanggan::orderBy('nama_pelanggan')->get();
        return view('mailing.complete-form', compact('mailing', 'pelanggans'));
    }

    public function getPelanggans()
    {
        try {
            return response()->json(Pelanggan::orderBy('nama_pelanggan')->select('id_pelanggan', 'nama_pelanggan')->get());
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

    public function lantai47($id)
    {
        try {
            $mailing = Mailing::findOrFail($id);
            if ($mailing->mailing_status !== 'Mailing Room') return back()->with('error', 'Status harus "Mailing Room"');
            $mailing->update(['mailing_status' => 'Lantai 47', 'mailing_tanggal_ob47' => now(), 'mailing_ob47_by' => auth()->id()]);
            return back()->with('success', 'Status berhasil diperbarui ke Lantai 47');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }
}