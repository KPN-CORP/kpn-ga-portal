<?php
// app/Models/HelpKategori.php
namespace App\Models\help_m;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HelpKategori extends Model
{
    use HasFactory;
    
    protected $table = 'db_help_kategori';
    
    protected $fillable = [
        'nama', 'deskripsi', 'sla_jam', 'aktif'
    ];
    
    protected $casts = [
        'aktif' => 'boolean',
        'sla_jam' => 'integer'
    ];
    
    public function tiket()
    {
        return $this->hasMany(HelpTiket::class, 'kategori_id');
    }
}