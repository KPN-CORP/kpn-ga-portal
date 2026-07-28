<?php

namespace App\Models\Drms;

use Illuminate\Database\Eloquent\Model;

class ExpenseReport extends Model
{
    protected $table = 'drms_expense_reports';

    protected $fillable = [
        'driver_id',
        'request_id',
        'report_date',
        'category',
        'description',
        'amount',
    ];

    protected $casts = [
        'report_date' => 'date',
        'amount'      => 'decimal:2',
    ];

    /**
     * Kategori pengeluaran yang tersedia untuk driver.
     */
    public const CATEGORIES = [
        'toll'       => 'Toll',
        'parkir'     => 'Parkir',
        'bbm'        => 'BBM',
        'cuci_mobil' => 'Cuci Mobil',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function request()
    {
        return $this->belongsTo(DriverRequest::class, 'request_id');
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ucfirst($this->category);
    }

    /**
     * Jumlah hari maksimal entri masih bisa diedit setelah pertama kali diisi.
     */
    public const EDITABLE_DAYS = 10;

    /**
     * Batas waktu (tanggal) entri ini masih bisa diedit.
     */
    public function getEditDeadlineAttribute(): \Carbon\Carbon
    {
        return $this->created_at->copy()->addDays(self::EDITABLE_DAYS);
    }

    /**
     * Apakah entri ini masih di dalam masa edit (maks. 10 hari sejak diisi).
     * Entri tidak pernah bisa dihapus, jadi tidak ada accessor "isDeletable".
     */
    public function getIsEditableAttribute(): bool
    {
        return now()->lessThanOrEqualTo($this->edit_deadline);
    }
}
