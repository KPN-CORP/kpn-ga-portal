@extends('layouts.app_stock_sidebar')

@section('content')
@php
    $sel  = 'w-full border border-slate-200 rounded-lg px-2.5 py-1.5 text-[13px] bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400/30 focus:border-slate-400 disabled:bg-slate-50 disabled:text-slate-400';
    $card = 'bg-white border border-slate-200/70 rounded-xl shadow-[0_1px_2px_rgba(15,23,42,.04)]';
    $h    = 'text-[13px] font-semibold text-slate-800';
    $hint = 'text-[11px] text-slate-400';
    $lbl  = 'block text-[11px] font-medium text-slate-500 mb-1';
    $peran = ($access['is_super'] ?? false) ? 'Superadmin' : (($access['is_admin'] ?? false) ? 'Admin Area' : 'User');
@endphp
<script>
    window.__dash = {!! json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<div class="space-y-4 text-[13px] text-slate-700 min-w-0 max-w-full tabular-nums" :class="sp ? 'xl:pr-[640px]' : ''" @keydown.escape.window="sp && tutupPanel()" x-data="dashboardPage(window.__dash)" x-cloak>

    {{-- Header --}}
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-slate-900 tracking-tight">Dashboard E-Stationery</h2>
            <p class="mt-0.5 text-xs text-slate-400">
                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 font-medium mr-1">{{ $peran }}</span>
                Klik grafik, tabel, atau status untuk melihat datanya di panel kanan
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

    {{-- KPI --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
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

    {{-- Barang + pemohon teratas --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="{{ $card }} p-4">
            <div class="flex items-baseline justify-between mb-2">
                <h3 class="{{ $h }}">Barang paling banyak diminta<button type="button" @click="toggleKet('barang')" class="ml-1 align-middle text-slate-300 hover:text-slate-500" :class="!ketTutup.barang ? 'text-slate-500' : ''" title="Keterangan"><i class="far fa-circle-question text-[11px]"></i></button></h3>
                <span class="{{ $hint }}">klik baris untuk melihat data</span>
            </div>
            <p x-show="!ketTutup.barang" class="mb-3 text-xs leading-relaxed text-slate-500 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2" x-text="ket.barang"></p>
            <table class="w-full">
                <thead>
                    <tr class="text-[11px] uppercase tracking-wider text-slate-400">
                        <th class="text-left font-medium pb-2 w-6">#</th>
                        <th class="text-left font-medium pb-2">Barang</th>
                        <th class="text-right font-medium pb-2" x-text="ukuran === 'item' ? 'Item' : 'Permintaan'"></th>
                                                                    </tr>
                </thead>
                <tbody>
                    <template x-for="(b, i) in tabBarang" :key="b.id">
                        <tr @click="bukaBarang(b)" class="border-t border-slate-100 cursor-pointer hover:bg-slate-50/70 transition-opacity"
                            :class="barang && +barang !== b.id ? 'opacity-40' : ''">
                            <td class="py-2 text-slate-400" x-text="i + 1"></td>
                            <td class="py-2 pr-2 text-slate-700 max-w-[14rem] truncate" x-text="b.nama" :title="b.nama"></td>
                            <td class="py-2 text-right font-medium text-slate-900" x-text="angka(b.v)"></td>
                        </tr>
                    </template>
                    <tr x-show="!tabBarang.length"><td colspan="3" class="py-6 text-center text-slate-400">Belum ada data.</td></tr>
                </tbody>
            </table>
        </div>

        <div class="{{ $card }} p-4">
            <div class="flex items-baseline justify-between mb-2">
                <h3 class="{{ $h }}">10 pemohon teratas<button type="button" @click="toggleKet('pemohon')" class="ml-1 align-middle text-slate-300 hover:text-slate-500" :class="!ketTutup.pemohon ? 'text-slate-500' : ''" title="Keterangan"><i class="far fa-circle-question text-[11px]"></i></button></h3>
                <span class="{{ $hint }}">klik baris untuk melihat data</span>
            </div>
            <p x-show="!ketTutup.pemohon" class="mb-3 text-xs leading-relaxed text-slate-500 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2" x-text="ket.pemohon"></p>
            <table class="w-full">
                <thead>
                    <tr class="text-[11px] uppercase tracking-wider text-slate-400">
                        <th class="text-left font-medium pb-2 w-6">#</th>
                        <th class="text-left font-medium pb-2">Pemohon</th>
                        <th class="text-right font-medium pb-2" x-text="ukuran === 'item' ? 'Item' : 'Permintaan'"></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(p, i) in tabPemohon" :key="p.id">
                        <tr @click="bukaPemohon(p)" class="border-t border-slate-100 cursor-pointer hover:bg-slate-50/70">
                            <td class="py-2 text-slate-400" x-text="i + 1"></td>
                            <td class="py-2 pr-2 max-w-[16rem] text-slate-700 truncate" x-text="p.nama" :title="p.nama"></td>
                            <td class="py-2 text-right font-medium text-slate-900" x-text="angka(p.v)"></td>
                        </tr>
                    </template>
                    <tr x-show="!tabPemohon.length"><td colspan="3" class="py-6 text-center text-slate-400">Belum ada data.</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Nilai + risiko stok --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="{{ $card }} p-4">
            <div class="flex items-baseline justify-between mb-2">
                <h3 class="{{ $h }}">Nilai permintaan disetujui<button type="button" @click="toggleKet('nilai')" class="ml-1 align-middle text-slate-300 hover:text-slate-500" :class="!ketTutup.nilai ? 'text-slate-500' : ''" title="Keterangan"><i class="far fa-circle-question text-[11px]"></i></button></h3>
                <span class="{{ $hint }}">Rupiah, per <span x-text="gran === 'day' ? 'hari' : 'bulan'"></span></span>
            </div>
            <p x-show="!ketTutup.nilai" class="mb-3 text-xs leading-relaxed text-slate-500 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2" x-text="ket.nilai"></p>
            <div class="relative" data-chart-wrap style="height:220px"><canvas id="chNilai"></canvas></div>
        </div>
        <div class="{{ $card }} p-4">
            <div class="flex items-baseline justify-between mb-1">
                <h3 class="{{ $h }}">Risiko stok per Bisnis Unit<button type="button" @click="toggleKet('stok')" class="ml-1 align-middle text-slate-300 hover:text-slate-500" :class="!ketTutup.stok ? 'text-slate-500' : ''" title="Keterangan"><i class="far fa-circle-question text-[11px]"></i></button></h3>
                <span class="{{ $hint }}">klik untuk melihat data</span>
            </div>
            <p x-show="!ketTutup.stok" class="mb-3 text-xs leading-relaxed text-slate-500 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2" x-text="ket.stok"></p>
            <div class="flex items-center gap-4 text-xs text-slate-500 mb-3">
                <span class="inline-flex items-center gap-1.5"><i class="w-2 h-2 rounded-sm bg-slate-300"></i>Stok cukup <b class="text-slate-800 font-semibold" x-text="angka(stokKpi.aman)"></b></span>
                <span class="inline-flex items-center gap-1.5"><i class="w-2 h-2 rounded-sm bg-[#d2b37a]"></i>Menipis <b class="text-slate-800 font-semibold" x-text="angka(stokKpi.menipis)"></b></span>
                <span class="inline-flex items-center gap-1.5"><i class="w-2 h-2 rounded-sm bg-[#b06a6e]"></i>Habis <b class="text-slate-800 font-semibold" x-text="angka(stokKpi.habis)"></b></span>
            </div>
            <template x-for="a in stokArea" :key="a.id">
                <button type="button" @click="bukaStok(a)" class="w-full text-left py-1.5 transition-opacity" :class="idBu && +idBu !== a.id ? 'opacity-40' : ''">
                    <div class="flex justify-between text-xs">
                        <span class="truncate pr-3 text-slate-700" x-text="a.nama"></span>
                        <span class="text-slate-400 whitespace-nowrap" x-text="(a.menipis + a.habis) + ' dari ' + a.tot + ' barang'"></span>
                    </div>
                    <div class="flex h-1.5 mt-1 rounded-full overflow-hidden bg-slate-100">
                        <div class="bg-slate-300" :style="`width:${a.aman / a.tot * 100}%`"></div>
                        <div class="bg-[#d2b37a]" :style="`width:${a.menipis / a.tot * 100}%`"></div>
                        <div class="bg-[#b06a6e]" :style="`width:${a.habis / a.tot * 100}%`"></div>
                    </div>
                </button>
            </template>
            <p class="py-6 text-center text-slate-400" x-show="!stokArea.length">Belum ada data stok.</p>
        </div>
    </div>

    {{-- Unit (Bisnis Unit) + BU/Area --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="{{ $card }} p-4">
            <div class="flex items-baseline justify-between mb-2">
                <h3 class="{{ $h }}"><span x-text="(ukuran === 'item' ? 'Item diminta per unit' : 'Permintaan per unit') + ' (Bisnis Unit)'"></span><button type="button" @click="toggleKet('unit')" class="ml-1 align-middle text-slate-300 hover:text-slate-500" :class="!ketTutup.unit ? 'text-slate-500' : ''" title="Keterangan"><i class="far fa-circle-question text-[11px]"></i></button></h3>
                <span class="{{ $hint }}">10 teratas, klik untuk melihat data</span>
            </div>
            <p x-show="!ketTutup.unit" class="mb-3 text-xs leading-relaxed text-slate-500 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2" x-text="ket.unit"></p>
            <div class="relative" data-chart-wrap style="height:256px"><canvas id="chUnit"></canvas></div>
        </div>
        <div class="{{ $card }} p-4">
            <div class="flex items-baseline justify-between mb-2">
                <h3 class="{{ $h }}"><span x-text="judulGrup"></span><button type="button" @click="toggleKet('grup')" class="ml-1 align-middle text-slate-300 hover:text-slate-500" :class="!ketTutup.grup ? 'text-slate-500' : ''" title="Keterangan"><i class="far fa-circle-question text-[11px]"></i></button></h3>
                <span class="{{ $hint }}">klik untuk melihat data</span>
            </div>
            <p x-show="!ketTutup.grup" class="mb-3 text-xs leading-relaxed text-slate-500 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2" x-text="ket.grup"></p>
            <div class="relative" data-chart-wrap style="height:256px"><canvas id="chGrup"></canvas></div>
        </div>
    </div>

    {{-- Panel data (kanan) --}}
    <template x-if="sp">
        <aside class="fixed top-0 right-0 h-full w-[620px] max-w-full bg-white border-l border-slate-200 shadow-[-8px_0_24px_rgba(15,23,42,.08)] z-50 flex flex-col !mt-0">
            <div class="flex items-start justify-between gap-3 px-4 pt-4 pb-3 border-b border-slate-100">
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-slate-900 truncate" x-text="sp.t"></h3>
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
                                    <td class="py-2 px-1" :class="[spCols[j].r ? 'text-right whitespace-nowrap' : '', j === 0 ? 'whitespace-nowrap text-slate-500' : 'text-slate-700']">
                                        <span x-show="spCols[j].k === 'st'" class="inline-block w-1.5 h-1.5 rounded-full mr-1.5 align-middle" :style="`background:${row.w}`"></span><span x-text="cell"></span>
                                        <div x-show="j === 1 && row.sub" class="text-[11px] text-slate-400" x-text="row.sub"></div>
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

    <p class="text-xs text-slate-400">Data mencakup 24 bulan terakhir (mulai <span x-text="batasAwal"></span>). Barang, pemohon, dan unit hanya menghitung permintaan berstatus Disetujui (filter Status tidak berlaku); BU/Area tidak menghitung yang dibatalkan. "Item" = total jumlah barang yang diminta; "Permintaan" = jumlah baris permintaan. Periode pembanding = rentang yang sama panjang tepat sebelum periode terpilih.</p>
</div>

<script>
function dashboardPage(D) {
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

    const KET_KEYS = ['bulan', 'status', 'nilai', 'barang', 'pemohon', 'stok', 'unit', 'grup'];
    const KET_STORE = 'stockDashKetTutup';
    const STATUS_NAMA = ['Disetujui', 'Ditolak', 'Dalam Proses', 'Dibatalkan'];
    // urutan indeks status: 0 disetujui, 1 ditolak, 2 dalam proses, 3 dibatalkan
    const WARNA = ['#4f8a79', '#b06a6e', '#c4a266', '#c3c9d3'];

    // Chart & data bantu disimpan di luar state Alpine supaya tidak dibungkus proxy reaktif.
    const charts = {};
    const ctx = { keys: [], labels: [], gran: 'month', perArea: false, barang: [], grup: [], unit: [] };
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
        ukuran: 'item',   // 'item' = jumlah barang, 'baris' = jumlah permintaan

        // s = jumlah permintaan (baris) per status, q = jumlah barang (qty) per status
        stokKpi: { aman: 0, menipis: 0, habis: 0 },
        sp: null, spAll: [], spSum: [], tabPemohon: [],
        cbOpenKey: '', cbQ: '', cbHi: 0, cbTyped: false, CB_SEMUA,
        filterDef: [['idBu', 'Bisnis Unit'], ['idArea', 'Area Kerja'], ['unit', 'Unit (Divisi Pemohon)'], ['status', 'Status'], ['barang', 'Barang']],
        ketTutup: Object.fromEntries(KET_KEYS.map(k => [k, false])),
        cmp: { prevOk: false, pa: '', pb: '', total: { v: 0, p: 0, d: null }, rate: { v: null, d: null, ok: 0, dec: 0 }, pend: { v: 0, p: 0, d: null }, nilai: { v: 0, p: 0, d: null } },
        tabBarang: [], stokArea: [],
        kpi: { total: 0, s: [0, 0, 0, 0], q: [0, 0, 0, 0], qtyTotal: 0, nilai: 0 },
        gran: 'month',
        judulGrup: 'Permintaan per Bisnis Unit',
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
            if (k === 'idArea') return this.areaOptions.map(a => [String(a.id), this.idBu ? a.nama : a.nama + ' (' + a.bu_nama + ')']);
            if (k === 'unit')   return this.unitOptions.map(u => [u, u]);
            if (k === 'status') return [['0', 'Disetujui'], ['2', 'Dalam Proses'], ['1', 'Ditolak'], ['3', 'Dibatalkan']];
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
        bukaPanel(t, kind, pred, skip, act) {
            spCtx = { kind, pred, skip: skip || null, act: act || null };
            this.sp = { t, kind, q: '', pg: 0, actLabel: act ? act.label : '' };
            this.spHitung();
        },
        tutupPanel() { this.sp = null; spCtx = null; this.spAll = []; },
        spCari(v) { this.sp.q = v; this.sp.pg = 0; this.spHitung(); },
        jalankanAksi() { if (spCtx && spCtx.act) { spCtx.act.fn(); this.tutupPanel(); } },
        get spCols() {
            const k = this.sp ? this.sp.kind : 'req';
            if (k === 'stok') return [{ h: 'Area' }, { h: 'Stok cukup', r: 1 }, { h: 'Menipis', r: 1 }, { h: 'Habis', r: 1 }, { h: 'Total', r: 1 }];
            return [{ h: 'Tanggal' }, { h: 'Pemohon' }, { h: 'Barang' }, { h: 'Status', k: 'st' }, { h: 'Qty', r: 1 }, { h: 'Nilai', r: 1 }];
        },
        get spHal() { return this.sp ? this.spAll.slice(this.sp.pg * PG, (this.sp.pg + 1) * PG) : []; },
        get spJmlHal() { return Math.max(1, Math.ceil(this.spAll.length / PG)); },
        spHitung() {
            if (!this.sp || !spCtx) return;
            const { kind, pred, skip } = spCtx, q = this.sp.q.trim().toLowerCase();
            let rows = [];
            if (kind === 'req') {
                rows = this.saring(skip).filter(pred).sort((a, b) => b[0].localeCompare(a[0])).map(r => ({
                    c: [fmtTgl(r[0]), pemohonNama[r[8]] || '-', barangNama[r[4]] || ('#' + r[4]), STATUS_NAMA[r[5]], angka(qtyBaris(r)), rupiah(r[6])],
                    sub: (D.units[r[3]] || '-') + ' › ' + (areaNama[r[2]] || '-'), ex: buNama[r[1]] || '', w: WARNA[r[5]], m: [1, qtyBaris(r), r[6]],
                }));
            } else {
                rows = D.stok.filter(pred).sort((a, b) => (b[2] + b[3]) / ((b[1] + b[2] + b[3]) || 1) - (a[2] + a[3]) / ((a[1] + a[2] + a[3]) || 1))
                    .map(x => ({ c: [areaNama[x[0]] || 'Tanpa area', angka(x[1]), angka(x[2]), angka(x[3]), angka(x[1] + x[2] + x[3])], w: '', m: [x[1], x[2], x[3]] }));
            }
            rows.forEach(x => { x.s = (x.c.join(' ') + ' ' + (x.sub || '') + ' ' + (x.ex || '')).toLowerCase(); });
            const a = q ? rows.filter(x => x.s.includes(q)) : rows;
            const jml = [0, 0, 0];
            a.forEach(x => x.m.forEach((v, i) => { jml[i] += v; }));
            const L = { req: ['Permintaan', 'Item', 'Nilai'], stok: ['Stok cukup', 'Menipis', 'Habis'] }[kind];
            this.spSum = L.map((l, i) => ({ l, v: (kind === 'req' && i === 2) ? 'Rp ' + ringkas(jml[i]) : angka(jml[i]) }));
            this.spAll = a;
            this.sp.pg = Math.min(this.sp.pg, Math.max(0, Math.ceil(a.length / PG) - 1));
        },
        bukaStatus(i) {
            this.bukaPanel('Status ' + STATUS_NAMA[i], 'req', r => r[5] === i, 'status', { label: 'Jadikan filter Status', fn: () => { this.status = String(i); } });
        },
        bukaPemohon(p) {
            this.bukaPanel('Pemohon: ' + p.nama + ' · Disetujui', 'req', r => r[5] === 0 && r[8] === p.id, 'status', null);
        },
        bukaBarang(b) {
            this.bukaPanel(b.nama + ' · Disetujui', 'req', r => r[5] === 0 && r[4] === b.id, ['barang', 'status'], { label: 'Jadikan filter Barang', fn: () => { this.barang = String(b.id); } });
        },
        bukaStok(a) {
            this.bukaPanel('Stok · ' + a.nama, 'stok', x => (areaBu[x[0]] || 0) === a.id, null,
                this.lockedBu === null ? { label: 'Jadikan filter Bisnis Unit', fn: () => { this.idBu = String(a.id); } } : null);
        },
        klikBulan(e, els, chart) {
            if (!els.length) return;
            const hit = chart.getElementsAtEventForMode(e, 'nearest', { intersect: true }, true);
            const idx = els[0].index, st = hit.length ? [0, 2, 1, 3][hit[0].datasetIndex] : null;   // urutan dataset: disetujui, proses, ditolak, batal
            const key = ctx.keys[idx], lab = ctx.labels[idx], kf = ctx.gran === 'day' ? (t => t) : (t => t.slice(0, 7));
            const act = ctx.gran === 'month' ? { label: 'Batasi periode ke bulan ini', fn: () => {
                const [y, m] = key.split('-').map(Number);
                this.preset = ''; this.awal = `${y}-${pad(m)}-01`; this.akhir = ymd(new Date(y, m, 0));
            } } : null;
            this.bukaPanel((st === null ? 'Semua status' : STATUS_NAMA[st]) + ' · ' + lab, 'req', r => kf(r[0]) === key && (st === null || r[5] === st), null, act);
        },
        klikNilai(e, els) {
            if (!els.length) return;
            const key = ctx.keys[els[0].index], lab = ctx.labels[els[0].index], kf = ctx.gran === 'day' ? (t => t) : (t => t.slice(0, 7));
            this.bukaPanel('Disetujui · ' + lab, 'req', r => r[5] === 0 && kf(r[0]) === key, null, null);
        },
        klikUnit(els) {
            if (!els.length) return;
            const u = ctx.unit[els[0].index];
            this.bukaPanel('Unit: ' + u.nama + ' (' + (buNama[u.b] || '-') + ') · Disetujui', 'req', r => r[5] === 0 && r[3] === u.i && r[1] === u.b, ['unit', 'status'],
                { label: 'Jadikan filter Unit', fn: () => { this.unit = u.nama; if (this.lockedBu === null) this.idBu = String(u.b); } });
        },
        klikGrup(els) {
            if (!els.length) return;
            const id = ctx.grup[els[0].index], pa = ctx.perArea, nama = pa ? (areaNama[id] || '#' + id) : (buNama[id] || '#' + id);
            const act = (pa || this.lockedBu === null) ? { label: pa ? 'Jadikan filter Area' : 'Jadikan filter Bisnis Unit', fn: () => { if (pa) this.idArea = String(id); else this.idBu = String(id); } } : null;
            this.bukaPanel((pa ? 'Area: ' : 'Bisnis Unit: ') + nama, 'req', r => r[5] !== 3 && (pa ? r[2] === id : r[1] === id), pa ? 'area' : 'bu', act);
        },

        // ---- Keterangan tiap grafik/tabel (bisa diminimalkan, pilihan diingat di browser) ----
        get ket() {
            const item = this.ukuran === 'item';
            const sat = item ? 'jumlah item (total barang yang diminta)' : 'jumlah permintaan (banyaknya baris permintaan)';
            const per = this.gran === 'day' ? 'hari' : 'bulan';
            return {
                bulan:  `Menampilkan ${sat} per ${per}, ditumpuk menurut status. Mengikuti semua filter di atas. Klik sebuah batang untuk menampilkan datanya di panel kanan.`,
                status: `Pembagian ${sat} menurut status. Klik sebuah status untuk menampilkan datanya di panel kanan.`,
                nilai:  `Total nilai (jumlah × harga barang) dari permintaan berstatus Disetujui per ${per}. Mengikuti filter Bisnis Unit, Area, Unit, dan Barang. Klik sebuah titik untuk melihat datanya.`,
                barang: `10 barang teratas menurut ${sat}, hanya dari permintaan berstatus Disetujui (filter Status tidak berlaku). Klik baris untuk menampilkan datanya di panel kanan.`,
                pemohon: `10 pemohon dengan ${sat} terbanyak, hanya dari permintaan berstatus Disetujui (filter Status tidak berlaku). Klik baris untuk melihat permintaan pemohon itu.`,
                stok:   'Kondisi stok saat ini (bukan per periode), dikelompokkan per Bisnis Unit dan diurutkan dari persentase barang menipis + habis tertinggi. Stok cukup = di atas stok minimum; menipis = di atas 0 tetapi di bawah/sama dengan stok minimum; habis = stok 0. Klik sebuah Bisnis Unit untuk melihat rincian stok per area.',
                unit:   `10 unit teratas menurut ${sat}, hanya dari permintaan berstatus Disetujui (filter Status tidak berlaku). Nama unit ditulis bersama Bisnis Unit-nya dalam kurung. Klik batang untuk menampilkan datanya di panel kanan.`,
                grup:   `10 teratas menurut ${sat}, tanpa permintaan dibatalkan. Per Bisnis Unit; setelah satu Bisnis Unit dipilih, berubah menjadi per Area Kerja. Klik batang untuk menampilkan datanya di panel kanan.`,
            };
        },
        get semuaKetTutup() { return KET_KEYS.every(k => this.ketTutup[k]); },
        toggleKet(k) { this.ketTutup[k] = !this.ketTutup[k]; this.simpanKet(); },
        toggleSemuaKet() { const t = !this.semuaKetTutup; KET_KEYS.forEach(k => { this.ketTutup[k] = t; }); this.simpanKet(); },
        simpanKet() { try { localStorage.setItem(KET_STORE, JSON.stringify(this.ketTutup)); } catch (e) {} },

        get kartu() {
            const C = this.cmp, K = this.kpi, item = this.ukuran === 'item';
            return [
                { k: 'total', label: item ? 'Item diminta' : 'Total permintaan', val: angka(C.total.v),
                  sub: C.prevOk ? 'sebelumnya ' + angka(C.total.p) : 'pembanding tidak tersedia',
                  dtx: this.fd(C.total.d), dcls: this.dcls(C.total.d, 0) },
                { k: 'rate', label: 'Tingkat persetujuan', val: C.rate.v === null ? '–' : C.rate.v.toFixed(0) + '%',
                  sub: angka(C.rate.ok) + ' dari ' + angka(C.rate.dec) + ' yang diputuskan',
                  dtx: this.fpp(C.rate.d), dcls: this.dcls(C.rate.d, 1) },
                { k: 'pend', label: 'Dalam proses', val: angka(C.pend.v),
                  sub: 'dari ' + angka(C.total.v) + (item ? ' item' : ' permintaan'),
                  dtx: this.fd(C.pend.d), dcls: this.dcls(C.pend.d, -1) },
            ];
        },
        get statusRows() {
            const arr = this.ukuran === 'item' ? this.kpi.q : this.kpi.s;
            const tot = arr.reduce((a, b) => a + b, 0);
            return [0, 2, 1, 3].map(i => ({ i, nama: STATUS_NAMA[i], v: arr[i], pct: tot ? arr[i] / tot * 100 : 0, warna: WARNA[i] }));
        },

        get areaOptions() {
            return this.idBu ? this.areas.filter(a => String(a.bu) === String(this.idBu)) : this.areas;
        },
        get unitOptions() {
            const bu = this.idBu ? +this.idBu : 0;
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
            this.setPreset('year');
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
            const bu = this.idBu ? +this.idBu : 0, ar = this.idArea ? +this.idArea : 0;
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
            const bu = this.idBu ? +this.idBu : 0, ar = this.idArea ? +this.idArea : 0;

            // --- Ringkasan & komposisi (semua status tampil) ---
            const dasar = this.saring('status');
            const s = [0, 0, 0, 0];   // jumlah permintaan per status
            const q = [0, 0, 0, 0];   // jumlah barang (qty) per status
            let nilai = 0, qtyTotal = 0;
            dasar.forEach(r => {
                const jml = qtyBaris(r);
                s[r[5]]++;
                q[r[5]] += jml;
                qtyTotal += jml;
                if (r[5] === 0) nilai += r[6];
            });
            this.kpi = { total: dasar.length, s, q, qtyTotal, nilai };

            // Bobot per baris permintaan: jumlah item atau 1 (hitung baris)
            const item = this.ukuran === 'item';
            const w = r => item ? qtyBaris(r) : 1;
            const lbl = item ? 'Jumlah item' : 'Jumlah permintaan';

            // --- Periode pembanding: rentang sama panjang tepat sebelum periode aktif ---
            const agg = rows => {
                const o = { s: [0, 0, 0, 0], q: [0, 0, 0, 0], tot: 0, qty: 0, nilai: 0 };
                rows.forEach(r => { const j = qtyBaris(r); o.s[r[5]]++; o.q[r[5]] += j; o.tot++; o.qty += j; if (r[5] === 0) o.nilai += r[6]; });
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
                pend:  { v: V(cur, 2), p: V(prv, 2), d: dl(V(cur, 2), V(prv, 2)) },
                nilai: { v: cur.nilai, p: prv.nilai, d: dl(cur.nilai, prv.nilai) } };

            // --- Deret waktu ---
            const rg = rentang(aw, ak);
            this.gran = rg.gran;
            ctx.keys = rg.keys; ctx.gran = rg.gran; ctx.labels = rg.labels;
            const idxKey = {};
            rg.keys.forEach((k, i) => { idxKey[k] = i; });
            const potongKey = rg.gran === 'day' ? (t => t) : (t => t.slice(0, 7));

            const perStatus = [0, 1, 2, 3].map(() => rg.keys.map(() => 0));
            const nilaiSeri = rg.keys.map(() => 0);
            this.saring(null).forEach(r => {
                const i = idxKey[potongKey(r[0])];
                if (i === undefined) return;
                perStatus[r[5]][i] += w(r);
                if (r[5] === 0) nilaiSeri[i] += r[6];
            });


            // --- Stok ---
            const stok = [0, 0, 0];
            D.stok.forEach(x => {
                if (bu && areaBu[x[0]] !== bu) return;
                if (ar && x[0] !== ar) return;
                stok[0] += x[1]; stok[1] += x[2]; stok[2] += x[3];
            });

            this.stokKpi = { aman: stok[0], menipis: stok[1], habis: stok[2] };
            // Daftar risiko per Bisnis Unit: tidak ikut filter BU/Area supaya BU terpilih bisa disorot.
            const perBu = {};
            D.stok.forEach(x => {
                const b = areaBu[x[0]] || 0;
                const o = perBu[b] || (perBu[b] = { aman: 0, menipis: 0, habis: 0 });
                o.aman += x[1]; o.menipis += x[2]; o.habis += x[3];
            });
            const stokArea = Object.entries(perBu)
                .map(([id, o]) => { const t = o.aman + o.menipis + o.habis; return { id: +id, nama: buNama[id] || 'Tanpa Bisnis Unit', ...o, tot: t, risk: t ? (o.menipis + o.habis) / t : 0 }; })
                .filter(x => x.tot > 0)
                .sort((a, b) => b.risk - a.risk || b.habis - a.habis)
                .slice(0, 8);
            this.stokArea = stokArea;

            // --- Barang / Unit / BU-Area (tanpa dibatalkan) ---
            const hitung = (rows, kunci) => {
                const m = {};
                rows.forEach(r => { if (r[5] !== 3) { const k = kunci(r); m[k] = (m[k] || 0) + w(r); } });
                return m;
            };
            const hitungSetuju = (rows, kunci) => hitung(rows.filter(r => r[5] === 0), kunci);
            const tBarang = top(hitungSetuju(this.saring(['barang', 'status']), r => r[4]), 10);
            const tUnit   = top(hitungSetuju(this.saring(['unit', 'status']), r => r[3] + '|' + r[1]), 10);
            const perArea = !!bu;
            ctx.perArea = perArea;
            const awalJudul = item ? 'Item Diminta per ' : 'Permintaan per ';
            this.judulGrup = awalJudul + (perArea ? 'Area Kerja' : 'Bisnis Unit');
            const tGrup = top(hitung(this.saring(perArea ? 'area' : 'bu'), r => perArea ? r[2] : r[1]), 10);

            ctx.barang = tBarang.map(x => +x[0]);
            ctx.unit   = tUnit.map(x => { const [i, b] = x[0].split('|').map(Number); return { i, b, nama: D.units[i] }; });
            ctx.grup   = tGrup.map(x => +x[0]);

            this.tabBarang = tBarang.map(x => ({ id: +x[0], nama: barangNama[x[0]] || ('#' + x[0]), v: x[1] }));
            this.tabPemohon = top(hitungSetuju(this.saring('status'), r => r[8]), 10).map(x => ({
                id: +x[0], nama: pemohonNama[x[0]] || ('User #' + x[0]), v: x[1] }));

            // --- Gambar ---
            const set = (id, labels, datasets) => {
                const c = charts[id];
                if (!c) return;
                c.data.labels = labels;
                c.data.datasets = datasets;
                c.update();
            };

            set('chBulan', rg.labels, [0, 2, 1, 3].map(i => ({
                label: STATUS_NAMA[i], data: perStatus[i], backgroundColor: WARNA[i], borderRadius: 2, maxBarThickness: 28,
            })));


            set('chNilai', rg.labels, [{
                label: 'Nilai disetujui', data: nilaiSeri, borderColor: '#4a6a94', borderWidth: 2,
                backgroundColor: 'rgba(74,106,148,.08)', fill: true, tension: .3, pointRadius: rg.keys.length > 40 ? 0 : 3,
            }]);



            set('chUnit', ctx.unit.map(u => u.nama + ' (' + (buNama[u.b] || '-') + ')'), [{
                label: lbl, data: tUnit.map(x => x[1]),
                backgroundColor: ctx.unit.map(u => (!this.unit || (u.nama === this.unit && (!bu || u.b === bu)) ? '#4a6a94' : '#4a6a9455')), borderRadius: 3,
            }]);

            const selGrup = perArea ? (ar || null) : (bu || null);
            set('chGrup', tGrup.map(x => perArea ? (areaNama[x[0]] || ('#' + x[0])) : (buNama[x[0]] || ('#' + x[0]))), [{
                label: lbl, data: tGrup.map(x => x[1]),
                backgroundColor: sorot('#64748b', ctx.grup, selGrup), borderRadius: 3,
            }]);

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

            const mk = (id, cfg) => { const el = document.getElementById(id); if (el) charts[id] = new Chart(el, cfg); };
            // Tooltip langsung muncul di tempat (tidak meluncur) dan tanpa animasi saat hover,
            // supaya angka yang tampil tidak "lari-lari" ketika mouse digerakkan.
            const tipDasar = { animation: { duration: 0 }, position: 'cursor' };
            const dasar = {
                responsive: true, maintainAspectRatio: false,
                transitions: { active: { animation: { duration: 0 } } },
            };
            const legendBawah = { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } };
            const horizontal = (warna, onClick) => ({
                type: 'bar',
                data: { labels: [], datasets: [] },
                options: {
                    ...dasar, indexAxis: 'y',
                    interaction: { mode: 'nearest', axis: 'y', intersect: false },
                    scales: { x: sumbuAngka, y: { grid: { display: false }, ticks: { callback: potong } } },
                    plugins: { legend: { display: false }, tooltip: tipDasar },
                    onClick, onHover: pointer,
                },
            });

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


            mk('chNilai', {
                type: 'line',
                data: { labels: [], datasets: [] },
                options: {
                    ...dasar,
                    interaction: { mode: 'index', axis: 'x', intersect: false },
                    scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 16, autoSkip: true } },
                              y: { beginAtZero: true, grid: { color: '#f3f4f6' }, ticks: { callback: v => ringkas(v) } } },
                    plugins: { legend: { display: false }, tooltip: { ...tipDasar, callbacks: { label: c => rupiah(c.parsed.y) } } },
                    onClick: (e, els) => self.klikNilai(e, els), onHover: pointer,
                },
            });


            mk('chUnit', horizontal('#4a6a94', (e, els) => self.klikUnit(els)));
            mk('chGrup', horizontal('#64748b', (e, els) => self.klikGrup(els)));

        },
    };
}
</script>
@endsection