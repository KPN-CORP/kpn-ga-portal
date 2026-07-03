<?php

namespace App\Http\Controllers\IDCard;

use App\Models\IDCard\RequestIdCard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DetailController extends IDCardBaseController
{
    public function detail($id)
    {
        if (!$this->canAccessRequest($id)) {
            abort(403, 'Anda tidak memiliki akses ke request ini.');
        }

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

        $bisnisUnit = DB::table('tb_bisnis_unit')->where('id_bisnis_unit', $data->bisnis_unit_id)->first();
        $data->bisnis_unit_nama = $bisnisUnit->nama_bisnis_unit ?? '-';

        $logs = DB::table('request_idcard_logs')
            ->select('request_idcard_logs.*', 'users.name as action_by_name')
            ->leftJoin('users', 'request_idcard_logs.action_by', '=', 'users.id')
            ->where('request_idcard_logs.request_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $canProses = $this->canProses();
        $isPending = ($data->status == 'pending');

        return view('idcard.detail', compact('data', 'logs', 'canProses', 'isPending'));
    }

    public function edit($id)
    {
        if (!$this->canProses() || !$this->canAccessRequest($id)) {
            abort(403, 'Anda tidak memiliki akses untuk mengedit.');
        }

        $data = RequestIdCard::findOrFail($id);
        if ($data->status !== 'pending') {
            return redirect()->route('idcard.detail', $id)->with('error', 'Request yang sudah diproses tidak dapat diedit.');
        }

        $bisnisUnits = DB::table('tb_bisnis_unit')->get();
        return view('idcard.edit', compact('data', 'bisnisUnits'));
    }

    public function update(Request $req, $id)
    {
        if (!$this->canProses() || !$this->canAccessRequest($id)) {
            abort(403, 'Anda tidak memiliki akses.');
        }

        $item = RequestIdCard::findOrFail($id);
        if ($item->status !== 'pending') {
            return back()->with('error', 'Request yang sudah diproses tidak dapat diedit.');
        }

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

            // Upload foto
            if ($req->hasFile('foto')) {
                if ($item->foto && Storage::disk('private')->exists('idcard/foto/' . $item->foto)) {
                    Storage::disk('private')->delete('idcard/foto/' . $item->foto);
                }
                $filename = 'foto_' . time() . '_' . uniqid() . '.' . $req->file('foto')->getClientOriginalExtension();
                $req->file('foto')->storeAs('idcard/foto', $filename, 'private');
                $item->foto = $filename;
            }

            // Upload bukti bayar
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

            // Reset
            $item->tanggal_join = null;
            $item->masa_berlaku = null;
            $item->sampai_tanggal = null;
            $item->nomor_kartu = null;

            if (in_array($kategori, ['karyawan_baru', 'karyawan_mutasi', 'ganti_kartu'])) {
                $item->tanggal_join = $req->tanggal_join;
            }

            if (in_array($kategori, ['magang', 'magang_extend'])) {
                $item->masa_berlaku = $req->masa_berlaku;
                $item->sampai_tanggal = $req->sampai_tanggal;
                $item->nomor_kartu = $req->nomor_kartu;
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

    public function photo($filename)
    {
        if (!Auth::check()) abort(403);

        $data = DB::table('request_idcard')
            ->where(function ($q) use ($filename) {
                $q->where('foto', $filename)->orWhere('bukti_bayar', $filename);
            })->first();

        if (!$data) abort(404);

        if (!$this->canAccessRequest($data->id)) {
            abort(403);
        }

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
}