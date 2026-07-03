<?php

namespace App\Http\Controllers\IDCard;

use App\Http\Controllers\Controller;
use App\Traits\IDCardAccessTrait;

class IDCardBaseController extends Controller
{
    use IDCardAccessTrait;

    protected $kategoriLabels = [
        'karyawan_baru'    => 'Karyawan Baru',
        'karyawan_mutasi'  => 'Karyawan Mutasi',
        'ganti_kartu'      => 'Ganti Kartu',
        'magang'           => 'Magang',
        'magang_extend'    => 'Magang Extend',
        'perubahan_lantai' => 'Perubahan Lantai',
    ];

    protected $statusLabels = [
        'pending'  => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];
}