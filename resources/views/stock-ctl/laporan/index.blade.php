@extends('layouts.app_stock_sidebar')

@section('content')
@php
    $laporanCfg = [
        'areas'      => $areas,
        'lockedBu'   => $lockedBu,
        'previewUrl' => route('stock-ctl.laporan.preview'),
        'excelUrl'   => route('stock-ctl.laporan.excel'),
        'csrf'       => csrf_token(),
    ];
@endphp
<script>
    window.__laporanCfg = {!! json_encode($laporanCfg, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) !!};
</script>

<div class="space-y-6 text-sm text-gray-800 font-sans min-w-0 max-w-full" x-data="laporanPage(window.__laporanCfg)" x-cloak>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">Laporan</h2>
            <p class="text-gray-500 mt-0.5">Pilih jenis laporan, atur filter, lihat pratinjau, lalu unduh sebagai file Excel (.xlsx).</p>
        </div>
        <a href="{{ route('stock-ctl.laporan.history') }}" class="text-blue-600 hover:underline text-sm">
            <i class="fas fa-clock-rotate-left mr-1"></i> Riwayat cetak
        </a>
    </div>

    {{-- 1. Jenis laporan --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <template x-for="j in jenisList" :key="j.key">
            <button type="button" @click="pilihJenis(j.key)"
                    class="text-left rounded-xl border p-4 transition focus:outline-none focus:ring-2 focus:ring-blue-500"
                    :class="jenis === j.key ? 'border-blue-600 bg-blue-50 ring-1 ring-blue-600' : 'bg-white hover:border-gray-400'">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center"
                          :class="jenis === j.key ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-500'">
                        <i class="fas" :class="j.icon"></i>
                    </span>
                    <span class="font-semibold" x-text="j.label"></span>
                </div>
                <p class="text-xs text-gray-500 mt-2" x-text="j.desc"></p>
            </button>
        </template>
    </div>

    {{-- 2. Filter --}}
    <div class="bg-white border rounded-xl" x-show="jenis">
        <div class="px-5 py-3 border-b flex items-center justify-between">
            <h3 class="font-semibold text-gray-800"><i class="fas fa-filter mr-1 text-gray-400"></i> Filter</h3>
            <button type="button" @click="resetFilter()" class="text-xs text-gray-500 hover:text-red-600">
                <i class="fas fa-rotate-left mr-1"></i> Reset filter
            </button>
        </div>

        <div class="p-5 space-y-5">
            {{-- Lingkup: BU -> Area -> Barang --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">1. Bisnis Unit</label>
                    <select x-model="idBu" @change="onBuChange()" :disabled="lockedBu !== null"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 disabled:bg-gray-100">
                        <option value="">Semua Bisnis Unit</option>
                        @foreach($bisnisUnits as $bu)
                            <option value="{{ $bu->id_bisnis_unit }}">{{ $bu->nama_bisnis_unit }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1" x-show="lockedBu !== null">Terkunci sesuai Bisnis Unit akun Anda.</p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        2. Area Kerja <span x-show="jenis === 'kartu_stok'" class="text-red-500">*</span>
                    </label>
                    <select x-model="idArea" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="" x-text="idBu ? 'Semua Area di Bisnis Unit ini' : 'Semua Area'"></option>
                        <template x-for="a in areaOptions" :key="a.id">
                            <option :value="a.id" x-text="idBu ? a.nama : (a.nama + ' (' + a.bu_nama + ')')"></option>
                        </template>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">
                        <span x-text="areaOptions.length"></span> area tersedia<span x-show="idBu"> pada Bisnis Unit terpilih</span>.
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">
                        Barang <span x-show="jenis === 'kartu_stok'" class="text-red-500">*</span>
                    </label>
                    <select x-model="idBarang" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Barang</option>
                        @foreach($barang as $b)
                            <option value="{{ $b['id'] }}">{{ $b['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Khusus Mutasi: jenis transaksi + status --}}
            <div x-show="jenis === 'mutasi'" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Jenis Transaksi</label>
                    <div class="inline-flex flex-wrap gap-2">
                        <template x-for="o in jenisMutasiOptions" :key="o.value">
                            <button type="button" @click="jenisMutasi = o.value"
                                    class="px-3 py-1.5 rounded-lg border text-sm transition"
                                    :class="jenisMutasi === o.value ? o.active : 'bg-white text-gray-600 hover:bg-gray-50'">
                                <i class="fas mr-1" :class="o.icon"></i><span x-text="o.label"></span>
                            </button>
                        </template>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Status Transaksi</label>
                    <select x-model="statusTransaksi" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="aktif">Aktif saja (disarankan)</option>
                        <option value="">Semua status</option>
                        <option value="dibatalkan">Dibatalkan</option>
                        <option value="koreksi">Koreksi</option>
                    </select>
                </div>
            </div>

            {{-- Khusus Permintaan --}}
            <div x-show="jenis === 'permintaan'" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Status Permintaan</label>
                    <select x-model="statusPermintaan" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua status</option>
                        <option value="pending_l1">Menunggu L1</option>
                        <option value="pending_admin">Menunggu Admin</option>
                        <option value="disetujui">Disetujui</option>
                        <option value="ditolak">Ditolak</option>
                    </select>
                </div>
            </div>

            {{-- Khusus Stok --}}
            <div x-show="jenis === 'stok'" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Status Stok</label>
                    <select x-model="statusStok" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua status</option>
                        <option value="masih">Masih</option>
                        <option value="menipis">Menipis (di bawah / sama dengan minimum)</option>
                        <option value="habis">Habis (stok 0)</option>
                    </select>
                </div>
            </div>

            {{-- Periode --}}
            <div x-show="jenis && jenis !== 'stok'" class="space-y-2">
                <label class="block text-xs font-medium text-gray-600">Periode</label>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <input type="date" x-model="tanggalAwal" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-400 mt-1">Dari tanggal</p>
                    </div>
                    <div>
                        <input type="date" x-model="tanggalAkhir" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-400 mt-1">Sampai tanggal</p>
                    </div>
                    <div class="md:col-span-2 flex flex-wrap items-start gap-2">
                        <template x-for="p in presets" :key="p.key">
                            <button type="button" @click="setPreset(p.key)"
                                    class="px-2.5 py-1.5 rounded-lg border bg-white text-xs text-gray-600 hover:bg-gray-50" x-text="p.label"></button>
                        </template>
                    </div>
                </div>
            </div>

            <div x-show="errorMsg" x-cloak class="text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2" x-text="errorMsg"></div>
        </div>

        <div class="px-5 py-3 border-t bg-gray-50 rounded-b-xl flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <button type="button" @click="lihatLaporan()" :disabled="loading"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                <i class="fas mr-1" :class="loading ? 'fa-spinner fa-spin' : 'fa-eye'"></i>
                <span x-text="loading ? 'Memuat...' : 'Lihat Laporan'"></span>
            </button>
            <button type="button" @click="downloadExcel()" :disabled="downloading"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50">
                <i class="fas mr-1" :class="downloading ? 'fa-spinner fa-spin' : 'fa-file-excel'"></i>
                <span x-text="downloading ? 'Menyiapkan file...' : 'Download Excel (.xlsx)'"></span>
            </button>
        </div>
    </div>

    {{-- 3. Hasil / Preview --}}
    <div x-show="preview" x-cloak class="bg-white border rounded-xl min-w-0 max-w-full isolate">
        <div class="px-5 py-4 border-b space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h3 class="text-base font-semibold text-gray-900" x-text="preview ? preview.title : ''"></h3>
                    <p class="text-xs text-gray-500">
                        Menampilkan <span x-text="preview ? preview.rows.length : 0"></span>
                        dari <span x-text="preview ? preview.total_rows : 0"></span> baris
                    </p>
                </div>
                <button type="button" @click="downloadExcel()" :disabled="downloading"
                        class="self-start sm:self-auto px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs hover:bg-green-700 disabled:opacity-50">
                    <i class="fas fa-download mr-1"></i> Download Excel (semua baris)
                </button>
            </div>

            {{-- Chip filter aktif --}}
            <div class="flex flex-wrap gap-2">
                <template x-for="f in (preview ? preview.filters : [])" :key="f[0]">
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-xs text-gray-700">
                        <span class="text-gray-500" x-text="f[0] + ':'"></span>
                        <span class="font-medium" x-text="f[1]"></span>
                    </span>
                </template>
            </div>

            {{-- Ringkasan --}}
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3">
                <template x-for="s in (preview ? preview.summary : [])" :key="s.label">
                    <div class="rounded-lg border bg-gray-50 px-4 py-3">
                        <div class="text-xs text-gray-500" x-text="s.label"></div>
                        <div class="text-lg font-semibold text-gray-900 mt-0.5" x-text="s.value"></div>
                    </div>
                </template>
            </div>
        </div>

        <div class="px-5 py-3 border-b flex flex-col sm:flex-row sm:items-center gap-2">
            <div class="relative sm:w-72">
                <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                <input type="text" x-model="cari" placeholder="Cari di hasil preview..."
                       class="w-full border rounded-lg pl-8 pr-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <p x-show="preview && preview.truncated" class="text-xs text-amber-600">
                <i class="fas fa-circle-info mr-1"></i>
                Preview dibatasi 200 baris pertama. File Excel berisi seluruh <span x-text="preview ? preview.total_rows : 0"></span> baris.
            </p>
        </div>

        <div class="overflow-auto max-h-[70vh] overscroll-x-contain relative" style="width:0;min-width:100%">
            <table class="w-full text-xs">
                <thead class="bg-gray-100 text-gray-700 sticky top-0 z-[1]">
                    <tr>
                        <th class="px-3 py-2 text-center w-10">No</th>
                        <template x-for="(c, ci) in (preview ? preview.columns : [])" :key="ci">
                            <th class="px-3 py-2 whitespace-nowrap font-semibold"
                                :class="alignClass(c.type)" x-text="c.label"></th>
                        </template>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <template x-for="(row, ri) in filteredRows" :key="ri">
                        <tr class="odd:bg-white even:bg-gray-50/60 hover:bg-blue-50/50">
                            <td class="px-3 py-2 text-center text-gray-400" x-text="ri + 1"></td>
                            <template x-for="(cell, ci) in row" :key="ci">
                                <td class="px-3 py-2 whitespace-nowrap" :class="alignClass(preview.columns[ci].type)">
                                    <template x-if="preview.columns[ci].type === 'badge' && cell">
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-medium"
                                              :style="badgeStyle(cell)" x-text="cell"></span>
                                    </template>
                                    <template x-if="!(preview.columns[ci].type === 'badge' && cell)">
                                        <span x-text="cell"></span>
                                    </template>
                                </td>
                            </template>
                        </tr>
                    </template>
                    <tr x-show="preview && filteredRows.length === 0">
                        <td class="px-3 py-10 text-center text-gray-500" :colspan="preview ? preview.columns.length + 1 : 1">
                            Tidak ada data untuk kombinasi filter ini.
                        </td>
                    </tr>
                </tbody>
                <tfoot x-show="preview && preview.totals" class="bg-blue-50 font-semibold text-blue-900 sticky bottom-0">
                    <tr class="border-t-2 border-blue-300">
                        <td class="px-3 py-2 text-center">Σ</td>
                        <template x-for="(t, ci) in (preview && preview.totals ? preview.totals : [])" :key="ci">
                            <td class="px-3 py-2 whitespace-nowrap" :class="alignClass(preview.columns[ci].type)"
                                x-text="ci === 0 && !t ? 'TOTAL' : t"></td>
                        </template>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Riwayat --}}
    <div class="bg-white border rounded-xl p-5">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-semibold text-gray-800">Riwayat Cetak Terbaru</h3>
            <a href="{{ route('stock-ctl.laporan.history') }}" class="text-blue-600 hover:underline text-sm">Lihat semua</a>
        </div>

        @if($recentHistory->isEmpty())
            <p class="text-gray-500 text-center py-4">Belum ada riwayat cetak.</p>
        @else
            <div class="overflow-x-auto" style="width:0;min-width:100%">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-2 text-left">Waktu</th>
                            <th class="px-4 py-2 text-left">Jenis</th>
                            <th class="px-4 py-2 text-left">Area</th>
                            <th class="px-4 py-2 text-left">Barang</th>
                            <th class="px-4 py-2 text-left">Periode</th>
                            <th class="px-4 py-2 text-left">User</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($recentHistory as $h)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $h->dicetak_pada->timezone('Asia/Jakarta')->format('d M Y H:i') }}</td>
                            <td class="px-4 py-2">{{ $h->jenis_label }}</td>
                            <td class="px-4 py-2">
                                @if($h->area)
                                    {{ $h->area->nama_area }} ({{ $h->area->bisnisUnit->nama_bisnis_unit ?? '-' }})
                                @else
                                    Semua Area
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ $h->barang->nama_barang ?? 'Semua Barang' }}</td>
                            <td class="px-4 py-2">
                                @if($h->tanggal_awal && $h->tanggal_akhir)
                                    {{ \Carbon\Carbon::parse($h->tanggal_awal)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($h->tanggal_akhir)->format('d/m/Y') }}
                                @elseif($h->tanggal_awal)
                                    Mulai {{ \Carbon\Carbon::parse($h->tanggal_awal)->format('d/m/Y') }}
                                @elseif($h->tanggal_akhir)
                                    Sampai {{ \Carbon\Carbon::parse($h->tanggal_akhir)->format('d/m/Y') }}
                                @else
                                    Semua
                                @endif
                            </td>
                            <td class="px-4 py-2">{{ $h->user->name ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<script>
function laporanPage(cfg) {
    const pad = n => String(n).padStart(2, '0');
    const ymd = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

    return {
        areas: cfg.areas,
        lockedBu: cfg.lockedBu,

        jenis: '',
        idBu: cfg.lockedBu !== null ? String(cfg.lockedBu) : '',
        idArea: '',
        idBarang: '',
        jenisMutasi: '',
        statusTransaksi: 'aktif',
        statusPermintaan: '',
        statusStok: '',
        tanggalAwal: '',
        tanggalAkhir: '',

        loading: false,
        downloading: false,
        preview: null,
        errorMsg: '',
        cari: '',

        jenisList: [
            { key: 'stok',       label: 'Laporan Stok',   icon: 'fa-boxes-stacked',       desc: 'Posisi stok saat ini per area, lengkap dengan nilai dan status.' },
            { key: 'mutasi',     label: 'Mutasi Barang',  icon: 'fa-right-left',          desc: 'Barang masuk, keluar, transfer & opname pada periode tertentu.' },
            { key: 'permintaan', label: 'Permintaan',     icon: 'fa-clipboard-list',      desc: 'Daftar permintaan beserta jejak persetujuan L1 dan Admin.' },
            { key: 'kartu_stok', label: 'Kartu Stok',     icon: 'fa-book',                desc: 'Saldo berjalan 1 barang di 1 area (wajib pilih keduanya).' },
        ],
        jenisMutasiOptions: [
            { value: '',         label: 'Semua',    icon: 'fa-layer-group',     active: 'bg-gray-800 text-white border-gray-800' },
            { value: 'masuk',    label: 'Masuk',    icon: 'fa-arrow-down',      active: 'bg-green-600 text-white border-green-600' },
            { value: 'keluar',   label: 'Keluar',   icon: 'fa-arrow-up',        active: 'bg-red-600 text-white border-red-600' },
            { value: 'transfer', label: 'Transfer', icon: 'fa-right-left',      active: 'bg-blue-600 text-white border-blue-600' },
            { value: 'opname',   label: 'Opname',   icon: 'fa-clipboard-check', active: 'bg-purple-600 text-white border-purple-600' },
        ],
        presets: [
            { key: 'today',     label: 'Hari ini' },
            { key: '7d',        label: '7 hari terakhir' },
            { key: 'month',     label: 'Bulan ini' },
            { key: 'lastmonth', label: 'Bulan lalu' },
            { key: 'year',      label: 'Tahun ini' },
            { key: 'clear',     label: 'Hapus periode' },
        ],

        // Area bergantung pada Bisnis Unit yang dipilih
        get areaOptions() {
            return this.idBu ? this.areas.filter(a => String(a.bu) === String(this.idBu)) : this.areas;
        },

        get filteredRows() {
            if (!this.preview) return [];
            const q = this.cari.trim().toLowerCase();
            if (!q) return this.preview.rows;
            return this.preview.rows.filter(r => r.join(' ').toLowerCase().includes(q));
        },

        pilihJenis(key) {
            this.jenis = key;
            this.preview = null;
            this.errorMsg = '';
            this.cari = '';
        },

        onBuChange() {
            // Area yang sudah dipilih tapi bukan milik BU baru -> dikosongkan
            if (this.idArea && !this.areaOptions.some(a => String(a.id) === String(this.idArea))) {
                this.idArea = '';
            }
        },

        resetFilter() {
            this.idBu = this.lockedBu !== null ? String(this.lockedBu) : '';
            this.idArea = '';
            this.idBarang = '';
            this.jenisMutasi = '';
            this.statusTransaksi = 'aktif';
            this.statusPermintaan = '';
            this.statusStok = '';
            this.tanggalAwal = '';
            this.tanggalAkhir = '';
            this.errorMsg = '';
            this.preview = null;
        },

        setPreset(key) {
            const now = new Date();
            if (key === 'today') {
                this.tanggalAwal = this.tanggalAkhir = ymd(now);
            } else if (key === '7d') {
                const s = new Date(now); s.setDate(s.getDate() - 6);
                this.tanggalAwal = ymd(s); this.tanggalAkhir = ymd(now);
            } else if (key === 'month') {
                this.tanggalAwal = ymd(new Date(now.getFullYear(), now.getMonth(), 1));
                this.tanggalAkhir = ymd(new Date(now.getFullYear(), now.getMonth() + 1, 0));
            } else if (key === 'lastmonth') {
                this.tanggalAwal = ymd(new Date(now.getFullYear(), now.getMonth() - 1, 1));
                this.tanggalAkhir = ymd(new Date(now.getFullYear(), now.getMonth(), 0));
            } else if (key === 'year') {
                this.tanggalAwal = `${now.getFullYear()}-01-01`;
                this.tanggalAkhir = `${now.getFullYear()}-12-31`;
            } else {
                this.tanggalAwal = this.tanggalAkhir = '';
            }
        },

        validasi() {
            this.errorMsg = '';
            if (!this.jenis) { this.errorMsg = 'Pilih jenis laporan terlebih dahulu.'; return false; }
            if (this.jenis === 'kartu_stok' && (!this.idArea || !this.idBarang)) {
                this.errorMsg = 'Kartu Stok wajib memilih 1 Area Kerja dan 1 Barang.';
                return false;
            }
            if (this.tanggalAwal && this.tanggalAkhir && this.tanggalAkhir < this.tanggalAwal) {
                this.errorMsg = 'Periode akhir tidak boleh lebih awal dari periode awal.';
                return false;
            }
            return true;
        },

        formData() {
            const fd = new FormData();
            fd.append('_token', cfg.csrf);
            fd.append('jenis', this.jenis);
            fd.append('id_bisnis_unit', this.idBu || '');
            fd.append('id_area', this.idArea || '');
            fd.append('id_barang', this.idBarang || '');
            if (this.jenis === 'mutasi') {
                fd.append('jenis_mutasi', this.jenisMutasi || '');
                fd.append('status_transaksi', this.statusTransaksi || '');
            }
            if (this.jenis === 'permintaan') fd.append('status_permintaan', this.statusPermintaan || '');
            if (this.jenis === 'stok') fd.append('status_stok', this.statusStok || '');
            if (this.jenis !== 'stok') {
                fd.append('tanggal_awal', this.tanggalAwal || '');
                fd.append('tanggal_akhir', this.tanggalAkhir || '');
            }
            return fd;
        },

        async errorDari(res) {
            const err = await res.json().catch(() => null);
            if (err?.errors) return Object.values(err.errors)[0][0];
            return err?.message || 'Terjadi kesalahan pada server.';
        },

        async lihatLaporan() {
            if (!this.validasi()) return;
            this.loading = true;
            this.preview = null;
            this.cari = '';
            try {
                const res = await fetch(cfg.previewUrl, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                    body: this.formData(),
                });
                if (!res.ok) { this.errorMsg = await this.errorDari(res); return; }
                this.preview = await res.json();
                this.$nextTick(() => this.$root.querySelector('table')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' }));
            } catch (e) {
                this.errorMsg = 'Terjadi kesalahan jaringan saat memuat preview.';
            } finally {
                this.loading = false;
            }
        },

        async downloadExcel() {
            if (!this.validasi()) return;
            this.downloading = true;
            try {
                const res = await fetch(cfg.excelUrl, {
                    method: 'POST',
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json, application/octet-stream' },
                    body: this.formData(),
                });
                if (!res.ok) { this.errorMsg = await this.errorDari(res); return; }

                const blob = await res.blob();
                const cd = res.headers.get('Content-Disposition') || '';
                const m = cd.match(/filename\*?=(?:UTF-8'')?"?([^";]+)"?/i);
                const filename = m ? decodeURIComponent(m[1]) : 'laporan.xlsx';

                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url; a.download = filename;
                document.body.appendChild(a); a.click(); a.remove();
                URL.revokeObjectURL(url);
            } catch (e) {
                this.errorMsg = 'Gagal mengunduh file Excel. Silakan coba lagi.';
            } finally {
                this.downloading = false;
            }
        },

        alignClass(type) {
            return ['qty', 'money'].includes(type) ? 'text-right'
                 : ['datetime', 'badge'].includes(type) ? 'text-center' : 'text-left';
        },

        // Warna badge pakai inline style (hex sama persis dengan Excel),
        // jadi tidak bergantung pada Tailwind. [background, teks]
        badgeStyle(text) {
            const hijau  = ['#C6EFCE', '#006100'];
            const merah  = ['#FFC7CE', '#9C0006'];
            const kuning = ['#FFEB9C', '#9C5700'];
            const map = {
                'Masih': hijau, 'Disetujui': hijau, 'Aktif': hijau, 'Masuk': hijau,
                'Habis': merah, 'Ditolak': merah, 'Dibatalkan': merah, 'Keluar': merah,
                'Menipis': kuning, 'Menunggu L1': kuning, 'Menunggu Admin': kuning, 'Koreksi': kuning,
                'Transfer': ['#DDEBF7', '#1F4E78'],
                'Opname':   ['#E4DFEC', '#5B3F86'],
            };
            const key = String(text).replace(/[\u00A0\u200B-\u200D\uFEFF]/g, ' ').replace(/\s+/g, ' ').trim();
            const c = map[key] || ['#F3F4F6', '#374151'];
            return `background-color:${c[0]};color:${c[1]}`;
        },
    };
}
</script>
@endsection