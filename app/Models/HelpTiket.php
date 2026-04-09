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
        'bisnis_unit_id',
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
     * Status tiket yang tersedia
     */
    const STATUS_TIKET = [
        'OPEN' => 'OPEN',
        'ON_PROCESS' => 'ON_PROCESS',
        'WAITING' => 'WAITING',
        'DONE' => 'DONE',
        'CLOSED' => 'CLOSED',
        'HELP_GA_CORP' => 'HELP_GA_CORP',
    ];

    // RELATIONSHIPS
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(HelpKategori::class, 'kategori_id');
    }
    
    public function bisnisUnit(): BelongsTo
    {
        return $this->belongsTo(BisnisUnit::class, 'bisnis_unit_id', 'id_bisnis_unit');
    }
    
    public function pelapor(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'pelapor_id', 'id_pelanggan');
    }
    
    public function ditugaskanKe(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'ditugaskan_ke', 'id_pelanggan');
    }
    
    public function komentar(): HasMany
    {
        return $this->hasMany(HelpKomentar::class, 'tiket_id');
    }
    
    public function lampiran(): HasMany
    {
        return $this->hasMany(HelpLampiran::class, 'tiket_id');
    }
    
    public function logStatus(): HasMany
    {
        return $this->hasMany(HelpLogStatus::class, 'tiket_id');
    }
    
    // SCOPES
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

    public function scopeHelpGaCorp($query)
    {
        return $query->where('status', 'HELP_GA_CORP');
    }
}