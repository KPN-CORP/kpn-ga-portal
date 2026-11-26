<?php

namespace App\Models\budget_m;

use App\Models\User;
use App\Support\budget_s\BudgetPeran;
use Illuminate\Database\Eloquent\Model;

class BudgetItem extends Model
{
    protected $table = 'budget_item';
    protected $fillable = ['sub_id', 'nama', 'bu', 'pt', 'dept_head_user_id', 'pic_user_id', 'sumber', 'keterangan', 'aktif', 'tahun_mulai', 'tahun_akhir', 'item_asal_id'];
    protected $casts = ['aktif' => 'boolean'];

    public function sub()
    {
        return $this->belongsTo(BudgetSub::class, 'sub_id');
    }

    public function deptHead()
    {
        return $this->belongsTo(User::class, 'dept_head_user_id');
    }

    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function asal()
    {
        return $this->belongsTo(self::class, 'item_asal_id');
    }

    /**
     * ATURAN TAHUN (satu aturan): item berlaku di tahun T bila terdaftar di budget_item_tahun untuk T dan aktif di T.
     * Nonaktif hanya berlaku untuk tahun itu; tahun lain tidak terpengaruh. Tidak ada lagi "berlaku sampai".
     */
    public function masihBerlaku(int $tahun): bool
    {
        $baris = $this->relationLoaded('tahunTerdaftar')
            ? $this->tahunTerdaftar->first(fn ($t) => (int) $t->tahun === $tahun)
            : $this->tahunTerdaftar()->where('tahun', $tahun)->first();

        return $baris !== null && (bool) $baris->aktif;
    }

    /** Item yang masih aktif di setidaknya satu tahun (pengganti pengecekan item.aktif yang lama, dipakai daftar pilihan item). */
    public function scopeAktifDiTahunMana($q)
    {
        return $q->whereHas('tahunTerdaftar', fn ($t) => $t->where('aktif', true));
    }

    /** Terdaftar di tahun ini (aktif maupun nonaktif). */
    public function terdaftarDi(int $tahun): bool
    {
        return $this->relationLoaded('tahunTerdaftar')
            ? $this->tahunTerdaftar->contains(fn ($t) => (int) $t->tahun === $tahun)
            : $this->tahunTerdaftar()->where('tahun', $tahun)->exists();
    }

    /** Item yang terdaftar dan aktif di tahun ini. */
    public function scopeMasihBerlakuDi($q, int $tahun)
    {
        return $q->whereHas('tahunTerdaftar', fn ($t) => $t->where('tahun', $tahun)->where('aktif', true));
    }

    public function tahunTerdaftar()
    {
        return $this->hasMany(BudgetItemTahun::class, 'item_id');
    }

    /** Daftarkan item ke tahun ini (aktif). Tidak error bila sudah ada dan tidak mengubah status yang sudah ada. True bila baru terdaftar. */
    public function daftarkanDi(int $tahun, bool $aktif = true): bool
    {
        return BudgetItemTahun::firstOrCreate(['item_id' => $this->id, 'tahun' => $tahun], ['aktif' => $aktif])->wasRecentlyCreated;
    }

    /** Aktifkan atau nonaktifkan item HANYA di tahun ini. */
    public function aturAktifDi(int $tahun, bool $aktif): void
    {
        BudgetItemTahun::updateOrCreate(['item_id' => $this->id, 'tahun' => $tahun], ['aktif' => $aktif]);
        $this->unsetRelation('tahunTerdaftar');
    }

    /**
     * Item yang tampil di tahun ini: yang aktif di tahun itu, ATAU yang punya data (budget/tagihan) di tahun itu.
     * Syarat kedua menjamin data tahun lama tidak pernah hilang walau item sudah dinonaktifkan di tahun tersebut.
     * Item nonaktif tanpa data tidak tampil (kecuali diminta lewat scopeTermasukNonaktifDi).
     */
    public function scopeBerlakuDi($q, int $tahun)
    {
        return $q->where(function ($w) use ($tahun) {
            $w->where(fn ($a) => $a->masihBerlakuDi($tahun))
                ->orWhereHas('bulan', fn ($b) => $b->where('tahun', $tahun))
                ->orWhereHas('tagihan', fn ($t) => $t->where('tahun', $tahun));
        });
    }

    /** Semua item yang terdaftar di tahun ini, termasuk yang nonaktif (untuk daftar "tampilkan nonaktif" supaya bisa diaktifkan lagi). */
    public function scopeTermasukNonaktifDi($q, int $tahun)
    {
        return $q->where(function ($w) use ($tahun) {
            $w->whereHas('tahunTerdaftar', fn ($t) => $t->where('tahun', $tahun))
                ->orWhereHas('bulan', fn ($b) => $b->where('tahun', $tahun))
                ->orWhereHas('tagihan', fn ($t) => $t->where('tahun', $tahun));
        });
    }

    /**
     * Item yang benar-benar dipakai untuk tanggal/tahun tertentu: dirinya bila aktif di tahun itu,
     * kalau tidak, penerus/pendahulunya (rantai item_asal_id) yang aktif di tahun itu. Null bila tidak ada.
     * Dipakai agar form realisasi yang menunjuk item lama tetap bisa mencatat ke item yang berlaku di tahun baru.
     */
    public function untukTahun(int $tahun): ?self
    {
        if ($this->masihBerlaku($tahun)) {
            return $this;
        }
        $kandidat = collect([$this->id]);
        $dilihat = [$this->id => true];
        for ($n = 0; $n < 8 && $kandidat->isNotEmpty(); $n++) {
            $sambung = self::query()->whereIn('item_asal_id', $kandidat)
                ->orWhereIn('id', self::query()->whereIn('id', $kandidat)->whereNotNull('item_asal_id')->pluck('item_asal_id'))
                ->get()->filter(fn ($i) => !isset($dilihat[$i->id]));
            foreach ($sambung as $i) {
                $dilihat[$i->id] = true;
                if ($i->masihBerlaku($tahun)) {
                    return $i;
                }
            }
            $kandidat = $sambung->pluck('id');
        }
        return null;
    }

    public function bulan()
    {
        return $this->hasMany(BudgetBulan::class, 'item_id');
    }

    public function tagihan()
    {
        return $this->hasMany(BudgetTagihan::class, 'item_id');
    }

    /** Admin: semua item. Dept Head: item miliknya (seluruh tim). PIC: item Dept-nya, atau hanya bagian yang ditugaskan bila dibatasi. Lainnya: kosong. */
    public function scopeTerlihatOleh($q, $user)
    {
        $peran = BudgetPeran::peran($user);
        if ($peran === BudgetPeran::ADMIN) {
            return $q;
        }
        if ($peran === BudgetPeran::PIC && BudgetPeran::picDibatasi($user)) {
            // Dept Head memakai saklar "Batasi PIC": PIC hanya melihat bagian yang ditugaskan
            return BudgetPeran::batasItem($q, $user);
        }
        if ($peran === BudgetPeran::DEPT_HEAD || $peran === BudgetPeran::PIC) {
            // Dept Head melihat semua milik timnya; PIC tanpa pembatasan melihat data yang sama dengan Dept-nya
            return $q->whereIn('dept_head_user_id', BudgetPeran::kepalaIds($user));
        }
        return $q->whereRaw('1 = 0');
    }
}