<?php

namespace App\Http\Requests\budget_r;

use App\Support\budget_s\BudgetPeran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanKategoriRequest extends FormRequest
{
    /**
     * Menambah kategori/subkategori baru: Admin, Dept Head, dan PIC (milik Dept Head masing-masing).
     * Mengubah (ada id): hanya Admin.
     */
    public function authorize(): bool
    {
        $peran = BudgetPeran::peran($this->user());
        if ($peran === null) {
            return false;
        }
        return $this->filled('id') ? $peran === BudgetPeran::ADMIN : true;
    }

    public function rules(): array
    {
        $nama = ['required', 'string', 'max:255'];
        if (!$this->filled('id') && $this->input('jenis') === 'sub') {
            $nama[] = Rule::unique('budget_sub', 'nama')->where('kategori_id', (int) $this->input('kategori_id'));
        }

        return [
            'jenis' => ['required', 'in:kategori,sub'],
            'id' => ['nullable', 'integer'],
            'kategori_id' => ['required_if:jenis,sub', 'nullable', 'integer', 'exists:budget_kategori,id'],
            'dept_head_user_id' => ['nullable', 'integer'],
            'nama' => $nama,
            'urut' => ['nullable', 'integer', 'min:0'],
            'aktif' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['nama.unique' => 'Nama tersebut sudah ada.'];
    }
}