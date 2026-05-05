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
        // Validasi type
        if (!in_array($type, ['foto_barang', 'gambar_akhir'])) {
            abort(404, 'Tipe file tidak valid');
        }
        
        // Path file
        $path = "messenger/{$type}/{$filename}";
        
        // Cek apakah file ada di private storage
        if (!Storage::disk('private')->exists($path)) {
            abort(404, 'File tidak ditemukan');
        }
        
        // Validasi akses user
        $user = Auth::user();
        $access = DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->first();
        
        $hasAccessAll = false;
        if ($access && isset($access->akses_messenger_all) && (int)$access->akses_messenger_all === 1) {
            $hasAccessAll = true;
        }
        
        // Jika tidak punya akses semua, cek kepemilikan transaksi
        if (!$hasAccessAll) {
            // Cari transaksi berdasarkan filename
            $transaksi = DB::table('tb_transaksi')
                ->where($type, $filename)
                ->first();
            
            if ($transaksi) {
                // Cari pelanggan yang terkait dengan user login
                $pelanggan = DB::table('tb_pelanggan')
                    ->where('id_login', Auth::id())
                    ->first();
                
                // Jika user bukan pemilik transaksi, tolak akses
                if ($pelanggan && $transaksi->pengirim != $pelanggan->id_pelanggan) {
                    abort(403, 'Anda tidak memiliki akses ke file ini');
                }
            } else {
                abort(404, 'Transaksi tidak ditemukan');
            }
        }
        
        // Return file dari private storage
        $filePath = Storage::disk('private')->path($path);
        $mimeType = Storage::disk('private')->mimeType($path);
        
        return response()->file($filePath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $filename . '"'
        ]);
    }

    /* =====================================================
     |  INDEX (LIST UTAMA) - MEMBUTUHKAN: status_messenger = 1
     ===================================================== */
    public function index(Request $request)
    {
        // Cek akses messenger_all
        $user = Auth::user();
        $access = DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->first();
        
        $hasAccessAll = false;
        if ($access && isset($access->akses_messenger_all) && (int)$access->akses_messenger_all === 1) {
            $hasAccessAll = true;
        }

        // Jika punya akses semua, ambil semua pelanggan untuk filter
        if ($hasAccessAll) {
            $pelangganList = DB::table('tb_pelanggan')->get();
        }

        // Query dasar
        $query = DB::table('tb_transaksi as t')
            ->leftJoin('tb_pelanggan as p', 'p.id_pelanggan', '=', 't.pengirim')
            ->select(
                't.*',
                'p.nama_pelanggan as nama_pengirim',
                'p.no_hp_pelanggan as hp_pengirim'
            );

        // FILTER BERDASARKAN AKSES
        if (!$hasAccessAll) {
            // Cari pelanggan yang terkait dengan user login
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

            // Hanya tampilkan transaksi user tersebut
            $query->where('t.pengirim', $pelanggan->id_pelanggan);
        } else {
            // Jika punya akses semua, bisa filter by pengirim (opsional)
            if ($request->filled('pengirim') && $request->pengirim !== 'all') {
                $query->where('t.pengirim', $request->pengirim);
            }
        }

        // SEARCH
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

        // STATUS
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('t.status', $request->status);
        }

        // DATE
        if ($request->filled('date')) {
            $query->whereDate('t.created_at', $request->date);
        }

        // ORDER & PAGINATE
        $transaksi = $query
            ->orderByDesc('t.created_at')
            ->paginate(10)
            ->withQueryString();

        // Data untuk view
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
     |  PROSES (STATUS DIPROSES) - UNTUK KURIR
     |  MEMBUTUHKAN: proses_messenger = 1
     ===================================================== */
    public function proses(Request $request)
    {
        // Cari data kurir yang sedang login
        $kurir = DB::table('tb_pelanggan')
            ->where('id_login', Auth::id())
            ->first();
        
        if (!$kurir) {
            return back()->with('error', 'Data kurir tidak ditemukan.');
        }
        
        $kurir_id = $kurir->id_pelanggan;

        // Cek apakah user memiliki akses semua data
        $user = Auth::user();
        $access = DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->first();
        
        $hasAccessAll = false;
        if ($access && isset($access->akses_messenger_all) && (int)$access->akses_messenger_all === 1) {
            $hasAccessAll = true;
        }

        // Query dasar
        $query = DB::table('tb_transaksi as t')
            ->leftJoin('tb_pelanggan as p', 'p.id_pelanggan', '=', 't.pengirim')
            ->select(
                't.*',
                'p.nama_pelanggan as nama_pengirim',
                'p.no_hp_pelanggan as hp_pengirim',
                DB::raw('(SELECT nama_pelanggan FROM tb_pelanggan WHERE id_pelanggan = t.kurir) as nama_kurir')
            )
            ->whereNotIn('t.status', ['Terkirim', 'Ditolak', 'Batal']);

        // FILTER BERDASARKAN HAK AKSES
        if (!$hasAccessAll) {
            // Jika tidak punya akses semua, hanya tampilkan transaksi yang diambil oleh kurir ini
            $query->where(function ($q) use ($kurir_id) {
                $q->where('t.kurir', $kurir_id) // Transaksi yang diambil oleh kurir ini
                  ->orWhere('t.kurir', 0); // Atau transaksi yang belum ada kurirnya
            });
        }

        // SEARCH (opsional)
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
     |  DETAIL - MEMBUTUHKAN: detail_messenger = 1
     ===================================================== */
    public function detail($id)
    {
        $transaksi = DB::table('tb_transaksi')
            ->where('no_transaksi', $id)
            ->first();

        // ⛔ TRANSAKSI TIDAK DITEMUKAN
        if (!$transaksi) {
            abort(403, 'Anda tidak memiliki akses ke transaksi ini');
        }

        // Cek akses: jika tidak punya akses semua, cek apakah user adalah pemilik transaksi
        $user = Auth::user();
        $access = DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->first();
        
        $hasAccessAll = false;
        if ($access && isset($access->akses_messenger_all) && (int)$access->akses_messenger_all === 1) {
            $hasAccessAll = true;
        }

        if (!$hasAccessAll) {
            // Cari pelanggan yang terkait dengan user login
            $pelanggan = DB::table('tb_pelanggan')
                ->where('id_login', Auth::id())
                ->first();

            // Jika user bukan pemilik transaksi, tolak akses
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

        // Tambahkan URL untuk file
        $transaksi->foto_barang_url = $this->getFileUrl($transaksi->foto_barang, 'foto_barang');
        $transaksi->gambar_akhir_url = $this->getFileUrl($transaksi->gambar_akhir, 'gambar_akhir');

        return view('messenger.detail', compact(
            'transaksi',
            'pengirim',
            'kurir'
        ));
    }

    /* =====================================================
     |  REQUEST FORM - MEMBUTUHKAN: request_messenger = 1
     ===================================================== */
    public function request()
    {
        return view('messenger.request');
    }

    /* =====================================================
     |  PRINT - MEMBUTUHKAN: detail_messenger = 1
     ===================================================== */
    public function print($no_transaksi)
    {
        $transaksi = TransaksiMessenger::with('user')->where('no_transaksi', $no_transaksi)->firstOrFail();
        
        // Cek akses untuk print
        $user = Auth::user();
        $access = DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->first();
        
        $hasAccessAll = false;
        if ($access && isset($access->akses_messenger_all) && (int)$access->akses_messenger_all === 1) {
            $hasAccessAll = true;
        }

        if (!$hasAccessAll) {
            // Cari pelanggan yang terkait dengan user login
            $pelanggan = DB::table('tb_pelanggan')
                ->where('id_login', Auth::id())
                ->first();

            // Jika user bukan pemilik transaksi, tolak akses
            if ($pelanggan && $transaksi->pengirim != $pelanggan->id_pelanggan) {
                abort(403, 'Anda tidak memiliki akses untuk mencetak transaksi ini');
            }
        }

        // Tambahkan URL untuk file
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
     |  ANTAR PENGIRIMAN - CATAT KURIR
     |  MEMBUTUHKAN: proses_messenger = 1
     ===================================================== */
    public function antar($no_transaksi)
    {
        // Cari data kurir yang sedang login
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

        // hanya boleh dari status awal
        if (!in_array($trx->status, ['Belum Terkirim', 'Pengiriman Dibuat'])) {
            return back()->with('error', 'Status tidak valid');
        }

        $waktu = $trx->waktu ?? '';

        // Tambah timeline Proses Pengiriman (tanpa nama kurir di timeline)
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
     |  TOLAK PENGIRIMAN - MEMBUTUHKAN: proses_messenger = 1
     ===================================================== */
    public function tolak(Request $request, $no_transaksi)
    {
        // Validasi
        $request->validate([
            'alasan_tolak' => 'required|string|max:500'
        ]);

        // Cari data kurir yang sedang login
        $kurir = DB::table('tb_pelanggan')
            ->where('id_login', Auth::id())
            ->first();

        if (!$kurir) {
            return back()->with('error', 'Data kurir tidak ditemukan. Silakan login ulang.');
        }

        // Cari transaksi
        $trx = DB::table('tb_transaksi')
            ->where('no_transaksi', $no_transaksi)
            ->first();

        if (!$trx) {
            return back()->with('error', 'Transaksi tidak ditemukan');
        }

        // CEK STATUS: Boleh tolak untuk status yang belum selesai
        $allowedStatus = ['Belum Terkirim', 'Pengiriman Dibuat', 'Proses Pengiriman'];
        if (!in_array($trx->status, $allowedStatus)) {
            return back()->with('error', 'Status tidak valid untuk ditolak.');
        }

        // Jika sudah ada kurir, validasi hanya kurir tersebut yang bisa tolak
        if ($trx->kurir > 0 && $trx->kurir != $kurir->id_pelanggan) {
            return back()->with('error', 'Anda bukan kurir yang menangani pengiriman ini.');
        }

        try {
            // Jika belum ada kurir, catat kurir yang menolak
            if ($trx->kurir == 0) {
                DB::table('tb_transaksi')
                    ->where('no_transaksi', $no_transaksi)
                    ->update([
                        'kurir' => $kurir->id_pelanggan
                    ]);
            }
            
            // Append waktu dengan alasan tolak
            $waktu = $this->appendWaktu($trx->waktu, 'Ditolak');
            
            // Update database
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
     |  SELESAIKAN PENGIRIMAN - DENGAN note_penerima
     |  MEMBUTUHKAN: proses_messenger = 1
     ===================================================== */
    public function selesaikan(Request $request, $no_transaksi)
    {
        // Validasi file
        $request->validate([
            'gambar_akhir' => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'note_penerima' => 'nullable|string|max:500'
        ]);

        // Cari data kurir yang sedang login
        $kurir = DB::table('tb_pelanggan')
            ->where('id_login', Auth::id())
            ->first();

        if (!$kurir) {
            return back()->with('error', 'Data kurir tidak ditemukan. Silakan login ulang.');
        }

        // Cari transaksi
        $trx = DB::table('tb_transaksi')
            ->where('no_transaksi', $no_transaksi)
            ->first();

        if (!$trx) {
            return back()->with('error', 'Transaksi tidak ditemukan');
        }

        // Cek status
        if ($trx->status !== 'Proses Pengiriman') {
            return back()->with('error', 'Status tidak valid. Harus "Proses Pengiriman"');
        }

        // VALIDASI: Hanya kurir yang mengambil pengiriman ini yang bisa selesaikan
        if ($trx->kurir != $kurir->id_pelanggan) {
            return back()->with('error', 'Anda bukan kurir yang menangani pengiriman ini.');
        }

        try {
            // Upload file ke private storage
            $file = $request->file('gambar_akhir');
            
            // Generate nama file yang unik
            $fileName = 'bukti_' . time() . '_' . $no_transaksi . '.' . $file->getClientOriginalExtension();
            
            // Simpan ke storage private
            Storage::disk('private')->putFileAs(
                'messenger/gambar_akhir',
                $file,
                $fileName
            );
            
            // Append waktu
            $waktu = $this->appendWaktu($trx->waktu, 'Terkirim');
            
            // Update database dengan note_penerima
            DB::table('tb_transaksi')
                ->where('no_transaksi', $no_transaksi)
                ->update([
                    'status'       => 'Terkirim',
                    'gambar_akhir' => $fileName,
                    'note_penerima'     => $request->note_penerima,
                    'waktu'        => $waktu,
                    'updated_at'   => now()
                ]);

            return back()->with('success', '✅ Bukti berhasil diupload! Pengiriman telah selesai.');

        } catch (\Exception $e) {
            // Log error
            \Log::error('Gagal upload bukti:', [
                'no_transaksi' => $no_transaksi,
                'kurir_id' => $kurir->id_pelanggan,
                'error' => $e->getMessage()
            ]);
            
            return back()->with('error', '❌ Gagal mengupload gambar: ' . $e->getMessage());
        }
    }

    /* =====================================================
     |  CANCEL PENGIRIMAN - MEMBUTUHKAN: detail_messenger = 1
     ===================================================== */
    public function cancel(Request $request, $no_transaksi)
    {
        // Cari transaksi
        $trx = DB::table('tb_transaksi')
            ->where('no_transaksi', $no_transaksi)
            ->first();

        if (!$trx) {
            return back()->with('error', 'Transaksi tidak ditemukan');
        }

        // Cek akses: jika tidak punya akses semua, cek apakah user adalah pemilik transaksi
        $user = Auth::user();
        $access = DB::table('tb_access_menu')
            ->where('username', $user->username)
            ->first();
        
        $hasAccessAll = false;
        if ($access && isset($access->akses_messenger_all) && (int)$access->akses_messenger_all === 1) {
            $hasAccessAll = true;
        }

        if (!$hasAccessAll) {
            // Cari pelanggan yang terkait dengan user login
            $pelanggan = DB::table('tb_pelanggan')
                ->where('id_login', Auth::id())
                ->first();

            // Jika user bukan pemilik transaksi, tolak akses
            if (!$pelanggan || $trx->pengirim != $pelanggan->id_pelanggan) {
                abort(403, 'Anda tidak memiliki akses untuk membatalkan transaksi ini');
            }
        }

        // Validasi status
        if (!in_array($trx->status, ['Belum Terkirim', 'Pengiriman Dibuat'])) {
            return back()->with('error', 'Hanya transaksi dengan status "Belum Terkirim" atau "Pengiriman Dibuat" yang dapat dibatalkan');
        }

        try {
            // Append waktu
            $waktu = $this->appendWaktu($trx->waktu, 'Batal');
            
            // Update database
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
     |  STORE (BUAT PENGIRIMAN BARU) - MEMBUTUHKAN: request_messenger = 1
     ===================================================== */
    public function store(Request $request)
    {
        // Validasi
        $validator = Validator::make($request->all(), [
            'jenis_barang' => 'required|in:paket,dokumen',
            'deskripsi' => 'required|string|max:500',
            'alamat_asal' => 'required|string|max:255',
            'alamat_tujuan' => 'required|string|max:255',
            'penerima' => 'required|string|max:100',
            'no_hp_penerima' => 'required|string|max:13|regex:/^[0-9]{10,13}$/',
            'foto_barang' => 'required|file|max:20480|mimes:jpg,jpeg,png,pdf,doc,docx',
        ], [
            'no_hp_penerima.regex' => 'Nomor HP harus 10-13 digit angka',
            'foto_barang.max' => 'Ukuran file maksimal 20MB',
            'foto_barang.mimes' => 'Format file harus: JPG, PNG, PDF, DOC, DOCX'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $userId = Auth::id();
            $user = Auth::user();

            // Cari pelanggan berdasarkan id_login, buat baru jika tidak ada
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

            // Upload file ke private storage
            $fileName = null;
            if ($request->hasFile('foto_barang')) {
                $file = $request->file('foto_barang');
                
                // Generate nama file yang unik
                $fileName = 'msg_' . date('YmdHis') . '_' . rand(1000, 9999) . '.' . $file->getClientOriginalExtension();
                
                // Simpan ke storage private
                Storage::disk('private')->putFileAs(
                    'messenger/foto_barang',
                    $file,
                    $fileName
                );
                
                Log::info('File saved to private storage', [
                    'path' => 'messenger/foto_barang/' . $fileName,
                    'filename' => $fileName,
                    'size' => $file->getSize()
                ]);
            }

            // Nomor transaksi & waktu
            $noTransaksi = 'GA' . date('YmdHis');
            $waktu = "Pengiriman Dibuat &nbsp;&nbsp;(" . date('d-m-Y H:i:s') . ")";

            // Insert data
            DB::table('tb_transaksi')->insert([
                'no_transaksi' => $noTransaksi,
                'pengirim' => $pelanggan->id_pelanggan,
                'alamat_asal' => $request->alamat_asal,
                'alamat_tujuan' => $request->alamat_tujuan,
                'penerima' => $request->penerima,
                'no_hp_penerima' => $request->no_hp_penerima,
                'nama_barang' => $request->jenis_barang,
                'deskripsi' => $request->deskripsi,
                'foto_barang' => $fileName,
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
            
            // Hapus file jika ada error
            if (isset($fileName) && Storage::disk('private')->exists('messenger/foto_barang/' . $fileName)) {
                Storage::disk('private')->delete('messenger/foto_barang/' . $fileName);
            }

            return redirect()->back()
                ->with('error', 'Terjadi kesalahan: ' . $e->getMessage())
                ->withInput();
        }
    }
}