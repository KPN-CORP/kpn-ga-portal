<?php

namespace App\Models\budget_m;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class BudgetFormField extends Model
{
    public const TIPE = ['teks' => 'Teks', 'angka' => 'Angka', 'tanggal' => 'Tanggal', 'pilihan' => 'Pilihan'];

    protected $table = 'budget_form_field';
    protected $fillable = ['form_id', 'label', 'tipe', 'pilihan', 'wajib', 'urut', 'aktif'];
    protected $casts = ['wajib' => 'boolean', 'aktif' => 'boolean'];

    public function form()
    {
        return $this->belongsTo(BudgetForm::class, 'form_id');
    }

    /** Opsi untuk tipe pilihan (satu per baris, tanpa baris kosong). */
    public function opsi(): array
    {
        if ($this->tipe !== 'pilihan') {
            return [];
        }
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $this->pilihan))));
    }

    /** Aturan validasi untuk isian field ini. */
    public function aturan(): array
    {
        $a = [$this->wajib ? 'required' : 'nullable'];
        return match ($this->tipe) {
            'angka' => array_merge($a, ['numeric', 'between:-99999999999,99999999999']),
            'tanggal' => array_merge($a, ['date']),
            'pilihan' => array_merge($a, ['string', Rule::in($this->opsi())]),
            default => array_merge($a, ['string', 'max:255']),
        };
    }
}
