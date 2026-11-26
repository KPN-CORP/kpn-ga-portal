<?php

namespace App\Services;

use App\Models\IDCard\IdCardItem;
use App\Models\IDCard\IdCardKartuPinjam;
use App\Models\IDCard\IdCardStokAdmin;
use App\Models\IDCard\IdCardStokLog;
use App\Models\IDCard\RequestIdCard;
use App\Models\IDCard\RequestIdCardItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Satu tempat untuk semua logika stok ID Card dan status kartu pinjam.
 *
 * STOK     : PER ADMIN. Tiap admin yang meng-approve punya stok sendiri (idcard_stok_log.user_id = pemilik).
 *            Hanya karyawan_baru / karyawan_mutasi / ganti_kartu (RequestIdCard::KATEGORI_POTONG_STOK)
 *            yang memotong, dan yang dipotong adalah stok admin yang menekan Approve.
 * PINJAM   : magang / magang_extend / visitor TIDAK menyentuh stok sama sekali.
 *            Hanya dicatat sebagai kartu Digunakan / Ada (statusKartuPinjam).
 */
class IDCardStokService
{
    // ------------------------------------------------------------------
    // STOK (per admin)
    // ------------------------------------------------------------------

    /** Item aktif berurutan. */
    public function items(): Collection
    {
        return IdCardItem::where('is_active', 1)->orderBy('urutan')->orderBy('id')->get();
    }

    private function statusStok(int $stok, int $min): string
    {
        if ($stok <= 0) {
            return 'habis';
        }
        return ($min > 0 && $stok <= $min) ? 'menipis' : 'aman';
    }

    /** Ringkasan stok milik SATU admin: saldo, minimum, kekurangan, status per item. */
    public function ringkasan(int $ownerId): Collection
    {
        $items = $this->items();
        $saldo = IdCardStokLog::where('user_id', $ownerId)
            ->select('item_id', DB::raw('SUM(qty) as total'))
            ->groupBy('item_id')->pluck('total', 'item_id');
        $mins  = IdCardStokAdmin::where('user_id', $ownerId)->pluck('min_stok', 'item_id');

        return $items->map(function (IdCardItem $i) use ($saldo, $mins) {
            $stok   = (int) ($saldo[$i->id] ?? 0);
            $min    = (int) ($mins[$i->id] ?? 0);
            $status = $this->statusStok($stok, $min);

            return (object) [
                'id'         => $i->id,
                'kode'       => $i->kode,
                'nama'       => $i->nama,
                'stok'       => $stok,
                'min_stok'   => $min,
                'kurang'     => max(0, $min - $stok),
                'status'     => $status,
                'perlu_beli' => $status !== 'aman',
            ];
        });
    }

