<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class HelpTiket extends Model
{
    use SoftDeletes;

    protected $table = 'db_help_tiket';
    
    protected $fillable = [
        'nomor_tiket',
        'judul',
        'deskripsi',
        'kategori_id',
        'pelapor_id',
        'ditugaskan_ke',
        'status',
        'prioritas',
        'catatan_penyelesaian',
        'diverifikasi_pada',
        'diproses_pada',
        'menunggu_pada',
        'diselesaikan_pada',
        'ditutup_pada'
    ];
    
    protected $dates = [
        'diverifikasi_pada',
        'diproses_pada',
        'menunggu_pada',
        'diselesaikan_pada',
        'ditutup_pada',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    
    protected $casts = [
        'diverifikasi_pada' => 'datetime',
        'diproses_pada' => 'datetime',
        'menunggu_pada' => 'datetime',
        'diselesaikan_pada' => 'datetime',
        'ditutup_pada' => 'datetime'
    ];

    /**
     * RELATIONSHIPS
     */
    
    // Kategori tiket
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(HelpKategori::class, 'kategori_id');
    }
    
    // Pelapor (dari users table) - pelapor_id references users.id
    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'pelapor_id', 'id_pelanggan');
    }
    
    // Penanggung jawab (dari users table) - ditugaskan_ke references users.id
    public function ditugaskanKe(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'ditugaskan_ke', 'id_pelanggan');
    }
    
    // Komentar
    public function komentar(): HasMany
    {
        return $this->hasMany(HelpKomentar::class, 'tiket_id');
    }
    
    // Lampiran
    public function lampiran(): HasMany
    {
        return $this->hasMany(HelpLampiran::class, 'tiket_id');
    }
    
    // Log status
    public function logStatus(): HasMany
    {
        return $this->hasMany(HelpLogStatus::class, 'tiket_id');
    }
    
    /**
     * SCOPES
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'OPEN');
    }
    
    public function scopeOnProcess($query)
    {
        return $query->where('status', 'ON_PROCESS');
    }
    
    public function scopeWaiting($query)
    {
        return $query->where('status', 'WAITING');
    }
    
    public function scopeDone($query)
    {
        return $query->where('status', 'DONE');
    }
    
    public function scopeClosed($query)
    {
        return $query->where('status', 'CLOSED');
    }
}