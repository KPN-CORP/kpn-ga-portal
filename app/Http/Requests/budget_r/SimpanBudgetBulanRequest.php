<?php

namespace App\Http\Requests\budget_r;

use Illuminate\Foundation\Http\FormRequest;

class SimpanBudgetBulanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // hak per item dicek di BudgetAllController
    }

    public function rules(): array
    {
        return [
            'item_id' => ['required', 'integer', 'exists:budget_item,id'],
            'tahun' => ['required', 'integer', 'exists:budget_tahun,tahun'],
            'nominal' => ['required', 'array'],
            'nominal.*' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
