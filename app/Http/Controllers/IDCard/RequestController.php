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

    public function store(Request $req)
    {
        ini_set('upload_max_filesize', '50M');
        ini_set('post_max_size', '55M');
        ini_set('max_execution_time', '300');

        $kategori = $req->kategori;

        $validationRules = [
            'nik'      => 'required|string|max:50',
            'nama'     => 'required|string|max:100',
            'kategori' => 'required|in:karyawan_baru,karyawan_mutasi,ganti_kartu,magang,magang_extend,perubahan_lantai',
            'bisnis_unit_id' => 'required|exists:tb_bisnis_unit,id_bisnis_unit',
            'keterangan' => 'required|string|max:255'
        ];

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
                        $fail('Masih ada request ID Card dengan NIK ini yang sedang menunggu diproses.');
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

        $validator = Validator::make($req->all(), $validationRules);
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

            if (in_array($kategori, ['magang', 'magang_extend'])) {
                $dataToCreate['masa_berlaku'] = $req->masa_berlaku;
                $dataToCreate['sampai_tanggal'] = $req->sampai_tanggal;
                $dataToCreate['nomor_kartu'] = $req->nomor_kartu;
            }

            if ($kategori === 'ganti_kartu') {
                $dataToCreate['bukti_bayar'] = $buktiBayarName;
            }

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

            return redirect()->route('idcard.index')->with('success', 'Request ID Card berhasil dibuat!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Error in store: " . $e->getMessage());
            return back()->with('error', 'Gagal menyimpan request: ' . $e->getMessage())->withInput();
        }
    }
}