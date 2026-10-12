@extends('layouts.app_stock_sidebar')

@section('content')
<script>
    window.__dash = {!! json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>

<div class="space-y-5 text-sm text-gray-800 font-sans min-w-0 max-w-full" x-data="dashboardPage(window.__dash)" x-cloak>

    {{-- Header --}}
    <div class="flex flex-wrap items-end justify-between gap-2">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Dashboard E-Stationery</h2>
            <div class="flex items-center gap-2 mt-1">
                @if($access['is_super'] ?? false)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">Superadmin Mode</span>
                @elseif($access['is_admin'] ?? false)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">Admin Area</span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">User Mode</span>
                @endif
                <span class="text-xs text-gray-400">Klik grafik untuk memfilter</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <div class="flex items-center gap-1 text-xs">
                <span class="text-gray-400">Hitung per:</span>
                <div class="inline-flex rounded-lg border overflow-hidden bg-white">
                    <button type="button" @click="ukuran = 'item'"
                            class="px-3 py-1.5 transition"
                            :class="ukuran === 'item' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'">Item</button>
                    <button type="button" @click="ukuran = 'baris'"
                            class="px-3 py-1.5 border-l transition"
                            :class="ukuran === 'baris' ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-50'">Permintaan</button>
                </div>
            </div>
            <button type="button" @click="resetFilter()" class="text-xs px-3 py-1.5 rounded-lg border bg-white text-gray-600 hover:text-red-600 hover:border-red-300">
                <i class="fas fa-rotate-left mr-1"></i> Reset semua filter
            </button>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white border rounded-xl shadow-sm">
        <div class="p-4 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Bisnis Unit</label>
                    <select x-model="idBu" :disabled="lockedBu !== null"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100">
                        <option value="">Semua Bisnis Unit</option>
                        <template x-for="b in bus" :key="b.id">
                            <option :value="String(b.id)" x-text="b.nama"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Area Kerja</label>
                    <select x-model="idArea" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="" x-text="idBu ? 'Semua Area' : 'Semua Area (semua BU)'"></option>
                        <template x-for="a in areaOptions" :key="a.id">
                            <option :value="String(a.id)" x-text="idBu ? a.nama : (a.nama + ' (' + a.bu_nama + ')')"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Unit (Divisi Pemohon)</label>
                    <select x-model="unit" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Unit</option>
                        <template x-for="u in unitOptions" :key="u">
                            <option :value="u" x-text="u"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
                    <select x-model="status" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="0">Disetujui</option>
                        <option value="2">Dalam Proses</option>
                        <option value="1">Ditolak</option>
                        <option value="3">Dibatalkan</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Barang</label>
                    <select x-model="barang" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Barang</option>
                        <template x-for="b in barangList" :key="b[0]">
                            <option :value="String(b[0])" x-text="b[1]"></option>
                        </template>
                    </select>
                </div>
            </div>

            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Periode Awal</label>
                    <input type="date" x-model="awal" @change="preset = ''" :min="batasAwal" :max="hariIni"
                           class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Periode Akhir</label>
                    <input type="date" x-model="akhir" @change="preset = ''" :min="batasAwal"
                           class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex flex-wrap gap-2 pb-0.5">
                    <template x-for="p in presets" :key="p.key">
                        <button type="button" @click="setPreset(p.key)"
                                class="px-3 py-1.5 rounded-full border text-xs transition"
                                :class="preset === p.key ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 hover:bg-gray-50'"
                                x-text="p.label"></button>
                    </template>
                </div>
            </div>

            {{-- Filter aktif --}}
            <div class="flex flex-wrap items-center gap-2" x-show="chips.length">
                <span class="text-xs text-gray-400">Filter aktif:</span>
                <template x-for="c in chips" :key="c.k">
                    <span class="inline-flex items-center gap-1 pl-2.5 pr-1.5 py-1 rounded-full bg-blue-50 text-blue-700 text-xs border border-blue-100">
                        <span x-text="c.t"></span>
                        <button type="button" @click="hapusChip(c.k)" class="w-4 h-4 rounded-full hover:bg-blue-200 leading-none">&times;</button>
                    </span>
                </template>
            </div>

            <p class="text-xs text-red-600" x-show="errorMsg" x-text="errorMsg"></p>
            <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2" x-show="chartMissing">
                Library grafik (Chart.js) tidak termuat. Periksa koneksi internet / akses ke cdnjs.cloudflare.com.
            </p>
            <p class="text-xs text-gray-400">Data dashboard mencakup 24 bulan terakhir (mulai <span x-text="batasAwal"></span>).</p>
        </div>
    </div>

    {{-- Ringkasan --}}
    <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
        <div class="bg-white border border-l-4 border-l-blue-500 rounded-xl p-4 shadow-sm">
            <p class="text-xs text-gray-500">Total Permintaan</p>
            <p class="text-2xl font-semibold mt-1" x-text="angka(kpi.total)"></p>
            <p class="text-xs text-gray-400" x-text="angka(kpi.qtyTotal) + ' item diminta'"></p>
        </div>
        <button type="button" @click="toggleStatus(0)" class="text-left bg-white border border-l-4 border-l-green-500 rounded-xl p-4 shadow-sm hover:shadow transition" :class="status === '0' ? 'ring-2 ring-green-500' : ''">
            <p class="text-xs text-gray-500">Disetujui</p>
            <p class="text-2xl font-semibold mt-1 text-green-700" x-text="angka(kpi.s[0])"></p>
            <p class="text-xs text-gray-400" x-text="persen(kpi.s[0]) + ' · ' + angka(kpi.q[0]) + ' item'"></p>
        </button>
        <button type="button" @click="toggleStatus(2)" class="text-left bg-white border border-l-4 border-l-amber-500 rounded-xl p-4 shadow-sm hover:shadow transition" :class="status === '2' ? 'ring-2 ring-amber-500' : ''">
            <p class="text-xs text-gray-500">Dalam Proses</p>
            <p class="text-2xl font-semibold mt-1 text-amber-600" x-text="angka(kpi.s[2])"></p>
            <p class="text-xs text-gray-400" x-text="persen(kpi.s[2]) + ' · ' + angka(kpi.q[2]) + ' item'"></p>
        </button>
        <button type="button" @click="toggleStatus(1)" class="text-left bg-white border border-l-4 border-l-red-500 rounded-xl p-4 shadow-sm hover:shadow transition" :class="status === '1' ? 'ring-2 ring-red-500' : ''">
            <p class="text-xs text-gray-500">Ditolak</p>
            <p class="text-2xl font-semibold mt-1 text-red-600" x-text="angka(kpi.s[1])"></p>
            <p class="text-xs text-gray-400" x-text="persen(kpi.s[1]) + ' · ' + angka(kpi.q[1]) + ' item'"></p>
        </button>
        <button type="button" @click="toggleStatus(3)" class="text-left bg-white border border-l-4 border-l-gray-400 rounded-xl p-4 shadow-sm hover:shadow transition" :class="status === '3' ? 'ring-2 ring-gray-400' : ''">
            <p class="text-xs text-gray-500">Dibatalkan</p>
            <p class="text-2xl font-semibold mt-1 text-gray-600" x-text="angka(kpi.s[3])"></p>
            <p class="text-xs text-gray-400" x-text="persen(kpi.s[3]) + ' · ' + angka(kpi.q[3]) + ' item'"></p>
        </button>
        <div class="bg-white border border-l-4 border-l-purple-500 rounded-xl p-4 shadow-sm col-span-2 md:col-span-1">
            <p class="text-xs text-gray-500">Nilai Disetujui</p>
            <p class="text-xl font-semibold mt-1 text-purple-700" x-text="rupiah(kpi.nilai)"></p>
            <p class="text-xs text-gray-400" x-text="angka(kpi.q[0]) + ' item disetujui'"></p>
        </div>
    </div>

    <div class="text-center text-sm text-gray-500 bg-white border rounded-xl py-3" x-show="kpi.total === 0">
        Tidak ada permintaan untuk filter ini.
    </div>

    {{-- Grafik --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        <div class="bg-white border rounded-xl p-4 shadow-sm lg:col-span-2">
            <div class="flex items-baseline justify-between mb-2">
                <h3 class="font-medium"><span x-text="ukuran === 'item' ? 'Item Diminta' : 'Permintaan'"></span> per <span x-text="gran === 'day' ? 'Hari' : 'Bulan'"></span></h3>
                <span class="text-xs text-gray-400" x-text="gran === 'month' ? 'klik batang untuk melihat per hari' : 'per hari'"></span>
            </div>
            <div class="relative" data-chart-wrap style="height:230px"><canvas id="chBulan"></canvas></div>
        </div>

        <div class="bg-white border rounded-xl p-4 shadow-sm">
            <div class="flex items-baseline justify-between mb-2">
                <h3 class="font-medium">Komposisi Status</h3>
                <span class="text-xs text-gray-400">klik untuk memfilter</span>
            </div>
            <div class="relative" data-chart-wrap style="height:230px"><canvas id="chStatus"></canvas></div>
        </div>

        <div class="bg-white border rounded-xl p-4 shadow-sm lg:col-span-2">
            <div class="flex items-baseline justify-between mb-2">
                <h3 class="font-medium">Nilai Permintaan Disetujui</h3>
                <span class="text-xs text-gray-400">Rupiah, per <span x-text="gran === 'day' ? 'hari' : 'bulan'"></span></span>
            </div>
            <div class="relative" data-chart-wrap style="height:230px"><canvas id="chNilai"></canvas></div>
        </div>

        <div class="bg-white border rounded-xl p-4 shadow-sm">
            <div class="flex items-baseline justify-between mb-2">
                <h3 class="font-medium">Kondisi Stok Saat Ini</h3>
                <span class="text-xs text-gray-400">mengikuti BU / Area</span>
            </div>
            <div class="relative" data-chart-wrap style="height:165px"><canvas id="chStok"></canvas></div>
            <div class="grid grid-cols-3 gap-2 mt-3 text-center">
                <div class="rounded-lg bg-green-50 border border-green-100 py-2">
                    <p class="text-lg font-semibold text-green-700" x-text="angka(stokKpi.aman)"></p>
                    <p class="text-xs text-gray-500">barang ada</p>
                </div>
                <div class="rounded-lg bg-amber-50 border border-amber-100 py-2">
                    <p class="text-lg font-semibold text-amber-600" x-text="angka(stokKpi.menipis)"></p>
                    <p class="text-xs text-gray-500">barang menipis</p>
                </div>
                <div class="rounded-lg bg-red-50 border border-red-100 py-2">
                    <p class="text-lg font-semibold text-red-600" x-text="angka(stokKpi.habis)"></p>
                    <p class="text-xs text-gray-500">barang habis</p>
                </div>
            </div>
        </div>

        <div class="bg-white border rounded-xl p-4 shadow-sm">
            <div class="flex items-baseline justify-between mb-2">
                <h3 class="font-medium" x-text="judulGrup"></h3>
                <span class="text-xs text-gray-400">klik untuk memfilter</span>
            </div>
            <div class="relative" data-chart-wrap style="height:256px"><canvas id="chGrup"></canvas></div>
        </div>

        <div class="bg-white border rounded-xl p-4 shadow-sm">
            <div class="flex items-baseline justify-between mb-2">
                <h3 class="font-medium" x-text="ukuran === 'item' ? 'Item Diminta per Unit' : 'Permintaan per Unit'"></h3>
                <span class="text-xs text-gray-400">10 teratas, klik untuk memfilter</span>
            </div>
            <div class="relative" data-chart-wrap style="height:256px"><canvas id="chUnit"></canvas></div>
        </div>

        <div class="bg-white border rounded-xl p-4 shadow-sm">
            <div class="flex items-baseline justify-between mb-2">
                <h3 class="font-medium" x-text="ukuran === 'item' ? '10 Barang Paling Banyak Diminta (item)' : '10 Barang Paling Sering Diminta'"></h3>
                <span class="text-xs text-gray-400">klik untuk memfilter</span>
            </div>
            <div class="relative" data-chart-wrap style="height:256px"><canvas id="chBarang"></canvas></div>
        </div>

        <div class="bg-white border rounded-xl p-4 shadow-sm lg:col-span-3">
            <div class="flex items-baseline justify-between mb-2">
                <h3 class="font-medium">Transaksi Masuk vs Keluar</h3>
                <span class="text-xs text-gray-400" x-text="(ukuran === 'item' ? 'total jumlah item' : 'jumlah transaksi') + ' aktif, mengikuti BU / Area / Periode'"></span>
            </div>
            <div class="relative" data-chart-wrap style="height:205px"><canvas id="chTrx"></canvas></div>
        </div>
    </div>
    <p class="text-xs text-gray-400">Grafik Barang, Unit, dan BU/Area tidak menghitung permintaan yang dibatalkan. "Item" = total jumlah barang yang diminta, "Permintaan" = jumlah baris permintaan. Ganti lewat tombol "Hitung per" di atas.</p>
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

    const STATUS_NAMA = ['Disetujui', 'Ditolak', 'Dalam Proses', 'Dibatalkan'];
    // urutan indeks status: 0 disetujui, 1 ditolak, 2 dalam proses, 3 dibatalkan
    const WARNA = ['#16a34a', '#dc2626', '#f59e0b', '#9ca3af'];

    // Chart & data bantu disimpan di luar state Alpine supaya tidak dibungkus proxy reaktif.
    const charts = {};
    const ctx = { keys: [], gran: 'month', barang: [], grup: [], unit: [] };
    let timer = null;

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
    const potong = function (v) { const l = this.getLabelForValue(v); return l.length > 24 ? l.slice(0, 23) + '…' : l; };
    const sumbuAngka = { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#f3f4f6' } };

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
        saring(skip) {
            const aw = this.awal || this.batasAwal, ak = this.akhir || this.hariIni;
            const bu = this.idBu ? +this.idBu : 0, ar = this.idArea ? +this.idArea : 0;
            const un = this.unit ? D.units.indexOf(this.unit) : -1;
            const br = this.barang ? +this.barang : 0;
            const st = this.status === '' ? -1 : +this.status;
            return D.rows.filter(r =>
                r[0] >= aw && r[0] <= ak &&
                (skip === 'bu'     || !bu || r[1] === bu) &&
                (skip === 'area'   || !ar || r[2] === ar) &&
                (skip === 'unit'   || un < 0 || r[3] === un) &&
                (skip === 'barang' || !br || r[4] === br) &&
                (skip === 'status' || st < 0 || r[5] === st));
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

            // --- Deret waktu ---
            const rg = rentang(aw, ak);
            this.gran = rg.gran;
            ctx.keys = rg.keys; ctx.gran = rg.gran;
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

            // --- Transaksi ---
            const masuk = rg.keys.map(() => 0), keluar = rg.keys.map(() => 0);
            D.trx.forEach(t => {
                if (t[0] < aw || t[0] > ak) return;
                if (bu && areaBu[t[2]] !== bu) return;
                if (ar && t[2] !== ar) return;
                const i = idxKey[potongKey(t[0])];
                if (i === undefined) return;
                (t[1] === 0 ? masuk : keluar)[i] += item ? (Number(t[4]) || 0) : t[3];
            });

            // --- Stok ---
            const stok = [0, 0, 0];
            D.stok.forEach(x => {
                if (bu && areaBu[x[0]] !== bu) return;
                if (ar && x[0] !== ar) return;
                stok[0] += x[1]; stok[1] += x[2]; stok[2] += x[3];
            });

            this.stokKpi = { aman: stok[0], menipis: stok[1], habis: stok[2] };

            // --- Barang / Unit / BU-Area (tanpa dibatalkan) ---
            const hitung = (rows, kunci) => {
                const m = {};
                rows.forEach(r => { if (r[5] !== 3) { const k = kunci(r); m[k] = (m[k] || 0) + w(r); } });
                return m;
            };
            const tBarang = top(hitung(this.saring('barang'), r => r[4]), 10);
            const tUnit   = top(hitung(this.saring('unit'),   r => r[3]), 10);
            const perArea = !!bu;
            const awalJudul = item ? 'Item Diminta per ' : 'Permintaan per ';
            this.judulGrup = awalJudul + (perArea ? 'Area Kerja' : 'Bisnis Unit');
            const tGrup = top(hitung(this.saring(perArea ? 'area' : 'bu'), r => perArea ? r[2] : r[1]), 10);

            ctx.barang = tBarang.map(x => +x[0]);
            ctx.unit   = tUnit.map(x => D.units[+x[0]]);
            ctx.grup   = tGrup.map(x => +x[0]);

            // --- Gambar ---
            const set = (id, labels, datasets) => {
                const c = charts[id];
                if (!c) return;
                c.data.labels = labels;
                c.data.datasets = datasets;
                c.update();
            };

            set('chBulan', rg.labels, [0, 2, 1, 3].map(i => ({
                label: STATUS_NAMA[i], data: perStatus[i], backgroundColor: WARNA[i], borderRadius: 3,
            })));

            set('chStatus', ['Disetujui', 'Dalam Proses', 'Ditolak', 'Dibatalkan'], [{
                data: item ? [q[0], q[2], q[1], q[3]] : [s[0], s[2], s[1], s[3]],
                backgroundColor: [0, 2, 1, 3].map(i => (this.status === '' || +this.status === i) ? WARNA[i] : WARNA[i] + '55'),
                borderWidth: 2, borderColor: '#fff',
            }]);

            set('chNilai', rg.labels, [{
                label: 'Nilai disetujui', data: nilaiSeri, borderColor: '#7c3aed',
                backgroundColor: 'rgba(124,58,237,.12)', fill: true, tension: .3, pointRadius: rg.keys.length > 40 ? 0 : 3,
            }]);

            set('chStok', ['Ada (' + angka(stok[0]) + ')', 'Menipis (' + angka(stok[1]) + ')', 'Habis (' + angka(stok[2]) + ')'], [{
                data: stok, backgroundColor: ['#16a34a', '#f59e0b', '#dc2626'], borderWidth: 2, borderColor: '#fff',
            }]);

            const selBarang = this.barang ? +this.barang : null;
            set('chBarang', tBarang.map(x => barangNama[x[0]] || ('#' + x[0])), [{
                label: lbl, data: tBarang.map(x => x[1]),
                backgroundColor: sorot('#2563eb', ctx.barang, selBarang), borderRadius: 3,
            }]);

            const selUnit = this.unit || null;
            set('chUnit', tUnit.map(x => D.units[+x[0]]), [{
                label: lbl, data: tUnit.map(x => x[1]),
                backgroundColor: sorot('#0d9488', ctx.unit, selUnit), borderRadius: 3,
            }]);

            const selGrup = perArea ? (ar || null) : (bu || null);
            set('chGrup', tGrup.map(x => perArea ? (areaNama[x[0]] || ('#' + x[0])) : (buNama[x[0]] || ('#' + x[0]))), [{
                label: lbl, data: tGrup.map(x => x[1]),
                backgroundColor: sorot('#7c3aed', ctx.grup, selGrup), borderRadius: 3,
            }]);

            set('chTrx', rg.labels, [
                { label: 'Masuk',  data: masuk,  backgroundColor: '#2563eb', borderRadius: 3 },
                { label: 'Keluar', data: keluar, backgroundColor: '#ea580c', borderRadius: 3 },
            ]);
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
            Chart.defaults.color = '#6b7280';

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
                    onClick: (e, els) => {
                        if (!els.length || ctx.gran !== 'month') return;
                        const [y, m] = ctx.keys[els[0].index].split('-').map(Number);
                        self.preset = '';
                        self.awal = `${y}-${pad(m)}-01`;
                        self.akhir = ymd(new Date(y, m, 0));
                    },
                    onHover: (e, els) => { const t = e.native && e.native.target; if (t) t.style.cursor = (els.length && ctx.gran === 'month') ? 'pointer' : 'default'; },
                },
            });

            mk('chStatus', {
                type: 'doughnut',
                data: { labels: [], datasets: [] },
                options: {
                    ...dasar, cutout: '62%',
                    plugins: { legend: legendBawah, tooltip: tipDasar },
                    onClick: (e, els) => {
                        if (!els.length) return;
                        const peta = [0, 2, 1, 3]; // urutan tampilan -> kode status
                        self.toggleStatus(peta[els[0].index]);
                    },
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
                },
            });

            mk('chStok', {
                type: 'doughnut',
                data: { labels: [], datasets: [] },
                options: { ...dasar, cutout: '62%', plugins: { legend: legendBawah, tooltip: tipDasar } },
            });

            mk('chBarang', horizontal('#2563eb', (e, els) => {
                if (els.length) self.togglePilih('barang', ctx.barang[els[0].index]);
            }));
            mk('chUnit', horizontal('#0d9488', (e, els) => {
                if (els.length) { const n = ctx.unit[els[0].index]; self.unit = self.unit === n ? '' : n; }
            }));
            mk('chGrup', horizontal('#7c3aed', (e, els) => {
                if (!els.length) return;
                const id = ctx.grup[els[0].index];
                if (self.idBu) self.togglePilih('idArea', id);
                else if (self.lockedBu === null) self.idBu = String(id);
            }));

            mk('chTrx', {
                type: 'bar',
                data: { labels: [], datasets: [] },
                options: {
                    ...dasar,
                    interaction: { mode: 'index', axis: 'x', intersect: false },
                    scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 16, autoSkip: true } }, y: sumbuAngka },
                    plugins: { legend: legendBawah, tooltip: tipDasar },
                },
            });
        },
    };
}
</script>
@endsection