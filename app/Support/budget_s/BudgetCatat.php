<?php

namespace App\Support\budget_s;

use App\Models\budget_m\BudgetLog;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Pencatat log perubahan. Kegagalan mencatat TIDAK boleh menggagalkan aksi utama,
 * jadi error hanya dilaporkan ke log Laravel (storage/logs).
 *
 * $tambahan: ['item_id' => ..., 'tahun' => ...] (opsional, untuk memudahkan pencarian)
 */
class BudgetCatat
{
    public static function tulis(string $aksi, string $objek, $objekId = null, ?string $ket = null, $sebelum = null, $sesudah = null, array $tambahan = []): void
    {
        try {
            BudgetLog::create([
                'user_id' => auth()->id(),
                'aksi' => $aksi,
                'objek' => $objek,
                'objek_id' => $objekId,
                'item_id' => $tambahan['item_id'] ?? null,
                'tahun' => $tambahan['tahun'] ?? null,
                'keterangan' => $ket !== null ? mb_substr($ket, 0, 255) : null,
                'sebelum' => self::data($sebelum),
                'sesudah' => self::data($sesudah),
                'ip' => request()?->ip(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    protected static function data($d): ?array
    {
        if ($d === null) {
            return null;
        }
        return $d instanceof Model ? $d->getAttributes() : (array) $d;
    }
}
