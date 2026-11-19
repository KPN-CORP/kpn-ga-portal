<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GaFile extends Model
{
    protected $table = 'ga_files';

    protected $guarded = [];

    protected $casts = [
        'local_exists'       => 'boolean',
        'cloud_exists'       => 'boolean',
        'local_verified_at'  => 'datetime',
        'local_rechecked_at' => 'datetime',
        'local_problem_at'   => 'datetime',
        'cloud_uploaded_at'  => 'datetime',
        'cloud_delete_after' => 'datetime',
        'last_viewed_at'     => 'datetime',
    ];

    public static function lookup(string $disk, string $path): ?self
    {
        return static::where('disk', $disk)->where('path_hash', sha1($path))->first();
    }

    /**
     * Local dianggap aman hanya jika sudah terverifikasi untuk versi (hash) yang sekarang.
     */
    public function isLocalVerified(): bool
    {
        return $this->local_exists
            && $this->local_verified_at !== null
            && $this->local_sha256
            && $this->local_problem_at === null
            && hash_equals((string) $this->sha256, (string) $this->local_sha256);
    }

    /**
     * Syarat penghapusan Cloud: Local terverifikasi DAN agent meng-hash ulang file itu
     * dalam verify_max_age_hours terakhir. Mencegah penghapusan berdasarkan verifikasi basi
     * (mis. file Local sudah hilang / Local di-restore ke titik lama).
     */
    public function isLocalFresh(): bool
    {
        if (! $this->isLocalVerified() || $this->local_rechecked_at === null) {
            return false;
        }

        return $this->local_rechecked_at->gt(now()->subHours((int) config('gafiles.verify_max_age_hours', 48)));
    }

    public function requests()
    {
        return $this->hasMany(GaFileAccessRequest::class, 'file_id');
    }
}
