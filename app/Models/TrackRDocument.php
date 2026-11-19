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
                    ->withPivot('received_at', 'status')
                    ->orderBy('track_r_recipients.received_at');
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

    /* ========== SCOPE & TAB ========== */

    // Dokumen yang boleh dilihat: pengirim + penerima TERAKHIR saja.
    // (Penerima sebelumnya tidak lagi punya akses; tabel recipients hanya riwayat.)
    public function scopeVisibleTo($query, User $user)
    {
        if ($user->isSuperadminTrack()) {
            return $query;
        }

        return $query->where(function ($q) use ($user) {
            $q->where('pengirim_id', $user->id)
              ->orWhere('penerima_id', $user->id);
        });
    }

    // Arah: masuk (saya penerima saat ini) | keluar (saya pengirim) | semua (hanya superadmin)
    public function scopeInDirection($query, ?string $arah, int $userId)
    {
        return match ($arah) {
            'masuk'  => $query->where('penerima_id', $userId),
            'keluar' => $query->where('pengirim_id', $userId),
            default  => $query,
        };
    }

    // Tab: semua | perlu_tindakan | menunggu | diterima | ditolak
    public function scopeInTab($query, ?string $tab, int $userId)
    {
        return match ($tab) {
            'perlu_tindakan' => $query->where('penerima_id', $userId)->whereIn('status', ['dikirim', 'diteruskan']),
            'menunggu'       => $query->where('pengirim_id', $userId)->whereIn('status', ['dikirim', 'diteruskan']),
            'diterima'       => $query->whereIn('status', ['diterima', 'selesai']),
            'ditolak'        => $query->where('status', 'ditolak'),
            default          => $query,
        };
    }

    // Angka di pill tiap tab (satu query)
    public static function tabCounts(User $user): array
    {
        $uid = $user->id;

        $row = static::visibleTo($user)->selectRaw(
            "COUNT(*) AS semua,
             COALESCE(SUM(CASE WHEN penerima_id = ? AND status IN ('dikirim','diteruskan') THEN 1 ELSE 0 END), 0) AS perlu_tindakan,
             COALESCE(SUM(CASE WHEN pengirim_id = ? AND status IN ('dikirim','diteruskan') THEN 1 ELSE 0 END), 0) AS menunggu,
             COALESCE(SUM(CASE WHEN status IN ('diterima','selesai') THEN 1 ELSE 0 END), 0) AS diterima,
             COALESCE(SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END), 0) AS ditolak",
            [$uid, $uid]
        )->first();

        return [
            'semua'          => (int) $row->semua,
            'perlu_tindakan' => (int) $row->perlu_tindakan,
            'menunggu'       => (int) $row->menunggu,
            'diterima'       => (int) $row->diterima,
            'ditolak'        => (int) $row->ditolak,
        ];
    }

    // Angka tab per arah (satu query). 'semua' hanya untuk superadmin.
    public static function directionCounts(User $user): array
    {
        $uid = $user->id;

        $row = static::visibleTo($user)->selectRaw(
            "COALESCE(SUM(CASE WHEN penerima_id = ? THEN 1 ELSE 0 END), 0) AS m_semua,
             COALESCE(SUM(CASE WHEN penerima_id = ? AND status IN ('dikirim','diteruskan') THEN 1 ELSE 0 END), 0) AS m_perlu,
             COALESCE(SUM(CASE WHEN penerima_id = ? AND status IN ('diterima','selesai') THEN 1 ELSE 0 END), 0) AS m_diterima,
             COALESCE(SUM(CASE WHEN penerima_id = ? AND status = 'ditolak' THEN 1 ELSE 0 END), 0) AS m_ditolak,
             COALESCE(SUM(CASE WHEN pengirim_id = ? THEN 1 ELSE 0 END), 0) AS k_semua,
             COALESCE(SUM(CASE WHEN pengirim_id = ? AND status IN ('dikirim','diteruskan') THEN 1 ELSE 0 END), 0) AS k_menunggu,
             COALESCE(SUM(CASE WHEN pengirim_id = ? AND status IN ('diterima','selesai') THEN 1 ELSE 0 END), 0) AS k_diterima,
             COALESCE(SUM(CASE WHEN pengirim_id = ? AND status = 'ditolak' THEN 1 ELSE 0 END), 0) AS k_ditolak",
            array_fill(0, 8, $uid)
        )->first();

        return [
            'masuk' => [
                'semua'          => (int) $row->m_semua,
                'perlu_tindakan' => (int) $row->m_perlu,
                'diterima'       => (int) $row->m_diterima,
                'ditolak'        => (int) $row->m_ditolak,
            ],
            'keluar' => [
                'semua'    => (int) $row->k_semua,
                'menunggu' => (int) $row->k_menunggu,
                'diterima' => (int) $row->k_diterima,
                'ditolak'  => (int) $row->k_ditolak,
            ],
            'semua' => $user->isSuperadminTrack() ? static::tabCounts($user) : null,
        ];
    }

    /* ========== CEK AKSES ========== */
    public function hasAccess(User $user): bool
    {
        return $user->id === $this->pengirim_id
            || $user->id === $this->penerima_id;
    }

    /* ========== STATUS BERDASARKAN PENGGUNA ========== */
    public function statusForUser(User $user): array
    {
        $isSender           = $user->id === $this->pengirim_id;
        $isCurrentRecipient = $user->id === $this->penerima_id;
        $status             = $this->status;

        $color = match ($status) {
            'dikirim'    => 'bg-blue-100 text-blue-700 border-blue-200',
            'diterima', 'selesai' => 'bg-green-100 text-green-700 border-green-200',
            'ditolak'    => 'bg-red-100 text-red-700 border-red-200',
            'diteruskan' => 'bg-purple-100 text-purple-700 border-purple-200',
            default      => 'bg-gray-100 text-gray-700 border-gray-200',
        };

        $label = ucfirst($status);

        if ($isCurrentRecipient) {
            $label = match ($status) {
                'dikirim', 'diteruskan' => 'Dokumen Masuk',
                'diterima', 'selesai'   => 'Diterima',
                'ditolak'               => 'Ditolak',
                default                 => $label,
            };
        } elseif ($isSender) {
            $label = match ($status) {
                'dikirim'    => 'Menunggu Penerima',
                'diteruskan' => 'Diteruskan ke ' . ($this->penerima->name ?? '?'),
                'diterima', 'selesai' => 'Diterima',
                'ditolak'    => 'Ditolak',
                default      => $label,
            };
            if ($status === 'diteruskan') {
                $color = 'bg-indigo-100 text-indigo-700 border-indigo-200';
            }
        }

        return ['label' => $label, 'color' => $color];
    }
}