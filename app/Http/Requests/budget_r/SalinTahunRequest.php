<?php

namespace App\Http\Requests\budget_r;

use App\Support\budget_s\BudgetPeran;
use Illuminate\Foundation\Http\FormRequest;

class SalinTahunRequest extends FormRequest
{
    public function authorize(): bool
    {
        return BudgetPeran::peran($this->user()) !== null;
    }

    public function rules(): array
    {
        return [
            'dari' => ['required', 'integer', 'exists:budget_tahun,tahun'],
            'ke' => ['required', 'integer', 'exists:budget_tahun,tahun', 'different:dari'],
            'timpa' => ['nullable', 'boolean'],
            'angka' => ['nullable', 'boolean'],
            'kategori_id' => ['nullable', 'integer'],
            'sub_id' => ['nullable', 'integer'],
            'item_id' => ['nullable', 'integer'],
        ];
    }
}