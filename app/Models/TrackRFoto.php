<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackRFoto extends Model
{
    protected $table = 'track_r_fotos';
    
    protected $fillable = [
        'track_r_document_id',
        'nama_file',
        'path',
        'tipe',
        'ukuran',
    ];

    public function document()
    {
        return $this->belongsTo(TrackRDocument::class, 'track_r_document_id');
    }
    
    // Helper untuk mendapatkan URL file
    public function getUrlAttribute()
    {
        return route('track-r.foto.download', [
            'document' => $this->track_r_document_id,
            'foto' => $this->id
        ]);
    }
    
    // Helper untuk icon berdasarkan tipe file
    public function getIconAttribute()
    {
        return match($this->tipe) {
            'pdf' => 'fas fa-file-pdf',
            'doc', 'docx' => 'fas fa-file-word',
            'jpg', 'jpeg', 'png' => 'fas fa-file-image',
            default => 'fas fa-file'
        };
    }
}