    /** Jumlah item yang perlu dibeli oleh satu admin (badge sidebar). Tidak melempar error. */
    public function jumlahPerluBeli(int $ownerId): int
    {
        try {
            return $this->ringkasan($ownerId)->where('perlu_beli', true)->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Admin yang punya akses menu Stok (diceklis stok_idcard di tb_access_menu).
     * Dipakai untuk pilihan pemilik stok dan "Stok Semua Admin": hanya yang punya akses, bukan semua admin.
     */
    public function daftarAdmin(): Collection
    {
        try {
            $usernames = DB::table('tb_access_menu')->where('stok_idcard', 1)->pluck('username');
        } catch (\Throwable $e) {
            return collect(); // kolom belum dibuat
        }

        return User::whereIn('username', $usernames)->orderBy('name')->get();
    }

    /**
     * Matriks stok semua admin (untuk superadmin).
     *
     * @return Collection berisi object {user, stok: [item_id => {stok,min_stok,status}], perlu_beli}
     */
    public function matriksSemuaAdmin(): Collection
    {
        $items = $this->items();
        $saldo = IdCardStokLog::select('user_id', 'item_id', DB::raw('SUM(qty) as total'))
            ->groupBy('user_id', 'item_id')->get()->groupBy('user_id');
        $mins  = IdCardStokAdmin::get()->groupBy('user_id');

        // Hanya admin yang punya akses Stok (riwayat stok admin yang aksesnya dicabut tidak ditampilkan di matriks)
        $ownerIds = $this->daftarAdmin()->pluck('id')->unique()->values();
        $users    = User::whereIn('id', $ownerIds)->orderBy('name')->get();

        return $users->map(function (User $u) use ($items, $saldo, $mins) {
            $s = ($saldo->get($u->id) ?? collect())->pluck('total', 'item_id');
            $m = ($mins->get($u->id) ?? collect())->pluck('min_stok', 'item_id');

            $per = [];
            $perluBeli = false;
            foreach ($items as $i) {
                $stok = (int) ($s[$i->id] ?? 0);
                $min  = (int) ($m[$i->id] ?? 0);
                $st   = $this->statusStok($stok, $min);
                $per[$i->id] = (object) ['stok' => $stok, 'min_stok' => $min, 'status' => $st];
                $perluBeli = $perluBeli || $st !== 'aman';
            }

            return (object) ['user' => $u, 'stok' => $per, 'perlu_beli' => $perluBeli];
        });
    }

    /** Saldo satu item milik satu admin (tanpa lock). */
    public function saldo(int $ownerId, int $itemId): int
    {
        return (int) IdCardStokLog::where('user_id', $ownerId)->where('item_id', $itemId)->sum('qty');
    }

    /**
     * Catat mutasi pada stok milik $ownerId. WAJIB dipanggil di dalam DB::transaction milik pemanggil
     * (baris item dikunci supaya dua proses tidak menghitung saldo bersamaan).
     *
     * @param int $qty bertanda: + masuk, - keluar
     */
    public function catat(int $ownerId, int $itemId, string $tipe, int $qty, ?int $byUserId, ?int $requestId = null, ?string $catatan = null): IdCardStokLog
    {
        IdCardItem::whereKey($itemId)->lockForUpdate()->firstOrFail();

        $saldoBaru = $this->saldo($ownerId, $itemId) + $qty;

        return IdCardStokLog::create([
            'user_id'       => $ownerId,
            'item_id'       => $itemId,
            'tipe'          => $tipe,
            'qty'           => $qty,
            'saldo_setelah' => $saldoBaru,
            'request_id'    => $requestId,
            'catatan'       => $catatan,
            'created_by'    => $byUserId,
            'created_at'    => now(),
        ]);
    }

    public function stokMasuk(int $ownerId, int $itemId, int $qty, ?int $byUserId, ?string $catatan = null): IdCardStokLog
    {
        return DB::transaction(fn () => $this->catat($ownerId, $itemId, 'masuk', abs($qty), $byUserId, null, $catatan));
    }

    /** Opname: set stok milik $ownerId ke angka hasil hitung fisik. Selisih dicatat sebagai penyesuaian. */
    public function opname(int $ownerId, int $itemId, int $stokFisik, ?int $byUserId, ?string $catatan = null): ?IdCardStokLog
    {
        return DB::transaction(function () use ($ownerId, $itemId, $stokFisik, $byUserId, $catatan) {
            IdCardItem::whereKey($itemId)->lockForUpdate()->firstOrFail();
            $selisih = $stokFisik - $this->saldo($ownerId, $itemId);
            if ($selisih === 0) {
                return null;
            }
            return $this->catat($ownerId, $itemId, 'penyesuaian', $selisih, $byUserId, null,
                trim('Opname, stok fisik ' . $stokFisik . ($catatan ? ' | ' . $catatan : '')));
        });
    }

    public function setMinimum(int $ownerId, int $itemId, int $min): void
    {
        IdCardStokAdmin::updateOrCreate(
            ['user_id' => $ownerId, 'item_id' => $itemId],
            ['min_stok' => $min]
        );
    }

    /**
     * Potong stok untuk request karyawan yang di-approve: yang berkurang adalah stok ADMIN YANG APPROVE.
     * Memakai transaksi milik pemanggil (ApprovalController). Melempar RuntimeException bila stok admin itu kurang.
     * Request tanpa item (request lama sebelum fitur stok) tidak memotong apa pun.
     */
    public function potongUntukRequest(RequestIdCard $req, int $approverId): void
    {
        if (!in_array($req->kategori, RequestIdCard::KATEGORI_POTONG_STOK, true)) {
            return; // magang / visitor: tidak menyentuh stok
        }

        // Admin yang tidak diceklis stok_idcard (tidak punya akses menu Stok) tidak punya stok sendiri: approve tetap jalan,
        // stok tidak dipotong (supaya tidak error "tambah stok dulu" padahal menunya tidak bisa dibuka).
        if (!\App\Support\IDCardAkses::bisaUserId($approverId, \App\Support\IDCardAkses::STOK)) {
            return;
        }

        $rows = RequestIdCardItem::with('item')->where('request_id', $req->id)->get();

        foreach ($rows as $row) {
            $nama = optional($row->item)->nama ?? ('Item #' . $row->item_id);

            IdCardItem::whereKey($row->item_id)->lockForUpdate()->firstOrFail();
            $sisa = $this->saldo($approverId, $row->item_id);

            if ($sisa < $row->qty) {
                throw new \RuntimeException("Stok {$nama} Anda tidak cukup (sisa {$sisa}, diminta {$row->qty}). Tambah stok Anda dulu di menu Stok.");
            }

            $this->catat($approverId, $row->item_id, 'keluar', -1 * (int) $row->qty, $approverId, $req->id,
                "{$req->kategori_text}: {$req->nama} ({$req->nik})");
        }
    }

    // ------------------------------------------------------------------
    // KARTU PINJAM (tanpa stok)
    // ------------------------------------------------------------------

    public static function normalisasiNomor(?string $nomor): string
    {
        return mb_strtoupper(trim((string) $nomor));
    }

    /** Daftarkan nomor kartu ke master bila belum ada (dipanggil saat approve magang/visitor). */
    public function daftarkanKartu(?string $nomor, string $kategori, ?int $userId = null): void
    {
        $nomor = trim((string) $nomor);
        if ($nomor === '') {
            return;
        }
        try {
            if (IdCardKartuPinjam::where('nomor_kartu', $nomor)->exists()) {
                return;
            }
            IdCardKartuPinjam::create([
                'nomor_kartu' => $nomor,
                'jenis'       => $kategori === 'visitor' ? 'visitor' : 'magang',
                'is_active'   => 1,
                'created_by'  => $userId,
            ]);
        } catch (\Throwable $e) {
            // Daftar kartu pinjam hanya pelengkap: jangan gagalkan approve
            \Log::warning('IDCard daftarkanKartu gagal: ' . $e->getMessage());
        }
    }

    /** True bila nomor ada di master dan ditandai tidak beredar (rusak / hilang). */
    public function kartuDitarik(?string $nomor): bool
    {
        $nomor = trim((string) $nomor);
        if ($nomor === '') {
            return false;
        }
        try {
            return IdCardKartuPinjam::where('nomor_kartu', $nomor)->where('is_active', 0)->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Status tiap kartu pinjam: digunakan | ada | nonaktif, plus pemakai aktif.
     * Digunakan = ada request magang/visitor approved, is_active = 1 dengan nomor kartu itu
     *             (artinya kartu fisik belum dikembalikan ke GA).
     *
     * @param  Collection $kartu koleksi IdCardKartuPinjam
     * @return Collection berisi object {kartu, status, pemakai, periode}
     */
    public function statusKartuPinjam(Collection $kartu): Collection
    {
        $nomor = $kartu->pluck('nomor_kartu')->all();

        $pemakai = RequestIdCard::with('bisnisUnit')
            ->whereIn('nomor_kartu', $nomor)
            ->whereIn('kategori', RequestIdCard::KATEGORI_KEMBALI)
            ->where('status', 'approved')
            ->where('is_active', 1)
            ->orderBy('id')
            ->get()
            ->keyBy(fn ($r) => self::normalisasiNomor($r->nomor_kartu)); // id terbesar menimpa (terbaru)

        return $kartu->map(function (IdCardKartuPinjam $k) use ($pemakai) {
            $p = $pemakai->get(self::normalisasiNomor($k->nomor_kartu));

            if (!$k->is_active) {
                $status = 'nonaktif';
            } elseif ($p) {
                $status = 'digunakan';
            } else {
                $status = 'ada';
            }

            return (object) [
                'kartu'   => $k,
                'status'  => $status,
                'pemakai' => $p,
                'periode' => $p ? $p->periode_info : null,
            ];
        });
    }

    /** Hitung ringkas untuk kartu-kartu pinjam (dipakai halaman Kartu Pinjam dan report). */
    public function hitungKartuPinjam(): array
    {
        $semua = $this->statusKartuPinjam(IdCardKartuPinjam::orderBy('nomor_kartu')->get());

        return [
            'total'     => $semua->count(),
            'digunakan' => $semua->where('status', 'digunakan')->count(),
            'ada'       => $semua->where('status', 'ada')->count(),
            'nonaktif'  => $semua->where('status', 'nonaktif')->count(),
        ];
    }
}
