<?php

namespace App\Http\Requests\budget_r;

use Illuminate\Foundation\Http\FormRequest;

class SimpanTagihanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // hak per item dicek di TagihanController
    }

    public function rules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'item_id' => ['required', 'integer', 'exists:budget_item,id'],
            'tahun' => ['required', 'integer', 'exists:budget_tahun,tahun'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'tagihan' => ['required', 'numeric', 'min:0'],
            'ppn' => ['nullable', 'numeric', 'min:0'],
            'biaya_materai' => ['nullable', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function attributes(): array
    {
        return ['item_id' => 'item', 'biaya_materai' => 'biaya materai', 'ppn' => 'PPN'];
    }
}
