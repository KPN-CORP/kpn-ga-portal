<?php

namespace App\Support\budget_s;

use App\Models\budget_m\BudgetAkses;
use App\Models\budget_m\BudgetItem;
use App\Models\budget_m\BudgetPicHead;
use App\Models\budget_m\BudgetPicTugas;
use App\Models\budget_m\BudgetTahun;
use Illuminate\Support\Facades\Schema;

/**
 * Satu-satunya sumber hak akses modul budget (dibaca dari tabel budget_akses, BUKAN users.role).
 * Admin: akses penuh, termasuk mengubah data dan tetap bisa mengisi budget pada tahun yang dikunci.
 */
class BudgetPeran
{
    public const ADMIN = 'admin';
    public const DEPT_HEAD = 'dept_head';
    public const PIC = 'pic';

    protected static array $cache = [];
    protected static array $kepalaCache = [];
    protected static ?array $deptMap = null;
    protected static ?array $orangMap = null;
    protected static ?array $batasMap = null;
    protected static array $cakupanCache = [];
    protected static ?bool $fiturPic = null;

    public static function peran($user = null): ?string
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return null;
        }
        if (!array_key_exists($user->id, self::$cache)) {
            $peran = BudgetAkses::query()
                ->where('user_id', $user->id)->where('aktif', true)->value('peran');

            // PIC yang sudah dilepas dari semua Dept Head langsung kehilangan akses modul budget
            if ($peran === self::PIC
                && !BudgetPicHead::where('pic_user_id', $user->id)->where('aktif', true)->exists()) {
                $peran = null;
            }

            self::$cache[$user->id] = $peran;
        }
        return self::$cache[$user->id];
    }

    public static function lupakan($userId): void
    {
        unset(self::$cache[$userId], self::$kepalaCache[$userId]);
        self::$deptMap = self::$orangMap = self::$batasMap = null;
        self::$cakupanCache = [];
    }

    /** id Dept Head yang datanya boleh diakses user ini (Dept Head: dirinya; PIC: Dept Head-nya). Admin: kosong (akses semua). */
    public static function kepalaIds($user = null): array
    {
        $user = $user ?? auth()->user();
        $p = self::peran($user);
        if ($p === self::DEPT_HEAD) {
            return [(int) $user->id];
        }
        if ($p === self::PIC) {
            return self::$kepalaCache[$user->id] ??= BudgetPicHead::where('pic_user_id', $user->id)->where('aktif', true)
                ->pluck('dept_head_user_id')->map(fn ($x) => (int) $x)->all();
        }
        return [];
    }

    /** Dept Head pemilik data menurut peran: Dept Head dirinya, PIC salah satu Dept Head-nya, Admin yang dipilih. 0 = tidak jelas. */
    public static function tentukanKepala($user, $input): int
    {
        $p = self::peran($user);
        $in = (int) $input;
        if ($p === self::DEPT_HEAD) {
            return (int) $user->id;
        }
        if ($p === self::PIC) {
            $ids = self::kepalaIds($user);
            if ($in) {
                return in_array($in, $ids, true) ? $in : 0;
            }
            return count($ids) === 1 ? $ids[0] : 0;
        }
        return $p === self::ADMIN ? $in : 0;
    }

    protected static function muatDept(): void
    {
        if (self::$deptMap !== null) {
            return;
        }
        self::$deptMap = self::$orangMap = [];
        foreach (BudgetAkses::with('user')->where('peran', self::DEPT_HEAD)->get() as $a) {
            $orang = $a->user->name ?? $a->user->email ?? ('#' . $a->user_id);
            self::$orangMap[(int) $a->user_id] = $orang;
            self::$deptMap[(int) $a->user_id] = $a->nama_dept ?: $orang;
        }
    }

    /** Nama departemen/kelompok milik seorang Dept Head (jika belum diisi Admin: nama orangnya). */
    public static function namaDept($kepalaId): string
    {
        if (!$kepalaId) {
            return 'Umum';
        }
        self::muatDept();
        return self::$deptMap[(int) $kepalaId] ?? ('#' . $kepalaId);
    }

    public static function namaOrang($kepalaId): string
    {
        if (!$kepalaId) {
            return '-';
        }
        self::muatDept();
        return self::$orangMap[(int) $kepalaId] ?? ('#' . $kepalaId);
    }

    /** "Nama Dept (Nama Orang)" untuk pilihan dropdown. */
    public static function namaDeptLengkap($kepalaId): string
    {
        $d = self::namaDept($kepalaId);
        $o = self::namaOrang($kepalaId);
        return $d === $o ? $d : $d . ' (' . $o . ')';
    }

    /** Teks peran untuk sidebar: Dept Head tampil dengan nama departemennya. */
    public static function labelUser($user = null): string
    {
        $user = $user ?? auth()->user();
        $p = self::peran($user);
        return match ($p) {
            self::DEPT_HEAD => BudgetAkses::where('user_id', $user->id)->value('nama_dept') ?: 'Dept Head',
            self::PIC => 'PIC ' . collect(self::kepalaIds($user))->map(fn ($id) => self::namaDept($id))->implode(', '),
            default => self::label($p),
        };
    }

    public static function isAdmin($user = null): bool
    {
        return self::peran($user) === self::ADMIN;
    }

    public static function isDeptHead($user = null): bool
    {
        return self::peran($user) === self::DEPT_HEAD;
    }

    public static function isPic($user = null): bool
    {
        return self::peran($user) === self::PIC;
    }

    public static function label(?string $peran): string
    {
        return match ($peran) {
            self::ADMIN => 'Admin',
            self::DEPT_HEAD => 'Dept Head',
            self::PIC => 'PIC',
            default => '-',
        };
    }

    /** Item ada di departemen yang boleh diakses user (Dept Head pemilik, atau PIC dari Dept Head tersebut). Dipakai untuk MELIHAT. */
    protected static function terkait(BudgetItem $item, $user, ?string $peran): bool
    {
        if ($peran === self::PIC) {
            // Dept Head yang memakai saklar "Batasi PIC": PIC hanya terkait dengan bagian yang ditugaskan padanya
            return self::picBolehItem($item, $user);
        }
        if ($peran !== self::DEPT_HEAD) {
            return false;
        }
        return in_array((int) $item->dept_head_user_id, self::kepalaIds($user), true);
    }

    /**
     * Boleh MENGISI/MENGUBAH item: Dept Head pemilik item, dan PIC dari Dept Head tersebut.
     * PIC punya hak yang sama dengan Dept Head untuk semua item di departemennya (tidak lagi dibatasi item yang ditunjuk padanya).
     */
    protected static function bolehKelola(BudgetItem $item, $user, ?string $peran): bool
    {
        return self::terkait($item, $user, $peran);
    }

    /**
     * Hapus kategori: HANYA Admin. PIC (dan Dept Head) tidak boleh menghapus kategori.
     * Dipakai sebagai penjaga tambahan di controller, selain pembatasan di route.
     */
    public static function bisaHapusKategori($user = null): bool
    {
        return self::isAdmin($user);
    }

    /** Menunjuk/mengosongkan PIC sebuah item: Admin, Dept Head pemilik item, dan PIC dari Dept Head tersebut. */
    public static function bisaAturPic(BudgetItem $item, $user = null): bool
    {
        $user = $user ?? auth()->user();
        $p = self::peran($user);
        if ($p === self::PIC && self::batasiPic($item->dept_head_user_id)) {
            // Bila Dept Head membatasi PIC, penugasan PIC hanya dilakukan Dept Head dan Admin
            return false;
        }
        return $p === self::ADMIN || (($p === self::DEPT_HEAD || $p === self::PIC) && self::terkait($item, $user, $p));
    }

    public static function bisaLihat(BudgetItem $item, $user = null): bool
    {
        $user = $user ?? auth()->user();
        $p = self::peran($user);
        return $p === self::ADMIN || ($p !== null && self::terkait($item, $user, $p));
    }

    /** Ubah/nonaktifkan item: Admin, Dept Head pemilik, dan PIC yang ditunjuk pada item itu. */
    public static function bisaUbahItem(BudgetItem $item, $user = null): bool
    {
        $user = $user ?? auth()->user();
        $p = self::peran($user);
        return $p === self::ADMIN || self::bolehKelola($item, $user, $p);
    }

    /** Isi budget bulanan. Tahun terkunci menutup Dept Head dan PIC, tetapi tidak Admin. */
    public static function bisaIsiBudget(BudgetItem $item, int $tahun, $user = null): bool
    {
        $user = $user ?? auth()->user();
        $p = self::peran($user);
        if ($p === self::ADMIN) {
            return true;
        }
        if ($p === null || BudgetTahun::terkunci($tahun)) {
            return false;
        }
        return self::bolehKelola($item, $user, $p);
    }

    /** Input/ubah/hapus tagihan (tidak terpengaruh kunci tahun). Sama dengan hak kelola: Dept Head dan PIC untuk semua item departemennya. */
    public static function bisaInputTagihan(BudgetItem $item, $user = null): bool
    {
        $user = $user ?? auth()->user();
        $p = self::peran($user);
        return $p === self::ADMIN || self::bolehKelola($item, $user, $p);
    }

    // ------------------------------------------------------------------
    // PENUGASAN PIC (saklar "Batasi PIC" per Dept Head)
    // ------------------------------------------------------------------

    /** Kolom batasi_pic dan tabel budget_pic_tugas sudah dibuat? Bila belum, semua perilaku lama berlaku. */
    public static function fiturPicAktif(): bool
    {
        return self::$fiturPic ??= (Schema::hasTable('budget_pic_tugas') && Schema::hasColumn('budget_akses', 'batasi_pic'));
    }

    /** Apakah Dept Head ini membatasi PIC-nya hanya pada bagian yang ditugaskan? */
    public static function batasiPic($kepalaId): bool
    {
        if (!$kepalaId || !self::fiturPicAktif()) {
            return false;
        }
        if (self::$batasMap === null) {
            self::$batasMap = BudgetAkses::where('peran', self::DEPT_HEAD)->pluck('batasi_pic', 'user_id')
                ->map(fn ($v) => (bool) $v)->all();
        }
        return (bool) (self::$batasMap[(int) $kepalaId] ?? false);
    }

    /**
     * Cakupan data seorang PIC. bebas = Dept Head yang TIDAK membatasi (PIC melihat semua),
     * batas = Dept Head yang membatasi => daftar id kategori/sub/item yang ditugaskan.
     *
     * @return array{bebas: int[], batas: array<int, array{kat: int[], sub: int[], item: int[]}>}
     */
    public static function cakupanPic($user = null): array
    {
        $user = $user ?? auth()->user();
        if (isset(self::$cakupanCache[$user->id])) {
            return self::$cakupanCache[$user->id];
        }

        $bebas = [];
        $batas = [];
        if (self::peran($user) === self::PIC) {
            foreach (self::kepalaIds($user) as $h) {
                if (self::batasiPic($h)) {
                    $batas[$h] = ['kat' => [], 'sub' => [], 'item' => []];
                } else {
                    $bebas[] = (int) $h;
                }
            }
            if ($batas) {
                BudgetPicTugas::where('pic_user_id', $user->id)->whereIn('dept_head_user_id', array_keys($batas))->get()
                    ->each(function ($t) use (&$batas) {
                        $h = (int) $t->dept_head_user_id;
                        if ($t->item_id) {
                            $batas[$h]['item'][] = (int) $t->item_id;
                        } elseif ($t->sub_id) {
                            $batas[$h]['sub'][] = (int) $t->sub_id;
                        } elseif ($t->kategori_id) {
                            $batas[$h]['kat'][] = (int) $t->kategori_id;
                        }
                    });
            }
        }

        return self::$cakupanCache[$user->id] = ['bebas' => $bebas, 'batas' => $batas];
    }

    /** PIC yang datanya dibatasi penugasan (minimal satu Dept Head-nya memakai saklar). */
    public static function picDibatasi($user = null): bool
    {
        $user = $user ?? auth()->user();
        return self::peran($user) === self::PIC && !empty(self::cakupanPic($user)['batas']);
    }

    /** Terapkan batas penugasan pada query BudgetItem untuk seorang PIC (dipakai scope item dan relasi). */
    public static function batasItem($q, $user)
    {
        $c = self::cakupanPic($user);
        $uid = (int) $user->id;

        return $q->where(function ($w) use ($c, $uid) {
            if ($c['bebas']) {
                $w->orWhereIn('dept_head_user_id', $c['bebas']);
            }
            foreach ($c['batas'] as $h => $t) {
                $w->orWhere(function ($x) use ($h, $t, $uid) {
                    $x->where('dept_head_user_id', $h)->where(function ($y) use ($t, $uid) {
                        $y->where('pic_user_id', $uid)
                            ->orWhereIn('id', $t['item'])
                            ->orWhereIn('sub_id', $t['sub'])
                            ->orWhereHas('sub', fn ($s) => $s->whereIn('kategori_id', $t['kat']));
                    });
                });
            }
            if (!$c['bebas'] && !$c['batas']) {
                $w->whereRaw('1 = 0');
            }
        });
    }

    /** Boleh PIC ini menyentuh item ini (melihat/mengelola)? Memperhitungkan saklar Dept Head pemilik item. */
    public static function picBolehItem(BudgetItem $item, $user): bool
    {
        $c = self::cakupanPic($user);
        $h = (int) $item->dept_head_user_id;

        if (in_array($h, $c['bebas'], true)) {
            return true;
        }
        if (!isset($c['batas'][$h])) {
            return false;
        }
        $t = $c['batas'][$h];
        if ((int) $item->pic_user_id === (int) $user->id
            || in_array((int) $item->id, $t['item'], true)
            || in_array((int) $item->sub_id, $t['sub'], true)) {
            return true;
        }
        $katId = (int) ($item->relationLoaded('sub') ? ($item->sub->kategori_id ?? 0) : $item->sub()->value('kategori_id'));
        return in_array($katId, $t['kat'], true);
    }

    protected static function semuaTugas(array $c, string $jenis): array
    {
        return collect($c['batas'])->flatMap(fn ($t) => $t[$jenis])->unique()->values()->all();
    }

    /** Scope kategori untuk PIC yang dibatasi: kategori yang ditugaskan, atau yang memuat sub/item yang ditugaskan. */
    public static function batasKategori($q, $user)
    {
        $c = self::cakupanPic($user);
        $katSemua = self::semuaTugas($c, 'kat');
        $subSemua = self::semuaTugas($c, 'sub');

        return $q->where(function ($w) use ($c, $user, $katSemua, $subSemua) {
            if ($c['bebas']) {
                // Dept Head tanpa pembatasan: perilaku lama (termasuk kategori Umum)
                $w->orWhereIn('dept_head_user_id', $c['bebas'])->orWhereNull('dept_head_user_id');
            }
            $w->orWhere(function ($x) use ($c, $user, $katSemua, $subSemua) {
                $x->where(fn ($o) => $o->whereNull('dept_head_user_id')->orWhereIn('dept_head_user_id', array_keys($c['batas'])))
                    ->where(function ($y) use ($user, $katSemua, $subSemua) {
                        $y->whereIn('id', $katSemua)->orWhereHas('subs', function ($s) use ($user, $subSemua) {
                            $s->where(function ($z) use ($user, $subSemua) {
                                $z->whereIn('budget_sub.id', $subSemua)
                                    ->orWhereHas('items', fn ($i) => self::batasItem($i, $user));
                            });
                        });
                    });
            });
        });
    }

    /** Scope subkategori untuk PIC yang dibatasi. */
    public static function batasSub($q, $user)
    {
        $c = self::cakupanPic($user);
        $katSemua = self::semuaTugas($c, 'kat');
        $subSemua = self::semuaTugas($c, 'sub');

        return $q->whereHas('kategori', fn ($k) => $k->terlihatOleh($user))
            ->where(function ($w) use ($c, $user, $katSemua, $subSemua) {
                if ($c['bebas']) {
                    $w->orWhereHas('kategori', fn ($k) => $k->where(fn ($o) => $o->whereIn('dept_head_user_id', $c['bebas'])->orWhereNull('dept_head_user_id')));
                }
                $w->orWhereIn('id', $subSemua)
                    ->orWhereIn('kategori_id', $katSemua)
                    ->orWhereHas('items', fn ($i) => self::batasItem($i, $user));
            });
    }

    /** Tambah kategori/subkategori baru: Admin dan Dept Head. PIC hanya boleh mengubah, tidak menambah. */
    public static function bisaTambahMaster($user = null): bool
    {
        $p = self::peran($user ?? auth()->user());
        return $p === self::ADMIN || $p === self::DEPT_HEAD;
    }

    /** Ubah nama/urutan/status kategori: Admin; Dept Head pemilik; PIC dari Dept Head tsb (bila dibatasi: hanya yang ditugaskan). Kategori Umum: Admin saja. */
    public static function bisaUbahKategori($kategori, $user = null): bool
    {
        $user = $user ?? auth()->user();
        $p = self::peran($user);
        if ($p === self::ADMIN) {
            return true;
        }
        $owner = (int) $kategori->dept_head_user_id;
        if (!$owner) {
            return false;
        }
        if ($p === self::DEPT_HEAD) {
            return $owner === (int) $user->id;
        }
        if ($p === self::PIC && in_array($owner, self::kepalaIds($user), true)) {
            $c = self::cakupanPic($user);
            return in_array($owner, $c['bebas'], true) || in_array((int) $kategori->id, $c['batas'][$owner]['kat'] ?? [], true);
        }
        return false;
    }

    /** Ubah subkategori: sama seperti kategori; PIC yang dibatasi boleh bila subnya atau kategorinya ditugaskan. */
    public static function bisaUbahSub($sub, $user = null): bool
    {
        $user = $user ?? auth()->user();
        $kategori = $sub->kategori;
        if (!$kategori) {
            return false;
        }
        if (self::bisaUbahKategori($kategori, $user)) {
            return true;
        }
        if (self::peran($user) === self::PIC && $kategori->dept_head_user_id) {
            $owner = (int) $kategori->dept_head_user_id;
            $c = self::cakupanPic($user);
            return in_array((int) $sub->id, $c['batas'][$owner]['sub'] ?? [], true);
        }
        return false;
    }
}
