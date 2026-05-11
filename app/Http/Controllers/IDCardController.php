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
     * Helper untuk mengecek akses proses (approve/reject/edit)
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

    // ==================== LIST ====================
    public function index(Request $req)
    {
        $hasSpecialAccess = $this->canProcessIDCard();
        $bisnisUnits = DB::table('tb_bisnis_unit')->get();
        $query = RequestIdCard::orderBy('created_at', 'desc');

        if (!$hasSpecialAccess) {
            $query->where('user_id', Auth::id());
        }

        if ($req->search) {
            $query->where(function ($q) use ($req) {
                $q->where('nama', 'like', "%{$req->search}%")
                  ->orWhere('nik', 'like', "%{$req->search}%")
                  ->orWhere('kategori', 'like', "%{$req->search}%");
            });
        }

        if ($req->nomor_kartu) {
            $query->where('nomor_kartu', 'like', "%{$req->nomor_kartu}%");
        }

        if ($req->status && $req->status != 'all') {
            $query->where('status', $req->status);
        }

        if ($hasSpecialAccess && $req->bisnis_unit_id && $req->bisnis_unit_id != 'all') {
            $query->where('bisnis_unit_id', $req->bisnis_unit_id);
        }

        if ($req->kategori && $req->kategori != 'all') {
            $query->where('kategori', $req->kategori);
        }

        if ($req->periode && $req->periode != 'all') {
            $today = now()->format('Y-m-d');
            switch ($req->periode) {
                case 'masa_aktif':
                    $query->where('masa_berlaku', '<=', $today)
                          ->where('sampai_tanggal', '>=', $today);
                    break;
                case 'masa_tidak_aktif':
                    $query->where(function ($q) use ($today) {
                        $q->where('masa_berlaku', '>', $today)
                          ->orWhere('sampai_tanggal', '<', $today);
                    });
                    break;
                case 'masa_habis_segera':
                    $thirtyDaysFromNow = now()->addDays(30)->format('Y-m-d');
                    $query->where('sampai_tanggal', '>=', $today)
                          ->where('sampai_tanggal', '<=', $thirtyDaysFromNow);
                    break;
            }
        }

        $perPage = $req->get('per_page', 10);
        $data = $query->paginate($perPage)->withQueryString();

        $statusLabels = [
            'pending'  => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak'
        ];

        $kategoriLabels = [
            'karyawan_baru'   => 'Karyawan Baru',
            'karyawan_mutasi' => 'Karyawan Mutasi',
            'ganti_kartu'     => 'Ganti Kartu',
            'magang'          => 'Magang',
            'magang_extend'   => 'Magang Extend'
        ];

        return view('idcard.list', compact('data', 'bisnisUnits', 'statusLabels', 'kategoriLabels', 'hasSpecialAccess'));
    }

    // ==================== CREATE FORM ====================
    public function create()
    {
        $bisnisUnits = DB::table('tb_bisnis_unit')->get();
        return view('idcard.request', compact('bisnisUnits'));
    }

    // ==================== STORE (dengan validasi NIK yang memperbolehkan duplicate asal tidak pending) ====================
    public function store(Request $req)
    {
        ini_set('upload_max_filesize', '50M');
        ini_set('post_max_size', '55M');
        ini_set('max_execution_time', '300');

        \Log::info('ID Card Store Request:', $req->all());

        $kategori = $req->kategori;

        $validationRules = [
            'nik'      => 'required|string|max:50',
            'nama'     => 'required|string|max:100',
            'kategori' => 'required|in:karyawan_baru,karyawan_mutasi,ganti_kartu,magang,magang_extend',
            'bisnis_unit_id' => 'required|exists:tb_bisnis_unit,id_bisnis_unit',
            'keterangan' => 'required|string|max:255'
        ];

        // Validasi NIK: hanya dilarang jika ada request PENDING dengan NIK yang sama (kecuali magang_extend)
        if ($kategori !== 'magang_extend') {
            $validationRules['nik'] = [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    $pendingExists = RequestIdCard::where('nik', $value)
                        ->where('status', 'pending')
                        ->exists();
                    if ($pendingExists) {
                        $fail('Masih ada request ID Card dengan NIK ini yang sedang menunggu diproses. Selesaikan request sebelumnya terlebih dahulu.');
                    }
                }
            ];
        }

        if (in_array($kategori, ['karyawan_baru', 'karyawan_mutasi', 'ganti_kartu'])) {
            $validationRules['tanggal_join'] = 'required|date';
            $validationRules['foto'] = 'required|image|mimes:jpg,jpeg,png|max:10240';
        }

        if (in_array($kategori, ['magang', 'magang_extend'])) {
            $validationRules['masa_berlaku'] = 'required|date';
            $validationRules['sampai_tanggal'] = 'required|date|after:masa_berlaku';

            if ($kategori === 'magang') {
                $validationRules['nomor_kartu'] = [
                    'required',
                    'string',
                    'max:50',
                    function ($attribute, $value, $fail) {
                        $pendingExists = RequestIdCard::where('nomor_kartu', $value)
                            ->where('status', 'pending')
                            ->exists();
                        if ($pendingExists) {
                            $fail('Nomor kartu sudah digunakan pada request magang yang masih pending.');
                        }
                    }
                ];
            } else {
                $validationRules['nomor_kartu'] = 'required|string|max:50';
            }
        }

        if ($kategori === 'ganti_kartu') {
            $validationRules['bukti_bayar'] = 'required|mimes:jpg,jpeg,png,pdf|max:10240';
        }

        $customMessages = [
            'foto.max' => 'Ukuran foto maksimal 10MB. Kompres foto Anda terlebih dahulu.',
            'bukti_bayar.max' => 'Ukuran bukti bayar maksimal 10MB.',
            'foto.image' => 'File harus berupa gambar (JPG, JPEG, PNG)',
            'sampai_tanggal.after' => 'Sampai Tanggal harus setelah Masa Berlaku.',
        ];

        $validator = Validator::make($req->all(), $validationRules, $customMessages);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            $filename = null;
            if (in_array($kategori, ['karyawan_baru', 'karyawan_mutasi', 'ganti_kartu']) && $req->hasFile('foto')) {
                $foto = $req->file('foto');
                $filename = 'foto_' . time() . '_' . uniqid() . '.' . $foto->getClientOriginalExtension();
                $foto->storeAs('idcard/foto', $filename, 'private');
            }

            $buktiBayarName = null;
            if ($kategori === 'ganti_kartu' && $req->hasFile('bukti_bayar')) {
                $buktiBayar = $req->file('bukti_bayar');
                $buktiBayarName = 'bukti_' . time() . '_' . uniqid() . '.' . $buktiBayar->getClientOriginalExtension();
                $buktiBayar->storeAs('idcard/bukti_bayar', $buktiBayarName, 'private');
            }

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

            DB::beginTransaction();
            $requestIdCard = RequestIdCard::create($dataToCreate);
            DB::table('request_idcard_logs')->insert([
                'request_id' => $requestIdCard->id,
                'action' => 'created',
                'action_by' => Auth::id(),
                'notes' => 'Request ID Card dibuat - Kategori: ' . $kategori,
                'created_at' => now()
            ]);
            DB::commit();

            return redirect()->route('idcard')->with('success', 'Request ID Card berhasil dibuat!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error in store: " . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan request: ' . $e->getMessage())->withInput();
        }
    }

    // ==================== DETAIL ====================
    public function detail($id)
    {
        $data = DB::table('request_idcard')
            ->select('request_idcard.*', 'users.name as user_name',
                'approved_user.name as approved_by_name',
                'rejected_user.name as rejected_by_name')
            ->leftJoin('users', 'request_idcard.user_id', '=', 'users.id')
            ->leftJoin('users as approved_user', 'request_idcard.approved_by', '=', 'approved_user.id')
            ->leftJoin('users as rejected_user', 'request_idcard.rejected_by', '=', 'rejected_user.id')
            ->where('request_idcard.id', $id)
            ->first();

        if (!$data) abort(404);

        $hasSpecialAccess = $this->canProcessIDCard();
        $canView = $hasSpecialAccess || ($data->user_id == Auth::id());
        if (!$canView) {
            return redirect()->route('idcard')->with('error', 'Anda tidak memiliki akses.');
        }

        $bisnisUnit = DB::table('tb_bisnis_unit')->where('id_bisnis_unit', $data->bisnis_unit_id)->first();
        $data->bisnis_unit_nama = $bisnisUnit->nama_bisnis_unit ?? '-';

        $kategoriLabels = [
            'karyawan_baru'   => 'Karyawan Baru',
            'karyawan_mutasi' => 'Karyawan Mutasi',
            'ganti_kartu'     => 'Ganti Kartu',
            'magang'          => 'Magang',
            'magang_extend'   => 'Magang Extend'
        ];
        $data->kategori_label = $kategoriLabels[$data->kategori] ?? $data->kategori;

        $logs = DB::table('request_idcard_logs')
            ->select('request_idcard_logs.*', 'users.name as action_by_name')
            ->leftJoin('users', 'request_idcard_logs.action_by', '=', 'users.id')
            ->where('request_idcard_logs.request_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $canProses = $hasSpecialAccess;
        $isPending = ($data->status == 'pending');

        return view('idcard.detail', compact('data', 'logs', 'canProses', 'isPending'));
    }

    // ==================== EDIT FORM (ADMIN ONLY) ====================
    public function edit($id)
    {
        if (!$this->canProcessIDCard()) {
            return redirect()->route('idcard')->with('error', 'Anda tidak memiliki akses untuk mengedit.');
        }

        $data = RequestIdCard::findOrFail($id);
        if ($data->status !== 'pending') {
            return redirect()->route('idcard.detail', $id)->with('error', 'Request yang sudah diproses tidak dapat diedit.');
        }

        $bisnisUnits = DB::table('tb_bisnis_unit')->get();
        return view('idcard.edit', compact('data', 'bisnisUnits'));
    }

    // ==================== UPDATE (ADMIN ONLY) ====================
    public function update(Request $req, $id)
    {
        if (!$this->canProcessIDCard()) {
            return back()->with('error', 'Anda tidak memiliki akses untuk mengedit.');
        }

        $item = RequestIdCard::findOrFail($id);
        if ($item->status !== 'pending') {
            return back()->with('error', 'Request yang sudah diproses tidak dapat diedit.');
        }

        $kategori = $req->kategori;

        $validationRules = [
            'nik'      => 'required|string|max:50',
            'nama'     => 'required|string|max:100',
            'kategori' => 'required|in:karyawan_baru,karyawan_mutasi,ganti_kartu,magang,magang_extend',
            'bisnis_unit_id' => 'required|exists:tb_bisnis_unit,id_bisnis_unit',
            'keterangan' => 'required|string|max:255'
        ];

        // Validasi NIK (ignore current id, hanya cek pending lainnya)
        if ($kategori !== 'magang_extend') {
            $validationRules['nik'] = [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($id) {
                    $pendingExists = RequestIdCard::where('nik', $value)
                        ->where('status', 'pending')
                        ->where('id', '!=', $id)
                        ->exists();
                    if ($pendingExists) {
                        $fail('Masih ada request lain dengan NIK ini yang sedang pending.');
                    }
                }
            ];
        }

        if (in_array($kategori, ['karyawan_baru', 'karyawan_mutasi', 'ganti_kartu'])) {
            $validationRules['tanggal_join'] = 'required|date';
            $validationRules['foto'] = 'nullable|image|mimes:jpg,jpeg,png|max:10240';
        }

        if (in_array($kategori, ['magang', 'magang_extend'])) {
            $validationRules['masa_berlaku'] = 'required|date';
            $validationRules['sampai_tanggal'] = 'required|date|after:masa_berlaku';
            if ($kategori === 'magang') {
                $validationRules['nomor_kartu'] = [
                    'required',
                    'string',
                    'max:50',
                    function ($attribute, $value, $fail) use ($id) {
                        $pendingExists = RequestIdCard::where('nomor_kartu', $value)
                            ->where('status', 'pending')
                            ->where('id', '!=', $id)
                            ->exists();
                        if ($pendingExists) {
                            $fail('Nomor kartu sudah digunakan pada request magang pending lain.');
                        }
                    }
                ];
            } else {
                $validationRules['nomor_kartu'] = 'required|string|max:50';
            }
        }

        if ($kategori === 'ganti_kartu') {
            $validationRules['bukti_bayar'] = 'nullable|mimes:jpg,jpeg,png,pdf|max:10240';
        }

        $validator = Validator::make($req->all(), $validationRules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        try {
            DB::beginTransaction();

            // Upload foto baru jika ada
            if ($req->hasFile('foto')) {
                if ($item->foto && Storage::disk('private')->exists('idcard/foto/' . $item->foto)) {
                    Storage::disk('private')->delete('idcard/foto/' . $item->foto);
                }
                $filename = 'foto_' . time() . '_' . uniqid() . '.' . $req->file('foto')->getClientOriginalExtension();
                $req->file('foto')->storeAs('idcard/foto', $filename, 'private');
                $item->foto = $filename;
            }

            // Upload bukti bayar baru jika ada
            if ($req->hasFile('bukti_bayar')) {
                if ($item->bukti_bayar && Storage::disk('private')->exists('idcard/bukti_bayar/' . $item->bukti_bayar)) {
                    Storage::disk('private')->delete('idcard/bukti_bayar/' . $item->bukti_bayar);
                }
                $buktiName = 'bukti_' . time() . '_' . uniqid() . '.' . $req->file('bukti_bayar')->getClientOriginalExtension();
                $req->file('bukti_bayar')->storeAs('idcard/bukti_bayar', $buktiName, 'private');
                $item->bukti_bayar = $buktiName;
            }

            // Update field
            $item->nik = $req->nik;
            $item->nama = $req->nama;
            $item->kategori = $kategori;
            $item->bisnis_unit_id = $req->bisnis_unit_id;
            $item->keterangan = $req->keterangan;

            if (in_array($kategori, ['karyawan_baru', 'karyawan_mutasi', 'ganti_kartu'])) {
                $item->tanggal_join = $req->tanggal_join;
                $item->masa_berlaku = null;
                $item->sampai_tanggal = null;
                $item->nomor_kartu = null;
            }

            if (in_array($kategori, ['magang', 'magang_extend'])) {
                $item->masa_berlaku = $req->masa_berlaku;
                $item->sampai_tanggal = $req->sampai_tanggal;
                $item->nomor_kartu = $req->nomor_kartu;
                $item->tanggal_join = null;
                // Hapus foto jika sebelumnya ada (karena magang tidak pakai foto)
                if ($item->foto && Storage::disk('private')->exists('idcard/foto/' . $item->foto)) {
                    Storage::disk('private')->delete('idcard/foto/' . $item->foto);
                }
                $item->foto = null;
            }

            $item->updated_at = now();
            $item->save();

            DB::table('request_idcard_logs')->insert([
                'request_id' => $id,
                'action' => 'updated',
                'action_by' => Auth::id(),
                'notes' => 'Request ID Card diedit oleh admin',
                'created_at' => now()
            ]);

            DB::commit();
            return redirect()->route('idcard.detail', $id)->with('success', 'Data request berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Update error: " . $e->getMessage());
            return back()->with('error', 'Gagal mengupdate: ' . $e->getMessage());
        }
    }

    // ==================== PHOTO ====================
    public function photo($filename)
    {
        if (!Auth::check()) abort(403);

        $user = Auth::user();
        $data = DB::table('request_idcard')
            ->where(function ($q) use ($filename) {
                $q->where('foto', $filename)->orWhere('bukti_bayar', $filename);
            })->first();

        if (!$data) abort(404);

        $canView = $this->canProcessIDCard() || $data->user_id == $user->id;
        if (!$canView) abort(403);

        $disk = Storage::disk('private');
        $paths = [
            'idcard/foto/' . $filename,
            'idcard/bukti_bayar/' . $filename,
        ];
        $foundPath = null;
        foreach ($paths as $path) {
            if ($disk->exists($path)) {
                $foundPath = $disk->path($path);
                break;
            }
        }
        if (!$foundPath) abort(404);

        $mime = mime_content_type($foundPath);
        return response()->file($foundPath, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($foundPath) . '"',
        ]);
    }

    // ==================== APPROVE ====================
    public function approve(Request $req, $id)
    {
        if (!$this->canProcessIDCard()) {
            return back()->with('error', 'Anda tidak memiliki akses untuk melakukan approval!');
        }

        $item = RequestIdCard::findOrFail($id);
        if ($item->status != 'pending') {
            return back()->with('error', 'Request sudah diproses.');
        }

        if (in_array($item->kategori, ['magang', 'magang_extend'])) {
            $rules = [];
            if ($item->kategori === 'magang') {
                $rules['nomor_kartu'] = 'required|string|max:50|unique:request_idcard,nomor_kartu,' . $id;
            } else {
                $rules['nomor_kartu'] = 'required|string|max:50';
            }
            if ($req->has('sampai_tanggal') && !empty($req->sampai_tanggal)) {
                $rules['sampai_tanggal'] = 'date|after:masa_berlaku';
            }
            $validator = Validator::make($req->all(), $rules);
            if ($validator->fails()) {
                return back()->withErrors($validator)->withInput();
            }
        }

        DB::beginTransaction();
        try {
            if (in_array($item->kategori, ['magang', 'magang_extend'])) {
                $item->nomor_kartu = $req->nomor_kartu;
                if ($req->has('sampai_tanggal') && !empty($req->sampai_tanggal)) {
                    $item->sampai_tanggal = $req->sampai_tanggal;
                }
            }
            $item->status = 'approved';
            $item->approved_by = Auth::id();
            $item->approved_at = now();
            $item->rejected_by = null;
            $item->rejected_at = null;
            $item->rejection_reason = null;
            $item->save();

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
            return back()->with('error', 'Gagal menyetujui: ' . $e->getMessage());
        }
    }

    // ==================== REJECT ====================
    public function reject(Request $req, $id)
    {
        if (!$this->canProcessIDCard()) {
            return back()->with('error', 'Anda tidak memiliki akses untuk melakukan penolakan!');
        }

        $item = RequestIdCard::findOrFail($id);
        if ($item->status != 'pending') {
            return back()->with('error', 'Request sudah diproses.');
        }

        $validator = Validator::make($req->all(), [
            'rejection_reason' => 'required|string|min:5|max:500'
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
            $item->save();

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
            return back()->with('error', 'Gagal menolak: ' . $e->getMessage());
        }
    }
}