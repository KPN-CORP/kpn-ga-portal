<?php

namespace App\Models\Memos;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MemosAttachments extends Model
{
    /** Disk privat (storage/app/private): lampiran baru hanya bisa dibuka lewat route yang diotorisasi. */
    public const DISK = 'local';

    /** Disk lama: lampiran yang diupload sebelum dipindah ke disk privat masih ada di sini. */
    private const LEGACY_DISK = 'public';

    protected $table = 'memos_attachments';

    protected $fillable = ['memo_id', 'file_path', 'original_name', 'mime_type', 'is_checked'];

    protected $casts = [
        'is_checked' => 'boolean',
    ];

    public function memo()
    {
        return $this->belongsTo(Memos::class, 'memo_id');
    }

    /**
     * Disk tempat file ini berada (null kalau file sudah hilang).
     */
    public function disk(): ?string
    {
        foreach ([self::DISK, self::LEGACY_DISK] as $disk) {
            if (Storage::disk($disk)->exists($this->file_path)) {
                return $disk;
            }
        }

        return null;
    }

    /**
     * Hapus file fisik dari disk mana pun tempatnya berada.
     */
    public function deleteFile(): void
    {
        foreach ([self::DISK, self::LEGACY_DISK] as $disk) {
            Storage::disk($disk)->delete($this->file_path);
        }
    }
}
