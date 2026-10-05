<?php

namespace App\Http\Controllers;

use App\Models\GaFileAccessRequest;

class GaFileStatusController extends Controller
{
    /**
     * Dipolling browser tiap 2 detik. Hanya mengembalikan status;
     * file itu sendiri tetap diambil lewat route aslinya (dengan otorisasi modulnya).
     */
    public function show($id)
    {
        $r = GaFileAccessRequest::findOrFail($id);

        return response()->json([
            'status'  => $r->status,
            'ready'   => $r->status === 'COMPLETED',
            'failed'  => $r->status === 'FAILED',
            'message' => $r->status === 'FAILED' ? 'Gagal mengambil file dari server lokal. Hubungi admin.' : null,
        ])->header('Cache-Control', 'no-store');
    }
}
