<?php
namespace App\Models\StockCtl;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Permintaan extends Model
{
    protected $table = 'stock_ctl_permintaan';
    protected $primaryKey = 'id_permintaan';
    public $timestamps = false;

    // Status constants
    const STATUS_PENDING_L1 = 'pending_l1';
    const STATUS_PENDING_ADMIN = 'pending_admin';
    const STATUS_APPROVED = 'disetujui';
    const STATUS_REJECTED = 'ditolak';
    const STATUS_CANCELLED = 'dibatalkan';

    protected $fillable = [
        // 'nomor_permintaan' dihapus karena tidak digunakan
        'id_user_pemohon',
        'id_barang',
        'jumlah',
        'keterangan',
        'status',
        'id_area_kerja',
        'approved_l1_by',
        'approved_l1_at',
        'approved_admin_by',
        'approved_admin_at',
        'rejected_by',
        'rejected_at',
        'alasan_tolak', 
        'rejection_reason',
    ];

    // Kode per id_bisnis_unit (id sesuai tb_bisnis_unit). Unit baru: tambahkan di sini.
    const KODE_UNIT = [1 => 'Corp', 2 => 'CMT', 3 => 'PTY', 4 => 'PLT', 5 => 'DWS'];

    // Supaya ikut muncul di ->toJson() (dipakai modal detail di view)
    protected $appends = ['no_tampil'];

    private static $petaNomor = null;

    protected static function booted()
    {
        // permintaan baru dibuat -> hitung ulang peta nomor pada pemakaian berikutnya
        static::created(function () {
            self::$petaNomor = null;
        });
    }

    protected $casts = [
        'tanggal_permintaan' => 'datetime',
        'tanggal_approval' => 'datetime',
        'approved_l1_at' => 'datetime',
        'approved_admin_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Peta id_permintaan => nomor, dihitung dari data (tidak disimpan di database).
     * Format {kode unit}-{2 digit tahun}-{urut}, contoh CMT-26-01.
     * Urutan per unit per tahun mengikuti id_permintaan, jadi selalu rapat tanpa lompat.
     * Unit diambil dari area kerja permintaan, tahun dari tanggal_permintaan (WIB).
     * Dihitung sekali per request.
     */
    public static function petaNomor()
    {
        if (self::$petaNomor !== null) {
            return self::$petaNomor;
        }

        $rows = DB::table('stock_ctl_permintaan as p')
            ->join('stock_ctl_area_kerja as a', 'a.id_area_kerja', '=', 'p.id_area_kerja')
            ->orderBy('p.id_permintaan')
            ->get(['p.id_permintaan', 'p.tanggal_permintaan', 'a.id_bisnis_unit']);

        $urut = [];
        $peta = [];
        foreach ($rows as $r) {
            $kode = self::KODE_UNIT[$r->id_bisnis_unit] ?? null;
            if (!$kode || !$r->tanggal_permintaan) {
                continue;
            }
            $yy  = Carbon::parse($r->tanggal_permintaan)->timezone('Asia/Jakarta')->format('y');
            $key = $kode . '-' . $yy;
            $urut[$key] = ($urut[$key] ?? 0) + 1;
            $peta[$r->id_permintaan] = $key . '-' . str_pad($urut[$key], 2, '0', STR_PAD_LEFT);
        }

        return self::$petaNomor = $peta;
    }

    /**
     * Nomor untuk ditampilkan. Jika tidak bisa dihitung (unit belum ada di KODE_UNIT),
     * jatuh ke format lama supaya tidak pernah kosong.
     */
    public function getNoTampilAttribute()
    {
        return self::petaNomor()[$this->id_permintaan] ?? ('ATK-SC-' . $this->id_permintaan);
    }

    /**
     * Relasi ke pemohon (user yang mengajukan)
     */
    public function pemohon()
    {
        return $this->belongsTo(User::class, 'id_user_pemohon');
    }

    /**
     * Relasi ke barang
     */
    public function barang()
    {
        return $this->belongsTo(Barang::class, 'id_barang');
    }

    /**
     * Relasi ke atasan yang menyetujui level 1 (L1)
     */
    public function approverL1()
    {
        return $this->belongsTo(User::class, 'approved_l1_by');
    }

    /**
     * Relasi ke admin yang menyetujui level 2 (final)
     */
    public function approverAdmin()
    {
        return $this->belongsTo(User::class, 'approved_admin_by');
    }

    /**
     * Relasi ke user yang menolak (bisa L1 atau admin)
     */
    public function rejector()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Relasi ke area kerja
     */
    public function areaKerja()
    {
        return $this->belongsTo(AreaKerja::class, 'id_area_kerja');
    }

    /**
     * Relasi 'approver' untuk kompatibilitas dengan kode lama.
     * Mengembalikan admin approval (final) jika ada.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_admin_by');
    }
    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}