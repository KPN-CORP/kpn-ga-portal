@extends('layouts.app_messenger_sidebar')

@section('content')
@php
    // ---------- helper tampilan ----------
    $lbl = 'block mb-1.5 text-xs font-semibold text-gray-500 uppercase tracking-wide';
    $ctl = 'w-full h-10 px-3 text-sm bg-white border border-gray-300 rounded-lg
            focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500';

    $kurirList   = $kurirList ?? [];
    $kurirSelIds = collect($kurirFilter ?? [])->map(fn ($v) => (string) $v)->all();
    $kurirNames  = collect($kurirList)->filter(fn ($k) => in_array((string) $k->id_pelanggan, $kurirSelIds, true))
                        ->pluck('nama_pelanggan')->values()->all();
    $compare     = $compare ?? false;
    $timelineSeries = $timelineSeries ?? [];
    $compareRows    = $compareRows ?? [];
    $palette = ['#2563eb', '#f97316', '#10b981', '#8b5cf6', '#ec4899', '#14b8a6'];

    $pct = fn ($n) => $totalKeseluruhan > 0 ? round(($n / $totalKeseluruhan) * 100) : 0;
    $batalTotal = ($summary['Ditolak'] ?? 0) + ($summary['Batal'] ?? 0);

    $cards = [
        ['label' => 'Total Transaksi',     'value' => $totalKeseluruhan,                   'icon' => 'fa-layer-group',  'bg' => 'bg-slate-100',  'fg' => 'text-slate-700',  'pct' => null],
        ['label' => 'Belum Terkirim',      'value' => $summary['Belum Terkirim'],          'icon' => 'fa-hourglass-half','bg' => 'bg-blue-100',   'fg' => 'text-blue-700',   'pct' => $pct($summary['Belum Terkirim'])],
        ['label' => 'Proses Pengiriman',   'value' => $summary['Proses Pengiriman'],       'icon' => 'fa-truck',        'bg' => 'bg-orange-100', 'fg' => 'text-orange-700', 'pct' => $pct($summary['Proses Pengiriman'])],
        ['label' => 'Dok. Belum Tersedia', 'value' => $summary['Dokumen Belum Tersedia'],  'icon' => 'fa-file-alt',     'bg' => 'bg-purple-100', 'fg' => 'text-purple-700', 'pct' => $pct($summary['Dokumen Belum Tersedia'])],
        ['label' => 'Terkirim',            'value' => $summary['Terkirim'],                'icon' => 'fa-check-circle', 'bg' => 'bg-green-100',  'fg' => 'text-green-700',  'pct' => $pct($summary['Terkirim'])],
        ['label' => 'Batal',     'value' => $batalTotal,                         'icon' => 'fa-ban',          'bg' => 'bg-red-100',    'fg' => 'text-red-700',    'pct' => $pct($batalTotal)],
    ];
@endphp

