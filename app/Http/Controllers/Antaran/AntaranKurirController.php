<?php

namespace App\Http\Controllers\Antaran;

use App\Http\Controllers\Controller;
use App\Traits\AntaranHelpers;
use App\Traits\CompressesImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * ANTARAN — sisi KURIR.
 * Catatan: kurir di modul ini sengaja TIDAK punya opsi "Tolak" (beda dari
 * Messenger lama). Kalau dokumen fisik belum siap, pakai kembalikan().
 */
class AntaranKurirController extends Controller
{
    use AntaranHelpers, CompressesImages;

    /** Sama seperti di AntaranController — biar gak tergantung sinkron trait. */
    private function getFileUrl(?string $filename, string $type = 'foto_barang'): ?string
    {
        if (!$filename) return null;
        return route('messenger.file', ['type' => $type, 'filename' => $filename]);
    }

    public function proses(Request $request)
    {
        $kurir = $this->currentPelanggan();
        if (!$kurir) return back()->with('error', 'Data kurir tidak ditemukan.');

        $hasAccessAll = $this->hasAccessAll();

        $query = DB::table('tb_transaksi as t')
            ->leftJoin('tb_pelanggan as p', 'p.id_pelanggan', '=', 't.pengirim')
            ->select('t.*', 'p.nama_pelanggan as nama_pengirim', 'p.no_hp_pelanggan as no_hp_pengirim')
            ->whereNotIn('t.status', ['Terkirim', 'Ditolak', 'Batal']);

        if (!$hasAccessAll) {
            $query->where(fn($q) => $q->where('t.kurir', $kurir->id_pelanggan)->orWhere('t.kurir', 0));
        }

        $transaksi = $query->orderByDesc('t.created_at')->get()->map(function ($item) {
            $item->foto_barang_url = $this->getFileUrl($item->foto_barang ?? null, 'foto_barang');
            return $item;
        });

        return view('antaran.proses', compact('transaksi', 'kurir'));
    }

    public function antar($no_transaksi)
    {
        $kurir = $this->currentPelanggan();
        if (!$kurir) return back()->with('error', 'Data kurir tidak ditemukan.');

        $trx = DB::table('tb_transaksi')->where('no_transaksi', $no_transaksi)->first();
        if (!$trx) return back()->with('error', 'Resi tidak ditemukan');
        if (!in_array($trx->status, ['Belum Terkirim', 'Pengiriman Dibuat'])) {
            return back()->with('error', 'Status tidak valid');
        }

        DB::table('tb_transaksi')->where('no_transaksi', $no_transaksi)->update([
            'status'     => 'Proses Pengiriman',
            'kurir'      => $kurir->id_pelanggan,
            'waktu'      => $this->appendWaktu($trx->waktu, 'Proses Pengiriman'),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Kiriman diambil, tracking dimulai.');
    }

    public function selesaikan(Request $request, $no_transaksi)
    {
        $request->validate([
            'gambar_akhir'  => 'required|image|mimes:jpg,jpeg,png|max:5120',
            'note_penerima' => 'nullable|string|max:500',
        ]);

        $kurir = $this->currentPelanggan();
        if (!$kurir) return back()->with('error', 'Data kurir tidak ditemukan.');

        $trx = DB::table('tb_transaksi')->where('no_transaksi', $no_transaksi)->first();
        if (!$trx) return back()->with('error', 'Resi tidak ditemukan');
        if ($trx->status !== 'Proses Pengiriman' || $trx->kurir != $kurir->id_pelanggan) {
            return back()->with('error', 'Status/kurir tidak valid');
        }

        try {
            $saved = $this->storeCompressedOrMove(
                $request->file('gambar_akhir'),
                'messenger/gambar_akhir',
                'bukti_' . time() . '_' . $no_transaksi
            );

            DB::table('tb_transaksi')->where('no_transaksi', $no_transaksi)->update([
                'status'        => 'Terkirim',
                'gambar_akhir'  => $saved,
                'note_penerima' => $request->note_penerima,
                'waktu'         => $this->appendWaktu($trx->waktu, 'Terkirim'),
                'updated_at'    => now(),
            ]);

            return back()->with('success', 'Kiriman selesai.');
        } catch (\Exception $e) {
            Log::error('Antaran selesaikan error: ' . $e->getMessage());
            return back()->with('error', 'Gagal upload bukti: ' . $e->getMessage());
        }
    }

    public function kembalikan($no_transaksi)
    {
        $kurir = $this->currentPelanggan();
        if (!$kurir) return back()->with('error', 'Data kurir tidak ditemukan.');

        $trx = DB::table('tb_transaksi')->where('no_transaksi', $no_transaksi)->first();
        if (!$trx) return back()->with('error', 'Resi tidak ditemukan');
        if (!in_array($trx->status, ['Belum Terkirim', 'Pengiriman Dibuat', 'Proses Pengiriman'])) {
            return back()->with('error', 'Status tidak valid.');
        }

        DB::table('tb_transaksi')->where('no_transaksi', $no_transaksi)->update([
            'status'     => 'Dokumen Belum Tersedia',
            'kurir'      => $kurir->id_pelanggan,
            'waktu'      => $this->appendWaktu($trx->waktu, 'Dokumen Belum Tersedia'),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Menunggu dokumen tersedia dari pengirim.');
    }
}
