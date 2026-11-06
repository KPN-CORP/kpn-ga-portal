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

    /** Masih berlaku di tahun ini: aktif, dalam rentang tahun_mulai..tahun_akhir, DAN terdaftar di tahun itu. */
    public function masihBerlaku(int $tahun): bool
    {
        $terdaftar = $this->relationLoaded('tahunTerdaftar')
            ? $this->tahunTerdaftar->contains(fn ($t) => (int) $t->tahun === $tahun)
            : $this->tahunTerdaftar()->where('tahun', $tahun)->exists();

        return $this->aktif && $terdaftar
            && ($this->tahun_mulai === null || (int) $this->tahun_mulai <= $tahun)
            && ($this->tahun_akhir === null || (int) $this->tahun_akhir >= $tahun);
    }

    /** Aktif dan dalam rentang tahun_mulai..tahun_akhir (belum melihat pendaftaran tahun). Untuk menentukan target salin. */
    public function scopeRentangDi($q, int $tahun)
    {
        return $q->where('aktif', true)
            ->where(fn ($m) => $m->whereNull('tahun_mulai')->orWhere('tahun_mulai', '<=', $tahun))
            ->where(fn ($m) => $m->whereNull('tahun_akhir')->orWhere('tahun_akhir', '>=', $tahun));
    }

    /** Item yang masih berlaku di tahun ini dan sudah terdaftar di tahun itu. */
    public function scopeMasihBerlakuDi($q, int $tahun)
    {
        return $q->rentangDi($tahun)->whereHas('tahunTerdaftar', fn ($t) => $t->where('tahun', $tahun));
    }

    public function tahunTerdaftar()
    {
        return $this->hasMany(BudgetItemTahun::class, 'item_id');
    }

    /** Daftarkan item ke tahun ini (tidak error bila sudah ada). Mengembalikan true bila baru terdaftar. */
    public function daftarkanDi(int $tahun): bool
    {
        return BudgetItemTahun::firstOrCreate(['item_id' => $this->id, 'tahun' => $tahun])->wasRecentlyCreated;
    }

    /**
     * Item yang tampil di tahun ini: yang terdaftar dan masih berlaku, ATAU yang punya data (budget/tagihan) di tahun itu.
     * Syarat kedua menjamin data tahun lama tidak pernah hilang, walau item sudah diganti nama, dihentikan, atau dinonaktifkan.
     */
    public function scopeBerlakuDi($q, int $tahun)
    {
        return $q->where(function ($w) use ($tahun) {
            $w->where(fn ($a) => $a->masihBerlakuDi($tahun))
                ->orWhereHas('bulan', fn ($b) => $b->where('tahun', $tahun))
                ->orWhereHas('tagihan', fn ($t) => $t->where('tahun', $tahun));
        });
    }

    public function bulan()
    {
        return $this->hasMany(BudgetBulan::class, 'item_id');
    }

    public function tagihan()
    {
        return $this->hasMany(BudgetTagihan::class, 'item_id');
    }

    /** Admin: semua item. Dept: item miliknya. PIC: item milik Dept-nya. Lainnya: kosong. */
    public function scopeTerlihatOleh($q, $user)
    {
        $peran = BudgetPeran::peran($user);
        if ($peran === BudgetPeran::ADMIN) {
            return $q;
        }
        if ($peran === BudgetPeran::DEPT_HEAD || $peran === BudgetPeran::PIC) {
            // PIC melihat data yang sama dengan Dept-nya
            return $q->whereIn('dept_head_user_id', BudgetPeran::kepalaIds($user));
        }
        return $q->whereRaw('1 = 0');
    }
}