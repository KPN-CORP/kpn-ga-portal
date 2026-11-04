<?php

namespace App\Http\Controllers\budget_c;

use App\Http\Controllers\Controller;
use App\Models\budget_m\BudgetBulan;
use App\Models\budget_m\BudgetTahun;
use App\Support\budget_s\BudgetFormat;
use App\Support\budget_s\BudgetRingkas;
use Illuminate\Http\Request;

/** Unduh CSV budget bulanan + realisasi sesuai hak akses user (bisa dibuka di Excel). */
class TemplateController extends Controller
{
    public function unduh(Request $r)
    {
        $tahun = BudgetTahun::pilih($r->query('tahun'));
        $items = BudgetRingkas::perItem($r->user(), $tahun, [
            'bu' => $r->query('bu'),
            'kategori_id' => $r->query('kategori_id'),
        ]);
        $map = BudgetBulan::where('tahun', $tahun)->whereIn('item_id', $items->pluck('id'))->get()->groupBy('item_id');

        return response()->streamDownload(function () use ($items, $map, $tahun) {
            $o = fopen('php://output', 'w');
            fwrite($o, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
            fputcsv($o, array_merge(
                ['Tahun', 'Kategori', 'Subkategori', 'Item', 'BU', 'PT', 'Dept Head', 'PIC'],
                array_values(BudgetFormat::BULAN_SINGKAT),
                ['Total Budget', 'Realisasi', 'Persen']
            ), ';');

            foreach ($items as $i) {
                $bulan = ($map->get($i->id) ?? collect())->pluck('nominal', 'bulan');
                $baris = [$tahun, $i->sub->kategori->nama, $i->sub->nama, $i->nama, $i->bu, $i->pt,
                    $i->deptHead?->name, $i->pic?->name];
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
