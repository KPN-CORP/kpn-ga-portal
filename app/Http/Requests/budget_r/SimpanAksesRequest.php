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

    /** Admin hanya mengatur Admin dan Dept Head. PIC ditambah dan dilepas oleh Dept Head. */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'peran' => ['required', 'in:admin,dept_head'],
            'nama_dept' => ['nullable', 'string', 'max:100'],
            'aktif' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['user_id' => 'user', 'nama_dept' => 'nama departemen'];
    }
}