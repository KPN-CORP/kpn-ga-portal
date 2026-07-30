<?php

namespace App\Http\Controllers\Antaran;

use App\Http\Controllers\Controller;
use App\Traits\AntaranHelpers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * ANTARAN — endpoint tracking GPS saja (dipisah biar AntaranController &
 * AntaranKurirController tidak melebar). Semua baca/tulis ke tb_lokasi_kurir.
 */
class AntaranTrackingController extends Controller
{
    use AntaranHelpers;

    /** Dipanggil kurir tiap ~15 detik selama status "Proses Pengiriman". */
    public function updateLokasi(Request $request, $no_transaksi)
    {
        $request->validate([
            'latitude'  => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'akurasi'   => 'nullable|numeric',
        ]);

        $kurir = $this->currentPelanggan();
        if (!$kurir) return response()->json(['error' => 'Kurir tidak ditemukan'], 403);

        $trx = DB::table('tb_transaksi')->where('no_transaksi', $no_transaksi)->first();
        if (!$trx || $trx->kurir != $kurir->id_pelanggan) {
            return response()->json(['error' => 'Bukan kurir resi ini'], 403);
        }
        if ($trx->status !== 'Proses Pengiriman') {
            return response()->json(['error' => 'Status tidak dalam pengantaran'], 422);
        }

        DB::table('tb_lokasi_kurir')->insert([
            'no_transaksi'  => $no_transaksi,
            'kurir'         => $kurir->id_pelanggan,
            'latitude'      => $request->latitude,
            'longitude'     => $request->longitude,
            'akurasi_meter' => $request->akurasi,
            'created_at'    => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    /** Dipanggil halaman lacak (polling) buat narik titik terbaru tanpa reload. */
    public function lokasiJson($no_transaksi)
    {
        $titik = DB::table('tb_lokasi_kurir')
            ->where('no_transaksi', $no_transaksi)
            ->orderBy('created_at')
            ->get(['latitude', 'longitude', 'created_at']);

        $trx = DB::table('tb_transaksi')->where('no_transaksi', $no_transaksi)->first(['status']);

        return response()->json(['status' => $trx->status ?? null, 'titik' => $titik]);
    }

    /**
     * Halaman peta TERPISAH: rute kurir SATU HARI PENUH, digabung dari semua
     * resi yang dia pegang hari itu — mulai dari titik jemput pertama sampai
     * titik terakhir yang tercatat (biasanya = antaran dokumen terakhir hari itu).
     * Beda dari peta di /lacak/{no_transaksi} yang cuma nampilin 1 resi.
     */
    public function ruteHarian(Request $request)
    {
        $tanggal = $request->filled('tanggal') ? $request->tanggal : now()->toDateString();

        $kurirId = $this->currentPelanggan()->id_pelanggan ?? null;
        if ($this->hasAccessAll() && $request->filled('kurir')) {
            $kurirId = (int) $request->kurir; // admin boleh lihat rute kurir lain
        }
        if (!$kurirId) abort(403, 'Data kurir tidak ditemukan.');

        $titik = DB::table('tb_lokasi_kurir')
            ->where('kurir', $kurirId)
            ->whereDate('created_at', $tanggal)
            ->orderBy('created_at')
            ->get(['no_transaksi', 'latitude', 'longitude', 'created_at']);

        $noResiHariIni = $titik->pluck('no_transaksi')->unique()->values();

        $resiHariIni = DB::table('tb_transaksi')
            ->whereIn('no_transaksi', $noResiHariIni)
            ->orderBy('created_at')
            ->get(['no_transaksi', 'alamat_asal', 'alamat_tujuan', 'status']);

        $kurir = DB::table('tb_pelanggan')->where('id_pelanggan', $kurirId)->first(['nama_pelanggan']);

        return view('antaran.rute-harian', compact('titik', 'resiHariIni', 'tanggal', 'kurirId', 'kurir'));
    }

    /** Versi JSON dari ruteHarian(), buat auto-refresh halaman peta harian. */
    public function ruteHarianJson(Request $request)
    {
        $tanggal = $request->filled('tanggal') ? $request->tanggal : now()->toDateString();

        $kurirId = $this->currentPelanggan()->id_pelanggan ?? null;
        if ($this->hasAccessAll() && $request->filled('kurir')) {
            $kurirId = (int) $request->kurir;
        }
        if (!$kurirId) return response()->json(['error' => 'Kurir tidak ditemukan'], 403);

        $titik = DB::table('tb_lokasi_kurir')
            ->where('kurir', $kurirId)
            ->whereDate('created_at', $tanggal)
            ->orderBy('created_at')
            ->get(['latitude', 'longitude', 'created_at']);

        return response()->json(['titik' => $titik]);
    }
}
