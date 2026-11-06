<?php

namespace App\Http\Requests\budget_r;

use App\Models\budget_m\BudgetAkses;
use App\Models\budget_m\BudgetItem;
use App\Models\budget_m\BudgetPicHead;
use App\Models\budget_m\HcisPegawai;
use App\Support\budget_s\BudgetPeran;
use Illuminate\Foundation\Http\FormRequest;

class SimpanItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (BudgetPeran::peran($user) === null) {
            return false;
        }
        if ($this->filled('id')) {
            $item = BudgetItem::find($this->input('id'));
            return $item && BudgetPeran::bisaUbahItem($item, $user);
        }
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'sub_id' => ['required', 'integer', 'exists:budget_sub,id', function ($attr, $val, $fail) {
                if (!\App\Models\budget_m\BudgetSub::terlihatOleh($this->user())->whereKey($val)->exists()) {
                    $fail('Subkategori tidak tersedia untuk Anda.');
                }
            }],
            'nama' => ['required', 'string', 'max:255'],
            'bu' => ['required', 'string', 'max:255'],
            'pt' => ['required', 'string', 'max:255'],
            'dept_head_user_id' => ['nullable', 'integer'],
            'pic_user_id' => ['nullable', 'integer'],
            'sumber' => ['nullable', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
            'aktif' => ['nullable', 'boolean'],
            // Item baru: mulai berlaku di tahun halaman yang sedang dibuka.
            'tahun_mulai' => ['nullable', 'integer', 'exists:budget_tahun,tahun'],
            // Ubah item: 'semua' = perbaiki di semua tahun, 'mulai' = item baru mulai tahun_berlaku (tahun lama tidak berubah).
            'mode' => ['nullable', 'in:semua,mulai'],
            'tahun_berlaku' => ['nullable', 'required_if:mode,mulai', 'integer', 'exists:budget_tahun,tahun'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            $user = $this->user();

            if (!HcisPegawai::pasanganAda($this->input('bu'), $this->input('pt'))) {
                $v->errors()->add('pt', 'Kombinasi BU dan PT tidak ditemukan di data HCIS.');
            }

            // Pemilik item: Dept Head selalu dirinya sendiri; Admin wajib memilih Dept Head aktif.
            $head = BudgetPeran::tentukanKepala($user, $this->input('dept_head_user_id'));
            if (!$head) {
                $v->errors()->add('dept_head_user_id', 'Departemen pemilik item wajib dipilih.');
                return;
            }
            $headAktif = BudgetAkses::where('user_id', $head)->where('peran', BudgetPeran::DEPT_HEAD)->where('aktif', true)->exists();
            if (!$headAktif) {
                $v->errors()->add('dept_head_user_id', 'Pemilik item harus user dengan akses Dept Head yang aktif.');
                return;
            }

            // PIC harus bawahan aktif dari Dept Head pemilik item.
            if ($this->filled('pic_user_id')) {
                $bawahan = BudgetPicHead::where('dept_head_user_id', $head)
                    ->where('pic_user_id', $this->input('pic_user_id'))->where('aktif', true)->exists();
                if (!$bawahan) {
                    $v->errors()->add('pic_user_id', 'PIC yang dipilih bukan bawahan aktif Dept Head pemilik item.');
                }
            }
        });
    }
}