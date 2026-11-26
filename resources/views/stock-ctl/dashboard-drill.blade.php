@extends('layouts.app_stock_sidebar')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    @keyframes drillMasuk { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
    .drill-masuk { animation: drillMasuk .22s ease-out; }
</style>
@endpush

@section('content')
@php
    $sel  = 'w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-[13px] bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400/30 focus:border-slate-400 disabled:bg-slate-50 disabled:text-slate-400';
    $card = 'bg-white rounded-xl border soft-border soft-shadow';
    $h    = 'text-[13px] font-semibold text-slate-800';
    $hint = 'text-[11px] text-slate-400';
    $lbl  = 'block text-[11px] font-medium text-slate-500 mb-1';
    $sec  = 'text-[11px] font-semibold text-gray-400 uppercase tracking-wider';
    $peran = ($access['is_super'] ?? false) ? 'Superadmin' : (($access['is_admin'] ?? false) ? 'Admin Area' : 'User');
@endphp

<div class="space-y-6 text-[13px] text-slate-700 min-w-0 max-w-full tabular-nums" :class="sp ? 'xl:pr-[640px]' : ''" @keydown.escape.window="escTutup()" x-data="drillPage(window.__dash)" x-cloak>

    {{-- Header --}}
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 tracking-tight">Dashboard E-Stationery (Drill-down)</h2>
            <p class="mt-0.5 text-xs text-slate-400">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-medium mr-1">{{ $peran }}</span>
                Klik barang untuk menelusuri Bisnis Unit, Area, Unit, dan Pemohon. Klik grafik atau status untuk melihat datanya di panel kanan
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
            <button type="button" @click="resetFilter()" class="text-xs px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-slate-900 hover:border-slate-300">
                <i class="fas fa-rotate-left mr-1 text-[10px]"></i> Reset
            </button>
            <button type="button" @click="toggleSemuaKet()" class="text-xs px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-slate-600 hover:text-slate-900 hover:border-slate-300">
                <i class="far fa-circle-question mr-1 text-[10px]"></i><span x-text="semuaKetTutup ? 'Tampilkan keterangan' : 'Sembunyikan keterangan'"></span>
            </button>
        </div>
    </div>

    {{-- Filter --}}
    <div class="{{ $card }} p-3.5 space-y-3">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
            <template x-for="f in filterDef" :key="f[0]">
                <div class="relative">
                    <label class="{{ $lbl }}" x-text="f[1]"></label>
                    <input type="text" role="combobox" autocomplete="off" class="{{ $sel }}"
                           :placeholder="CB_SEMUA[f[0]]" :disabled="f[0] === 'idBu' && lockedBu !== null"
                           :value="cbShown(f[0])" :aria-expanded="cbOpenKey === f[0]"
                           @focus="cbFocus(f[0], $event)" @click="cbOpenKey !== f[0] && cbFocus(f[0], $event)"
                           @input="cbInput(f[0], $event)" @keydown="cbKey(f[0], $event)" @blur="cbBlur(f[0])">
                    <ul x-show="cbOpenKey === f[0]" class="absolute z-30 mt-1 w-full max-h-60 overflow-auto bg-white border border-slate-200 rounded-lg shadow-lg py-1 text-[13px]">
                        <template x-for="(o, i) in cbList(f[0])" :key="o[0]">
                            <li @mousedown.prevent="cbPick(f[0], o[0])" class="px-3 py-1.5 cursor-pointer"
                                :class="[i === cbHi ? 'bg-slate-100' : '', cbVal(f[0]) === String(o[0]) ? 'font-medium text-slate-900' : 'text-slate-600']" x-text="o[1]"></li>
                        </template>
                        <li x-show="!cbList(f[0]).length" class="px-3 py-1.5 text-slate-400">Tidak ada yang cocok</li>
                    </ul>
                </div>
            </template>
        </div>

        <div class="flex flex-wrap items-end gap-3">
            <div>
                <label class="{{ $lbl }}">Periode awal</label>
                <input type="date" x-model="awal" @change="preset = ''" :min="batasAwal" :max="hariIni" class="{{ $sel }}">
            </div>
            <div>
                <label class="{{ $lbl }}">Periode akhir</label>
                <input type="date" x-model="akhir" @change="preset = ''" :min="batasAwal" class="{{ $sel }}">
            </div>
            <div class="inline-flex flex-wrap rounded-lg border border-slate-200 bg-slate-50 p-0.5 text-xs">
                <template x-for="p in presets" :key="p.key">
                    <button type="button" @click="setPreset(p.key)" class="px-2.5 py-1 rounded-md transition"
                            :class="preset === p.key ? 'bg-white text-slate-900 shadow-sm font-medium' : 'text-slate-500 hover:text-slate-800'"
                            x-text="p.label"></button>
                </template>
            </div>
            <div class="flex flex-wrap items-center gap-1.5" x-show="chips.length">
                <template x-for="c in chips" :key="c.k">
                    <span class="inline-flex items-center gap-1 pl-2 pr-1 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs">
                        <span x-text="c.t"></span>
                        <button type="button" @click="hapusChip(c.k)" class="w-4 h-4 rounded hover:bg-slate-200 leading-none">&times;</button>
                    </span>
                </template>
            </div>
        </div>

        <p class="text-xs text-rose-600" x-show="errorMsg" x-text="errorMsg"></p>
        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2" x-show="chartMissing">
            Library grafik (Chart.js) tidak termuat. Periksa koneksi internet / akses ke cdnjs.cloudflare.com.
        </p>
    </div>

    <section class="space-y-3">
        <h2 class="{{ $sec }}">Ringkasan</h2>
        <div class="space-y-4">
    {{-- KPI --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        <template x-for="c in kartu" :key="c.k">
            <div class="{{ $card }} p-4">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs font-medium text-slate-500" x-text="c.label"></p>
                    <span class="text-[11px] font-medium px-1.5 py-0.5 rounded-md whitespace-nowrap" :class="c.dcls" x-show="c.dtx" x-text="c.dtx"></span>
                </div>
                <p class="mt-2 text-[26px] leading-none font-semibold text-slate-900 tracking-tight" x-text="c.val"></p>
                <p class="mt-1.5 text-xs text-slate-400" x-text="c.sub"></p>
            </div>
        </template>
    </div>

    <div class="text-center text-sm text-slate-500 {{ $card }} py-3" x-show="kpi.total === 0">Tidak ada permintaan untuk filter ini.</div>
        </div>
    </section>

    <section class="space-y-3">
        <h2 class="{{ $sec }}">Tren dan status</h2>
        <div class="space-y-4">
    {{-- Tren + status --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">
        <div class="{{ $card }} p-4 lg:col-span-8">
            <div class="flex items-baseline justify-between mb-2">
                <h3 class="{{ $h }}"><span x-text="ukuran === 'item' ? 'Item diminta' : 'Permintaan'"></span> per <span x-text="gran === 'day' ? 'hari' : 'bulan'"></span><button type="button" @click="toggleKet('bulan')" class="ml-1 align-middle text-slate-300 hover:text-slate-500" :class="!ketTutup.bulan ? 'text-slate-500' : ''" title="Keterangan"><i class="far fa-circle-question text-[11px]"></i></button></h3>
                <span class="{{ $hint }}" x-text="gran === 'month' ? 'klik batang untuk melihat data' : 'per hari'"></span>
            </div>
            <p x-show="!ketTutup.bulan" class="mb-3 text-xs leading-relaxed text-slate-500 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2" x-text="ket.bulan"></p>
            <div class="relative" data-chart-wrap style="height:250px"><canvas id="chBulan"></canvas></div>
        </div>

        <div class="{{ $card }} p-4 lg:col-span-4">
            <div class="flex items-baseline justify-between mb-3">
                <h3 class="{{ $h }}">Komposisi status<button type="button" @click="toggleKet('status')" class="ml-1 align-middle text-slate-300 hover:text-slate-500" :class="!ketTutup.status ? 'text-slate-500' : ''" title="Keterangan"><i class="far fa-circle-question text-[11px]"></i></button></h3>
                <span class="{{ $hint }}">klik untuk melihat data</span>
            </div>
            <p x-show="!ketTutup.status" class="mb-3 text-xs leading-relaxed text-slate-500 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2" x-text="ket.status"></p>
            <div class="flex h-2.5 rounded-full overflow-hidden bg-slate-100 gap-px">
                <template x-for="r in statusRows" :key="r.i">
                    <button type="button" @click="bukaStatus(r.i)" :style="`width:${r.pct}%;background:${r.warna}`"
                            :class="status !== '' && +status !== r.i ? 'opacity-30' : ''" class="h-full transition-opacity" :title="r.nama"></button>
                </template>
            </div>
            <div class="mt-3 divide-y divide-slate-100">
                <template x-for="r in statusRows" :key="r.i">
                    <button type="button" @click="bukaStatus(r.i)" class="w-full flex items-center gap-2.5 py-2 text-left hover:bg-slate-50/70 transition-opacity"
                            :class="status !== '' && +status !== r.i ? 'opacity-40' : ''">
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
        </div>
    </section>

    <section class="space-y-3">
        <h2 class="{{ $sec }}">Telusuri permintaan</h2>
        <div class="space-y-3">
            <div class="flex items-baseline justify-between">
                <h3 class="{{ $h }}">Barang <span class="text-slate-300">›</span> Bisnis Unit <span class="text-slate-300">›</span> Area Kerja <span class="text-slate-300">›</span> Unit <span class="text-slate-300">›</span> Pemohon<button type="button" @click="toggleKet('telusur')" class="ml-1 align-middle text-slate-300 hover:text-slate-500" :class="!ketTutup.telusur ? 'text-slate-500' : ''" title="Keterangan"><i class="far fa-circle-question text-[11px]"></i></button></h3>
                <span class="{{ $hint }}">klik item untuk membuka kotak berikutnya</span>
            </div>
            <p x-show="!ketTutup.telusur" class="text-xs leading-relaxed text-slate-500 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2" x-text="ket.telusur"></p>

            <div x-ref="strip" class="grid grid-cols-1 lg:grid-cols-2 gap-4 min-w-0 max-w-full">
                <template x-for="(c, i) in kolom" :key="c.level">
                    <div class="drill-masuk {{ $card }} flex flex-col min-w-0" style="height:480px">
                        <div class="px-4 pt-3 pb-2 border-b border-slate-100 space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <h3 class="{{ $h }}" x-text="JUDUL[c.level]"></h3>
                                    <p class="{{ $hint }} mt-0.5 truncate" x-show="i > 0" x-text="subjudul(i)" :title="subjudul(i)"></p>
                                </div>
                                <button type="button" x-show="i > 0" @click="tutupKolom(i)" class="w-6 h-6 shrink-0 rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700 text-lg leading-none" aria-label="Tutup">&times;</button>
                            </div>
                            <p class="text-[11px] text-slate-500">
                                <b class="text-slate-800" x-text="angka(c.items.length)"></b> data ·
                                <b class="text-slate-800" x-text="angka(c.tot.n)"></b> permintaan ·
                                <b class="text-slate-800" x-text="angka(c.tot.qt)"></b> item
                            </p>
                            <input type="search" x-show="c.items.length > 8" x-model="cq[i]" :placeholder="'Cari ' + JUDUL[c.level].toLowerCase()"
                                   class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-[13px] focus:outline-none focus:ring-2 focus:ring-slate-400/30 focus:border-slate-400" aria-label="Cari">
                        </div>

                        <div class="overflow-y-auto flex-1 py-1">
                            <p class="px-4 py-8 text-center text-slate-400" x-show="!c.items.length">Tidak ada data untuk filter ini.</p>
                            <p class="px-4 py-8 text-center text-slate-400" x-show="c.items.length && !daftarKolom(i).length">Tidak ada yang cocok.</p>
                            <template x-for="it in daftarKolom(i)" :key="it.k">
                                <button type="button" @click="pilihKolom(i, it)" class="group block w-full text-left px-4 py-2 transition"
                                        :class="c.sel === it.k ? 'bg-slate-100' : 'hover:bg-slate-50'">
                                    <span class="flex items-center justify-between gap-2">
                                        <span class="truncate" :class="c.sel === it.k ? 'font-medium text-slate-900' : 'text-slate-700'" x-text="it.label" :title="it.label"></span>
                                        <span class="flex items-center gap-2 shrink-0">
                                            <span class="text-right">
                                                <span class="block text-[13px] font-medium text-slate-900" x-text="angka(metrik(it))"></span>
                                                <span class="block text-[10px] text-slate-400" x-text="ukuran === 'item' ? angka(it.n) + ' permintaan' : angka(it.qt) + ' item'"></span>
                                            </span>
                                            <i class="fas fa-chevron-right text-[9px] text-slate-300 group-hover:text-slate-500"></i>
                                        </span>
                                    </span>
                                    {{-- status hanya berupa warna: komposisi status per baris --}}
                                    <span class="mt-2 flex h-2.5 rounded-full overflow-hidden bg-slate-100 gap-px" :title="rincian(it)">
                                        <template x-for="g in segmen(it)" :key="g.i"><span class="h-full" :style="`width:${g.pct}%;background:${g.warna}`"></span></template>
                                    </span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </section>

    {{-- Panel data (kanan) --}}
    <template x-if="sp">
        <aside class="fixed top-0 right-0 h-full w-[620px] max-w-full bg-white border-l border-slate-200 shadow-[-8px_0_24px_rgba(15,23,42,.08)] z-[56] flex flex-col !mt-0">
            <div class="flex items-start justify-between gap-3 px-4 pt-4 pb-3 border-b border-slate-100">
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-slate-900 truncate" x-text="sp.t"></h3>
                    <p class="text-[11px] text-slate-400 mt-0.5 truncate" x-show="sp.path" x-text="sp.path" :title="sp.path"></p>
                    <p class="text-[11px] text-slate-400 mt-0.5" x-text="'Periode ' + (awal || batasAwal) + ' s.d. ' + (akhir || hariIni)"></p>
                </div>
                <button type="button" @click="tutupPanel()" class="w-7 h-7 rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700 text-lg leading-none" aria-label="Tutup">&times;</button>
            </div>
            <div class="px-4 py-3 space-y-3 overflow-y-auto flex-1">
                <input type="search" :value="sp.q" @input="spCari($event.target.value)" placeholder="Cari pemohon, barang, unit, area, atau tanggal"
                       class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-[13px] focus:outline-none focus:ring-2 focus:ring-slate-400/30 focus:border-slate-400" aria-label="Cari">
                <div class="grid grid-cols-3 gap-2">
                    <template x-for="m in spSum" :key="m.l">
                        <div class="rounded-lg bg-slate-50 border border-slate-100 px-3 py-2">
                            <p class="text-[11px] text-slate-400" x-text="m.l"></p>
                            <p class="text-[15px] font-semibold text-slate-900 mt-0.5" x-text="m.v"></p>
                        </div>
                    </template>
                </div>
                <button type="button" x-show="sp.actLabel" @click="jalankanAksi()" class="text-xs px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 hover:text-slate-900 hover:border-slate-300" x-text="sp.actLabel"></button>
                <table class="w-full text-[13px]">
                    <thead>
                        <tr class="text-[11px] uppercase tracking-wider text-slate-400">
                            <template x-for="col in spCols" :key="col.h"><th class="font-medium pb-2 px-1" :class="col.r ? 'text-right' : 'text-left'" x-text="col.h"></th></template>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(row, i) in spHal" :key="i">
                            <tr class="border-t border-slate-100 align-top">
                                <template x-for="(cell, j) in row.c" :key="j">
                                    <td class="py-2 px-1" :class="[spCols[j].r ? 'text-right whitespace-nowrap' : '', j === 0 ? 'whitespace-nowrap text-slate-500' : (cell === 'Harga belum ada' ? 'text-slate-400 text-xs' : 'text-slate-700')]">
                                        <span x-show="spCols[j].k === 'st'" class="inline-block w-1.5 h-1.5 rounded-full mr-1.5 align-middle" :style="`background:${row.w}`"></span><span x-text="cell"></span>
                                        <template x-if="j === 1 && row.sub"><div class="text-[11px] text-slate-400" x-text="row.sub"></div></template>
                                        <template x-if="spCols[j].k === 'st' && row.alasan"><div class="text-[11px] text-rose-600 mt-0.5 whitespace-normal" style="max-width:11rem" x-text="'Alasan: ' + row.alasan"></div></template>
                                    </td>
                                </template>
                            </tr>
                        </template>
                        <tr x-show="!spAll.length"><td :colspan="spCols.length" class="py-8 text-center text-slate-400">Tidak ada data yang sesuai.</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between gap-2 px-4 py-3 border-t border-slate-100 text-xs text-slate-500">
                <button type="button" :disabled="sp.pg === 0" @click="sp.pg--" class="px-2.5 py-1 rounded-md border border-slate-200 hover:bg-slate-50 disabled:opacity-40 disabled:hover:bg-transparent">‹ Sebelumnya</button>
                <span class="text-center" x-text="`Halaman ${sp.pg + 1} dari ${spJmlHal}` + (spAll.length ? ` · ${sp.pg * 10 + 1}-${Math.min(spAll.length, (sp.pg + 1) * 10)} dari ${angka(spAll.length)}` : '')"></span>
                <button type="button" :disabled="sp.pg >= spJmlHal - 1" @click="sp.pg++" class="px-2.5 py-1 rounded-md border border-slate-200 hover:bg-slate-50 disabled:opacity-40 disabled:hover:bg-transparent">Berikutnya ›</button>
            </div>
        </aside>
    </template>

    <p class="text-xs text-slate-400">Data mencakup 24 bulan terakhir (mulai <span x-text="batasAwal"></span>). Kotak penelusuran menghitung semua status dan mengikuti semua filter di atas; status ditampilkan sebagai warna. Gunakan filter Status untuk mempersempit.</p>
</div>
@endsection

@push('scripts')
<script>
    window.__dash = {!! json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
function drillPage(D) {
    const pad = n => String(n).padStart(2, '0');
    const ymd = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const parse = s => new Date(s + 'T00:00:00');
    const angka = v => Number(v || 0).toLocaleString('id-ID');
    const rupiah = v => 'Rp ' + angka(Math.round(v));
    const ringkas = v => v >= 1e9 ? (v / 1e9).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' M'
                       : v >= 1e6 ? (v / 1e6).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' jt'
                       : angka(v);

    const areaBu = {}, areaNama = {}, buNama = {}, barangNama = {};
    D.areas.forEach(a => { areaBu[a.id] = a.bu; areaNama[a.id] = a.nama; });
    D.bus.forEach(b => { buNama[b.id] = b.nama; });
    D.barang.forEach(b => { barangNama[b[0]] = b[1]; });
    const unitBu = D.units.map(() => new Set());
    D.rows.forEach(r => unitBu[r[3]].add(r[1]));
    const pemohonNama = {};
    (D.pemohon || []).forEach(x => { pemohonNama[x[0]] = x[1]; });

    const KET_KEYS = ['bulan', 'status', 'telusur'];
    const KET_STORE = 'stockDrillKetTutup';
    const STATUS_NAMA = ['Disetujui', 'Ditolak', 'Pending L1', 'Dibatalkan', 'Pending Admin'];
    // kode status (sama dengan controller): 0 disetujui, 1 ditolak, 2 pending L1, 3 dibatalkan, 4 pending admin
    const WARNA = ['#4f8a79', '#b06a6e', '#c4a266', '#c3c9d3', '#6b8cae'];
    // urutan tampil: Disetujui, Pending L1, Pending Admin, Ditolak, Dibatalkan (mengikuti alur approval)
    const URUT = [0, 2, 4, 1, 3];

    // Chart & data bantu disimpan di luar state Alpine supaya tidak dibungkus proxy reaktif.
    const charts = {};
    const ctx = { keys: [], labels: [], gran: 'month' };
    let timer = null;
    let spCtx = null;               // predikat & aksi panel (di luar state Alpine)
    const PG = 10;                  // baris per halaman di panel
    const fmtTgl = t => t.slice(8) + '/' + t.slice(5, 7) + '/' + t.slice(0, 4);
    const CB_SEMUA = { idBu: 'Semua Bisnis Unit', idArea: 'Semua Area', unit: 'Semua Unit', status: 'Semua Status', barang: 'Semua Barang' };

    // Layout memakai `html { zoom: 0.8 }`. Chart.js menghitung posisi mouse dalam piksel layar
    // tetapi ukuran grafik dalam piksel sebelum zoom, sehingga hover/klik meleset.
    // Solusi: pembungkus grafik diberi zoom kebalikan (1 / zoom halaman) sehingga zoom efektif = 1.
    const zoomHalaman = () => parseFloat(getComputedStyle(document.documentElement).zoom) || 1;
    const kompensasiZoom = () => {
        const z = zoomHalaman();
        document.querySelectorAll('[data-chart-wrap]').forEach(w => { w.style.zoom = z !== 1 ? String(1 / z) : ''; });
        return z;
    };

    const rentang = (aw, ak) => {
        const a = parse(aw), b = parse(ak);
        const hari = Math.round((b - a) / 86400000) + 1;
        const keys = [], labels = [];
        if (hari <= 62) {
            for (let d = new Date(a); d <= b; d.setDate(d.getDate() + 1)) {
                keys.push(ymd(d));
                labels.push(d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }));
            }
            return { gran: 'day', keys, labels };
        }
        for (let m = new Date(a.getFullYear(), a.getMonth(), 1); m <= b; m.setMonth(m.getMonth() + 1)) {
            keys.push(`${m.getFullYear()}-${pad(m.getMonth() + 1)}`);
            labels.push(m.toLocaleDateString('id-ID', { month: 'short', year: '2-digit' }));
        }
        return { gran: 'month', keys, labels };
    };

    // Sorot item terpilih: yang lain dibuat pudar.
    const sorot = (basis, ids, sel) => ids.map(id => (sel === null || id === sel) ? basis : basis + '55');
    const top = (map, n) => Object.entries(map).sort((a, b) => b[1] - a[1]).slice(0, n);
    const pointer = (evt, els) => { const t = evt.native && evt.native.target; if (t) t.style.cursor = els.length ? 'pointer' : 'default'; };
    const potong = function (v) { const l = this.getLabelForValue(v); return l.length > 34 ? l.slice(0, 33) + '…' : l; };
    const sumbuAngka = { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f1f5f9' }, border: { display: false } };

    // Jumlah barang pada satu baris permintaan (r[7]). Kalau controller belum mengirim, dianggap 0.
    const qtyBaris = r => Number(r[7]) || 0;
    // Alasan tolak ada di r[9] (hanya terisi untuk status Ditolak).
    const alasanTolak = r => String(r[9] || '').trim() || 'tidak dicatat';

    // ---- Penelusuran: Barang > Bisnis Unit > Area Kerja > Unit > Pemohon ----
    // Untuk MENYEMBUNYIKAN level Area cukup hapus 'area' dari LV.
    const LV = ['barang', 'bu', 'area', 'unit', 'user'];
    const JUDUL = { barang: 'Barang', bu: 'Bisnis Unit', area: 'Area Kerja', unit: 'Unit', user: 'Pemohon' };
    const kunci = { barang: r => r[4], bu: r => r[1], area: r => r[2], unit: r => r[3], user: r => r[8] };
    const namaDari = {
        barang: k => barangNama[k] || ('#' + k),
        bu:     k => buNama[k] || ('#' + k),
        area:   k => areaNama[k] || ('#' + k),
        unit:   k => D.units[k] || '(Tanpa Unit)',
        user:   k => pemohonNama[k] || ('User #' + k),
    };
    const kelompok = (rows, lv) => {
        const m = new Map();
        rows.forEach(r => {
            const k = kunci[lv](r), j = qtyBaris(r);
            let o = m.get(k);
            if (!o) { o = { k, label: namaDari[lv](k), s: [0, 0, 0, 0, 0], q: [0, 0, 0, 0, 0], n: 0, qt: 0 }; m.set(k, o); }
            o.s[r[5]]++; o.q[r[5]] += j; o.n++; o.qt += j;
        });
        return [...m.values()];
    };

    return {
        bus: D.bus,
        areas: D.areas,
        barangList: D.barang,
        lockedBu: D.lockedBu,
        batasAwal: D.batasAwal,
        hariIni: D.hariIni,

        idBu: D.lockedBu !== null ? String(D.lockedBu) : '',
        idArea: '',
        unit: '',
        status: '',
        barang: '',
        awal: '',
        akhir: '',
        preset: 'year',
        ukuran: 'baris',  // 'item' = jumlah barang, 'baris' = jumlah permintaan (default)

        // s = jumlah permintaan (baris) per status, q = jumlah barang (qty) per status
        sp: null, spAll: [], spSum: [],
        kolom: [], pilihan: [], cq: LV.map(() => ''), JUDUL,
        cbOpenKey: '', cbQ: '', cbHi: 0, cbTyped: false, CB_SEMUA,
        filterDef: [['idBu', 'Bisnis Unit'], ['idArea', 'Area Kerja'], ['unit', 'Unit (Divisi Pemohon)'], ['status', 'Status'], ['barang', 'Barang']],
        ketTutup: Object.fromEntries(KET_KEYS.map(k => [k, false])),
        cmp: { prevOk: false, pa: '', pb: '', total: { v: 0, p: 0, d: null }, rate: { v: null, d: null, ok: 0, dec: 0 }, pl1: { v: 0, p: 0, d: null }, padm: { v: 0, p: 0, d: null } },
        kpi: { total: 0, s: [0, 0, 0, 0, 0], q: [0, 0, 0, 0, 0], qtyTotal: 0 },
        gran: 'month',
        errorMsg: '',
        chartMissing: false,

        presets: [
            { key: 'month',     label: 'Bulan ini' },
            { key: 'lastmonth', label: 'Bulan lalu' },
            { key: '3m',        label: '3 bulan' },
            { key: '6m',        label: '6 bulan' },
            { key: 'year',      label: 'Tahun ini' },
            { key: 'lastyear',  label: 'Tahun lalu' },
            { key: 'all',       label: 'Semua data' },
        ],

        angka, rupiah,
        persen(n) { return this.kpi.total ? Math.round(n / this.kpi.total * 100) + '%' : '0%'; },

        fd(d)  { return d === null ? '' : (d > 0 ? '▲ ' : d < 0 ? '▼ ' : '') + Math.abs(d).toFixed(0) + '%'; },
        fpp(d) { return d === null ? '' : (d > 0 ? '▲ ' : d < 0 ? '▼ ' : '') + Math.abs(d).toFixed(1) + ' pp'; },
        // good: 1 = naik itu baik, -1 = turun itu baik, 0 = netral
        dcls(d, good) {
            if (d === null || !good || Math.abs(d) < 0.5) return 'text-slate-500 bg-slate-100';
            return d * good > 0 ? 'text-teal-800 bg-teal-50' : 'text-rose-800 bg-rose-50';
        },
        // ---- Filter yang bisa diketik: hanya menerima nilai yang ada di daftar saran ----
        cbOpts(k) {
            if (k === 'idBu')   return this.bus.map(b => [String(b.id), b.nama]);
            if (k === 'idArea') return this.areaOptions.map(a => [String(a.id), (this.idBu && this.lockedBu === null) ? a.nama : a.nama + ' (' + a.bu_nama + ')']);
            if (k === 'unit')   return this.unitOptions.map(u => [u, u]);
            if (k === 'status') return [['0', 'Disetujui'], ['2', 'Pending L1'], ['4', 'Pending Admin'], ['1', 'Ditolak'], ['3', 'Dibatalkan']];
            return this.barangList.map(b => [String(b[0]), b[1]]);
        },
        cbVal(k) { return String(this[k]); },
        cbLabel(k) { const v = this.cbVal(k); if (v === '') return ''; const o = this.cbOpts(k).find(x => x[0] === v); return o ? o[1] : ''; },
        cbShown(k) { return this.cbOpenKey === k ? this.cbQ : this.cbLabel(k); },
        cbList(k) {
            const q = (this.cbOpenKey === k && this.cbTyped) ? this.cbQ.trim().toLowerCase() : '';
            const all = this.cbOpts(k), f = q ? all.filter(x => String(x[1]).toLowerCase().includes(q)) : all;
            return (q ? f : [['', CB_SEMUA[k]], ...f]).slice(0, 100);
        },
        cbFocus(k, e) { this.cbOpenKey = k; this.cbQ = this.cbLabel(k); this.cbTyped = false; this.cbHi = 0; e.target.select(); },
        cbInput(k, e) { this.cbQ = e.target.value; this.cbTyped = true; this.cbHi = 0; },
        cbPick(k, v) { this[k] = v; this.cbOpenKey = ''; },
        cbKey(k, e) {
            const n = this.cbList(k).length;
            if (e.key === 'ArrowDown') { e.preventDefault(); this.cbHi = Math.min(n - 1, this.cbHi + 1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); this.cbHi = Math.max(0, this.cbHi - 1); }
            else if (e.key === 'Enter') { e.preventDefault(); const o = this.cbList(k)[this.cbHi]; if (o) this.cbPick(k, o[0]); e.target.blur(); }
            else if (e.key === 'Escape') { e.stopPropagation(); this.cbOpenKey = ''; e.target.blur(); }
        },
        cbBlur(k) {
            if (this.cbOpenKey !== k) return;
            const t = this.cbQ.trim().toLowerCase();
            if (this.cbTyped) {
                if (t === '') this[k] = '';
                else { const m = this.cbOpts(k).find(x => String(x[1]).toLowerCase() === t); if (m) this[k] = m[0]; }   // teks tidak cocok: kembali ke pilihan semula
            }
            this.cbOpenKey = '';
        },

        // ---- Panel data di kanan: 10 baris per halaman, bisa dicari ----
        bukaPanel(t, kind, pred, skip, act, meta) {
            spCtx = { kind, pred, skip: skip || null, act: act || null };
            this.sp = { t, kind, q: '', pg: 0, actLabel: act ? act.label : '', path: (meta && meta.path) || '', chain: !!(meta && meta.chain) };
            this.spHitung();
        },
        tutupPanel() { this.sp = null; spCtx = null; this.spAll = []; },
        spCari(v) { this.sp.q = v; this.sp.pg = 0; this.spHitung(); },
        jalankanAksi() { if (spCtx && spCtx.act) { spCtx.act.fn(); this.tutupPanel(); } },
        get spCols() {
            return [{ h: 'Tanggal' }, { h: 'Pemohon' }, { h: 'Barang' }, { h: 'Status', k: 'st' }, { h: 'Qty', r: 1 }, { h: 'Nilai', r: 1 }];
        },
        get spHal() { return this.sp ? this.spAll.slice(this.sp.pg * PG, (this.sp.pg + 1) * PG) : []; },
        get spJmlHal() { return Math.max(1, Math.ceil(this.spAll.length / PG)); },
        spHitung() {
            if (!this.sp || !spCtx) return;
            const { pred, skip } = spCtx, q = this.sp.q.trim().toLowerCase();
            const rows = this.saring(skip).filter(pred).sort((a, b) => b[0].localeCompare(a[0])).map(r => ({
                c: [fmtTgl(r[0]), pemohonNama[r[8]] || '-', barangNama[r[4]] || ('#' + r[4]), STATUS_NAMA[r[5]], angka(qtyBaris(r)), r[6] === 0 ? 'Harga belum ada' : rupiah(r[6])],
                sub: (D.units[r[3]] || '-') + ' › ' + (areaNama[r[2]] || '-') + ' › ' + (buNama[r[1]] || '-'),
                alasan: r[5] === 1 ? alasanTolak(r) : '',   // status Ditolak: tampilkan alasan tolak
                w: WARNA[r[5]], m: [1, qtyBaris(r), r[6]],
            }));
            rows.forEach(x => { x.s = (x.c.join(' ') + ' ' + (x.sub || '') + ' ' + (x.alasan || '')).toLowerCase(); });
            const a = q ? rows.filter(x => x.s.includes(q)) : rows;
            const jml = [0, 0, 0];
            a.forEach(x => x.m.forEach((v, i) => { jml[i] += v; }));
            this.spSum = ['Permintaan', 'Item', 'Nilai'].map((l, i) => ({ l, v: i === 2 ? 'Rp ' + ringkas(jml[i]) : angka(jml[i]) }));
            this.spAll = a;
            this.sp.pg = Math.min(this.sp.pg, Math.max(0, Math.ceil(a.length / PG) - 1));
        },
        bukaStatus(i) {
            this.bukaPanel('Status ' + STATUS_NAMA[i], 'req', r => r[5] === i, 'status', { label: 'Jadikan filter Status', fn: () => { this.status = String(i); } });
        },
        klikBulan(e, els, chart) {
            if (!els.length) return;
            const hit = chart.getElementsAtEventForMode(e, 'nearest', { intersect: true }, true);
            const idx = els[0].index, st = hit.length ? URUT[hit[0].datasetIndex] : null;   // urutan dataset = URUT
            const key = ctx.keys[idx], lab = ctx.labels[idx], kf = ctx.gran === 'day' ? (t => t) : (t => t.slice(0, 7));
            const act = ctx.gran === 'month' ? { label: 'Batasi periode ke bulan ini', fn: () => {
                const [y, m] = key.split('-').map(Number);
                this.preset = ''; this.awal = `${y}-${pad(m)}-01`; this.akhir = ymd(new Date(y, m, 0));
            } } : null;
            this.bukaPanel((st === null ? 'Semua status' : STATUS_NAMA[st]) + ' · ' + lab, 'req', r => kf(r[0]) === key && (st === null || r[5] === st), null, act);
        },
        // ---- Penelusuran (kotak berdampingan) ----
        metrik(it) { return this.ukuran === 'item' ? it.qt : it.n; },
        daftarKolom(i) {
            const c = this.kolom[i];
            if (!c) return [];
            const q = (this.cq[i] || '').trim().toLowerCase();
            return q ? c.items.filter(x => String(x.label).toLowerCase().includes(q)) : c.items;
        },
        segmen(it) {
            const item = this.ukuran === 'item', tot = item ? it.qt : it.n;
            return URUT.map(i => ({ i, warna: WARNA[i], pct: tot ? (item ? it.q[i] : it.s[i]) / tot * 100 : 0 })).filter(g => g.pct > 0);
        },
        rincian(it) {
            const item = this.ukuran === 'item';
            return URUT.filter(i => (item ? it.q[i] : it.s[i]) > 0).map(i => STATUS_NAMA[i] + ': ' + angka(item ? it.q[i] : it.s[i])).join(' · ');
        },
        subjudul(i) {
            const p = this.kolom[i - 1];
            if (!p) return '';
            const it = p.items.find(x => x.k === p.sel);
            return it ? JUDUL[p.level] + ': ' + it.label : '';
        },
        hitungChain() {
            const out = [];
            let rows = this.saring(null);
            for (let i = 0; i < LV.length; i++) {
                const lv = LV[i];
                const item = this.ukuran === 'item';
                const items = kelompok(rows, lv).sort((a, b) =>
                    (item ? b.qt - a.qt : b.n - a.n) || (b.n - a.n) || a.label.localeCompare(b.label, 'id'));
                const tot = { n: rows.length, qt: rows.reduce((s, r) => s + qtyBaris(r), 0) };
                const sel = this.pilihan[i];
                const ada = sel !== undefined && items.some(x => x.k === sel);
                out.push({ level: lv, items, tot, sel: ada ? sel : null });
                if (!ada) { this.pilihan.length = i; break; }
                rows = rows.filter(r => kunci[lv](r) === sel);
            }
            this.kolom = out;
        },
        geser() {
            // kotak baru bisa berada di baris berikutnya: gulung halaman ke bawah secukupnya agar kotak terlihat
            this.$nextTick(() => {
                const st = this.$refs.strip;
                if (!st || !st.querySelectorAll) return;
                const box = [...st.querySelectorAll('.drill-masuk')].pop();
                if (box) box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            });
        },
        pilihKolom(i, it) {
            if (this.sp && this.sp.chain) this.tutupPanel();
            this.pilihan.length = i;
            this.pilihan[i] = it.k;
            if (i === LV.length - 1) {
                this.hitungChain();
                this.bukaPemohonChain(it);
                return;
            }
            this.cq.forEach((_, j) => { if (j > i) this.cq[j] = ''; });
            this.hitungChain();
            this.geser();
        },
        tutupKolom(i) {
            if (i < 1) return;
            if (this.sp && this.sp.chain) this.tutupPanel();
            this.pilihan.length = i - 1;
            this.cq.forEach((_, j) => { if (j >= i) this.cq[j] = ''; });
            this.hitungChain();
        },
        // Level terakhir: tampil seperti panel dashboard (ringkasan + daftar permintaan)
        bukaPemohonChain(it) {
            const atas = LV.slice(0, -1);                 // level sebelum Pemohon
            const pil = this.pilihan.slice(0, atas.length);
            const nama = i => { const c = this.kolom[i]; const x = c && c.items.find(y => y.k === c.sel); return x ? x.label : '-'; };
            const idxBarang = LV.indexOf('barang');
            const jalur = atas.map((lv, i) => i).filter(i => LV[i] !== 'barang').map(nama).join(' › ');
            const st = this.status === '' ? '' : ' · ' + STATUS_NAMA[+this.status];
            this.bukaPanel(it.label + ' · ' + nama(idxBarang) + st, 'req',
                r => atas.every((lv, i) => kunci[lv](r) === pil[i]) && r[8] === it.k, null,
                { label: 'Jadikan filter Barang', fn: () => { this.barang = String(pil[idxBarang]); } },
                { chain: true, path: jalur });
        },
        escTutup() {
            if (this.sp) { this.tutupPanel(); return; }
            if (this.kolom.length > 1) this.tutupKolom(this.kolom.length - 1);
        },

        // ---- Keterangan tiap grafik/tabel (bisa diminimalkan, pilihan diingat di browser) ----
        get ket() {
            const item = this.ukuran === 'item';
            const sat = item ? 'jumlah item (total pengajuan)' : 'jumlah permintaan (banyaknya baris permintaan)';
            const per = this.gran === 'day' ? 'hari' : 'bulan';
            return {
                bulan:  `Menampilkan ${sat} per ${per}, ditumpuk menurut status. Mengikuti semua filter di atas. Klik sebuah batang untuk menampilkan datanya di panel kanan.`,
                status: `Pembagian ${sat} menurut status. Klik sebuah status untuk menampilkan datanya di panel kanan.`,
                telusur: `Urutan menurut ${sat}, semua status dan mengikuti semua filter di atas. Klik barang untuk membuka Bisnis Unit-nya di kotak sebelah, lalu Area Kerja, Unit, dan Pemohon; maksimal dua kotak per baris, kotak berikutnya turun ke bawah, dan setiap kotak berukuran sama serta bisa di-scroll. Klik pemohon untuk melihat daftar permintaannya di panel kanan. Garis kecil di bawah tiap baris menunjukkan komposisi status (hijau Disetujui, kuning Pending L1, biru Pending Admin, merah Ditolak, abu Dibatalkan); permintaan Ditolak menampilkan alasan tolak di panel.`,
            };
        },
        get semuaKetTutup() { return KET_KEYS.every(k => this.ketTutup[k]); },
        toggleKet(k) { this.ketTutup[k] = !this.ketTutup[k]; this.simpanKet(); },
        toggleSemuaKet() { const t = !this.semuaKetTutup; KET_KEYS.forEach(k => { this.ketTutup[k] = t; }); this.simpanKet(); },
        simpanKet() { try { localStorage.setItem(KET_STORE, JSON.stringify(this.ketTutup)); } catch (e) {} },

        get kartu() {
            const C = this.cmp, item = this.ukuran === 'item';
            return [
                { k: 'total', label: item ? 'Item diminta' : 'Total permintaan', val: angka(C.total.v),
                  sub: C.prevOk ? 'sebelumnya ' + angka(C.total.p) : 'pembanding tidak tersedia',
                  dtx: this.fd(C.total.d), dcls: this.dcls(C.total.d, 0) },
                { k: 'rate', label: 'Tingkat persetujuan', val: C.rate.v === null ? '–' : C.rate.v.toFixed(0) + '%',
                  sub: angka(C.rate.ok) + ' dari ' + angka(C.rate.dec) + ' yang diputuskan',
                  dtx: this.fpp(C.rate.d), dcls: this.dcls(C.rate.d, 1) },
                { k: 'pl1', label: 'Pending L1', val: angka(C.pl1.v),
                  sub: 'dari ' + angka(C.total.v) + (item ? ' item' : ' permintaan'),
                  dtx: this.fd(C.pl1.d), dcls: this.dcls(C.pl1.d, -1) },
                { k: 'padm', label: 'Pending Admin', val: angka(C.padm.v),
                  sub: 'dari ' + angka(C.total.v) + (item ? ' item' : ' permintaan'),
                  dtx: this.fd(C.padm.d), dcls: this.dcls(C.padm.d, -1) },
            ];
        },
        get statusRows() {
            const arr = this.ukuran === 'item' ? this.kpi.q : this.kpi.s;
            const tot = arr.reduce((a, b) => a + b, 0);
            return URUT.map(i => ({ i, nama: STATUS_NAMA[i], v: arr[i], pct: tot ? arr[i] / tot * 100 : 0, warna: WARNA[i] }));
        },

        get areaOptions() {
            return (this.idBu && this.lockedBu === null) ? this.areas.filter(a => String(a.bu) === String(this.idBu)) : this.areas;
        },
        get unitOptions() {
            const bu = (this.idBu && this.lockedBu === null) ? +this.idBu : 0;
            return D.units
                .map((nama, i) => ({ nama, i }))
                .filter(u => !bu || unitBu[u.i].has(bu))
                .map(u => u.nama)
                .sort((a, b) => a.localeCompare(b, 'id'));
        },
        get chips() {
            const c = [];
            if (this.idBu && this.lockedBu === null) c.push({ k: 'bu', t: 'BU: ' + (buNama[this.idBu] || '') });
            if (this.idArea) c.push({ k: 'area', t: 'Area: ' + (areaNama[this.idArea] || '') });
            if (this.unit) c.push({ k: 'unit', t: 'Unit: ' + this.unit });
            if (this.status !== '') c.push({ k: 'status', t: 'Status: ' + STATUS_NAMA[+this.status] });
            if (this.barang) c.push({ k: 'barang', t: 'Barang: ' + (barangNama[this.barang] || '') });
            return c;
        },

        // ------------------------------------------------------------------
        init() {
            try { const j = JSON.parse(localStorage.getItem(KET_STORE) || '{}'); KET_KEYS.forEach(k => { this.ketTutup[k] = !!j[k]; }); } catch (e) {}
            ['idBu', 'idArea', 'unit', 'status', 'barang', 'awal', 'akhir', 'ukuran'].forEach(k =>
                this.$watch(k, () => this.jadwal()));

            this.$watch('idBu', () => {
                if (this.idArea && !this.areaOptions.some(a => String(a.id) === String(this.idArea))) this.idArea = '';
                if (this.unit && !this.unitOptions.includes(this.unit)) this.unit = '';
            });
            // memilih area tanpa BU -> BU ikut terisi
            this.$watch('idArea', () => {
                if (this.idArea && !this.idBu && areaBu[this.idArea]) this.idBu = String(areaBu[this.idArea]);
            });

            // Grafik dibuat setelah komponen tampil (x-cloak lepas). Jika dibuat saat masih
            // tersembunyi, ukuran kanvas terbaca 0 dan posisi mouse bisa meleset dari grafik.
            this.$nextTick(() => requestAnimationFrame(() => {
                kompensasiZoom();
                this.buatGrafik();
                this.setPreset('year');
                this.refresh();
                const ukurUlang = () => Object.values(charts).forEach(c => c.resize());
                setTimeout(ukurUlang, 300);
                window.addEventListener('load', ukurUlang);
            }));
        },

        jadwal() {
            clearTimeout(timer);
            timer = setTimeout(() => this.refresh(), 30);
        },

        setPreset(key) {
            const now = parse(this.hariIni);
            const y = now.getFullYear(), m = now.getMonth();
            let a, b = ymd(now);
            if (key === 'month')          { a = ymd(new Date(y, m, 1));  b = ymd(new Date(y, m + 1, 0)); }
            else if (key === 'lastmonth') { a = ymd(new Date(y, m - 1, 1)); b = ymd(new Date(y, m, 0)); }
            else if (key === '3m')        { a = ymd(new Date(y, m - 2, 1)); }
            else if (key === '6m')        { a = ymd(new Date(y, m - 5, 1)); }
            else if (key === 'lastyear')  { a = `${y - 1}-01-01`; b = `${y - 1}-12-31`; }
            else if (key === 'all')       { a = this.batasAwal; }
            else                          { a = `${y}-01-01`; }
            if (a < this.batasAwal) a = this.batasAwal;
            this.preset = key;
            this.awal = a;
            this.akhir = b;
        },

        resetFilter() {
            this.idBu = this.lockedBu !== null ? String(this.lockedBu) : '';
            this.idArea = '';
            this.unit = '';
            this.status = '';
            this.barang = '';
            this.pilihan = [];
            this.cq = LV.map(() => '');
            this.tutupPanel();
            this.setPreset('year');
            this.hitungChain();   // filter yang tidak berubah tidak memicu refresh(); kotak tetap harus kembali ke awal
        },

        hapusChip(k) {
            if (k === 'bu') this.idBu = '';
            else if (k === 'area') this.idArea = '';
            else if (k === 'unit') this.unit = '';
            else if (k === 'status') this.status = '';
            else if (k === 'barang') this.barang = '';
        },

        toggleStatus(i) { this.status = this.status === String(i) ? '' : String(i); },
        togglePilih(prop, nilai) { this[prop] = this[prop] === String(nilai) ? '' : String(nilai); },

        // ------------------------------------------------------------------
        // Filter permintaan; `skip` = dimensi yang dilewati (agar grafik dimensi itu
        // tetap menampilkan semua pilihan dan item terpilih bisa disorot).
        saring(skip, aw2, ak2) {
            const aw = aw2 || this.awal || this.batasAwal, ak = ak2 || this.akhir || this.hariIni;
            // Admin (BU terkunci): server sudah membatasi data menurut BU pemohon, jadi BU area tidak boleh
            // dipakai menyaring lagi (permintaan ke area BU lain akan hilang).
            const bu = (this.idBu && this.lockedBu === null) ? +this.idBu : 0, ar = this.idArea ? +this.idArea : 0;
            const un = this.unit ? D.units.indexOf(this.unit) : -1;
            const br = this.barang ? +this.barang : 0;
            const st = this.status === '' ? -1 : +this.status;
            const sk = [].concat(skip || []);
            return D.rows.filter(r =>
                r[0] >= aw && r[0] <= ak &&
                (sk.includes('bu')     || !bu || r[1] === bu) &&
                (sk.includes('area')   || !ar || r[2] === ar) &&
                (sk.includes('unit')   || un < 0 || r[3] === un) &&
                (sk.includes('barang') || !br || r[4] === br) &&
                (sk.includes('status') || st < 0 || r[5] === st));
        },

        refresh() {
            if (this.awal && this.akhir && this.akhir < this.awal) {
                this.errorMsg = 'Periode akhir tidak boleh lebih awal dari periode awal.';
                return;
            }
            this.errorMsg = '';
            const aw = this.awal || this.batasAwal, ak = this.akhir || this.hariIni;
            const bu = this.idBu ? +this.idBu : 0, ar = this.idArea ? +this.idArea : 0;   // untuk stok & mode per Area
            const buF = this.lockedBu === null ? bu : 0;                                   // BU yang benar-benar memfilter permintaan

            // --- Ringkasan & komposisi (semua status tampil) ---
            const dasar = this.saring('status');
            const s = [0, 0, 0, 0, 0];   // jumlah permintaan per status
            const q = [0, 0, 0, 0, 0];   // jumlah barang (qty) per status
            let qtyTotal = 0;
            dasar.forEach(r => {
                const jml = qtyBaris(r);
                s[r[5]]++;
                q[r[5]] += jml;
                qtyTotal += jml;
            });
            this.kpi = { total: dasar.length, s, q, qtyTotal };

            // Bobot per baris permintaan: jumlah item atau 1 (hitung baris)
            const item = this.ukuran === 'item';
            const w = r => item ? qtyBaris(r) : 1;
            const lbl = item ? 'Jumlah item' : 'Jumlah permintaan';

            // --- Periode pembanding: rentang sama panjang tepat sebelum periode aktif ---
            const agg = rows => {
                const o = { s: [0, 0, 0, 0, 0], q: [0, 0, 0, 0, 0], tot: 0, qty: 0 };
                rows.forEach(r => { const j = qtyBaris(r); o.s[r[5]]++; o.q[r[5]] += j; o.tot++; o.qty += j; });
                return o;
            };
            const V = (o, i) => item ? o.q[i] : o.s[i];
            const T = o => item ? o.qty : o.tot;
            const cur = agg(dasar);
            const hariN = Math.round((parse(ak) - parse(aw)) / 86400000) + 1;
            const pEnd = parse(aw); pEnd.setDate(pEnd.getDate() - 1);
            const pStart = new Date(pEnd); pStart.setDate(pStart.getDate() - (hariN - 1));
            const pa = ymd(pStart), pb = ymd(pEnd);
            const prevOk = pa >= this.batasAwal;
            const prevRows = prevOk ? this.saring('status', pa, pb) : [];
            const prv = agg(prevRows);
            const dl = (c, p) => (prevOk && p > 0) ? (c - p) / p * 100 : null;
            const rt = o => (V(o, 0) + V(o, 1)) ? V(o, 0) / (V(o, 0) + V(o, 1)) * 100 : null;
            const rc = rt(cur), rp = rt(prv);
            this.cmp = { prevOk, pa, pb,
                total: { v: T(cur), p: T(prv), d: dl(T(cur), T(prv)) },
                rate:  { v: rc, d: (prevOk && rc !== null && rp !== null) ? rc - rp : null, ok: V(cur, 0), dec: V(cur, 0) + V(cur, 1) },
                pl1:   { v: V(cur, 2), p: V(prv, 2), d: dl(V(cur, 2), V(prv, 2)) },
                padm:  { v: V(cur, 4), p: V(prv, 4), d: dl(V(cur, 4), V(prv, 4)) } };

            // --- Deret waktu ---
            const rg = rentang(aw, ak);
            this.gran = rg.gran;
            ctx.keys = rg.keys; ctx.gran = rg.gran; ctx.labels = rg.labels;
            const idxKey = {};
            rg.keys.forEach((k, i) => { idxKey[k] = i; });
            const potongKey = rg.gran === 'day' ? (t => t) : (t => t.slice(0, 7));

            const perStatus = [0, 1, 2, 3, 4].map(() => rg.keys.map(() => 0));
            this.saring(null).forEach(r => {
                const i = idxKey[potongKey(r[0])];
                if (i === undefined) return;
                perStatus[r[5]][i] += w(r);
            });

            // --- Gambar ---
            const set = (id, labels, datasets) => {
                const c = charts[id];
                if (!c) return;
                try { c.data.labels = labels; c.data.datasets = datasets; c.update(); }
                catch (err) { console.error('Gagal memperbarui grafik ' + id, err); }
            };

            set('chBulan', rg.labels, URUT.map(i => ({
                label: STATUS_NAMA[i], data: perStatus[i], backgroundColor: WARNA[i], borderRadius: 2, maxBarThickness: 28,
            })));

            this.hitungChain();
            this.spHitung();
        },

        // ------------------------------------------------------------------
        buatGrafik() {
            if (typeof Chart === 'undefined') { this.chartMissing = true; return; }
            if (Object.keys(charts).length) return;
            const self = this;

            // Tooltip muncul tepat di posisi kursor
            if (Chart.Tooltip && Chart.Tooltip.positioners) {
                Chart.Tooltip.positioners.cursor = (items, pos) => pos;
            }

            Chart.defaults.font.family = 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif';
            Chart.defaults.font.size = Math.max(9, Math.round(12 * zoomHalaman()));
            Chart.defaults.color = '#64748b';
            Object.assign(Chart.defaults.plugins.tooltip, { backgroundColor: '#0f172a', padding: 9, cornerRadius: 6, boxPadding: 4 });

            const mk = (id, cfg) => {
                const el = document.getElementById(id);
                if (!el) return;
                try { charts[id] = new Chart(el, cfg); } catch (err) { console.error('Gagal membuat grafik ' + id, err); }
            };
            // Tooltip langsung muncul di tempat (tidak meluncur) dan tanpa animasi saat hover,
            // supaya angka yang tampil tidak "lari-lari" ketika mouse digerakkan.
            const tipDasar = { animation: { duration: 0 }, position: 'cursor' };
            const dasar = {
                responsive: true, maintainAspectRatio: false,
                transitions: { active: { animation: { duration: 0 } } },
            };
            const legendBawah = { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } };
            mk('chBulan', {
                type: 'bar',
                data: { labels: [], datasets: [] },
                options: {
                    ...dasar,
                    interaction: { mode: 'index', axis: 'x', intersect: false },
                    scales: { x: { stacked: true, grid: { display: false }, ticks: { maxTicksLimit: 16, autoSkip: true } },
                              y: { stacked: true, ...sumbuAngka } },
                    plugins: {
                        legend: legendBawah,
                        tooltip: { ...tipDasar, callbacks: { footer: items => 'Total: ' + angka(items.reduce((a, i) => a + i.parsed.y, 0)) } },
                    },
                    onClick: (e, els, chart) => self.klikBulan(e, els, chart),
                    onHover: pointer,
                },
            });
        },
    };
}
</script>
@endpush