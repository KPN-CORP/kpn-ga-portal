<?php

namespace App\Http\Controllers\Antaran;

use App\Http\Controllers\Controller;
use App\Traits\AntaranHelpers;
use App\Traits\CompressesImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
            ->leftJoin('tb_pelanggan as k', 'k.id_pelanggan', '=', 't.kurir')
            ->select('t.*', 'p.nama_pelanggan as nama_pengirim', 'p.no_hp_pelanggan as no_hp_pengirim', 'k.nama_pelanggan as nama_kurir')
            ->whereNotIn('t.status', ['Terkirim', 'Ditolak', 'Batal', 'Dokumen Belum Tersedia']);

        if (!$hasAccessAll) {
            $query->where(fn($q) => $q->where('t.kurir', $kurir->id_pelanggan)->orWhere('t.kurir', 0));
        }

        $transaksi = $query->orderByDesc('t.created_at')->get()->map(function ($item) {
            $item->foto_barang_url = $this->getFileUrl($item->foto_barang ?? null, 'foto_barang');
            return $item;
        });

        return view('antaran.proses', compact('transaksi', 'kurir'));
    }

    public function antar(Request $request, $no_transaksi)
    {
        // Foto ambil WAJIB: kurir foto barang saat mengambil, baru tracking dimulai.
        $request->validate([
            'gambar_awal' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);

        $kurir = $this->currentPelanggan();
        if (!$kurir) return back()->with('error', 'Data kurir tidak ditemukan.');

        $trx = DB::table('tb_transaksi')->where('no_transaksi', $no_transaksi)->first();
        if (!$trx) return back()->with('error', 'Resi tidak ditemukan');
        if (!in_array($trx->status, ['Belum Terkirim', 'Pengiriman Dibuat'])) {
            return back()->with('error', 'Status tidak valid');
        }

        try {
            $disk = Storage::disk('private');

            // Folder baru: pastikan ada & bisa ditulis. Kalau belum ada, proses copy/kompres
            // bisa gagal diam-diam (nama file tersimpan di DB tapi file-nya tidak ada).
            $disk->makeDirectory('messenger/gambar_awal');

            $saved = $this->storeCompressedOrMove(
                $request->file('gambar_awal'),
                'messenger/gambar_awal',
                'ambil_' . time() . '_' . $no_transaksi
            );

            // Pastikan file benar-benar ada di disk SEBELUM status diubah
            if (!$saved || !$disk->exists('messenger/gambar_awal/' . $saved)) {
                Log::error('Antaran antar: file foto ambil tidak tersimpan', [
                    'no_transaksi' => $no_transaksi,
                    'saved'        => $saved,
                ]);
                return back()->with('error', 'Foto gagal tersimpan di server (cek izin tulis folder messenger/gambar_awal di disk private).');
            }

            // whereIn status = cegah 2 kurir mengambil resi yang sama bersamaan
            $updated = DB::table('tb_transaksi')
                ->where('no_transaksi', $no_transaksi)
                ->whereIn('status', ['Belum Terkirim', 'Pengiriman Dibuat'])
                ->update([
                    'status'      => 'Proses Pengiriman',
                    'kurir'       => $kurir->id_pelanggan,
                    'gambar_awal' => $saved,
                    'waktu'       => $this->appendWaktu($trx->waktu, 'Proses Pengiriman'),
                    'updated_at'  => now(),
                ]);

            if (!$updated) {
                Storage::disk('private')->delete('messenger/gambar_awal/' . $saved);
                return back()->with('error', 'Resi sudah diambil kurir lain.');
            }

            // Kirim ulang: buang foto ambil lama biar storage tidak menumpuk
            if (!empty($trx->gambar_awal) && $trx->gambar_awal !== $saved) {
                Storage::disk('private')->delete('messenger/gambar_awal/' . $trx->gambar_awal);
            }

            return back()->with('success', 'Kiriman diambil, tracking dimulai.');
        } catch (\Exception $e) {
            Log::error('Antaran antar error: ' . $e->getMessage());
            return back()->with('error', 'Gagal upload foto ambil: ' . $e->getMessage());
        }
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