<div class="space-y-5 text-sm text-gray-800 font-sans min-w-0">

    {{-- ================= HEADER ================= --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Dashboard Messenger</h2>
            <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-xs">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 font-semibold">
                    <i class="far fa-calendar-alt"></i> {{ $periodLabel }}
                </span>
                @if($status)
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 font-semibold">
                        <i class="fas fa-tag"></i> {{ $status }}
                    </span>
                @endif
                @if(count($kurirNames))
                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-gray-100 text-gray-700 font-semibold">
                        <i class="fas fa-motorcycle"></i>
                        {{ implode(', ', array_slice($kurirNames, 0, 3)) }}@if(count($kurirNames) > 3) +{{ count($kurirNames) - 3 }} lainnya @endif
                    </span>
                    @if($compare)
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 font-semibold">
                            <i class="fas fa-balance-scale"></i> Mode Perbandingan
                        </span>
                    @endif
                @endif
            </div>
        </div>

        <a href="{{ route('messenger.index') }}"
           class="inline-flex items-center justify-center gap-2 h-10 px-4 text-sm font-semibold text-gray-700
                  bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
            <i class="fas fa-list text-gray-400"></i> Daftar Pengiriman
        </a>
    </div>

    {{-- ================= FILTER ================= --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
        <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100">
            <i class="fas fa-sliders-h text-gray-400"></i>
            <h3 class="text-sm font-semibold text-gray-700">Filter Dashboard</h3>
        </div>

        <form method="GET" action="{{ route('messenger.dashboard') }}">
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

                {{-- TAMPILAN --}}
                <div>
                    <label class="{{ $lbl }}">Tampilan</label>
                    <select name="view" id="viewSelect" class="{{ $ctl }}">
                        <option value="year"  {{ $view === 'year'  ? 'selected' : '' }}>Per Tahun (grafik bulanan)</option>
                        <option value="month" {{ $view === 'month' ? 'selected' : '' }}>Per Bulan (grafik harian)</option>
                    </select>
                </div>

                {{-- TAHUN --}}
                <div id="yearField" class="{{ $view === 'year' ? '' : 'hidden' }}">
                    <label class="{{ $lbl }}">Tahun</label>
                    <select name="year" class="{{ $ctl }}">
                        @foreach($years as $y)
                            <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- BULAN --}}
                <div id="monthField" class="{{ $view === 'month' ? '' : 'hidden' }}">
                    <label class="{{ $lbl }}">Bulan</label>
                    <select name="month" class="{{ $ctl }}">
                        @foreach($months as $key => $label)
                            <option value="{{ $key }}" {{ $month == $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- STATUS --}}
                <div>
                    <label class="{{ $lbl }}">Status</label>
                    <select name="status" class="{{ $ctl }}">
                        <option value="">Semua Status</option>
                        @foreach($statusList as $s)
                            <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- NAMA KURIR (bisa pilih lebih dari satu untuk dibandingkan) --}}
                <div class="relative" id="kurirBox">
                    <label class="{{ $lbl }}">Nama Messenger</label>
                    <button type="button" id="kurirBtn" class="{{ $ctl }} flex items-center justify-between gap-2 text-left">
                        <span id="kurirBtnText" class="truncate">Semua Messenger</span>
                        <i class="fas fa-chevron-down text-xs text-gray-400"></i>
                    </button>

                    <div id="kurirPanel" class="hidden absolute z-30 mt-1 w-full min-w-[16rem] bg-white border border-gray-200 rounded-lg shadow-lg">
                        <div class="p-2 border-b border-gray-100">
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                <input id="kurirSearch" type="text" autocomplete="off" placeholder="Cari messenger..."
                                       class="w-full h-9 pl-8 pr-3 text-sm border border-gray-300 rounded-lg
                                              focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        <div class="flex items-center justify-between px-3 py-1.5 text-xs border-b border-gray-100">
                            <button type="button" id="kurirAll"  class="text-blue-600 font-semibold hover:underline">Pilih semua</button>
                            <button type="button" id="kurirNone" class="text-gray-500 font-semibold hover:underline">Hapus pilihan</button>
                        </div>
                        <div class="max-h-56 overflow-y-auto py-1">
                            @foreach($kurirList as $k)
                                <label class="kurir-item flex items-center gap-2 px-3 py-2 text-sm cursor-pointer hover:bg-gray-50"
                                       data-name="{{ strtolower($k->nama_pelanggan) }}">
                                    <input type="checkbox" name="kurir[]" value="{{ $k->id_pelanggan }}"
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                           data-label="{{ $k->nama_pelanggan }}"
                                           {{ in_array((string) $k->id_pelanggan, $kurirSelIds, true) ? 'checked' : '' }}>
                                    <span class="truncate">{{ $k->nama_pelanggan }}</span>
                                </label>
                            @endforeach
                            <p id="kurirEmpty" class="hidden px-3 py-3 text-xs text-gray-400">Messenger tidak ditemukan.</p>
                        </div>
                        <div class="px-3 py-2 text-[11px] text-gray-400 border-t border-gray-100">
                            Centang <b>2&ndash;6 messenger</b> untuk membandingkan.
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 px-5 py-3 bg-gray-50 border-t border-gray-100 rounded-b-xl">
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <a href="{{ route('messenger.dashboard') }}"
                       class="flex-1 sm:flex-none inline-flex items-center justify-center h-10 px-5 text-sm font-medium
                              text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-100 transition">
                        Reset
                    </a>
                    <button type="submit"
                            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 h-10 px-6 text-sm
                                   font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-filter text-xs"></i> Tampilkan
                    </button>
                </div>
                <p class="hidden sm:block text-xs text-gray-500 text-right">
                    <i class="far fa-lightbulb mr-1"></i>
                    Pilih <b>Per Bulan</b> + messenger untuk memantau per hari. Centang <b>2&ndash;6 messenger</b> untuk membandingkan.
                </p>
            </div>
        </form>
    </div>

    {{-- ================= KARTU RINGKASAN ================= --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        @foreach($cards as $c)
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500">{{ $c['label'] }}</span>
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg {{ $c['bg'] }} {{ $c['fg'] }}">
                        <i class="fas {{ $c['icon'] }} text-sm"></i>
                    </span>
                </div>
                <div class="mt-2 text-2xl font-bold {{ $c['fg'] }}">{{ number_format($c['value']) }}</div>
                <div class="mt-0.5 text-[11px] text-gray-400">
                    @if(!is_null($c['pct'])) {{ $c['pct'] }}% dari total @else &nbsp; @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- ================= GRAFIK: TREN + STATUS ================= --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- TREN BULANAN / HARIAN --}}
        <div class="lg:col-span-2 bg-white border border-gray-200 rounded-xl shadow-sm p-5 min-w-0">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-gray-800">{{ $timelineTitle }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        @if($compare)
                            Perbandingan messenger &middot; klik nama di bawah untuk sembunyikan/tampilkan &middot; klik batang untuk detail
                        @else
                            Klik batang untuk melihat detail transaksi
                        @endif
                    </p>
                </div>
                <i class="fas fa-chart-bar text-gray-300 text-lg"></i>
            </div>

            <div id="tlLegend" class="mt-3 flex flex-wrap gap-1.5"></div>
            <div id="tlStats" class="mt-3 flex flex-wrap gap-2 text-xs"></div>
            <div id="chartTimeline" class="mt-3 w-full min-w-0"></div>
        </div>

        {{-- KOMPOSISI STATUS --}}
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 min-w-0">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h3 class="font-semibold text-gray-800">Komposisi Status</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Proporsi transaksi pada periode ini</p>
                </div>
                <i class="fas fa-chart-pie text-gray-300 text-lg"></i>
            </div>
            <div id="chartStatus" class="mt-4"></div>
        </div>
    </div>

    {{-- ================= TABEL PERBANDINGAN KURIR ================= --}}
    @if($compare && count($compareRows))
        @php $maxCmp = max(1, collect($compareRows)->max('total')); @endphp
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm">
            <div class="flex items-center justify-between gap-3 px-5 py-3 border-b border-gray-100">
                <div>
                    <h3 class="font-semibold text-gray-800">Perbandingan Messenger</h3>
                    <p class="text-xs text-gray-500">{{ $periodLabel }} &middot; rincian jumlah transaksi per status</p>
                </div>
                <i class="fas fa-balance-scale text-gray-300 text-lg"></i>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Messenger</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide w-56">Total</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide">Belum Terkirim</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide">Proses</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide">Dok. Belum Ada</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide">Terkirim</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide">Batal</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide">% Terkirim</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($compareRows as $i => $row)
                            @php
                                $col = $palette[$i % count($palette)];
                                $st  = $row['statuses'];
                                $done = $row['total'] > 0 ? round(($st['Terkirim'] / $row['total']) * 100) : 0;
                            @endphp
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 font-medium whitespace-nowrap">
                                    <span class="inline-block w-2.5 h-2.5 rounded-full mr-2" style="background:{{ $col }}"></span>{{ $row['name'] }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <span class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                            <span class="block h-full rounded-full" style="width:{{ ($row['total'] / $maxCmp) * 100 }}%;background:{{ $col }}"></span>
                                        </span>
                                        <span class="w-10 text-right font-semibold">{{ number_format($row['total']) }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">{{ $st['Belum Terkirim'] }}</td>
                                <td class="px-4 py-3 text-right">{{ $st['Proses Pengiriman'] }}</td>
                                <td class="px-4 py-3 text-right">{{ $st['Dokumen Belum Tersedia'] }}</td>
                                <td class="px-4 py-3 text-right text-green-700 font-semibold">{{ $st['Terkirim'] }}</td>
                                <td class="px-4 py-3 text-right text-red-600">{{ $st['Ditolak'] + $st['Batal'] }}</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ $done }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- ================= GRAFIK: PER KURIR ================= --}}
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
            <div>
                <h3 class="font-semibold text-gray-800">Total Diambil per Messenger</h3>
                <p class="text-xs text-gray-500 mt-0.5">Diurutkan dari yang terbanyak &middot; klik baris untuk melihat detail</p>
            </div>

            @if(count($kurirLabels))
                <div class="relative w-full sm:w-64">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                    <input id="searchKurir" type="text" placeholder="Cari messenger..." autocomplete="off"
                           class="w-full h-10 pl-9 pr-3 text-sm bg-white border border-gray-300 rounded-lg
                                  focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            @endif
        </div>

        @if(count($kurirLabels))
            <div id="chartKurir" class="mt-4 divide-y divide-gray-50"></div>
            <div id="pagerKurir" class="mt-3"></div>
        @else
            <div class="py-12 text-center text-gray-400">
                <i class="fas fa-inbox text-3xl mb-2"></i>
                <p class="text-sm">Belum ada transaksi yang diambil messenger pada periode ini.</p>
            </div>
        @endif
    </div>

    {{-- ================= DETAIL (DRILLDOWN) ================= --}}
    <div id="drilldownPanel" class="bg-white border border-gray-200 rounded-xl shadow-sm hidden">
        <div class="flex items-center justify-between gap-3 px-5 py-3 border-b border-gray-100">
            <div>
                <h3 id="drilldownTitle" class="font-semibold text-gray-800">-</h3>
                <p id="drilldownCount" class="text-xs text-gray-500"></p>
            </div>
            <button id="drilldownClose" type="button" title="Tutup"
                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400
                           hover:text-gray-700 hover:bg-gray-100 transition">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div id="drilldownLoading" class="hidden py-10 text-center text-gray-400 text-sm">
            <i class="fas fa-spinner fa-spin mr-2"></i> Memuat data...
        </div>

        <div class="overflow-x-auto max-h-[28rem] overflow-y-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 sticky top-0 z-10">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">No. Transaksi</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Pengirim</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Jenis</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Penerima</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Tanggal</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide">Aksi</th>
                    </tr>
                </thead>
                <tbody id="drilldownBody" class="divide-y divide-gray-100"></tbody>
            </table>
            <p id="drilldownEmpty" class="hidden text-center text-gray-400 text-sm py-8">Tidak ada data.</p>
        </div>
        <div id="drilldownPager" class="px-5 py-3 border-t border-gray-100"></div>
    </div>

