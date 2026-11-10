<?php

namespace App\Support\budget_s;

use App\Models\budget_m\BudgetForm;
use App\Models\budget_m\BudgetFormIsian;
use App\Models\budget_m\BudgetItem;
use App\Models\budget_m\BudgetPengaturan;
use App\Models\budget_m\BudgetTagihan;
use App\Models\budget_m\BudgetTahun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Satu isian form = satu baris budget_tagihan (dikaitkan lewat budget_tagihan.isian_id).
 * Semua angka dihitung DI SERVER: total = DPP + PPN + materai.
 * Pesan DomainException aman ditampilkan apa adanya ke pengguna.
 */
class BudgetFormService
{
    /**
     * Hitung PPN, materai, dan total.
     * Saat mengubah isian lama, tarif PPN dan nominal materai yang tersimpan dipertahankan
     * (perubahan pengaturan tidak menggeser angka invoice yang sudah dicatat).
     */
    public static function hitung(BudgetForm $form, float $dpp, bool $kenaPpn, bool $kenaMaterai, ?BudgetFormIsian $lama = null): array
    {
        $persen = 0.0;
        if ($form->pakai_ppn && $kenaPpn) {
            $persen = ($lama && (float) $lama->ppn_persen > 0) ? (float) $lama->ppn_persen : BudgetPengaturan::ppnPersen();
        }
        $ppn = $persen > 0 ? round($dpp * $persen / 100, 0) : 0.0;

        $materai = 0.0;
        if ($form->pakai_materai && $kenaMaterai) {
            $materai = ($lama && (float) $lama->materai > 0) ? (float) $lama->materai : BudgetPengaturan::materaiNominal();
        }

        return [
            'ppn_persen' => $persen > 0 ? $persen : null,
            'ppn' => $ppn,
            'materai' => $materai,
            'total' => round($dpp + $ppn + $materai, 2),
        ];
    }

    /**
     * Simpan isian baru atau ubah isian lama, sekaligus baris tagihannya (satu transaksi).
     * $in: tanggal, no_dokumen, dpp, kena_ppn, kena_materai, data (id_field => nilai, hanya field aktif).
     */
    public static function simpan(BudgetForm $form, ?BudgetFormIsian $isian, array $in, $user): BudgetFormIsian
    {
        if (!$form->aktif) {
            throw new \DomainException('Form "' . $form->nama . '" sudah diarsipkan, tidak bisa dipakai lagi.');
        }

        $tanggal = \Illuminate\Support\Carbon::parse($in['tanggal']);
        $tahun = (int) $tanggal->year;
        $bulan = (int) $tanggal->month;

        if (!BudgetTahun::where('tahun', $tahun)->exists()) {
            throw new \DomainException('Tahun anggaran ' . $tahun . ' belum terdaftar. Minta Admin menambahkannya di Master Data.');
        }
        $item = $form->item;
        if (!BudgetItem::berlakuDi($tahun)->whereKey($item->id)->exists()) {
            throw new \DomainException('Item "' . $item->nama . '" belum terdaftar atau tidak berlaku di tahun ' . $tahun . '. Cek tanggal invoice atau hubungi Admin.');
        }

        $dpp = (float) $in['dpp'];
        $h = static::hitung($form, $dpp, !empty($in['kena_ppn']), !empty($in['kena_materai']), $isian);

        $isianBaru = DB::transaction(function () use ($form, $isian, $in, $user, $tanggal, $tahun, $bulan, $dpp, $h, $item, &$sebelum) {
            $sebelum = $isian ? $isian->getAttributes() : null;

            // Nilai field arsip yang sudah tersimpan tetap dipertahankan; yang aktif ditimpa input baru
            $data = array_merge($isian?->data ?? [], $in['data'] ?? []);
            $data = array_filter($data, fn ($v) => $v !== null && $v !== '');

            $kolom = [
                'tanggal' => $tanggal->toDateString(),
                'no_dokumen' => ($in['no_dokumen'] ?? '') !== '' ? $in['no_dokumen'] : null,
                'dpp' => $dpp,
                'ppn_persen' => $h['ppn_persen'],
                'ppn' => $h['ppn'],
                'materai' => $h['materai'],
                'total' => $h['total'],
                'data' => $data ?: null,
            ];
            if ($isian) {
                $isian->update($kolom);
            } else {
                $isian = BudgetFormIsian::create($kolom + ['form_id' => $form->id, 'input_oleh' => $user->id]);
            }

            $tagihan = BudgetTagihan::firstOrNew(['isian_id' => $isian->id]);
            $tagihan->fill([
                'item_id' => $item->id,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'tagihan' => $dpp,
                'ppn' => $h['ppn'],
                'biaya_materai' => $h['materai'],
                'total' => $h['total'],
                'keterangan' => Str::limit($form->nama . ($isian->no_dokumen ? ' #' . $isian->no_dokumen : ''), 500, ''),
            ]);
            if (!$tagihan->exists) {
                $tagihan->input_oleh = $user->id;
            }
            $tagihan->save();

            return $isian;
        });

        BudgetCatat::tulis($sebelum ? 'ubah' : 'tambah', 'isian', $isianBaru->id, $form->nama, $sebelum, $isianBaru->getAttributes(), ['item_id' => $item->id, 'tahun' => $tahun]);

        return $isianBaru;
    }

    /** Hapus isian beserta baris tagihannya. */
    public static function hapus(BudgetFormIsian $isian, $user): void
    {
        $form = $isian->form()->with('item')->first();
        $tagihan = $isian->tagihan;
        $sebelum = $isian->getAttributes();
        $tahun = $tagihan?->tahun;

        DB::transaction(function () use ($isian, $tagihan) {
            $tagihan?->delete();
            $isian->delete();
        });

        BudgetCatat::tulis('hapus', 'isian', $isian->id, $form->nama ?? null, $sebelum, null, ['item_id' => $form->item_id ?? null, 'tahun' => $tahun]);
    }
}
