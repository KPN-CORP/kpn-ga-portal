<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Models\budget_m\BudgetBulan;
use App\Models\budget_m\BudgetKategori;
use App\Models\budget_m\BudgetTahun;
use App\Support\budget_s\BudgetExcel;
use App\Support\budget_s\BudgetFormat;
use App\Support\budget_s\BudgetPeran;
use App\Support\budget_s\BudgetRingkas;
use Illuminate\Http\Request;

/** Unduh budget bulanan + realisasi sesuai hak akses user: Excel (.xlsx, default) atau CSV (?format=csv). */
class TemplateController extends Controller
{
    public function unduh(Request $r)
    {
        $tahun = BudgetTahun::pilih($r->query('tahun'));
        $filter = [
            'bu' => $r->query('bu'),
            'pt' => $r->query('pt'),
            'kategori_id' => $r->query('kategori_id'),
        ];
        $items = BudgetRingkas::perItem($r->user(), $tahun, $filter)
            ->sortBy(fn ($i) => [
                $i->dept_head_user_id ? 0 : 1, mb_strtolower(BudgetPeran::namaDept($i->dept_head_user_id)),
                ...$i->sub->kategori->kunciUrut(), (int) $i->sub->urut, mb_strtolower($i->sub->nama), mb_strtolower($i->nama),
            ])->values();
        $map = BudgetBulan::where('tahun', $tahun)->whereIn('item_id', $items->pluck('id'))->get()->groupBy('item_id');

        if ($r->query('format') === 'csv') {
            return $this->csv($items, $map, $tahun);
        }

        $bulanan = BudgetRingkas::bulanan($items->pluck('id')->all(), $tahun);
        $isi = BudgetExcel::buat([
            'tahun' => $tahun,
            'filter' => $this->teksFilter($filter),
            'oleh' => $r->user()->name ?? '-',
            'waktu' => now()->format('d/m/Y H:i'),
            'items' => $items->map(fn ($i) => [
                'item' => $i->nama,
                'kategori' => $i->sub->kategori->nama,
                'sub' => $i->sub->nama,
                'dept' => BudgetPeran::namaDept($i->dept_head_user_id),
                'bu' => (string) $i->bu,
                'pt' => (string) $i->pt,
                'pic' => (string) ($i->pic?->name ?? ''),
                'bulan' => ($map->get($i->id) ?? collect())->pluck('nominal', 'bulan')->map(fn ($v) => (float) $v)->all(),
                'budget' => (float) $i->budget,
                'realisasi' => (float) $i->realisasi,
                'persen' => $i->persen,
            ])->all(),
            'bulanan' => ['budget' => array_values($bulanan['budget']), 'realisasi' => array_values($bulanan['realisasi'])],
        ]);

        return response()->streamDownload(function () use ($isi) {
            echo $isi;
        }, "budget-{$tahun}.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /** Teks filter yang dipakai, untuk judul di file Excel. */
    protected function teksFilter(array $f): string
    {
        $bagian = [];
        if (!empty($f['kategori_id'])) {
            $bagian[] = 'Kategori: ' . (BudgetKategori::find($f['kategori_id'])->nama ?? $f['kategori_id']);
        }
        if (!empty($f['bu'])) {
            $bagian[] = 'BU: ' . $f['bu'];
        }
        if (!empty($f['pt'])) {
            $bagian[] = 'PT: ' . $f['pt'];
        }

        return $bagian ? implode(' | ', $bagian) : 'Semua data yang boleh Anda lihat';
    }

    protected function csv($items, $map, int $tahun)
    {
        return response()->streamDownload(function () use ($items, $map, $tahun) {
            $o = fopen('php://output', 'w');
            fwrite($o, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($o, array_merge(
                ['Tahun', 'Kategori', 'Subkategori', 'Item', 'BU', 'PT', 'Departemen', 'PIC'],
                array_values(BudgetFormat::BULAN_SINGKAT),
                ['Total Budget', 'Realisasi', 'Persen']
            ), ';');

            foreach ($items as $i) {
                $bulan = ($map->get($i->id) ?? collect())->pluck('nominal', 'bulan');
                $baris = [$tahun, $i->sub->kategori->nama, $i->sub->nama, $i->nama, $i->bu, $i->pt,
                    BudgetPeran::namaDept($i->dept_head_user_id), $i->pic?->name];
                foreach (range(1, 12) as $b) {
                    $baris[] = BudgetFormat::angka($bulan->get($b));
                }
                $baris[] = BudgetFormat::angka($i->budget);
                $baris[] = BudgetFormat::angka($i->realisasi);
                $baris[] = $i->persen === null ? '' : $i->persen;
                fputcsv($o, $baris, ';');
            }
            fclose($o);
        }, "budget-{$tahun}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
