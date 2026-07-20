<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\TransaksiMessenger;
use Illuminate\Pagination\LengthAwarePaginator;

class MessengerController extends Controller
{
    /* =====================================================
     |  HELPER: KOMPRESI GAMBAR
     ===================================================== */
    /**
     * Kompres file gambar ke ukuran maksimal tertentu
     *
     * @param string $sourcePath Path file asli (sudah tersimpan sementara)
     * @param string $destPath Path tujuan final
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

        // Jika ukuran awal sudah <= limit, copy saja
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
                return false; // tipe tidak didukung (misal PDF)
        }
        if (!$src) return false;

        // Konversi PNG ke JPEG (background putih)
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

    /* =====================================================
     |  HELPER: GET FILE URL (MELALUI ROUTE)
     ===================================================== */
    private function getFileUrl($filename, $type = 'foto_barang')
    {
        if (!$filename) return null;
        
        return route('messenger.file', [
            'type' => $type,
            'filename' => $filename
        ]);
    }
    
    /* =====================================================
     |  GET FILE (UNTUK MENGAKSES FILE PRIVATE)
     ===================================================== */
    public function getFile($type, $filename)
    {
        if (!in_array($type, ['foto_barang', 'gambar_akhir'])) {
            abort(404, 'Tipe file tidak valid');
        }
        
        $path = "messenger/{$type}/{$filename}";
        
        if (!Storage::disk('private')->exists($path)) {
            abort(404, 'File tidak ditemukan');
        }
        
        $user = Auth::user();
        $access = DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->first();
        
        $hasAccessAll = false;
        if ($access && isset($access->akses_messenger_all) && (int)$access->akses_messenger_all === 1) {
            $hasAccessAll = true;
        }
        
        if (!$hasAccessAll) {
            $transaksi = DB::table('tb_transaksi')
                ->where($type, $filename)
                ->first();
            
            if ($transaksi) {
                $pelanggan = DB::table('tb_pelanggan')
                    ->where('id_login', Auth::id())
                    ->first();
                
                if ($pelanggan && $transaksi->pengirim != $pelanggan->id_pelanggan) {
                    abort(403, 'Anda tidak memiliki akses ke file ini');
                }
            } else {
                abort(404, 'Transaksi tidak ditemukan');
            }
        }
        
        $filePath = Storage::disk('private')->path($path);
        $mimeType = Storage::disk('private')->mimeType($path);
        
        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $filename . '"'
        ]);
    }

    /* =====================================================
     |  INDEX (LIST UTAMA)
     ===================================================== */
    public function index(Request $request)
    {
        $user = Auth::user();
        $access = DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->first();
        
        $hasAccessAll = false;
        if ($access && isset($access->akses_messenger_all) && (int)$access->akses_messenger_all === 1) {
            $hasAccessAll = true;
        }

        if ($hasAccessAll) {
            $pelangganList = DB::table('tb_pelanggan')->get();
        }

        $query = DB::table('tb_transaksi as t')
            ->leftJoin('tb_pelanggan as p', 'p.id_pelanggan', '=', 't.pengirim')
            ->select(
                't.*',
                'p.nama_pelanggan as nama_pengirim',
                'p.no_hp_pelanggan as hp_pengirim'
            );

        if (!$hasAccessAll) {
            $pelanggan = DB::table('tb_pelanggan')
                ->where('id_login', Auth::id())
                ->first();

            if (!$pelanggan) {
                $transaksi = new LengthAwarePaginator([], 0, 10);
                return view('messenger.messenger', [
                    'transaksi' => $transaksi,
                    'pelanggan' => null,
                    'pelangganList' => [],
                    'hasAccessAll' => $hasAccessAll,
                    'filters' => []
                ]);
            }

            $query->where('t.pengirim', $pelanggan->id_pelanggan);
        } else {
            if ($request->filled('pengirim') && $request->pengirim !== 'all') {
                $query->where('t.pengirim', $request->pengirim);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('t.no_transaksi', 'like', "%$search%")
                  ->orWhere('t.nama_barang', 'like', "%$search%")
                  ->orWhere('t.penerima', 'like', "%$search%")
                  ->orWhere('t.deskripsi', 'like', "%$search%")
                  ->orWhere('p.nama_pelanggan', 'like', "%$search%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('t.status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('t.created_at', $request->date);
        }

        $transaksi = $query
            ->orderByDesc('t.created_at')
            ->paginate(10)
            ->withQueryString();

        $pelanggan = !$hasAccessAll ? DB::table('tb_pelanggan')
            ->where('id_login', Auth::id())
            ->first() : null;

        return view('messenger.messenger', [
            'transaksi' => $transaksi,
            'pelanggan' => $pelanggan,
            'pelangganList' => $hasAccessAll ? $pelangganList ?? [] : [],
            'hasAccessAll' => $hasAccessAll,
            'filters' => $request->all()
        ]);
    }

    /* =====================================================
     |  PROSES (UNTUK KURIR)
     ===================================================== */
    public function proses(Request $request)
    {
        $kurir = DB::table('tb_pelanggan')
            ->where('id_login', Auth::id())
            ->first();
        
        if (!$kurir) {
            return back()->with('error', 'Data kurir tidak ditemukan.');
        }
        
        $kurir_id = $kurir->id_pelanggan;

        $user = Auth::user();
        $access = DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->first();
        
        $hasAccessAll = false;
        if ($access && isset($access->akses_messenger_all) && (int)$access->akses_messenger_all === 1) {
            $hasAccessAll = true;
        }

        $query = DB::table('tb_transaksi as t')
            ->leftJoin('tb_pelanggan as p', 'p.id_pelanggan', '=', 't.pengirim')
            ->select(
                't.*',
                'p.nama_pelanggan as nama_pengirim',
                'p.no_hp_pelanggan as hp_pengirim',
                DB::raw('(SELECT nama_pelanggan FROM tb_pelanggan WHERE id_pelanggan = t.kurir) as nama_kurir')
            )
            ->whereNotIn('t.status', ['Terkirim', 'Ditolak', 'Batal']);

        if (!$hasAccessAll) {
            $query->where(function ($q) use ($kurir_id) {
                $q->where('t.kurir', $kurir_id)
                  ->orWhere('t.kurir', 0);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('t.no_transaksi', 'like', "%$search%")
                ->orWhere('t.nama_barang', 'like', "%$search%")
                ->orWhere('t.penerima', 'like', "%$search%");
            });
        }

        $transaksi = $query
            ->orderByDesc('t.created_at')
            ->get();

        return view('messenger.proses', [
            'transaksi' => $transaksi,
            'kurir' => $kurir,
            'kurir_id' => $kurir_id,
            'has_full_access' => $hasAccessAll
        ]);
    }

    /* =====================================================
     |  DETAIL
     ===================================================== */
    public function detail($id)
    {
        $transaksi = DB::table('tb_transaksi')
            ->where('no_transaksi', $id)
            ->first();

        if (!$transaksi) {
            abort(403, 'Anda tidak memiliki akses ke transaksi ini');
        }

        $user = Auth::user();
        $access = DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->first();
        
        $hasAccessAll = false;
        if ($access && isset($access->akses_messenger_all) && (int)$access->akses_messenger_all === 1) {
            $hasAccessAll = true;
        }

        // Selalu ambil data pelanggan (baris login saat ini) supaya bisa
        // dipakai di view untuk cek kepemilikan transaksi (mis. tombol "Kirim Ulang")
        $pelanggan = DB::table('tb_pelanggan')
            ->where('id_login', Auth::id())
            ->first();

        if (!$hasAccessAll) {
            if ($pelanggan && $transaksi->pengirim != $pelanggan->id_pelanggan) {
                abort(403, 'Anda tidak memiliki akses ke transaksi ini');
            }
        }

        $pengirim = null;
        $kurir = null;

        if ($transaksi->pengirim > 0) {
            $pengirim = DB::table('tb_pelanggan')
                ->select('nama_pelanggan', 'no_hp_pelanggan')
                ->where('id_pelanggan', $transaksi->pengirim)
                ->first();
        }

        if ($transaksi->kurir > 0) {
            $kurir = DB::table('tb_pelanggan')
                ->select('nama_pelanggan', 'no_hp_pelanggan')
                ->where('id_pelanggan', $transaksi->kurir)
                ->first();
        }

        $transaksi->foto_barang_url = $this->getFileUrl($transaksi->foto_barang, 'foto_barang');
        $transaksi->gambar_akhir_url = $this->getFileUrl($transaksi->gambar_akhir, 'gambar_akhir');

        return view('messenger.detail', compact(
            'transaksi',
            'pengirim',
            'kurir',
            'pelanggan',
            'hasAccessAll'
        ));
    }

    /* =====================================================
     |  REQUEST FORM
     ===================================================== */
    public function request()
    {
        return view('messenger.request');
    }

    /* =====================================================
     |  PRINT
     ===================================================== */
    public function print($no_transaksi)
    {
        $transaksi = TransaksiMessenger::with('user')->where('no_transaksi', $no_transaksi)->firstOrFail();
        
        $user = Auth::user();
        $access = DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->first();
        
        $hasAccessAll = false;
        if ($access && isset($access->akses_messenger_all) && (int)$access->akses_messenger_all === 1) {
            $hasAccessAll = true;
        }

        if (!$hasAccessAll) {
            $pelanggan = DB::table('tb_pelanggan')
                ->where('id_login', Auth::id())
                ->first();

            if ($pelanggan && $transaksi->pengirim != $pelanggan->id_pelanggan) {
                abort(403, 'Anda tidak memiliki akses untuk mencetak transaksi ini');
            }
        }

        $transaksi->foto_barang_url = $this->getFileUrl($transaksi->foto_barang, 'foto_barang');
        $transaksi->gambar_akhir_url = $this->getFileUrl($transaksi->gambar_akhir, 'gambar_akhir');

        $pdf = Pdf::loadView('messenger.print', compact('transaksi'))->setPaper('a4', 'portrait');

        return $pdf->download($transaksi->no_transaksi . '.pdf');
    }

    /* =====================================================
     |  HELPER: APPEND WAKTU
     ===================================================== */
    private function appendWaktu($old, $label)
    {
        $timestamp = date('d-m-Y H:i:s');
        $line = $label . ' &nbsp;&nbsp;(' . $timestamp . ')';
        return trim($old)
            ? $old . '<br>' . $line
            : $line;
    }

    /* =====================================================
     |  ANTAR PENGIRIMAN
     ===================================================== */
    public function antar($no_transaksi)
    {
        $kurir = DB::table('tb_pelanggan')
            ->where('id_login', Auth::id())
            ->first();

        if (!$kurir) {
            return back()->with('error', 'Data kurir tidak ditemukan. Silakan login ulang.');
        }

        $trx = DB::table('tb_transaksi')
            ->where('no_transaksi', $no_transaksi)
            ->first();

        if (!$trx) {
            return back()->with('error', 'Transaksi tidak ditemukan');
        }

        if (!in_array($trx->status, ['Belum Terkirim', 'Pengiriman Dibuat'])) {
            return back()->with('error', 'Status tidak valid');
        }

        $waktu = $trx->waktu ?? '';

        $waktu = $this->appendWaktu($waktu, 'Proses Pengiriman');

        DB::table('tb_transaksi')
            ->where('no_transaksi', $no_transaksi)
            ->update([
                'status'     => 'Proses Pengiriman',
                'kurir'      => $kurir->id_pelanggan,
                'waktu'      => $waktu,
                'updated_at' => now()
            ]);

        return back()->with('success', 'Pengiriman diproses oleh ' . $kurir->nama_pelanggan);
    }

    /* =====================================================
     |  TOLAK PENGIRIMAN
     ===================================================== */
    public function tolak(Request $request, $no_transaksi)
    {
        $request->validate([
            'alasan_tolak' => 'required|string|max:500'
        ]);

        $kurir = DB::table('tb_pelanggan')
            ->where('id_login', Auth::id())
            ->first();

        if (!$kurir) {
            return back()->with('error', 'Data kurir tidak ditemukan. Silakan login ulang.');
        }

        $trx = DB::table('tb_transaksi')
            ->where('no_transaksi', $no_transaksi)
            ->first();

        if (!$trx) {
            return back()->with('error', 'Transaksi tidak ditemukan');
        }

        $allowedStatus = ['Belum Terkirim', 'Pengiriman Dibuat', 'Proses Pengiriman'];
        if (!in_array($trx->status, $allowedStatus)) {
            return back()->with('error', 'Status tidak valid untuk ditolak.');
        }

        if ($trx->kurir > 0 && $trx->kurir != $kurir->id_pelanggan) {
            return back()->with('error', 'Anda bukan kurir yang menangani pengiriman ini.');
        }

        try {
            if ($trx->kurir == 0) {
                DB::table('tb_transaksi')
                    ->where('no_transaksi', $no_transaksi)
                    ->update([
                        'kurir' => $kurir->id_pelanggan
                    ]);
            }
            
            $waktu = $this->appendWaktu($trx->waktu, 'Ditolak');
            
            DB::table('tb_transaksi')
                ->where('no_transaksi', $no_transaksi)
                ->update([
                    'status'     => 'Ditolak',
                    'note_penerima'   => $request->alasan_tolak,
                    'waktu'      => $waktu,
                    'updated_at' => now()
                ]);

            return back()->with('success', '✅ Pengiriman telah ditolak.');

        } catch (\Exception $e) {
            \Log::error('Gagal menolak pengiriman:', [
                'no_transaksi' => $no_transaksi,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', '❌ Gagal menolak pengiriman: ' . $e->getMessage());
        }
    }

    /* =====================================================
     |  KEMBALIKAN (DOKUMEN BELUM TERSEDIA)
     ===================================================== */
    public function kembalikan($no_transaksi)
    {
        $kurir = DB::table('tb_pelanggan')
            ->where('id_login', Auth::id())
            ->first();

        if (!$kurir) {
            return back()->with('error', 'Data kurir tidak ditemukan. Silakan login ulang.');
        }

        $trx = DB::table('tb_transaksi')
            ->where('no_transaksi', $no_transaksi)
            ->first();

        if (!$trx) {
            return back()->with('error', 'Transaksi tidak ditemukan');
        }

        // Cek hak akses: admin (akses penuh) atau kurir yang berhak menangani transaksi ini
        $user = Auth::user();
        $access = DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->first();

        $hasAccessAll = false;
        if ($access && isset($access->akses_messenger_all) && (int)$access->akses_messenger_all === 1) {
            $hasAccessAll = true;
        }

        $canAccess = $hasAccessAll || $trx->kurir == 0 || $trx->kurir == $kurir->id_pelanggan;

        if (!$canAccess) {
            return back()->with('error', 'Anda tidak memiliki akses untuk transaksi ini.');
        }

        if (!in_array($trx->status, ['Belum Terkirim', 'Pengiriman Dibuat'])) {
            return back()->with('error', 'Status tidak valid. Harus "Belum Terkirim".');
        }

        try {
            $waktu = $this->appendWaktu($trx->waktu, 'Dokumen Belum Tersedia');

            DB::table('tb_transaksi')
                ->where('no_transaksi', $no_transaksi)
                ->update([
                    'status'     => 'Dokumen Belum Tersedia',
                    'kurir'      => $kurir->id_pelanggan,
                    'waktu'      => $waktu,
                    'updated_at' => now()
                ]);

            return back()->with('success', '✅ Transaksi dikembalikan. Menunggu dokumen tersedia dari pengirim.');

        } catch (\Exception $e) {
            \Log::error('Gagal mengembalikan transaksi:', [
                'no_transaksi' => $no_transaksi,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', '❌ Gagal mengembalikan transaksi: ' . $e->getMessage());
        }
    }

    /* =====================================================
     |  SELESAIKAN PENGIRIMAN - DENGAN KOMPRESI GAMBAR
     ===================================================== */
    public function selesaikan(Request $request, $no_transaksi)
    {
        $request->validate([
            'gambar_akhir' => 'required|image|mimes:jpg,jpeg,png|max:5120', // 5MB
            'note_penerima' => 'nullable|string|max:500'
        ]);

        $kurir = DB::table('tb_pelanggan')
            ->where('id_login', Auth::id())
            ->first();

        if (!$kurir) {
            return back()->with('error', 'Data kurir tidak ditemukan.');
        }

        $trx = DB::table('tb_transaksi')
            ->where('no_transaksi', $no_transaksi)
            ->first();

        if (!$trx) {
            return back()->with('error', 'Transaksi tidak ditemukan');
        }

        if ($trx->status !== 'Proses Pengiriman') {
            return back()->with('error', 'Status tidak valid. Harus "Proses Pengiriman"');
        }

        if ($trx->kurir != $kurir->id_pelanggan) {
            return back()->with('error', 'Anda bukan kurir yang menangani pengiriman ini.');
        }

        try {
            $file = $request->file('gambar_akhir');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            
            // Simpan sementara ke storage private
            $tempFileName = 'temp_' . time() . '_' . uniqid() . '.' . $extension;
            $tempPath = $file->storeAs('messenger/temp', $tempFileName, 'private');
            $fullTempPath = Storage::disk('private')->path($tempPath);
            
            // Tentukan nama final (hasil kompresi akan berekstensi .jpg)
            $finalFileName = 'bukti_' . time() . '_' . $no_transaksi . '.jpg';
            $finalPath = 'messenger/gambar_akhir/' . $finalFileName;
            $fullFinalPath = Storage::disk('private')->path($finalPath);
            
            // Kompres gambar
            $compressSuccess = $this->compressImage($fullTempPath, $fullFinalPath, 1.5);
            
            if ($compressSuccess) {
                // Hapus file sementara
                Storage::disk('private')->delete($tempPath);
                $savedFileName = $finalFileName;
            } else {
                // Jika gagal kompres, gunakan file asli (pindahkan dari temp ke final)
                $originalFinalName = 'bukti_' . time() . '_' . $no_transaksi . '.' . $extension;
                $originalFinalPath = 'messenger/gambar_akhir/' . $originalFinalName;
                Storage::disk('private')->move($tempPath, $originalFinalPath);
                $savedFileName = $originalFinalName;
            }
            
            $waktu = $this->appendWaktu($trx->waktu, 'Terkirim');
            
            DB::table('tb_transaksi')
                ->where('no_transaksi', $no_transaksi)
                ->update([
                    'status'       => 'Terkirim',
                    'gambar_akhir' => $savedFileName,
                    'note_penerima'     => $request->note_penerima,
                    'waktu'        => $waktu,
                    'updated_at'   => now()
                ]);

            return back()->with('success', '✅ Bukti berhasil diupload! Pengiriman telah selesai.');

        } catch (\Exception $e) {
            \Log::error('Gagal upload bukti:', [
                'no_transaksi' => $no_transaksi,
                'kurir_id' => $kurir->id_pelanggan,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', '❌ Gagal mengupload gambar: ' . $e->getMessage());
        }
    }

    /* =====================================================
     |  CANCEL PENGIRIMAN
     ===================================================== */
    public function cancel(Request $request, $no_transaksi)
    {
        $trx = DB::table('tb_transaksi')
            ->where('no_transaksi', $no_transaksi)
            ->first();

        if (!$trx) {
            return back()->with('error', 'Transaksi tidak ditemukan');
        }

        $user = Auth::user();
        $access = DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->first();
        
        $hasAccessAll = false;
        if ($access && isset($access->akses_messenger_all) && (int)$access->akses_messenger_all === 1) {
            $hasAccessAll = true;
        }

        if (!$hasAccessAll) {
            $pelanggan = DB::table('tb_pelanggan')
                ->where('id_login', Auth::id())
                ->first();

            if (!$pelanggan || $trx->pengirim != $pelanggan->id_pelanggan) {
                abort(403, 'Anda tidak memiliki akses untuk membatalkan transaksi ini');
            }
        }

        if (!in_array($trx->status, ['Belum Terkirim', 'Pengiriman Dibuat'])) {
            return back()->with('error', 'Hanya transaksi dengan status "Belum Terkirim" atau "Pengiriman Dibuat" yang dapat dibatalkan');
        }

        try {
            $waktu = $this->appendWaktu($trx->waktu, 'Batal');
            
            DB::table('tb_transaksi')
                ->where('no_transaksi', $no_transaksi)
                ->update([
                    'status'     => 'Batal',
                    'waktu'      => $waktu,
                    'updated_at' => now()
                ]);

            return back()->with('success', '✅ Transaksi berhasil dibatalkan.');

        } catch (\Exception $e) {
            \Log::error('Gagal membatalkan transaksi:', [
                'no_transaksi' => $no_transaksi,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', '❌ Gagal membatalkan transaksi: ' . $e->getMessage());
        }
    }

    /* =====================================================
     |  KIRIM ULANG (SETELAH DOKUMEN TERSEDIA)
     ===================================================== */
    public function kirimUlang($no_transaksi)
    {
        $trx = DB::table('tb_transaksi')
            ->where('no_transaksi', $no_transaksi)
            ->first();

        if (!$trx) {
            return back()->with('error', 'Transaksi tidak ditemukan');
        }

        $user = Auth::user();
        $access = DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->first();

        $hasAccessAll = false;
        if ($access && isset($access->akses_messenger_all) && (int)$access->akses_messenger_all === 1) {
            $hasAccessAll = true;
        }

        if (!$hasAccessAll) {
            $pelanggan = DB::table('tb_pelanggan')
                ->where('id_login', Auth::id())
                ->first();

            if (!$pelanggan || $trx->pengirim != $pelanggan->id_pelanggan) {
                abort(403, 'Anda tidak memiliki akses untuk mengirim ulang transaksi ini');
            }
        }

        if ($trx->status !== 'Dokumen Belum Tersedia') {
            return back()->with('error', 'Status tidak valid. Harus "Dokumen Belum Tersedia".');
        }

        try {
            $waktu = $this->appendWaktu($trx->waktu, 'Kirim Ulang (dokumen sudah tersedia)');

            DB::table('tb_transaksi')
                ->where('no_transaksi', $no_transaksi)
                ->update([
                    'status'     => 'Belum Terkirim',
                    'kurir'      => 0,
                    'waktu'      => $waktu,
                    'updated_at' => now()
                ]);

            return back()->with('success', '✅ Pengiriman dikirim ulang dan menunggu kurir mengambil kembali.');

        } catch (\Exception $e) {
            \Log::error('Gagal mengirim ulang transaksi:', [
                'no_transaksi' => $no_transaksi,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', '❌ Gagal mengirim ulang transaksi: ' . $e->getMessage());
        }
    }

    /* =====================================================
     |  STORE (BUAT PENGIRIMAN BARU) - DENGAN KOMPRESI FOTO BARANG
     ===================================================== */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jenis_barang' => 'required|in:paket,dokumen',
            'deskripsi' => 'required|string|max:500',
            'alamat_asal' => 'required|string|max:255',
            'alamat_tujuan' => 'required|string|max:255',
            'penerima' => 'required|string|max:100',
            'no_hp_penerima' => 'required|string|max:13|regex:/^[0-9]{10,13}$/',
            'foto_barang' => 'required|file|max:20480|mimes:jpg,jpeg,png,pdf,doc,docx', // 20MB untuk file pendukung
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $userId = Auth::id();
            $user = Auth::user();

            $pelanggan = DB::table('tb_pelanggan')->where('id_login', $userId)->first();
            if (!$pelanggan) {
                $pelangganId = DB::table('tb_pelanggan')->insertGetId([
                    'id_login' => $userId,
                    'nama_pelanggan' => $user->name ?? $user->username ?? 'User_' . $userId,
                    'username_pelanggan' => $user->username ?? 'user_' . $userId,
                    'password' => bcrypt('default123'),
                    'no_hp_pelanggan' => '0000000000',
                    'email_pelanggan' => $user->email ?? 'user' . $userId . '@example.com',
                    'gambar' => '',
                    'role_akses' => 'Pelanggan',
                    'bisnis_unit' => 'Default',
                    'departemen' => 'Default',
                    'pic' => $user->name ?? $user->username ?? 'User_' . $userId,
                    'lantai_aktif' => null,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                $pelanggan = DB::table('tb_pelanggan')->where('id_pelanggan', $pelangganId)->first();
            }

            $file = $request->file('foto_barang');
            $originalExtension = $file->getClientOriginalExtension();
            
            // Simpan sementara
            $tempFileName = 'temp_' . time() . '_' . uniqid() . '.' . $originalExtension;
            $tempPath = $file->storeAs('messenger/temp', $tempFileName, 'private');
            $fullTempPath = Storage::disk('private')->path($tempPath);
            
            // Tentukan nama final (hasil kompresi untuk gambar akan jadi .jpg)
            $finalFileName = 'msg_' . date('YmdHis') . '_' . rand(1000, 9999);
            $savedFileName = null;
            
            // Jika file adalah gambar (jpg, jpeg, png) -> kompres
            if (in_array($originalExtension, ['jpg', 'jpeg', 'png', 'webp'])) {
                $finalFileNameFull = $finalFileName . '.jpg';
                $finalPath = 'messenger/foto_barang/' . $finalFileNameFull;
                $fullFinalPath = Storage::disk('private')->path($finalPath);
                
                $compressSuccess = $this->compressImage($fullTempPath, $fullFinalPath, 1.5);
                
                if ($compressSuccess) {
                    Storage::disk('private')->delete($tempPath);
                    $savedFileName = $finalFileNameFull;
                } else {
                    // Fallback: gunakan file asli dengan ekstensi asli
                    $originalFinalName = $finalFileName . '.' . $originalExtension;
                    $originalFinalPath = 'messenger/foto_barang/' . $originalFinalName;
                    Storage::disk('private')->move($tempPath, $originalFinalPath);
                    $savedFileName = $originalFinalName;
                }
            } else {
                // File bukan gambar (PDF, DOC, DOCX) -> simpan asli tanpa kompresi
                $originalFinalName = $finalFileName . '.' . $originalExtension;
                $originalFinalPath = 'messenger/foto_barang/' . $originalFinalName;
                Storage::disk('private')->move($tempPath, $originalFinalPath);
                $savedFileName = $originalFinalName;
            }

            $mapsAsal = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($request->alamat_asal);
            $mapsTujuan = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($request->alamat_tujuan);

            $noTransaksi = 'GA' . date('YmdHis');
            $waktu = "Pengiriman Dibuat &nbsp;&nbsp;(" . date('d-m-Y H:i:s') . ")";

            DB::table('tb_transaksi')->insert([
                'no_transaksi' => $noTransaksi,
                'pengirim' => $pelanggan->id_pelanggan,
                'alamat_asal' => $request->alamat_asal,
                'maps_asal' => $mapsAsal,
                'alamat_tujuan' => $request->alamat_tujuan,
                'maps_tujuan' => $mapsTujuan,
                'penerima' => $request->penerima,
                'no_hp_penerima' => $request->no_hp_penerima,
                'nama_barang' => $request->jenis_barang,
                'deskripsi' => $request->deskripsi,
                'foto_barang' => $savedFileName,
                'status' => 'Belum Terkirim',
                'kurir' => 0,
                'waktu' => $waktu,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            return redirect()->route('messenger.index')
                ->with('success', '✅ Pengiriman berhasil disimpan! Nomor Transaksi: ' . $noTransaksi);

        } catch (\Exception $e) {
            Log::error('Store transaction error: ' . $e->getMessage());
            if (isset($savedFileName) && Storage::disk('private')->exists('messenger/foto_barang/' . $savedFileName)) {
                Storage::disk('private')->delete('messenger/foto_barang/' . $savedFileName);
            }
            if (isset($tempPath) && Storage::disk('private')->exists($tempPath)) {
                Storage::disk('private')->delete($tempPath);
            }
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage())->withInput();
        }
    }
}