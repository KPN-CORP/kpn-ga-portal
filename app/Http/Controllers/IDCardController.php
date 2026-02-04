<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RequestIdCard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class IDCardController extends Controller
{
    /**
     * Helper untuk mengecek akses proses
     */
    private function canProcessIDCard()
    {
        $user = Auth::user();
        return $user->username == 'admin' || 
               DB::table('tb_access_menu')
                   ->where('username', $user->username)
                   ->where('proses_idcard', 1)
                   ->exists();
    }

    public function index(Request $req) 
    {
        // Cek apakah user memiliki akses khusus dari tb_access_menu
        $hasSpecialAccess = $this->canProcessIDCard();
        
        $bisnisUnits = DB::table('tb_bisnis_unit')->get();

        $query = RequestIdCard::orderBy('created_at','desc');
        
        // Jika tidak punya akses khusus, hanya tampilkan data user sendiri
        if (!$hasSpecialAccess) {
            $query->where('user_id', Auth::id());
        }

        if ($req->search) {
            $query->where(function($q) use ($req) {
                $q->where('nama', 'like', "%{$req->search}%")
                  ->orWhere('nik', 'like', "%{$req->search}%")
                  ->orWhere('kategori', 'like', "%{$req->search}%")
                  ->orWhere('nomor_kartu', 'like', "%{$req->search}%");
            });
        }

        if ($req->status && $req->status != 'all') {
            $query->where('status', $req->status);
        }

        // Filter bisnis unit hanya untuk user dengan akses khusus
        if ($hasSpecialAccess && $req->bisnis_unit_id && $req->bisnis_unit_id != 'all') {
            $query->where('bisnis_unit_id', $req->bisnis_unit_id);
        }

        if ($req->kategori && $req->kategori != 'all') {
            $query->where('kategori', $req->kategori);
        }

        $perPage = $req->get('per_page', 10);
        $data = $query->paginate($perPage);

        $statusLabels = [
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak'
        ];

        // Kategori labels untuk filter
        $kategoriLabels = [
            'karyawan_baru' => 'Karyawan Baru',
            'karyawan_mutasi' => 'Karyawan Mutasi',
            'ganti_kartu' => 'Ganti Kartu',
            'magang' => 'Magang',
            'magang_extend' => 'Magang Extend'
        ];

        return view('idcard.list', [
            'data' => $data,
            'bisnisUnits' => $bisnisUnits,
            'statusLabels' => $statusLabels,
            'kategoriLabels' => $kategoriLabels,
            'hasSpecialAccess' => $hasSpecialAccess
        ]);
    }

    public function create() {
        $bisnisUnits = DB::table('tb_bisnis_unit')->get();
        return view('idcard.request', compact('bisnisUnits'));
    }

    public function store(Request $req) 
    {
        // Set max file size - TAMBAHKAN UNTUK HANDLE 10MB
        ini_set('upload_max_filesize', '50M');
        ini_set('post_max_size', '55M');
        ini_set('max_execution_time', '300');
        
        \Log::info('ID Card Store Request:', $req->all());
        \Log::info('Kategori selected: ' . $req->kategori);
        
        // VALIDASI BERDASARKAN KATEGORI
        $validationRules = [
            'nik' => 'required|string|max:50',
            'nama' => 'required|string|max:100',
            'kategori' => 'required|in:karyawan_baru,karyawan_mutasi,ganti_kartu,magang,magang_extend',
            'bisnis_unit_id' => 'required|exists:tb_bisnis_unit,id_bisnis_unit',
            'keterangan' => 'required|string|max:255'
        ];
        
        $kategori = $req->kategori;
        
        // Kategori yang memerlukan tanggal join dan foto
        if (in_array($kategori, ['karyawan_baru', 'karyawan_mutasi', 'ganti_kartu'])) {
            $validationRules['tanggal_join'] = 'required|date';
            $validationRules['foto'] = 'required|image|mimes:jpg,jpeg,png|max:10240'; // 10MB
        }
        
        // Kategori yang memerlukan masa berlaku dan sampai tanggal
        if (in_array($kategori, ['magang', 'magang_extend'])) {
            $validationRules['masa_berlaku'] = 'required|date';
            $validationRules['sampai_tanggal'] = 'required|date|after:masa_berlaku';
            
            // PERBAIKAN: Untuk magang_extend, nomor kartu tidak harus unique
            if ($kategori === 'magang') {
                $validationRules['nomor_kartu'] = 'required|string|max:50|unique:request_idcard,nomor_kartu';
            } else {
                $validationRules['nomor_kartu'] = 'required|string|max:50';
            }
        }
        
        // Validasi NIK unik (untuk mencegah duplikat)
        // PERBAIKAN: Untuk magang_extend, NIK tidak harus unique (karena extend dari data lama)
        if ($kategori !== 'magang_extend') {
            $validationRules['nik'] = 'required|string|max:50|unique:request_idcard,nik';
        }
        
        // Khusus ganti kartu memerlukan bukti bayar
        if ($kategori === 'ganti_kartu') {
            $validationRules['bukti_bayar'] = 'required|mimes:jpg,jpeg,png,pdf|max:10240'; // 10MB
        }
        
        // Custom error messages
        $customMessages = [
            'foto.max' => 'Ukuran foto maksimal 10MB. Kompres foto Anda terlebih dahulu.',
            'bukti_bayar.max' => 'Ukuran bukti bayar maksimal 10MB. Kompres file Anda terlebih dahulu.',
            'foto.image' => 'File harus berupa gambar (JPG, JPEG, PNG)',
            'nik.unique' => 'NIK sudah terdaftar. Untuk Magang Extend, gunakan NIK yang sama dengan data sebelumnya.',
            'nomor_kartu.unique' => 'Nomor kartu sudah digunakan. Untuk Magang Extend, gunakan nomor kartu yang sama.',
            'sampai_tanggal.after' => 'Sampai Tanggal harus setelah Masa Berlaku.',
        ];
        
        $validator = Validator::make($req->all(), $validationRules, $customMessages);
        
        if ($validator->fails()) {
            \Log::error('Validation failed: ', $validator->errors()->toArray());
            return back()->withErrors($validator)->withInput();
        }
        
        try {
            // UPLOAD FOTO - untuk kategori yang memerlukan foto
            $filename = null;
            if (in_array($kategori, ['karyawan_baru', 'karyawan_mutasi', 'ganti_kartu']) && $req->hasFile('foto')) {
                $foto = $req->file('foto');
                $filename = 'foto_' . time() . '_' . uniqid() . '.' . $foto->getClientOriginalExtension();
                
                \Log::info("=== UPLOAD FOTO DEBUG ===");
                \Log::info("Original filename: " . $foto->getClientOriginalName());
                \Log::info("Generated filename: " . $filename);
                
                // Simpan menggunakan Storage facade dengan disk 'private'
                $path = $foto->storeAs('idcard/foto', $filename, 'private');
                
                \Log::info("Storage path: {$path}");
                
                // Verifikasi file tersimpan
                $disk = Storage::disk('private');
                if ($disk->exists('idcard/foto/' . $filename)) {
                    \Log::info("✓ File successfully saved");
                } else {
                    \Log::error("✗ File NOT saved");
                }
                
                \Log::info("=== END UPLOAD DEBUG ===");
            }
            
            // UPLOAD BUKTI BAYAR - khusus ganti kartu
            $buktiBayarName = null;
            if ($kategori === 'ganti_kartu' && $req->hasFile('bukti_bayar')) {
                $buktiBayar = $req->file('bukti_bayar');
                $ext = $buktiBayar->getClientOriginalExtension();
                $buktiBayarName = 'bukti_' . time() . '_' . uniqid() . '.' . $ext;
                
                \Log::info("=== UPLOAD BUKTI BAYAR DEBUG ===");
                \Log::info("Original filename: " . $buktiBayar->getClientOriginalName());
                \Log::info("Generated filename: " . $buktiBayarName);
                
                // Simpan menggunakan Storage facade dengan disk 'private'
                $path = $buktiBayar->storeAs('idcard/bukti_bayar', $buktiBayarName, 'private');
                
                \Log::info("Storage path: {$path}");
                
                // Verifikasi file tersimpan
                $disk = Storage::disk('private');
                if ($disk->exists('idcard/bukti_bayar/' . $buktiBayarName)) {
                    \Log::info("✓ File successfully saved");
                } else {
                    \Log::error("✗ File NOT saved");
                }
                
                \Log::info("=== END BUKTI BAYAR DEBUG ===");
            }
            
            // DATA UNTUK DISIMPAN - SESUAI STRUKTUR DATABASE
            $dataToCreate = [
                'nik' => $req->nik,
                'nama' => $req->nama,
                'kategori' => $kategori,
                'bisnis_unit_id' => $req->bisnis_unit_id,
                'tanggal_join' => in_array($kategori, ['karyawan_baru', 'karyawan_mutasi', 'ganti_kartu']) ? $req->tanggal_join : null,
                'masa_berlaku' => in_array($kategori, ['magang', 'magang_extend']) ? $req->masa_berlaku : null,
                'sampai_tanggal' => in_array($kategori, ['magang', 'magang_extend']) ? $req->sampai_tanggal : null,
                'nomor_kartu' => in_array($kategori, ['magang', 'magang_extend']) ? $req->nomor_kartu : null,
                'foto' => $filename,
                'bukti_bayar' => $buktiBayarName,
                'keterangan' => $req->keterangan,
                'status' => 'pending',
                'user_id' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            \Log::info("Data to create for kategori {$kategori}: ", $dataToCreate);
            
            DB::beginTransaction();
            
            try {
                $requestIdCard = RequestIdCard::create($dataToCreate);
                $idRequest = $requestIdCard->id;
                
                DB::table('request_idcard_logs')->insert([
                    'request_id' => $idRequest,
                    'action' => 'created',
                    'action_by' => Auth::id(),
                    'notes' => 'Request ID Card dibuat - Kategori: ' . $kategori,
                    'created_at' => now()
                ]);
                
                DB::commit();
                
                return redirect()->route('idcard')->with('success', 'Request ID Card berhasil dibuat!');
                
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error("Error saving to database: " . $e->getMessage());
                return back()->with('error', 'Gagal menyimpan ke database: ' . $e->getMessage())->withInput();
            }
            
        } catch (\Exception $e) {
            \Log::error("Error in store method: " . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan request: ' . $e->getMessage())->withInput();
        }
    }

    public function detail($id) {
        // AMBIL DATA DENGAN JOIN UNTUK approved_by DAN rejected_by
        $data = DB::table('request_idcard')
            ->select(
                'request_idcard.*', 
                'users.name as user_name',
                'approved_user.name as approved_by_name',
                'rejected_user.name as rejected_by_name'
            )
            ->leftJoin('users', 'request_idcard.user_id', '=', 'users.id')
            ->leftJoin('users as approved_user', 'request_idcard.approved_by', '=', 'approved_user.id')
            ->leftJoin('users as rejected_user', 'request_idcard.rejected_by', '=', 'rejected_user.id')
            ->where('request_idcard.id', $id)
            ->first();

        if (!$data) {
            abort(404);
        }
        
        // CEK AKSES: user hanya bisa lihat detail data mereka sendiri
        // kecuali punya akses khusus di tb_access_menu
        $canView = false;
        
        // Cek apakah user punya akses khusus
        $hasSpecialAccess = $this->canProcessIDCard();
        
        if ($hasSpecialAccess) {
            $canView = true;
        } elseif ($data->user_id == Auth::id()) {
            // User bisa lihat data mereka sendiri
            $canView = true;
        }
        
        if (!$canView) {
            return redirect()->route('idcard')->with('error', 'Anda tidak memiliki akses untuk melihat detail ini.');
        }

        $bisnisUnit = DB::table('tb_bisnis_unit')
            ->where('id_bisnis_unit', $data->bisnis_unit_id)
            ->first();

        $data->bisnis_unit_nama = $bisnisUnit->nama_bisnis_unit ?? '-';
        
        // Konversi kategori ke label yang lebih user-friendly
        $kategoriLabels = [
            'karyawan_baru' => 'Karyawan Baru',
            'karyawan_mutasi' => 'Karyawan Mutasi',
            'ganti_kartu' => 'Ganti Kartu',
            'magang' => 'Magang',
            'magang_extend' => 'Magang Extend'
        ];
        $data->kategori_label = $kategoriLabels[$data->kategori] ?? $data->kategori;
        
        // Ambil logs
        $logs = DB::table('request_idcard_logs')
            ->select('request_idcard_logs.*', 'users.name as action_by_name')
            ->leftJoin('users', 'request_idcard_logs.action_by', '=', 'users.id')
            ->where('request_idcard_logs.request_id', $id)
            ->orderBy('request_idcard_logs.created_at', 'desc')
            ->get();
        
        // CEK AKSES UNTUK PROSES (approve/reject)
        $canProses = $this->canProcessIDCard();
        
        $isPending = ($data->status == 'pending');
        
        return view('idcard.detail', compact('data', 'logs', 'canProses', 'isPending'));
    }

    /**
     * MENAMPILKAN FOTO - VERSI YANG DIPERBAIKI
     */
    public function photo($filename)
    {
        if (!Auth::check()) {
            abort(403, 'Unauthorized');
        }

        $user = Auth::user();
        $username = $user->username;
        
        \Log::info("=== PHOTO ACCESS ===");
        \Log::info("Requested file: {$filename}");
        \Log::info("User: {$username}");
        
        // Cari data yang memiliki file ini (untuk validasi akses)
        $data = DB::table('request_idcard')
            ->where(function($query) use ($filename) {
                $query->where('foto', $filename)
                      ->orWhere('bukti_bayar', $filename);
            })
            ->first();
        
        if (!$data) {
            \Log::error("File not found in database: {$filename}");
            abort(404, 'File tidak ditemukan');
        }
        
        // Cek apakah user punya akses
        $canView = false;
        
        // User bisa lihat jika:
        // 1. Punya akses khusus, atau
        // 2. Ini adalah data miliknya sendiri
        if ($this->canProcessIDCard() || $data->user_id == $user->id) {
            $canView = true;
        }
        
        if (!$canView) {
            \Log::warning("Unauthorized access attempt: {$filename} by {$username}");
            abort(403, 'Anda tidak memiliki akses untuk melihat file ini');
        }

        // Cari file menggunakan Storage facade
        $disk = Storage::disk('private');
        $paths = [
            'idcard/foto/' . $filename,
            'idcard/bukti_bayar/' . $filename,
        ];
        
        $foundPath = null;
        
        foreach ($paths as $path) {
            \Log::info("Checking path: {$path}");
            if ($disk->exists($path)) {
                $foundPath = $disk->path($path);
                \Log::info("✓ File found: {$path}");
                break;
            }
        }
        
        if (!$foundPath) {
            \Log::error("❌ File not found: {$filename}");
            abort(404, "File tidak ditemukan: {$filename}");
        }

        $mimeType = mime_content_type($foundPath);
        
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . basename($foundPath) . '"',
            'Cache-Control' => 'private, max-age=3600',
        ];

        \Log::info("Returning file with MIME: {$mimeType}");
        \Log::info("=== END PHOTO ACCESS ===");
        
        return response()->file($foundPath, $headers);
    }

    public function approve(Request $req, $id) {
        // VALIDASI AKSES
        if (!$this->canProcessIDCard()) {
            return back()->with('error', 'Anda tidak memiliki akses untuk melakukan approval!');
        }
        
        try {
            $item = RequestIdCard::findOrFail($id);
            
            if ($item->status != 'pending') {
                return back()->with('error', 'Request sudah diproses.');
            }
            
            DB::beginTransaction();
            
            try {
                // Untuk magang dan magang_extend, wajib isi nomor kartu jika belum ada
                if (in_array($item->kategori, ['magang', 'magang_extend']) && empty($item->nomor_kartu)) {
                    $validator = Validator::make($req->all(), [
                        'nomor_kartu' => 'required|string|max:50'
                    ], [
                        'nomor_kartu.required' => 'Nomor kartu wajib diisi untuk kategori ini'
                    ]);
                    
                    // Untuk magang_extend, nomor kartu tidak harus unique
                    if ($item->kategori === 'magang') {
                        $validator = Validator::make($req->all(), [
                            'nomor_kartu' => 'required|string|max:50|unique:request_idcard,nomor_kartu'
                        ], [
                            'nomor_kartu.required' => 'Nomor kartu wajib diisi untuk kategori ini',
                            'nomor_kartu.unique' => 'Nomor kartu sudah digunakan'
                        ]);
                    }
                    
                    if ($validator->fails()) {
                        return back()->withErrors($validator)->withInput();
                    }
                    
                    $item->nomor_kartu = $req->nomor_kartu;
                }
                
                $item->status = 'approved';
                $item->approved_by = Auth::id();
                $item->approved_at = now();
                $item->rejected_by = null;
                $item->rejected_at = null;
                $item->rejection_reason = null;
                $item->updated_at = now();
                
                $item->save();
                
                // Log activity
                DB::table('request_idcard_logs')->insert([
                    'request_id' => $id,
                    'action' => 'approved',
                    'action_by' => Auth::id(),
                    'notes' => 'Request ID Card disetujui',
                    'created_at' => now()
                ]);
                
                DB::commit();
                
                return back()->with('success', 'Request telah disetujui.');
                
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error("Error approving: " . $e->getMessage());
                return back()->with('error', 'Gagal menyetujui: ' . $e->getMessage());
            }
            
        } catch (\Exception $e) {
            \Log::error('Error in approve method: ' . $e->getMessage());
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function reject(Request $req, $id) {
        // VALIDASI AKSES
        if (!$this->canProcessIDCard()) {
            return back()->with('error', 'Anda tidak memiliki akses untuk melakukan penolakan!');
        }
        
        try {
            $item = RequestIdCard::findOrFail($id);
            
            if ($item->status != 'pending') {
                return back()->with('error', 'Request sudah diproses.');
            }
            
            $validator = Validator::make($req->all(), [
                'rejection_reason' => 'required|string|min:5|max:500'
            ], [
                'rejection_reason.required' => 'Alasan penolakan wajib diisi',
                'rejection_reason.min' => 'Alasan minimal 5 karakter',
                'rejection_reason.max' => 'Alasan maksimal 500 karakter'
            ]);
            
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
            
            DB::beginTransaction();
            
            try {
                $item->status = 'rejected';
                $item->rejection_reason = $req->rejection_reason;
                $item->rejected_by = Auth::id();
                $item->rejected_at = now();
                $item->approved_by = null;
                $item->approved_at = null;
                $item->updated_at = now();
                
                $item->save();
                
                // Log activity
                DB::table('request_idcard_logs')->insert([
                    'request_id' => $id,
                    'action' => 'rejected',
                    'action_by' => Auth::id(),
                    'notes' => 'Request ID Card ditolak: ' . $req->rejection_reason,
                    'created_at' => now()
                ]);
                
                DB::commit();
                
                return back()->with('error', 'Request telah ditolak.');
                
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error("Error rejecting: " . $e->getMessage());
                return back()->with('error', 'Gagal menolak: ' . $e->getMessage());
            }
            
        } catch (\Exception $e) {
            \Log::error('Error in reject method: ' . $e->getMessage());
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
    
    // Method untuk menampilkan logs (opsional)
    public function logs($id) {
        $logs = DB::table('request_idcard_logs')
            ->select('request_idcard_logs.*', 'users.name as action_by_name')
            ->leftJoin('users', 'request_idcard_logs.action_by', '=', 'users.id')
            ->where('request_idcard_logs.request_id', $id)
            ->orderBy('request_idcard_logs.created_at', 'desc')
            ->get();
            
        return response()->json($logs);
    }
}