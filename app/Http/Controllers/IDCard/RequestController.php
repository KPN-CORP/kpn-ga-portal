<?php

namespace App\Http\Controllers\IDCard;

use App\Models\IDCard\RequestIdCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class RequestController extends IDCardBaseController
{
    public function create()
    {
        $bisnisUnits = DB::table('tb_bisnis_unit')->get();
        return view('idcard.request', compact('bisnisUnits'));
    }

    /**
     * Cari data magang / visitor yang SUDAH DISETUJUI untuk keperluan extend.
     * Hasil digabung per NIK (diambil request terbaru), supaya tidak double.
     *
     * GET ?jenis=magang|visitor&q=kata_kunci
     */
    public function searchExisting(Request $req)
    {
        $jenis = $req->get('jenis');
        $q     = trim((string) $req->get('q', ''));

        if (!in_array($jenis, ['magang', 'visitor'], true) || mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $kategori = $jenis === 'magang' ? ['magang', 'magang_extend'] : ['visitor'];

        // 1) NIK yang cocok dengan kata kunci (nama / NIK / nomor kartu)
        $niks = RequestIdCard::whereIn('kategori', $kategori)
            ->where('status', 'approved')
            ->whereNotNull('nik')
            ->where(function ($w) use ($q) {
                $w->where('nama', 'like', "%{$q}%")
                  ->orWhere('nik', 'like', "%{$q}%")
                  ->orWhere('nomor_kartu', 'like', "%{$q}%");
            })
            ->distinct()
            ->limit(30)
            ->pluck('nik');

        if ($niks->isEmpty()) {
            return response()->json([]);
        }

        // 2) Request TERBARU untuk tiap NIK tersebut
        $latestIds = RequestIdCard::whereIn('kategori', $kategori)
            ->where('status', 'approved')
            ->whereIn('nik', $niks)
            ->selectRaw('MAX(id) as id')
            ->groupBy('nik')
            ->pluck('id');

        // 3) Terapkan filter akses bisnis unit seperti di halaman list
        $query = RequestIdCard::with('bisnisUnit')
            ->whereIn('id', $latestIds)
            ->orderByDesc('sampai_tanggal');
        $items = $this->applyAccessFilter($query)->limit(10)->get();

        $today = now()->startOfDay();

        return response()->json($items->map(function ($i) use ($today) {
            // Usul tanggal mulai baru: sehari setelah masa lama berakhir (minimal hari ini)
            $mulaiBaru = $i->sampai_tanggal ? $i->sampai_tanggal->copy()->addDay() : $today->copy();
            if ($mulaiBaru->lt($today)) {
                $mulaiBaru = $today->copy();
            }
            $periode = $i->periode_info;

            return [
                'id'                => $i->id,
                'nik'               => $i->nik,
                'nama'              => $i->nama,
                'kategori'          => $i->kategori,
                'bisnis_unit_id'    => $i->bisnis_unit_id,
                'bisnis_unit_nama'  => optional($i->bisnisUnit)->nama_bisnis_unit,
                'keterangan'        => $i->keterangan,
                'nomor_kartu'       => $i->nomor_kartu,
                'masa_berlaku'      => optional($i->masa_berlaku)->format('Y-m-d'),
                'sampai_tanggal'    => optional($i->sampai_tanggal)->format('Y-m-d'),
                'masa_berlaku_baru' => $mulaiBaru->format('Y-m-d'),
                'periode_label'     => $periode ? $periode['label'] : '-',
                'periode_range'     => $periode ? $periode['range'] : '',
                'kartu_aktif'       => (bool) $i->is_active,
            ];
        })->values());
    }

    public function store(Request $req)
    {
        ini_set('upload_max_filesize', '50M');
        ini_set('post_max_size', '55M');
        ini_set('max_execution_time', '300');

        // Form: kategori "magang" + mode "extend"  =>  disimpan sebagai magang_extend
        $kategori = $req->kategori;
        $mode     = $req->mode === 'extend' ? 'extend' : 'new';
        if ($kategori === 'magang' && $mode === 'extend') {
            $kategori = 'magang_extend';
        }
        $req->merge(['kategori' => $kategori]);

        // Extend (magang_extend / visitor mode extend) wajib menunjuk data lama
        $wajibExtend = $kategori === 'magang_extend' || ($kategori === 'visitor' && $mode === 'extend');

        $validationRules = [
            'nik'      => 'required|string|max:50',
            'nama'     => 'required|string|max:100',
            'kategori' => 'required|in:karyawan_baru,karyawan_mutasi,ganti_kartu,magang,magang_extend,visitor',
            'bisnis_unit_id' => 'required|exists:tb_bisnis_unit,id_bisnis_unit',
            'keterangan' => 'required|string|max:255'
        ];

        if ($kategori === 'magang_extend') {
            // Extend wajib memakai NIK magang yang sudah ada & disetujui (anti salah ketik / double input)
            $validationRules['nik'] = [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    $ada = RequestIdCard::where('nik', $value)
                        ->whereIn('kategori', ['magang', 'magang_extend'])
                        ->where('status', 'approved')
                        ->exists();
                    if (!$ada) {
                        $fail('NIK tidak ditemukan pada data magang yang sudah disetujui. Cari dan pilih dari data yang sudah ada.');
                        return;
                    }
                    $pendingExtend = RequestIdCard::where('nik', $value)
                        ->where('kategori', 'magang_extend')
                        ->where('status', 'pending')
                        ->exists();
                    if ($pendingExtend) {
                        $fail('Masih ada request extend dengan NIK ini yang sedang menunggu diproses.');
                    }
                }
            ];
        } else {
            $validationRules['nik'] = [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) {
                    $pendingExists = RequestIdCard::where('nik', $value)
                        ->where('status', 'pending')
                        ->exists();
                    if ($pendingExists) {
                        $fail('Masih ada request ID Card dengan NIK ini yang sedang menunggu diproses.');
                    }
                }
            ];
        }

        // Referensi data lama (extend magang / extend visitor)
        if ($wajibExtend) {
            $validationRules['extend_from_id'] = [
                'required',
                'integer',
                function ($attribute, $value, $fail) use ($req, $kategori) {
                    $src = RequestIdCard::find($value);
                    $jenisOk = $src && ($kategori === 'visitor'
                        ? $src->kategori === 'visitor'
                        : in_array($src->kategori, ['magang', 'magang_extend']));
                    if (!$src || $src->status !== 'approved' || !$jenisOk || $src->nik !== $req->nik) {
                        $fail('Data lama untuk extend tidak valid. Silakan cari dan pilih ulang.');
                    }
                }
            ];
        }

        if (in_array($kategori, ['karyawan_baru', 'karyawan_mutasi', 'ganti_kartu'])) {
            $validationRules['tanggal_join'] = 'required|date';
            $validationRules['foto'] = 'required|image|mimes:jpg,jpeg,png|max:10240';
        }

        if (in_array($kategori, ['magang', 'magang_extend', 'visitor'])) {
            $validationRules['masa_berlaku'] = 'required|date';
            // Visitor boleh kunjungan 1 hari (sampai = mulai), magang harus setelah tanggal mulai
            $validationRules['sampai_tanggal'] = $kategori === 'visitor'
                ? 'required|date|after_or_equal:masa_berlaku'
                : 'required|date|after:masa_berlaku';
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
            } elseif ($kategori === 'magang_extend') {
                $validationRules['nomor_kartu'] = 'required|string|max:50';
            } else {
                // visitor: nomor kartu (kata/angka) diisi saat approve; saat extend boleh terisi dari data lama
                $validationRules['nomor_kartu'] = 'nullable|string|max:50';
            }
        }

        if ($kategori === 'ganti_kartu') {
            $validationRules['bukti_bayar'] = 'required|mimes:jpg,jpeg,png,pdf|max:10240';
        }

        $validator = Validator::make($req->all(), $validationRules, [
            'extend_from_id.required' => 'Untuk extend, cari dan pilih dulu data lama yang sudah ada.',
        ]);
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
                'keterangan' => $req->keterangan,
                'status' => 'pending',
                'user_id' => Auth::id(),
                'created_at' => now(),
                'updated_at' => now()
            ];

            if (in_array($kategori, ['karyawan_baru', 'karyawan_mutasi', 'ganti_kartu'])) {
                $dataToCreate['tanggal_join'] = $req->tanggal_join;
                $dataToCreate['foto'] = $filename;
            }

            if (in_array($kategori, ['magang', 'magang_extend', 'visitor'])) {
                $dataToCreate['masa_berlaku'] = $req->masa_berlaku;
                $dataToCreate['sampai_tanggal'] = $req->sampai_tanggal;
                $dataToCreate['nomor_kartu'] = $req->nomor_kartu;
            }

            if ($kategori === 'ganti_kartu') {
                $dataToCreate['bukti_bayar'] = $buktiBayarName;
            }

            $catatanLog = 'Request ID Card dibuat - Kategori: ' . $kategori;
            if ($wajibExtend) {
                $catatanLog .= ' (extend dari request #' . $req->extend_from_id . ')';
            }

            DB::beginTransaction();
            $requestIdCard = RequestIdCard::create($dataToCreate);
            DB::table('request_idcard_logs')->insert([
                'request_id' => $requestIdCard->id,
                'action' => 'created',
                'action_by' => Auth::id(),
                'notes' => $catatanLog,
                'created_at' => now()
            ]);
            DB::commit();

            return redirect()->route('idcard.index')->with('success', 'Request ID Card berhasil dibuat!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error in store: " . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan request: ' . $e->getMessage())->withInput();
        }
    }
}