<?php

namespace App\Http\Requests\budget_r;

use App\Support\budget_s\BudgetPeran;
use Illuminate\Foundation\Http\FormRequest;

class SimpanAksesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return BudgetPeran::isAdmin($this->user());
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'peran' => ['required', 'in:admin,dept_head,pic'],
            'aktif' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['user_id' => 'user'];
    }
}
