<?php

namespace App\Http\Requests\budget_r;

use App\Support\budget_s\BudgetPeran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanKategoriRequest extends FormRequest
{
    /**
     * Menambah kategori/subkategori baru: Admin, Dept Head, dan PIC.
     * Mengubah (ada id): hanya Admin, karena kategori dipakai bersama oleh semua Dept Head.
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
        $baru = !$this->filled('id');

        $nama = ['required', 'string', 'max:255'];
        if ($baru) {
            $nama[] = $this->input('jenis') === 'sub'
                ? Rule::unique('budget_sub', 'nama')->where('kategori_id', (int) $this->input('kategori_id'))
                : Rule::unique('budget_kategori', 'nama');
        }

        return [
            'jenis' => ['required', 'in:kategori,sub'],
            'id' => ['nullable', 'integer'],
            'kategori_id' => ['required_if:jenis,sub', 'nullable', 'integer', 'exists:budget_kategori,id'],
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