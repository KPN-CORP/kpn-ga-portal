<?php

namespace App\Http\Controllers\IDCard;

use App\Http\Controllers\Controller;
use App\Traits\IDCardAccessTrait;

class IDCardBaseController extends Controller
{
    use IDCardAccessTrait;

    protected $kategoriLabels = \App\Models\IDCard\RequestIdCard::KATEGORI_LABELS
        + \App\Models\IDCard\RequestIdCard::KATEGORI_LEGACY;

    protected $statusLabels = [
        'pending'  => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
    ];
}