</div>

{{-- Tanpa library grafik (no CDN) — grafik dibuat manual dengan SVG/HTML --}}
<script>
(function () {
    /* ---------- DATA DARI CONTROLLER ---------- */
    const kurirLabels = @json($kurirLabels);
    const kurirIds    = @json($kurirIds);
    const kurirData   = @json($kurirData);

    const tlLabels = @json($timelineLabels);
    const tlKeys   = @json($timelineKeys);
    const tlData   = @json($timelineData);

    const summary      = @json($summary);
    const periodFrom   = @json($periodFrom);
    const periodTo     = @json($periodTo);
    const statusFilter = @json($status);
    const kurirSelIds  = @json(array_values($kurirFilter ?? []));
    const compareMode  = @json((bool) $compare);
    const tlSeriesRaw  = @json($timelineSeries);
    const PALETTE      = @json($palette);

    /* ---------- UTIL ---------- */
    const esc = (t) => String(t ?? '-').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const num = (n) => Number(n || 0).toLocaleString('id-ID');
    const shorten = (t, n) => String(t).length > n ? String(t).slice(0, n - 1) + '…' : String(t);

    const STATUS_COLOR = {
        'Belum Terkirim':         '#2563eb',
        'Proses Pengiriman':      '#f97316',
        'Dokumen Belum Tersedia': '#8b5cf6',
        'Terkirim':               '#16a34a',
        'Batal':        '#dc2626',
    };
    const badgeClass = (status) => ({
        'Belum Terkirim':         'bg-blue-100 text-blue-700',
        'Proses Pengiriman':      'bg-orange-100 text-orange-700',
        'Dokumen Belum Tersedia': 'bg-purple-100 text-purple-700',
        'Terkirim':               'bg-green-100 text-green-700',
        'Ditolak':                'bg-red-100 text-red-700',
    })[status] || 'bg-gray-100 text-gray-700';

    function niceMaxOf(values) {
        const maxVal = Math.max(1, ...values);
        const rough = maxVal / 4;
        const mag = Math.pow(10, Math.floor(Math.log10(rough || 1)));
        const step = Math.max(1, Math.ceil(rough / mag) * mag);
        return Math.ceil(maxVal / step) * step || maxVal;
    }

    /* ---------- TOGGLE TAHUN / BULAN ---------- */
    document.getElementById('viewSelect')?.addEventListener('change', function () {
        const isYear = this.value === 'year';
        document.getElementById('yearField').classList.toggle('hidden', !isYear);
        document.getElementById('monthField').classList.toggle('hidden', isYear);
    });


    /* ---------- DROPDOWN KURIR (multi-pilih) ---------- */
    (function () {
        const box = document.getElementById('kurirBox');
        if (!box) return;
        const btn    = document.getElementById('kurirBtn');
        const text   = document.getElementById('kurirBtnText');
        const panel  = document.getElementById('kurirPanel');
        const search = document.getElementById('kurirSearch');
        const empty  = document.getElementById('kurirEmpty');
        const items  = () => [...box.querySelectorAll('.kurir-item')];
        const checks = () => [...box.querySelectorAll('input[type=checkbox]')];

        function refreshText() {
            const on = checks().filter(c => c.checked);
            text.textContent = on.length === 0 ? 'Semua Messenger'
                             : on.length === 1 ? on[0].dataset.label
                             : on.length + ' messenger dipilih';
        }
        btn.addEventListener('click', () => {
            panel.classList.toggle('hidden');
            if (!panel.classList.contains('hidden')) search.focus();
        });
        document.addEventListener('click', (e) => { if (!box.contains(e.target)) panel.classList.add('hidden'); });
        checks().forEach(c => c.addEventListener('change', refreshText));

        search.addEventListener('input', () => {
            const q = search.value.trim().toLowerCase();
            let shown = 0;
            items().forEach(it => {
                const ok = !q || it.dataset.name.includes(q);
                it.classList.toggle('hidden', !ok);
                if (ok) shown++;
            });
            empty.classList.toggle('hidden', shown > 0);
        });
        document.getElementById('kurirAll').addEventListener('click', () => {
            items().filter(it => !it.classList.contains('hidden')).forEach(it => it.querySelector('input').checked = true);
            refreshText();
        });
        document.getElementById('kurirNone').addEventListener('click', () => {
            checks().forEach(c => c.checked = false);
            refreshText();
        });
        refreshText();
    })();

    /* ---------- TOOLTIP ---------- */
    const tip = document.createElement('div');
    tip.className = 'fixed z-50 pointer-events-none hidden px-2.5 py-1.5 rounded-lg bg-gray-900 text-white text-xs shadow-lg';
    document.body.appendChild(tip);
    const showTip = (e, html) => {
        tip.innerHTML = html;
        tip.classList.remove('hidden');
        const w = tip.offsetWidth;
        tip.style.left = Math.min(window.innerWidth - w - 8, e.clientX + 12) + 'px';
        tip.style.top  = (e.clientY - 38) + 'px';
    };
    const hideTip = () => tip.classList.add('hidden');

    /* ---------- PAGER ---------- */
    function mountPager(el, page, pages, total, noun, onGo) {
        el.innerHTML = '';
        if (pages <= 1) return;
        const btn = (txt, target, disabled) =>
            `<button type="button" data-go="${target}" ${disabled ? 'disabled' : ''}
                class="h-8 px-3 rounded-lg text-xs font-semibold border ${disabled ? 'text-gray-300 border-gray-100 cursor-not-allowed' : 'text-gray-700 border-gray-200 hover:bg-gray-50'}">${txt}</button>`;
        el.innerHTML = `<div class="flex items-center justify-between gap-2 flex-wrap">
            <span class="text-xs text-gray-500">Halaman ${page} dari ${pages} &middot; ${num(total)} ${noun}</span>
            <div class="flex gap-1">${btn('&laquo; Sebelumnya', page - 1, page <= 1)}${btn('Berikutnya &raquo;', page + 1, page >= pages)}</div>
        </div>`;
        el.querySelectorAll('button[data-go]:not([disabled])').forEach(b =>
            b.addEventListener('click', () => onGo(parseInt(b.dataset.go, 10))));
    }

    /* =====================================================
     |  DRILLDOWN
     ===================================================== */
    const panel     = document.getElementById('drilldownPanel');
    const titleEl   = document.getElementById('drilldownTitle');
    const countEl   = document.getElementById('drilldownCount');
    const bodyEl    = document.getElementById('drilldownBody');
    const loadingEl = document.getElementById('drilldownLoading');
    const emptyEl   = document.getElementById('drilldownEmpty');
    const ddPagerEl = document.getElementById('drilldownPager');

    const PAGE_SIZE = 20;
    let ddRows = [], ddPage = 1;

    function renderDrilldown() {
        const pages = Math.max(1, Math.ceil(ddRows.length / PAGE_SIZE));
        if (ddPage > pages) ddPage = pages;
        const slice = ddRows.slice((ddPage - 1) * PAGE_SIZE, ddPage * PAGE_SIZE);

        if (!ddRows.length) {
            bodyEl.innerHTML = '';
            emptyEl.textContent = 'Tidak ada data.';
            emptyEl.classList.remove('hidden');
        } else {
            emptyEl.classList.add('hidden');
            bodyEl.innerHTML = slice.map(r => `
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-medium whitespace-nowrap">${esc(r.no_transaksi)}</td>
                    <td class="px-4 py-3">${esc(r.nama_pengirim)}</td>
                    <td class="px-4 py-3">${esc(r.nama_barang)}</td>
                    <td class="px-4 py-3">${esc(r.penerima)}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold whitespace-nowrap ${badgeClass(r.status)}">${esc(r.status)}</span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">${esc(r.tanggal)}</td>
                    <td class="px-4 py-3">
                        <a href="{{ url('messenger') }}/${encodeURIComponent(r.no_transaksi)}" class="text-blue-600 font-semibold hover:underline">Detail</a>
                    </td>
                </tr>`).join('');
        }
        mountPager(ddPagerEl, ddPage, pages, ddRows.length, 'transaksi', (p) => { ddPage = p; renderDrilldown(); });
    }

    async function loadDrilldown(url) {
        panel.classList.remove('hidden');
        bodyEl.innerHTML = '';
        emptyEl.classList.add('hidden');
        ddPagerEl.innerHTML = '';
        loadingEl.classList.remove('hidden');
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

        try {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Gagal memuat data');
            const json = await res.json();

            titleEl.textContent = json.title;
            countEl.textContent = num(json.count) + ' transaksi';
            ddRows = json.rows || [];
            ddPage = 1;
            renderDrilldown();
        } catch (e) {
            bodyEl.innerHTML = '';
            emptyEl.textContent = 'Terjadi kesalahan saat memuat data.';
            emptyEl.classList.remove('hidden');
        } finally {
            loadingEl.classList.add('hidden');
        }
    }
    document.getElementById('drilldownClose')?.addEventListener('click', () => panel.classList.add('hidden'));

    // parameter filter yang ikut dikirim ke endpoint detail (kurir = daftar id, boleh banyak)
    const withFilters = (params, kurirIds) => {
        if (statusFilter) params.set('status', statusFilter);
        (kurirIds ?? kurirSelIds).forEach(id => params.append('kurir[]', id));
        const q = params.toString();
        return q ? '?' + q : '';
    };

    /* =====================================================
     |  GRAFIK 1: BATANG TREN (bulanan / harian) + PERBANDINGAN KURIR
     ===================================================== */
    const elTimeline = document.getElementById('chartTimeline');
    const elStats    = document.getElementById('tlStats');
    const elLegend   = document.getElementById('tlLegend');

    // 1 seri "Total" (normal) atau 1 seri per kurir (mode perbandingan)
    const series = compareMode
        ? tlSeriesRaw.map((s, i) => ({ id: s.id, name: s.name, data: s.data, color: PALETTE[i % PALETTE.length] }))
        : [{ id: null, name: 'Total', data: tlData, color: '#2563eb' }];
    const hiddenSeries = new Set();

    function renderLegend() {
        if (!elLegend) return;
        elLegend.innerHTML = '';
        if (!compareMode) return;
        series.forEach((s, i) => {
            const total = s.data.reduce((a, b) => a + b, 0);
            const off = hiddenSeries.has(i);
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'inline-flex items-center gap-2 px-3 py-1.5 rounded-full border text-xs select-none transition ' +
                (off ? 'border-gray-200 text-gray-400 opacity-50' : 'border-gray-200 text-gray-700 bg-white shadow-sm');
            chip.innerHTML = `<span class="w-2.5 h-2.5 rounded-full" style="background:${s.color}"></span>
                <span class="font-semibold">${esc(s.name)}</span><span class="text-gray-400">${num(total)}</span>`;
            chip.addEventListener('click', () => {
                off ? hiddenSeries.delete(i) : hiddenSeries.add(i);
                renderLegend();
                drawTimeline();
            });
            elLegend.appendChild(chip);
        });
    }

    function drawTimeline() {
        if (!elTimeline) return;
        if (!tlLabels.length) {
            elTimeline.innerHTML = '<p class="text-sm text-gray-400 py-12 text-center">Belum ada data pada periode ini.</p>';
            return;
        }
        const vis = series.map((_, i) => i).filter(i => !hiddenSeries.has(i));
        if (!vis.length) {
            elTimeline.innerHTML = '<p class="text-sm text-gray-400 py-12 text-center">Semua messenger disembunyikan.</p>';
            if (elStats) elStats.innerHTML = '';
            return;
        }

        const single = !compareMode;
        const W = Math.max(320, elTimeline.clientWidth || 720), H = 290;
        const pad = { l: 38, r: 12, t: 18, b: 30 };
        const cw = W - pad.l - pad.r, ch = H - pad.t - pad.b;
        const maxVal = niceMaxOf(vis.flatMap(i => series[i].data));
        const slot = cw / tlLabels.length;
        const gap = 2;
        const barW = Math.max(3, Math.min(single ? 44 : 30, (slot * 0.8) / vis.length - gap));
        const groupW = vis.length * barW + (vis.length - 1) * gap;
        const labelEvery = Math.max(1, Math.ceil(24 / slot));
        const base = series[vis[0]].data;
        const total = base.reduce((a, b) => a + b, 0);
        const avg = total / base.length;
        const peakI = base.indexOf(Math.max(...base));

        let svg = `<defs>
            <linearGradient id="gBar" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient>
            <linearGradient id="gPeak" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#f59e0b"/><stop offset="1" stop-color="#d97706"/></linearGradient></defs>`;

        for (let i = 0; i <= 4; i++) {
            const val = Math.round((maxVal / 4) * i);
            const y = pad.t + ch - (val / maxVal) * ch;
            svg += `<line x1="${pad.l}" y1="${y}" x2="${W - pad.r}" y2="${y}" stroke="${i === 0 ? '#cbd5e1' : '#eef2f7'}" ${i === 0 ? '' : 'stroke-dasharray="3 3"'}/>`
                 + `<text x="${pad.l - 8}" y="${y + 3}" font-size="10" fill="#94a3b8" text-anchor="end">${val}</text>`;
        }

        tlLabels.forEach((lb, i) => {
            const gx = pad.l + i * slot + (slot - groupW) / 2;
            let bars = '';
            vis.forEach((si, k) => {
                const v = series[si].data[i] || 0;
                if (!v) return;
                const bh = (v / maxVal) * ch;
                const x = gx + k * (barW + gap), y = pad.t + ch - bh;
                const isPeak = single && i === peakI && tlLabels.length > 1;
                const fill = single ? `url(#${isPeak ? 'gPeak' : 'gBar'})` : series[si].color;
                bars += `<rect class="bar-rect" data-s="${si}" x="${x}" y="${y}" width="${barW}" height="${bh}" rx="3" fill="${fill}" fill-opacity="0.9"/>`;
                if ((single && slot >= 22) || (!single && barW >= 14)) {
                    bars += `<text x="${x + barW / 2}" y="${y - 4}" font-size="10" font-weight="600" fill="#334155" text-anchor="middle" pointer-events="none">${v}</text>`;
                }
            });
            svg += `<g class="tl-slot" data-idx="${i}" style="cursor:pointer">
                <rect x="${pad.l + i * slot}" y="${pad.t}" width="${slot}" height="${ch}" fill="transparent"/>
                ${bars}
                ${i % labelEvery === 0 ? `<text x="${pad.l + i * slot + slot / 2}" y="${H - pad.b + 16}" font-size="10.5" fill="#64748b" text-anchor="middle">${esc(shorten(lb, slot < 34 ? 4 : 9))}</text>` : ''}
            </g>`;
        });

        if (single && avg > 0 && tlLabels.length > 1) {
            const ay = pad.t + ch - (avg / maxVal) * ch;
            svg += `<line x1="${pad.l}" y1="${ay}" x2="${W - pad.r}" y2="${ay}" stroke="#f59e0b" stroke-width="1.2" stroke-dasharray="5 4"/>`
                 + `<text x="${W - pad.r}" y="${ay - 4}" font-size="10" fill="#d97706" text-anchor="end">rata-rata ${avg.toFixed(1).replace('.', ',')}</text>`;
        }

        elTimeline.innerHTML = `<svg viewBox="0 0 ${W} ${H}" style="display:block;width:100%;height:auto">${svg}</svg>`;

        elTimeline.querySelectorAll('.tl-slot').forEach(g => {
            const i = parseInt(g.dataset.idx, 10);
            g.addEventListener('mousemove', (e) => {
                const lines = vis.map(si => `<div class="flex items-center gap-1.5">
                    ${single ? '' : `<span class="inline-block w-2 h-2 rounded-full" style="background:${series[si].color}"></span>`}
                    <span>${single ? '' : esc(series[si].name) + ': '}<b>${num(series[si].data[i])}</b> transaksi</span></div>`).join('');
                showTip(e, `<div class="font-semibold mb-0.5">${esc(tlLabels[i])}</div>${lines}`);
            });
            g.addEventListener('mouseleave', hideTip);
            g.addEventListener('click', (e) => {
                hideTip();
                let ids = kurirSelIds;
                if (compareMode) {
                    const s = e.target.dataset ? e.target.dataset.s : undefined;
                    ids = (s !== undefined && s !== '')
                        ? [series[parseInt(s, 10)].id]          // klik batang 1 kurir
                        : vis.map(si => series[si].id);         // klik area kosong: kurir yang tampil
                }
                loadDrilldown("{{ url('messenger/dashboard/data/bulan') }}/" + tlKeys[i] + withFilters(new URLSearchParams(), ids));
            });
        });

        if (elStats) {
            if (single) {
                const chip = (icon, text) => `<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-gray-50 border border-gray-200 text-gray-600"><i class="${icon} text-gray-400"></i>${text}</span>`;
                elStats.innerHTML =
                    chip('fas fa-calculator', `Total <b class="text-gray-800">${num(total)}</b>`) +
                    chip('fas fa-wave-square', `Rata-rata <b class="text-gray-800">${avg.toFixed(1).replace('.', ',')}</b>`) +
                    (base[peakI] > 0 ? chip('fas fa-arrow-up', `Tertinggi <b class="text-gray-800">${esc(tlLabels[peakI])}</b> (${num(base[peakI])})`) : '');
            } else {
                elStats.innerHTML = '';
            }
        }
    }

    /* =====================================================
     |  GRAFIK 2: DONAT KOMPOSISI STATUS
     ===================================================== */
    function drawStatus() {
        const el = document.getElementById('chartStatus');
        if (!el) return;

        const items = [
            { label: 'Belum Terkirim',         value: summary['Belum Terkirim'] || 0 },
            { label: 'Proses Pengiriman',      value: summary['Proses Pengiriman'] || 0 },
            { label: 'Dokumen Belum Tersedia', value: summary['Dokumen Belum Tersedia'] || 0 },
            { label: 'Terkirim',               value: summary['Terkirim'] || 0 },
            { label: 'Batal',        value: (summary['Ditolak'] || 0) + (summary['Batal'] || 0) },
        ];
        const total = items.reduce((a, b) => a + b.value, 0);

        const R = 52, C = 2 * Math.PI * R;
        let off = 0, ring = `<circle cx="70" cy="70" r="${R}" fill="none" stroke="#f1f5f9" stroke-width="18"/>`;
        if (total > 0) {
            items.forEach(it => {
                if (!it.value) return;
                const len = (it.value / total) * C;
                ring += `<circle cx="70" cy="70" r="${R}" fill="none" stroke="${STATUS_COLOR[it.label]}" stroke-width="18"
                            stroke-dasharray="${len} ${C - len}" stroke-dashoffset="${-off}" transform="rotate(-90 70 70)"/>`;
                off += len;
            });
        }

        const legend = items.map(it => {
            const p = total ? Math.round((it.value / total) * 100) : 0;
            return `<div class="flex items-center justify-between gap-2 py-1.5">
                <span class="flex items-center gap-2 text-gray-600 min-w-0">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:${STATUS_COLOR[it.label]}"></span>
                    <span class="truncate">${esc(it.label)}</span>
                </span>
                <span class="text-gray-800 whitespace-nowrap"><b>${num(it.value)}</b> <span class="text-gray-400 text-xs">(${p}%)</span></span>
            </div>`;
        }).join('');

        el.innerHTML = `
            <div class="flex justify-center">
                <svg viewBox="0 0 140 140" class="w-40 h-40">
                    ${ring}
                    <text x="70" y="68" text-anchor="middle" font-size="20" font-weight="700" fill="#1e293b">${num(total)}</text>
                    <text x="70" y="84" text-anchor="middle" font-size="9" fill="#94a3b8">TRANSAKSI</text>
                </svg>
            </div>
            <div class="mt-3 divide-y divide-gray-50 text-sm">${legend}</div>`;
    }

    /* =====================================================
     |  GRAFIK 3: RANKING PER KURIR (batang horizontal)
     ===================================================== */
    function setupKurir() {
        const el = document.getElementById('chartKurir');
        if (!el) return;
        const pagerEl  = document.getElementById('pagerKurir');
        const searchEl = document.getElementById('searchKurir');
        const pageSize = 15;
        const maxAll   = Math.max(1, ...kurirData);
        let page = 1, query = '';

        const render = () => {
            const all = kurirLabels.map((_, i) => i)
                .filter(i => !query || String(kurirLabels[i]).toLowerCase().includes(query))
                .sort((a, b) => (kurirData[b] || 0) - (kurirData[a] || 0));

            const pages = Math.max(1, Math.ceil(all.length / pageSize));
            if (page > pages) page = pages;
            const slice = all.slice((page - 1) * pageSize, page * pageSize);

            if (!all.length) {
                el.innerHTML = '<p class="text-sm text-gray-400 py-10 text-center">Messenger tidak ditemukan.</p>';
            } else {
                el.innerHTML = slice.map((idx, n) => {
                    const v = kurirData[idx] || 0;
                    const w = Math.max(v > 0 ? 2 : 0, (v / maxAll) * 100);
                    const rank = (page - 1) * pageSize + n + 1;
                    const medal = rank <= 3 && !query ? ['#f59e0b', '#94a3b8', '#c2410c'][rank - 1] : null;
                    return `<button type="button" data-idx="${idx}" class="kurir-row group w-full flex items-center gap-3 py-2 px-1 text-left rounded-lg hover:bg-gray-50 transition">
                        <span class="w-6 text-center text-xs font-semibold" style="color:${medal || '#94a3b8'}">${rank}</span>
                        <span class="w-36 sm:w-52 truncate text-sm text-gray-700 group-hover:text-gray-900" title="${esc(kurirLabels[idx])}">${esc(kurirLabels[idx])}</span>
                        <span class="flex-1 h-2.5 bg-gray-100 rounded-full overflow-hidden">
                            <span class="block h-full rounded-full" style="width:${w}%;background:linear-gradient(90deg,#60a5fa,#2563eb)"></span>
                        </span>
                        <span class="w-12 text-right text-sm font-semibold text-gray-800">${num(v)}</span>
                    </button>`;
                }).join('');

                el.querySelectorAll('.kurir-row').forEach(row => {
                    row.addEventListener('click', () => {
                        const idx = parseInt(row.dataset.idx, 10);
                        loadDrilldown("{{ url('messenger/dashboard/data/kurir') }}/" + kurirIds[idx]
                            + withFilters(new URLSearchParams({ from: periodFrom, to: periodTo }), []));
                    });
                });
            }
            mountPager(pagerEl, page, pages, all.length, 'messenger', (p) => { page = p; render(); });
        };

        searchEl?.addEventListener('input', () => {
            query = searchEl.value.trim().toLowerCase();
            page = 1;
            render();
        });
        render();
    }

    /* ---------- JALANKAN ---------- */
    renderLegend();
    drawTimeline();
    drawStatus();
    setupKurir();

    // otomatis menyesuaikan lebar (zoom, resize, buka/tutup sidebar)
    if (elTimeline) {
        let lastW = elTimeline.clientWidth, t;
        const onResize = () => {
            clearTimeout(t);
            t = setTimeout(() => {
                const w = elTimeline.clientWidth;
                if (Math.abs(w - lastW) > 2) { lastW = w; drawTimeline(); }
            }, 60);
        };
        if (window.ResizeObserver) new ResizeObserver(onResize).observe(elTimeline);
        window.addEventListener('resize', onResize);
    }
})();
</script>
@endsection