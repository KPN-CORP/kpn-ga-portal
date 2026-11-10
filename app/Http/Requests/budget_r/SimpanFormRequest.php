<?php

namespace App\Http\Requests\budget_r;

use App\Support\budget_s\BudgetPeran;
use Illuminate\Foundation\Http\FormRequest;

/** Membuat atau mengubah form beserta field-nya. Hak atas item tujuan dicek di FormController. */
class SimpanFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return BudgetPeran::peran($this->user()) !== null;
    }

    public function rules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'nama' => ['required', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'item_id' => ['required', 'integer', 'exists:budget_item,id'],
            'pakai_ppn' => ['nullable', 'boolean'],
            'pakai_materai' => ['nullable', 'boolean'],
            'fields' => ['nullable', 'array', 'max:30'],
            'fields.*.id' => ['nullable', 'integer'],
            'fields.*.label' => ['required', 'string', 'max:60'],
            'fields.*.tipe' => ['required', 'in:teks,angka,tanggal,pilihan'],
            'fields.*.pilihan' => ['nullable', 'string', 'max:1000'],
            'fields.*.wajib' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['item_id' => 'item tujuan', 'fields.*.label' => 'nama field', 'fields.*.tipe' => 'tipe field'];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }
            $labels = [];
            foreach ((array) $this->input('fields', []) as $i => $f) {
                $k = mb_strtolower(trim($f['label']));
                if (in_array($k, $labels, true)) {
                    $v->errors()->add("fields.$i.label", 'Nama field "' . $f['label'] . '" dipakai dua kali.');
                }
                $labels[] = $k;

                if (in_array($k, ['tanggal', 'no dokumen', 'nomor dokumen', 'dpp', 'ppn', 'materai', 'total'], true)) {
                    $v->errors()->add("fields.$i.label", 'Nama field "' . $f['label'] . '" sudah tersedia otomatis di form. Pakai nama lain.');
                }
                if (($f['tipe'] ?? '') === 'pilihan') {
                    $opsi = array_filter(array_map('trim', preg_split('/\R/', (string) ($f['pilihan'] ?? ''))));
                    if (count($opsi) < 2) {
                        $v->errors()->add("fields.$i.pilihan", 'Field "' . $f['label'] . '" bertipe pilihan, isi minimal 2 opsi (satu per baris).');
                    }
                }
            }
        });
    }
}
