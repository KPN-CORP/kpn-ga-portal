<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackRDocument extends Model
{
    protected $table = 'track_r_documents';

    protected $fillable = [
        'nomor_dokumen',
        'judul',
        'keterangan',
        'pengirim_id',
        'penerima_id',
        'status',
    ];

    /* ========== RELASI ========== */

    // Semua penerima (histori)
    public function recipients()
    {
        return $this->belongsToMany(User::class, 'track_r_recipients')
                    ->withTimestamps()
                    ->withPivot('received_at');
    }

    // Penerima saat ini (current)
    public function currentRecipient()
    {
        return $this->belongsTo(User::class, 'penerima_id');
    }

    // Alias untuk blade agar lebih mudah
    public function penerima()
    {
        return $this->currentRecipient();
    }

    public function pengirim()
    {
        return $this->belongsTo(User::class, 'pengirim_id');
    }

    public function logs()
    {
        return $this->hasMany(TrackRLog::class, 'track_r_document_id')
                    ->orderBy('created_at', 'asc');
    }

    public function fotos()
    {
        return $this->hasMany(TrackRFoto::class, 'track_r_document_id');
    }

    /* ========== CEK AKSES ========== */
    public function hasAccess(User $user): bool
    {
        return $user->id === $this->pengirim_id ||
               $this->recipients()->where('user_id', $user->id)->exists();
    }

    /* ========== STATUS BERDASARKAN PENGGUNA ========== */
    public function statusForUser(User $user): array
    {
        $isSender = $user->id === $this->pengirim_id;
        $isCurrentRecipient = $user->id === $this->penerima_id;
        $isPreviousRecipient = $this->recipients()
            ->where('user_id', $user->id)
            ->where('user_id', '!=', $this->penerima_id)
            ->exists();

        $baseStatus = $this->status;

        // Default
        $label = strtoupper($baseStatus);
        $color = match($baseStatus) {
            'dikirim' => 'bg-blue-100 text-blue-700 border-blue-200',
            'diterima' => 'bg-green-100 text-green-700 border-green-200',
            'ditolak' => 'bg-red-100 text-red-700 border-red-200',
            'diteruskan' => 'bg-purple-100 text-purple-700 border-purple-200',
            default => 'bg-gray-100 text-gray-700 border-gray-200'
        };

        if ($isSender) {
            match($baseStatus) {
                'dikirim' => $label = 'Menunggu Penerima',
                'diterima' => $label = 'Diterima',
                'ditolak' => $label = 'Ditolak',
                'diteruskan' => [
                    $label = 'Diteruskan ke ' . ($this->penerima->name ?? '?'),
                    $color = 'bg-indigo-100 text-indigo-700 border-indigo-200'
                ],
                default => null
            };
        } elseif ($isCurrentRecipient) {
            match($baseStatus) {
                'dikirim', 'diteruskan' => [
                    $label = 'Dokumen Masuk',
                    $color = 'bg-blue-100 text-blue-700 border-blue-200'
                ],
                'diterima' => $label = 'Diterima',
                'ditolak' => $label = 'Ditolak',
                default => null
            };
        } elseif ($isPreviousRecipient) {
            match($baseStatus) {
                'diteruskan' => $label = 'Diteruskan',
                default => $label = strtoupper($baseStatus)
            };
        }

        return ['label' => $label, 'color' => $color];
    }
}