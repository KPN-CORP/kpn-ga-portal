@extends('layouts.app_stock_sidebar')

@push('styles')
<style>[x-cloak] { display: none !important; }</style>
@endpush

@section('content')
@php
    $sel  = 'border border-slate-200 rounded-lg px-2.5 py-1.5 text-[13px] bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400/30 focus:border-slate-400';
    $card = 'bg-white rounded-xl border soft-border soft-shadow';
    $h    = 'text-[13px] font-semibold text-slate-800';
    $hint = 'text-[11px] text-slate-400';
    $lbl  = 'block text-[11px] font-medium text-slate-500 mb-1';
    $cari = 'w-full border border-slate-200 rounded-lg px-3 py-1.5 text-[13px] focus:outline-none focus:ring-2 focus:ring-slate-400/30 focus:border-slate-400';
    $ketBox = 'mb-3 text-xs leading-relaxed text-slate-500 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2';
    $peran = ($access['is_super'] ?? false) ? 'Superadmin' : 'Admin Area';
@endphp

<div class="space-y-5 text-[13px] text-slate-700 min-w-0 max-w-full tabular-nums"
     :style="{ paddingRight: padKanan() + 'px' }"
     @keydown.escape.window="escTutup()"
     x-data="drillPage(window.__drill)" x-cloak>

    {{-- Header --}}
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 tracking-tight">Dashboard E-Stationery (Drill-down)</h2>
            <p class="mt-0.5 text-xs text-slate-400">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-medium mr-1">{{ $peran }}</span>
                Klik grafik, status, atau barang untuk melihat datanya di panel kanan
            </p>
        </div>
        <div class="flex items-center gap-2">
            <div class="inline-flex items-center rounded-lg border border-slate-200 bg-white p-0.5 text-xs">
                <span class="px-2 text-slate-400">Hitung per</span>
                <button type="button" @click="ukuran = 'item'" class="px-3 py-1 rounded-md transition"
                        :class="ukuran === 'item' ? 'bg-slate-700 text-white' : 'text-slate-600 hover:bg-slate-50'">Item</button>
                <button type="button" @click="ukuran = 'baris'" class="px-3 py-1 rounded-md transition"
                        :class="ukuran === 'baris' ? 'bg-slate-700 text-white' : 'text-slate-600 hover:bg-slate-50'">Permintaan</button>
            </div>
            <a href="{{ route('stock-ctl.dashboard') }}" class="text-xs px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-slate-900 hover:border-slate-300">
                <i class="fas fa-chart-pie mr-1 text-[10px]"></i> Dashboard lama
            </a>
        </div>
    </div>

    {{-- Filter --}}
    <div class="{{ $card }} p-3.5">
        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="{{ $lbl }}">Tahun</label>
                <select x-model.number="tahun" @change="refresh()" class="{{ $sel }} min-w-[100px]">
                    @foreach($tahunList as $t)
                        <option value="{{ $t }}">{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">Bulan</label>
                <select x-model.number="bulan" @change="refresh()" class="{{ $sel }} min-w-[140px]">
                    <option value="0">Semua bulan</option>
                    <template x-for="(m, i) in bulanNama" :key="i">
                        <option :value="i + 1" x-text="m"></option>
                    </template>
                </select>
            </div>
            <div>
                <label class="{{ $lbl }}">Status</label>
                <select x-model="status" @change="refresh()" class="{{ $sel }} min-w-[150px]">
                    <option value="semua">Semua status</option>
                    <option value="disetujui">Disetujui</option>
                    <option value="menunggu">Menunggu approval</option>
                    <option value="ditolak">Ditolak</option>
                    <option value="dibatalkan">Dibatalkan</option>
                </select>
            </div>
            <button type="button" @click="reset()" class="text-xs px-3 py-2 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-slate-900 hover:border-slate-300">
                <i class="fas fa-rotate-left mr-1 text-[10px]"></i> Reset
            </button>
        </div>

        {{-- Jalur drill-down yang sedang dibuka --}}
        <div class="mt-3 flex flex-wrap items-center gap-1.5 text-xs" x-show="jalur().length">
            <span class="text-slate-400 mr-1">Jalur:</span>
            <template x-for="(j, i) in jalur()" :key="i">
                <span class="inline-flex items-center gap-1.5">
                    <button type="button" @click="kembaliKe(i)" class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 hover:bg-slate-200" x-text="j"></button>
                    <i class="fas fa-chevron-right text-[9px] text-slate-300" x-show="i < jalur().length - 1"></i>
                </span>
            </template>
        </div>

        <p class="mt-2 text-xs text-rose-600" x-show="errorMsg" x-text="errorMsg"></p>
        <p class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2" x-show="chartMissing">
            Library grafik (Chart.js) tidak termuat. Periksa koneksi internet / akses ke cdnjs.cloudflare.com.
        </p>
    </div>

    {{-- Tren + status (sama dengan dashboard lama) --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <div class="{{ $card }} p-4 lg:col-span-8">
            <div class="flex items-baseline justify-between mb-2">
                <h3 class="{{ $h }}"><span x-text="ukuran === 'item' ? 'Item diminta' : 'Permintaan'"></span> per <span x-text="gran === 'day' ? 'hari' : 'bulan'"></span><button type="button" @click="toggleKet('bulan')" class="ml-1 align-middle text-slate-300 hover:text-slate-500" :class="!ketTutup.bulan ? 'text-slate-500' : ''" title="Keterangan"><i class="far fa-circle-question text-[11px]"></i></button></h3>
                <span class="{{ $hint }}" x-text="gran === 'month' ? 'klik batang untuk melihat data' : 'per hari'"></span>
            </div>
            <p x-show="!ketTutup.bulan" class="{{ $ketBox }}" x-text="ket.bulan"></p>
            <div class="relative" data-chart-wrap style="height:250px"><canvas id="chBulan"></canvas></div>
        </div>

        <div class="{{ $card }} p-4 lg:col-span-4">
            <div class="flex items-baseline justify-between mb-3">
                <h3 class="{{ $h }}">Komposisi status<button type="button" @click="toggleKet('status')" class="ml-1 align-middle text-slate-300 hover:text-slate-500" :class="!ketTutup.status ? 'text-slate-500' : ''" title="Keterangan"><i class="far fa-circle-question text-[11px]"></i></button></h3>
                <span class="{{ $hint }}">klik untuk melihat data</span>
            </div>
            <p x-show="!ketTutup.status" class="{{ $ketBox }}" x-text="ket.status"></p>
            <div class="flex h-2.5 rounded-full overflow-hidden bg-slate-100 gap-px">
                <template x-for="r in statusRows" :key="r.i">
                    <button type="button" @click="bukaStatus(r.i)" :style="`width:${r.pct}%;background:${r.warna}`"
                            :class="!aktifStatus(r.i) ? 'opacity-30' : ''" class="h-full transition-opacity" :title="r.nama"></button>
                </template>
            </div>
            <div class="mt-3 divide-y divide-slate-100">
                <template x-for="r in statusRows" :key="r.i">
                    <button type="button" @click="bukaStatus(r.i)" class="w-full flex items-center gap-2.5 py-2 text-left hover:bg-slate-50/70 transition-opacity"
                            :class="!aktifStatus(r.i) ? 'opacity-40' : ''">
                        <span class="w-2 h-2 rounded-sm" :style="`background:${r.warna}`"></span>
                        <span class="flex-1 text-slate-600" x-text="r.nama"></span>
                        <span class="font-medium text-slate-900" x-text="angka(r.v)"></span>
                        <span class="w-9 text-right text-xs text-slate-400" x-text="r.pct.toFixed(0) + '%'"></span>
                    </button>
                </template>
            </div>
            <p class="mt-2 {{ $hint }}" x-text="'Satuan: ' + (ukuran === 'item' ? 'jumlah item' : 'jumlah permintaan')"></p>
        </div>
    </div>

    {{-- Daftar barang: tampil 10 baris, sisanya scroll ke bawah --}}
    <div class="{{ $card }} p-4 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h3 class="{{ $h }}">Barang <span class="font-normal text-slate-400">&middot; klik untuk melihat Bisnis Unit-nya</span></h3>
                <p class="{{ $hint }} mt-0.5" x-show="cols[0] && !cols[0].loading && !cols[0].error"
                   x-text="angka(cols[0] ? cols[0].items.length : 0) + ' barang · ' + angka(cols[0] ? cols[0].total.permintaan : 0) + ' permintaan · ' + angka(cols[0] ? cols[0].total.qty : 0) + ' item'"></p>
            </div>
            <div class="w-full sm:w-64"><input type="search" x-model="qBarang" placeholder="Cari barang" class="{{ $cari }}" aria-label="Cari barang"></div>
        </div>

        <p class="py-6 text-center text-slate-400" x-show="cols[0] && cols[0].loading">Memuat...</p>
        <p class="py-6 text-center text-rose-600" x-show="cols[0] && cols[0].error" x-text="cols[0] ? cols[0].error : ''"></p>
        <p class="py-6 text-center text-slate-400" x-show="cols[0] && !cols[0].loading && !cols[0].error && !daftar(cols[0]).length">Tidak ada data untuk filter ini.</p>

        <div class="overflow-y-auto overflow-x-auto rounded-lg border border-slate-100" style="max-height:410px" x-show="cols[0] && daftar(cols[0]).length">
            <table class="w-full text-[13px]">
                <thead class="sticky top-0 z-10 bg-white">
                    <tr class="text-[11px] uppercase tracking-wider text-slate-400 shadow-[0_1px_0_#f1f5f9]">
                        <th class="font-medium py-2 px-3 text-left w-10">#</th>
                        <th class="font-medium py-2 px-3 text-left">Barang</th>
                        <th class="font-medium py-2 px-3 text-right">Permintaan</th>
                        <th class="font-medium py-2 px-3 text-right">Item</th>
                        <th class="py-2 px-3 w-8"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(it, n) in daftar(cols[0])" :key="it.k">
                        <tr @click="pilih(0, it)" class="border-t border-slate-100 cursor-pointer transition"
                            :class="String(cols[0].sel) === String(it.k) ? 'bg-slate-100' : 'hover:bg-slate-50'">
                            <td class="py-2 px-3 text-slate-400" x-text="n + 1"></td>
                            <td class="py-2 px-3 relative">
                                <span class="absolute inset-y-1 left-0 rounded-r bg-slate-200/50 pointer-events-none" :style="'width:' + persen(cols[0], it) + '%'"></span>
                                <span class="relative" :class="String(cols[0].sel) === String(it.k) ? 'font-medium text-slate-900' : ''" x-text="it.label"></span>
                            </td>
                            <td class="py-2 px-3 text-right whitespace-nowrap" :class="ukuran === 'baris' ? 'font-medium text-slate-800' : 'text-slate-500'" x-text="angka(it.permintaan)"></td>
                            <td class="py-2 px-3 text-right whitespace-nowrap" :class="ukuran === 'item' ? 'font-medium text-slate-800' : 'text-slate-500'" x-text="angka(it.qty)"></td>
                            <td class="py-2 px-3 text-slate-300"><i class="fas fa-chevron-right text-[9px]"></i></td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Panel data (klik grafik / status): daftar permintaan, 10 baris per halaman --}}
    <template x-if="dp">
        <aside class="fixed top-0 right-0 h-full bg-white border-l border-slate-200 shadow-[-8px_0_24px_rgba(15,23,42,.08)] z-[56] flex flex-col !mt-0"
               :style="{ width: lebarData() + 'px', maxWidth: '100vw' }">
            <div class="flex items-start justify-between gap-3 px-4 pt-4 pb-3 border-b border-slate-100">
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-slate-900 truncate" x-text="dp.t"></h3>
                    <p class="text-[11px] text-slate-400 mt-0.5" x-text="'Periode ' + periodeTeks()"></p>
                </div>
                <button type="button" @click="tutupData()" class="w-7 h-7 rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700 text-lg leading-none" aria-label="Tutup">&times;</button>
            </div>
            <div class="px-4 py-3 space-y-3 overflow-y-auto flex-1">
                <input type="search" x-model="dp.q" @input="dp.pg = 0" placeholder="Cari pemohon, barang, unit, area, atau tanggal" class="{{ $cari }}" aria-label="Cari">
                <div class="grid grid-cols-3 gap-2" x-show="!dp.loading && !dp.error">
                    <template x-for="m in dpSum" :key="m.l">
                        <div class="rounded-lg bg-slate-50 border border-slate-100 px-3 py-2">
                            <p class="text-[11px] text-slate-400" x-text="m.l"></p>
                            <p class="text-[15px] font-semibold text-slate-900 mt-0.5" x-text="m.v"></p>
                        </div>
                    </template>
                </div>
                <button type="button" x-show="dp.act" @click="jalankanAksi()" class="text-xs px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-slate-900 hover:border-slate-300" x-text="dp.act ? dp.act.label : ''"></button>

                <p class="py-8 text-center text-slate-400" x-show="dp.loading">Memuat...</p>
                <p class="py-8 text-center text-rose-600" x-show="dp.error" x-text="dp.error"></p>

                <table class="w-full text-[13px]" x-show="!dp.loading && !dp.error">
                    <thead>
                        <tr class="text-[11px] uppercase tracking-wider text-slate-400">
                            <th class="font-medium pb-2 px-1 text-left">Tanggal</th>
                            <th class="font-medium pb-2 px-1 text-left">Pemohon</th>
                            <th class="font-medium pb-2 px-1 text-left">Barang</th>
                            <th class="font-medium pb-2 px-1 text-left">Status</th>
                            <th class="font-medium pb-2 px-1 text-right">Qty</th>
                            <th class="font-medium pb-2 px-1 text-right">Nilai</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="r in dpHal" :key="r.id">
                            <tr class="border-t border-slate-100 align-top">
                                <td class="py-2 px-1 whitespace-nowrap text-slate-500" x-text="fmtTgl(r.tgl)"></td>
                                <td class="py-2 px-1 text-slate-700">
                                    <span x-text="r.pemohon"></span>
                                    <div class="text-[11px] text-slate-400" x-text="r.sub"></div>
                                </td>
                                <td class="py-2 px-1 text-slate-700" x-text="r.barang"></td>
                                <td class="py-2 px-1 text-slate-700">
                                    <span class="inline-block w-1.5 h-1.5 rounded-full mr-1.5 align-middle" :style="`background:${WARNA[KODE[r.status]]}`"></span><span x-text="statusLabel(r.status)"></span>
                                </td>
                                <td class="py-2 px-1 text-right whitespace-nowrap" x-text="angka(r.qty)"></td>
                                <td class="py-2 px-1 text-right whitespace-nowrap" :class="r.nilai === 0 ? 'text-slate-400 text-xs' : ''" x-text="r.nilai === 0 ? 'Harga belum ada' : rupiah(r.nilai)"></td>
                            </tr>
                        </template>
                        <tr x-show="!dpAll.length"><td colspan="6" class="py-8 text-center text-slate-400">Tidak ada data yang sesuai.</td></tr>
                    </tbody>
                </table>
                <p class="text-[11px] text-slate-400" x-show="dp.cut && !dp.loading">Menampilkan 500 permintaan terbaru.</p>
            </div>
            <div class="flex items-center justify-between gap-2 px-4 py-3 border-t border-slate-100 text-xs text-slate-500">
                <button type="button" :disabled="dp.pg === 0" @click="dp.pg--" class="px-2.5 py-1 rounded-md border border-slate-200 hover:bg-slate-50 disabled:opacity-40 disabled:hover:bg-transparent">‹ Sebelumnya</button>
                <span class="text-center" x-text="`Halaman ${dp.pg + 1} dari ${dpJmlHal}` + (dpAll.length ? ` · ${dp.pg * 10 + 1}-${Math.min(dpAll.length, (dp.pg + 1) * 10)} dari ${angka(dpAll.length)}` : '')"></span>
                <button type="button" :disabled="dp.pg >= dpJmlHal - 1" @click="dp.pg++" class="px-2.5 py-1 rounded-md border border-slate-200 hover:bg-slate-50 disabled:opacity-40 disabled:hover:bg-transparent">Berikutnya ›</button>
            </div>
        </aside>
    </template>

    {{-- Panel drill-down: panel terbaru paling kanan, panel sebelumnya bergeser ke kiri --}}
    <div class="fixed top-0 right-0 h-full flex z-[56] !mt-0 pointer-events-none" x-show="drawers().length">
        <template x-for="d in drawers()" :key="d.c.level">
            <aside class="pointer-events-auto h-full bg-white border-l border-slate-200 shadow-[-8px_0_24px_rgba(15,23,42,.08)] flex flex-col"
                   :style="{ width: lebarPanel() + 'px', maxWidth: '100vw' }">
                <div class="flex items-start justify-between gap-3 px-4 pt-4 pb-3 border-b border-slate-100">
                    <div class="min-w-0">
                        <h3 class="text-sm font-semibold text-slate-900 truncate" x-text="judul[d.c.level]"></h3>
                        <p class="text-[11px] text-slate-400 mt-0.5 truncate" x-text="subjudul(d.i)"></p>
                    </div>
                    <button type="button" @click="tutup(d.i)" class="w-7 h-7 shrink-0 rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700 text-lg leading-none" aria-label="Tutup">&times;</button>
                </div>

                <div class="px-4 pt-3 space-y-3 flex-1 overflow-y-auto pb-4">
                    <div class="grid grid-cols-2 gap-2" x-show="!d.c.loading && !d.c.error">
                        <div class="rounded-lg bg-slate-50 border border-slate-100 px-3 py-2">
                            <p class="text-[11px] text-slate-400">Permintaan</p>
                            <p class="text-[15px] font-semibold text-slate-900 mt-0.5" x-text="angka(d.c.total.permintaan)"></p>
                        </div>
                        <div class="rounded-lg bg-slate-50 border border-slate-100 px-3 py-2">
                            <p class="text-[11px] text-slate-400">Item</p>
                            <p class="text-[15px] font-semibold text-slate-900 mt-0.5" x-text="angka(d.c.total.qty)"></p>
                        </div>
                    </div>

                    <input type="search" x-model="d.c.q" x-show="d.c.level !== 'detail' && d.c.items.length > 8" placeholder="Cari" class="{{ $cari }}" aria-label="Cari">

                    <p class="py-6 text-center text-xs text-slate-400" x-show="d.c.loading">Memuat...</p>
                    <p class="py-6 text-center text-xs text-rose-600" x-show="d.c.error" x-text="d.c.error"></p>
                    <p class="py-6 text-center text-xs text-slate-400" x-show="!d.c.loading && !d.c.error && !d.c.items.length">Tidak ada data untuk filter ini.</p>

                    <template x-if="d.c.level !== 'detail'">
                        <div class="-mx-4">
                            <template x-for="it in daftar(d.c)" :key="it.k">
                                <button type="button" @click="pilih(d.i, it)"
                                        class="relative w-full text-left px-4 py-2 transition group"
                                        :class="String(d.c.sel) === String(it.k) ? 'bg-slate-100' : 'hover:bg-slate-50'">
                                    <span class="absolute inset-y-1 left-0 rounded-r bg-slate-200/50 pointer-events-none" :style="'width:' + persen(d.c, it) + '%'"></span>
                                    <span class="relative flex items-center justify-between gap-2">
                                        <span class="truncate" :class="String(d.c.sel) === String(it.k) ? 'font-medium text-slate-900' : ''" x-text="it.label" :title="it.label"></span>
                                        <span class="flex items-center gap-2 shrink-0">
                                            <span class="text-right">
                                                <span class="block text-[13px] font-medium text-slate-800" x-text="angka(ukuran === 'item' ? it.qty : it.permintaan)"></span>
                                                <span class="block text-[10px] text-slate-400" x-text="ukuran === 'item' ? angka(it.permintaan) + ' permintaan' : angka(it.qty) + ' item'"></span>
                                            </span>
                                            <i class="fas fa-chevron-right text-[9px] text-slate-300 group-hover:text-slate-500"></i>
                                        </span>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </template>

                    <template x-if="d.c.level === 'detail'">
                        <div class="-mx-4">
                            <template x-for="it in d.c.items" :key="it.k">
                                <div class="px-4 py-2 flex items-center justify-between gap-2 border-t border-slate-100">
                                    <div class="min-w-0">
                                        <p class="text-[13px] text-slate-800" x-text="'#' + it.k"></p>
                                        <p class="text-[11px] text-slate-400" x-text="tanggal(it.tgl)"></p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p class="text-[13px] font-medium text-slate-800" x-text="angka(it.qty) + ' item'"></p>
                                        <span class="inline-block text-[10px] px-1.5 py-0.5 rounded-md" :class="statusCls(it.status)" x-text="statusLabel(it.status)"></span>
                                    </div>
                                </div>
                            </template>
                            <p class="px-4 py-2 text-[11px] text-slate-400" x-show="d.c.cut">Menampilkan 200 permintaan terbaru.</p>
                        </div>
                    </template>
                </div>
            </aside>
        </template>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.__drill = @json([
        'url'   => route('stock-ctl.dashboard-drill.data'),
        'chart' => route('stock-ctl.dashboard-drill.chart'),
        'rows'  => route('stock-ctl.dashboard-drill.rows'),
        'tahun' => $tahunIni,
    ]);
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
function drillPage(cfg) {
    // Urutan level drill-down. Untuk MENYEMBUNYIKAN level Area/Dept cukup hapus 'area' dari daftar ini.
    const LEVELS = ['barang', 'bu', 'area', 'unit', 'user', 'detail'];
    const PW = 340;            // lebar tiap panel drill-down (layar lebar)
    const PWD = 620;           // lebar panel data (klik grafik/status)
    const MAX_PANEL = 3;       // jumlah panel drill-down tampil bersamaan (layar lebar)
    const PG = 10;             // baris per halaman di panel data
    const KET_STORE = 'stockDrillKetTutup';

    // kode status (sama dengan dashboard lama): 0 disetujui, 1 ditolak, 2 pending L1, 3 dibatalkan, 4 pending admin
    const STATUS_NAMA = ['Disetujui', 'Ditolak', 'Pending L1', 'Dibatalkan', 'Pending Admin'];
    const STATUS_KEY  = ['disetujui', 'ditolak', 'pending_l1', 'dibatalkan', 'pending_admin'];
    const KODE = { disetujui: 0, ditolak: 1, pending_l1: 2, dibatalkan: 3, pending_admin: 4 };
    const WARNA = ['#4f8a79', '#b06a6e', '#c4a266', '#c3c9d3', '#6b8cae'];
    // urutan tampil: Disetujui, Pending L1, Pending Admin, Ditolak, Dibatalkan (mengikuti alur approval)
    const URUT = [0, 2, 4, 1, 3];
    // filter Status -> status yang ikut
    const FILTER_STATUS = { semua: null, disetujui: ['disetujui'], menunggu: ['pending_l1', 'pending_admin'], ditolak: ['ditolak'], dibatalkan: ['dibatalkan'] };
    const FILTER_NAMA = { disetujui: 'Disetujui', menunggu: 'Menunggu approval', ditolak: 'Ditolak', dibatalkan: 'Dibatalkan' };

    const fmt = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 2 });
    const angkaF = v => fmt.format(Number(v) || 0);
    const mq = window.matchMedia('(min-width: 1280px)');
    const BULAN = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

    // Chart disimpan di luar state Alpine supaya tidak dibungkus proxy reaktif.
    const charts = {};

    // Layout memakai `html { zoom: 0.8 }`. Chart.js menghitung posisi mouse dalam piksel layar
    // tetapi ukuran grafik dalam piksel sebelum zoom, sehingga hover/klik meleset.
    // Solusi: pembungkus grafik diberi zoom kebalikan (1 / zoom halaman) sehingga zoom efektif = 1.
    const zoomHalaman = () => parseFloat(getComputedStyle(document.documentElement).zoom) || 1;
    const kompensasiZoom = () => {
        const z = zoomHalaman();
        document.querySelectorAll('[data-chart-wrap]').forEach(w => { w.style.zoom = z !== 1 ? String(1 / z) : ''; });
        return z;
    };
    const pointer = (evt, els) => { const t = evt.native && evt.native.target; if (t) t.style.cursor = els.length ? 'pointer' : 'default'; };
    const sumbuAngka = { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' }, border: { display: false } };

    return {
        url: cfg.url,
        tahun: cfg.tahun,
        bulan: 0,
        status: 'semua',
        ukuran: 'baris',        // 'item' = jumlah barang, 'baris' = jumlah permintaan (default)
        cols: [],               // cols[0] = barang (halaman utama), cols[1..] = panel drill-down kanan
        qBarang: '',
        errorMsg: '',
        chartMissing: false,
        token: 0,
        xl: mq.matches,
        bulanNama: BULAN,
        WARNA, KODE,
        judul: { barang: 'Barang', bu: 'Bisnis Unit', area: 'Dept / Area Kerja', unit: 'Unit', user: 'User', detail: 'Total Permintaan' },

        // grafik
        gd: { gran: 'month', keys: [], labels: [], per: [], total: [] },
        get gran() { return this.gd.gran; },
        dp: null,               // panel data (klik grafik/status)
        dpTok: 0,
        ketTutup: { bulan: false, status: false },

        // ------------------------------------------------------------------
        init() {
            try { const j = JSON.parse(localStorage.getItem(KET_STORE) || '{}'); Object.keys(this.ketTutup).forEach(k => { this.ketTutup[k] = !!j[k]; }); } catch (e) {}
            mq.addEventListener('change', e => { this.xl = e.matches; });
            this.$watch('ukuran', () => this.gambarGrafik());

            // Grafik dibuat setelah komponen tampil (x-cloak lepas). Jika dibuat saat masih
            // tersembunyi, ukuran kanvas terbaca 0 dan posisi mouse bisa meleset dari grafik.
            this.$nextTick(() => requestAnimationFrame(() => {
                kompensasiZoom();
                this.buatGrafik();
                this.refresh();
                const ukurUlang = () => Object.values(charts).forEach(c => c.resize());
                setTimeout(ukurUlang, 300);
                window.addEventListener('load', ukurUlang);
            }));
        },

        // ---- format ----
        angka(v) { return angkaF(v); },
        rupiah(v) { return 'Rp ' + angkaF(Math.round(v)); },
        fmtTgl(t) { return t ? t.slice(8) + '/' + t.slice(5, 7) + '/' + t.slice(0, 4) : '-'; },
        tanggal(s) {
            if (!s) return '-';
            const [y, m, d] = s.split('-');
            return d + ' ' + BULAN[(+m) - 1].slice(0, 3) + ' ' + y;
        },
        statusLabel(s) {
            return ({ disetujui: 'Disetujui', ditolak: 'Ditolak', dibatalkan: 'Dibatalkan', pending_l1: 'Menunggu L1', pending_admin: 'Menunggu Admin' })[s] || s;
        },
        statusCls(s) {
            return ({
                disetujui: 'bg-emerald-50 text-emerald-700', ditolak: 'bg-rose-50 text-rose-700',
                dibatalkan: 'bg-slate-100 text-slate-500', pending_l1: 'bg-amber-50 text-amber-700', pending_admin: 'bg-amber-50 text-amber-700',
            })[s] || 'bg-slate-100 text-slate-600';
        },
        periodeTeks() { return (this.bulan ? BULAN[this.bulan - 1] + ' ' : '') + this.tahun; },

        // ---- keterangan grafik (bisa diminimalkan, pilihan diingat di browser) ----
        get ket() {
            const item = this.ukuran === 'item';
            const sat = item ? 'jumlah item (total pengajuan)' : 'jumlah permintaan (banyaknya baris permintaan)';
            const per = this.gran === 'day' ? 'hari' : 'bulan';
            return {
                bulan:  `Menampilkan ${sat} per ${per}, ditumpuk menurut status. Mengikuti semua filter di atas. Klik sebuah batang untuk menampilkan datanya di panel kanan.`,
                status: `Pembagian ${sat} menurut status. Klik sebuah status untuk menampilkan datanya di panel kanan.`,
            };
        },
        toggleKet(k) {
            this.ketTutup[k] = !this.ketTutup[k];
            try { localStorage.setItem(KET_STORE, JSON.stringify(this.ketTutup)); } catch (e) {}
        },

        // ---- komposisi status ----
        get statusRows() {
            const item = this.ukuran === 'item';
            const v = URUT.map(i => this.gd.total[i] ? (item ? this.gd.total[i].qty : this.gd.total[i].permintaan) : 0);
            const tot = v.reduce((a, b) => a + b, 0);
            return URUT.map((i, n) => ({ i, nama: STATUS_NAMA[i], warna: WARNA[i], v: v[n], pct: tot ? v[n] / tot * 100 : 0 }));
        },
        aktifStatus(i) {
            const f = FILTER_STATUS[this.status];
            return !f || f.includes(STATUS_KEY[i]);
        },

        // ---- grafik ----
        buatGrafik() {
            if (typeof Chart === 'undefined') { this.chartMissing = true; return; }
            if (charts.chBulan) return;
            const self = this;
            // Tooltip muncul tepat di posisi kursor
            if (Chart.Tooltip && Chart.Tooltip.positioners) {
                Chart.Tooltip.positioners.cursor = (items, pos) => pos;
            }
            Chart.defaults.font.family = 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';
            Chart.defaults.font.size = Math.max(9, Math.round(12 * zoomHalaman()));
            Chart.defaults.color = '#64748b';
            Object.assign(Chart.defaults.plugins.tooltip, { backgroundColor: '#0f172a', padding: 9, cornerRadius: 6, boxPadding: 4 });

            const el = document.getElementById('chBulan');
            if (!el) return;
            const tipDasar = { animation: { duration: 0 }, position: 'cursor' };
            try {
                charts.chBulan = new Chart(el, {
                    type: 'bar',
                    data: { labels: [], datasets: [] },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        transitions: { active: { animation: { duration: 0 } } },
                        interaction: { mode: 'index', axis: 'x', intersect: false },
                        scales: { x: { stacked: true, grid: { display: false }, ticks: { maxTicksLimit: 16, autoSkip: true } },
                                  y: { stacked: true, ...sumbuAngka } },
                        plugins: {
                            legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                            tooltip: { ...tipDasar, callbacks: { footer: items => 'Total: ' + angkaF(items.reduce((a, i) => a + i.parsed.y, 0)) } },
                        },
                        onClick: (e, els, chart) => self.klikBulan(e, els, chart),
                        onHover: pointer,
                    },
                });
            } catch (err) { console.error('Gagal membuat grafik chBulan', err); }
            this.gambarGrafik();
        },

        gambarGrafik() {
            const c = charts.chBulan;
            if (!c) return;
            const item = this.ukuran === 'item';
            try {
                c.data.labels = this.gd.labels;
                c.data.datasets = URUT.map(i => ({
                    label: STATUS_NAMA[i],
                    data: this.gd.per[i] ? (item ? this.gd.per[i].qty : this.gd.per[i].permintaan) : [],
                    backgroundColor: WARNA[i], borderRadius: 2, maxBarThickness: 28,
                }));
                c.update();
            } catch (err) { console.error('Gagal memperbarui grafik', err); }
        },

        async muatGrafik(tk) {
            try {
                const res = await fetch(cfg.chart + '?' + new URLSearchParams({ tahun: this.tahun, bulan: this.bulan, status: this.status }).toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin',
                });
                if (tk !== this.token) return;
                if (res.status === 401 || res.status === 419) throw new Error('Sesi habis, silakan muat ulang halaman.');
                if (!res.ok) throw new Error('Gagal memuat grafik (' + res.status + ').');
                const j = await res.json();
                const labels = j.keys.map(k => j.gran === 'day'
                    ? (+k.slice(8)) + ' ' + BULAN[(+k.slice(5, 7)) - 1].slice(0, 3)
                    : BULAN[(+k.slice(5, 7)) - 1].slice(0, 3) + ' ' + k.slice(2, 4));
                this.gd = { gran: j.gran, keys: j.keys, labels, per: j.per, total: j.total };
                this.gambarGrafik();
            } catch (e) {
                if (tk === this.token) this.errorMsg = e.message || 'Gagal memuat grafik.';
            }
        },

        klikBulan(e, els, chart) {
            if (!els.length) return;
            const hit = chart.getElementsAtEventForMode(e, 'nearest', { intersect: true }, true);
            const idx = els[0].index, st = hit.length ? URUT[hit[0].datasetIndex] : null;   // urutan dataset = URUT
            const key = this.gd.keys[idx], lab = this.gd.labels[idx];
            const act = this.gd.gran === 'month'
                ? { label: 'Batasi periode ke bulan ini', fn: () => { this.bulan = +key.slice(5, 7); } }
                : null;
            this.bukaData({ t: (st === null ? 'Semua status' : STATUS_NAMA[st]) + ' · ' + lab, bucket: key, st: st === null ? null : STATUS_KEY[st], act });
        },

        bukaStatus(i) {
            const key = STATUS_KEY[i];
            const f = Object.keys(FILTER_STATUS).find(k => FILTER_STATUS[k] && FILTER_STATUS[k].includes(key));
            this.bukaData({ t: 'Status ' + STATUS_NAMA[i], bucket: null, st: key,
                act: f ? { label: 'Jadikan filter Status: ' + FILTER_NAMA[f], fn: () => { this.status = f; } } : null });
        },

        // ---- panel data (klik grafik / status) ----
        async bukaData(spec) {
            // panel data dan panel drill-down tidak tampil bersamaan
            this.cols.splice(1);
            if (this.cols[0]) this.cols[0].sel = null;

            const tk = ++this.dpTok;
            this.dp = { t: spec.t, act: spec.act || null, q: '', pg: 0, rows: [], total: { permintaan: 0, qty: 0, nilai: 0 }, loading: true, error: '', cut: false };
            const p = new URLSearchParams({ tahun: this.tahun, bulan: this.bulan, status: this.status });
            if (spec.bucket) p.set('bucket', spec.bucket);
            if (spec.st) p.set('st', spec.st);
            try {
                const res = await fetch(cfg.rows + '?' + p.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin',
                });
                if (tk !== this.dpTok || !this.dp) return;
                if (res.status === 401 || res.status === 419) throw new Error('Sesi habis, silakan muat ulang halaman.');
                if (!res.ok) throw new Error('Gagal memuat data (' + res.status + ').');
                const j = await res.json();
                this.dp.rows = j.items; this.dp.total = j.total; this.dp.cut = !!j.cut;
            } catch (e) {
                if (tk === this.dpTok && this.dp) this.dp.error = e.message || 'Gagal memuat data.';
            } finally {
                if (tk === this.dpTok && this.dp) this.dp.loading = false;
            }
        },
        tutupData() { this.dp = null; this.dpTok++; },
        jalankanAksi() {
            if (!this.dp || !this.dp.act) return;
            const fn = this.dp.act.fn;
            this.tutupData();
            fn();
            this.refresh();
        },
        get dpAll() {
            if (!this.dp) return [];
            const q = (this.dp.q || '').trim().toLowerCase();
            if (!q) return this.dp.rows;
            return this.dp.rows.filter(r => (this.fmtTgl(r.tgl) + ' ' + r.pemohon + ' ' + r.barang + ' ' + r.sub + ' ' + this.statusLabel(r.status)).toLowerCase().includes(q));
        },
        get dpHal() { return this.dp ? this.dpAll.slice(this.dp.pg * PG, (this.dp.pg + 1) * PG) : []; },
        get dpJmlHal() { return Math.max(1, Math.ceil(this.dpAll.length / PG)); },
        get dpSum() {
            if (!this.dp) return [];
            const q = (this.dp.q || '').trim();
            const a = this.dpAll;   // bila dicari, ringkasan mengikuti hasil pencarian (maks. 500 baris terbaru)
            const qty = q ? a.reduce((s, r) => s + r.qty, 0) : this.dp.total.qty;
            const nilai = q ? a.reduce((s, r) => s + r.nilai, 0) : this.dp.total.nilai;
            const n = q ? a.length : this.dp.total.permintaan;
            const ringkas = v => v >= 1e9 ? (v / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' M'
                              : v >= 1e6 ? (v / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt' : angkaF(v);
            return [{ l: 'Permintaan', v: angkaF(n) }, { l: 'Item', v: angkaF(qty) }, { l: 'Nilai', v: 'Rp ' + ringkas(nilai) }];
        },
        lebarData() { return this.xl ? PWD : Math.min(window.innerWidth, PWD); },

        // ---- daftar drill-down ----
        metrik(it) { return this.ukuran === 'item' ? it.qty : it.permintaan; },
        persen(c, it) {
            const max = Math.max(...c.items.map(x => this.metrik(x)), 0);
            return max > 0 ? Math.max(3, Math.round(this.metrik(it) / max * 100)) : 0;
        },
        // daftar baris setelah pencarian & urutan menurut satuan terpilih
        daftar(c) {
            if (!c) return [];
            const q = ((c.level === 'barang' ? this.qBarang : c.q) || '').toLowerCase().trim();
            let a = q ? c.items.filter(x => String(x.label).toLowerCase().includes(q)) : c.items;
            return [...a].sort((x, y) => this.metrik(y) - this.metrik(x) || y.permintaan - x.permintaan);
        },

        // ---- panel drill-down kanan ----
        drawers() {
            if (this.dp) return [];
            const out = [];
            this.cols.forEach((c, i) => { if (i >= 1) out.push({ c, i }); });
            return out.slice(this.xl ? -MAX_PANEL : -1);
        },
        lebarPanel() { return this.xl ? PW : Math.min(window.innerWidth, 420); },
        padKanan() {
            if (!this.xl) return 0;
            return this.dp ? PWD : this.drawers().length * PW;
        },
        subjudul(i) {
            const p = this.cols[i - 1];
            if (!p) return '';
            const it = p.items.find(x => String(x.k) === String(p.sel));
            return it ? this.judul[p.level] + ': ' + it.label : '';
        },
        tutup(i) {
            // tutup panel ke-i beserta semua panel di kanannya
            if (i < 1 || !this.cols[i]) return;
            this.cols.splice(i);
            if (this.cols[i - 1]) this.cols[i - 1].sel = null;
        },
        escTutup() {
            if (this.dp) { this.tutupData(); return; }
            if (this.drawers().length) this.tutup(this.cols.length - 1);
        },

        jalur() {
            const out = [];
            this.cols.forEach(c => {
                if (c.sel === null || c.sel === undefined) return;
                const it = c.items.find(x => String(x.k) === String(c.sel));
                if (it) out.push(it.label);
            });
            return out;
        },
        kembaliKe(n) {
            // klik di jalur: tampilkan panel anak dari pilihan ke-n, tutup panel setelahnya
            this.cols.splice(n + 2);
            if (this.cols[n + 1]) this.cols[n + 1].sel = null;
        },

        params(idx) {
            const p = new URLSearchParams({ tahun: this.tahun, bulan: this.bulan, status: this.status, level: LEVELS[idx] });
            for (let i = 0; i < idx; i++) {
                if (this.cols[i] && this.cols[i].sel !== null) p.set(LEVELS[i], this.cols[i].sel);
            }
            return p;
        },

        // Muat kolom/panel untuk level ke-idx berdasarkan pilihan sebelumnya
        async buka(idx, tk) {
            this.cols.push({ level: LEVELS[idx], items: [], total: { permintaan: 0, qty: 0 }, sel: null, loading: true, error: '', cut: false, q: '' });
            const c = this.cols[this.cols.length - 1];   // proxy reaktif
            try {
                const res = await fetch(this.url + '?' + this.params(idx).toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (tk !== this.token) return null;
                if (res.status === 401 || res.status === 419) throw new Error('Sesi habis, silakan muat ulang halaman.');
                if (!res.ok) throw new Error('Gagal memuat data (' + res.status + ').');
                const j = await res.json();
                c.items = j.items; c.total = j.total; c.cut = !!j.cut;
                this.errorMsg = '';
            } catch (e) {
                if (tk === this.token) { c.error = e.message || 'Gagal memuat data.'; this.errorMsg = c.error; }
            } finally {
                if (tk === this.token) c.loading = false;
            }
            return c;
        },

        async pilih(i, it) {
            const c = this.cols[i];
            if (!c || c.level === 'detail') return;
            this.tutupData();
            this.cols.splice(i + 1);        // tutup panel di kanannya
            c.sel = it.k;
            await this.buka(i + 1, this.token);
        },

        // Ganti filter: muat ulang grafik & daftar, pertahankan jalur selama pilihannya masih ada
        async refresh() {
            const tk = ++this.token;
            this.tutupData();
            this.errorMsg = '';
            const path = this.cols.map(c => c.sel).filter(v => v !== null && v !== undefined);
            this.cols = [];
            this.muatGrafik(tk);
            let c = await this.buka(0, tk);
            for (let i = 0; i < path.length && c; i++) {
                if (tk !== this.token) return;
                if (!c.items.some(x => String(x.k) === String(path[i]))) break;
                c.sel = path[i];
                c = await this.buka(i + 1, tk);
            }
        },

        reset() {
            this.tahun = cfg.tahun; this.bulan = 0; this.status = 'semua'; this.qBarang = '';
            this.cols = [];
            this.refresh();
        },
    };
}
</script>
@endpush