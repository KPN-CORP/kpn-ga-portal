@extends('layouts.app_messenger_sidebar')

@section('content')
<div class="space-y-4 text-sm text-gray-800 font-sans min-w-0">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Dashboard Messenger</h2>
            <p class="text-xs text-gray-500">Periode: <span class="font-semibold">{{ $periodLabel }}</span>@if($status) &middot; Status: <span class="font-semibold">{{ $status }}</span>@endif</p>
        </div>
        <a href="{{ route('messenger.index') }}"
           class="inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700
                  rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
            <i class="fas fa-list mr-2"></i> Daftar Pengiriman
        </a>
    </div>

    {{-- FILTER --}}
    <div class="bg-white border rounded-xl p-4">
        <form method="GET" action="{{ route('messenger.dashboard') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[130px]">
                <label class="block text-xs font-bold text-gray-700 mb-1">Tampilan</label>
                <select name="view" id="viewSelect" class="w-full rounded-lg border-0 bg-gray-100 py-2 px-3 focus:ring-2 focus:ring-blue-400">
                    <option value="year"  {{ $view === 'year'  ? 'selected' : '' }}>Per Tahun</option>
                    <option value="month" {{ $view === 'month' ? 'selected' : '' }}>Per Bulan</option>
                </select>
            </div>
            <div class="flex-1 min-w-[150px]" id="yearField" style="{{ $view === 'year' ? '' : 'display:none' }}">
                <label class="block text-xs font-bold text-gray-700 mb-1">Tahun</label>
                <select name="year" class="w-full rounded-lg border-0 bg-gray-100 py-2 px-3 focus:ring-2 focus:ring-blue-400">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[170px]" id="monthField" style="{{ $view === 'month' ? '' : 'display:none' }}">
                <label class="block text-xs font-bold text-gray-700 mb-1">Bulan</label>
                <select name="month" class="w-full rounded-lg border-0 bg-gray-100 py-2 px-3 focus:ring-2 focus:ring-blue-400">
                    @foreach($months as $key => $label)
                        <option value="{{ $key }}" {{ $month == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[170px]">
                <label class="block text-xs font-bold text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full rounded-lg border-0 bg-gray-100 py-2 px-3 focus:ring-2 focus:ring-blue-400">
                    <option value="">Semua Status</option>
                    @foreach($statusList as $s)
                        <option value="{{ $s }}" {{ $status === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-lg transition">Tampilkan</button>
                <a href="{{ route('messenger.dashboard') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-5 py-2 rounded-lg transition">Reset</a>
            </div>
        </form>
        <script>
            document.getElementById('viewSelect').addEventListener('change', function () {
                const isYear = this.value === 'year';
                document.getElementById('yearField').style.display  = isYear ? '' : 'none';
                document.getElementById('monthField').style.display = isYear ? 'none' : '';
            });
        </script>
    </div>

    {{-- SUMMARY CARDS --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="bg-white border rounded-xl p-3">
            <div class="text-xs text-gray-500">Total Transaksi</div>
            <div class="text-xl font-bold text-gray-800 mt-1">{{ $totalKeseluruhan }}</div>
        </div>
        <div class="bg-white border rounded-xl p-3">
            <div class="text-xs text-gray-500">Belum Terkirim</div>
            <div class="text-xl font-bold text-blue-600 mt-1">{{ $summary['Belum Terkirim'] }}</div>
        </div>
        <div class="bg-white border rounded-xl p-3">
            <div class="text-xs text-gray-500">Proses Pengiriman</div>
            <div class="text-xl font-bold text-orange-600 mt-1">{{ $summary['Proses Pengiriman'] }}</div>
        </div>
        <div class="bg-white border rounded-xl p-3">
            <div class="text-xs text-gray-500">Dok. Belum Tersedia</div>
            <div class="text-xl font-bold text-purple-600 mt-1">{{ $summary['Dokumen Belum Tersedia'] }}</div>
        </div>
        <div class="bg-white border rounded-xl p-3">
            <div class="text-xs text-gray-500">Terkirim</div>
            <div class="text-xl font-bold text-green-600 mt-1">{{ $summary['Terkirim'] }}</div>
        </div>
        <div class="bg-white border rounded-xl p-3">
            <div class="text-xs text-gray-500">Batal Dikirim</div>
            <div class="text-xl font-bold text-red-600 mt-1">{{ $summary['Ditolak'] + $summary['Batal'] }}</div>
        </div>
    </div>

    {{-- GRAFIK 1 (PALING ATAS): LAPORAN BULANAN / HARIAN --}}
    <div class="bg-white border rounded-xl p-4">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h3 class="font-semibold text-gray-800">{{ $timelineTitle }}</h3>
                <p class="text-xs text-gray-500">Klik batang untuk melihat detail &middot; klik chip di bawah untuk sembunyikan/tampilkan</p>
            </div>
            <i class="fas fa-chart-line text-gray-300 text-lg"></i>
        </div>
        <div id="legendTimeline" class="flex flex-wrap gap-1.5 mb-2"></div>
        <div id="chartTimeline" class="w-full min-w-0 overflow-hidden"></div>
    </div>

    {{-- GRAFIK 2: TOTAL DIAMBIL PER KURIR (batang ke kanan) --}}
    <div class="bg-white border rounded-xl p-4">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h3 class="font-semibold text-gray-800">Total Diambil per Messenger</h3>
                <p class="text-xs text-gray-500">Klik batang untuk melihat detail &middot; klik chip di bawah untuk sembunyikan/tampilkan</p>
            </div>
            <i class="fas fa-chart-bar text-gray-300 text-lg"></i>
        </div>
        @if(count($kurirLabels))
            <div class="relative mb-2 max-w-xs">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input id="searchKurir" type="text" placeholder="Cari Messenger..." autocomplete="off"
                       class="w-full rounded-lg border-0 bg-gray-100 py-2 pl-8 pr-3 text-sm focus:ring-2 focus:ring-blue-400">
            </div>
            <div id="legendKurir" class="flex flex-wrap gap-1.5 mb-2"></div>
            <div id="chartKurir" class="w-full min-w-0 overflow-hidden"></div>
            <div id="pagerKurir" class="mt-2"></div>
        @else
            <p class="text-sm text-gray-400 py-8 text-center">Belum ada transaksi yang diambil messenger pada periode ini.</p>
        @endif
    </div>

    {{-- DRILLDOWN RESULT --}}
    <div id="drilldownPanel" class="bg-white border rounded-xl p-4 hidden">
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 id="drilldownTitle" class="font-semibold text-gray-800">-</h3>
                <p id="drilldownCount" class="text-xs text-gray-500"></p>
            </div>
            <button id="drilldownClose" class="text-gray-400 hover:text-gray-700">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <div id="drilldownLoading" class="hidden py-8 text-center text-gray-400 text-sm">
            <i class="fas fa-spinner fa-spin mr-2"></i> Memuat data...
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-800">
                    <tr>
                        <th class="px-3 py-2 text-left font-bold">No. Transaksi</th>
                        <th class="px-3 py-2 text-left font-bold">Pengirim</th>
                        <th class="px-3 py-2 text-left font-bold">Jenis</th>
                        <th class="px-3 py-2 text-left font-bold">Penerima</th>
                        <th class="px-3 py-2 text-left font-bold">Status</th>
                        <th class="px-3 py-2 text-left font-bold">Tanggal</th>
                        <th class="px-3 py-2 text-left font-bold">Aksi</th>
                    </tr>
                </thead>
                <tbody id="drilldownBody" class="divide-y"></tbody>
            </table>
            <p id="drilldownEmpty" class="hidden text-center text-gray-400 text-sm py-6">Tidak ada data.</p>
            <div id="drilldownPager" class="mt-3"></div>
        </div>
    </div>

</div>

{{-- Tanpa library grafik (no CDN, no file tambahan) — grafik SVG dibuat manual --}}
<script>
(function () {
    const kurirLabels = @json($kurirLabels);
    const kurirIds    = @json($kurirIds);
    const kurirData   = @json($kurirData);

    const tlLabels = @json($timelineLabels);
    const tlKeys   = @json($timelineKeys);
    const tlData   = @json($timelineData);

    const periodFrom   = @json($periodFrom);
    const periodTo     = @json($periodTo);
    const statusFilter = @json($status);

    const esc = (t) => String(t ?? '-').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    const badgeClass = (status) => ({
        'Belum Terkirim':          'bg-blue-100 text-blue-700',
        'Proses Pengiriman':       'bg-orange-100 text-orange-700',
        'Dokumen Belum Tersedia':  'bg-purple-100 text-purple-700',
        'Terkirim':                'bg-green-100 text-green-700',
        'Ditolak':                 'bg-red-100 text-red-700',
    })[status] || 'bg-gray-100 text-gray-700';

    /* ---------------- DRILLDOWN ---------------- */
    const panel     = document.getElementById('drilldownPanel');
    const titleEl   = document.getElementById('drilldownTitle');
    const countEl   = document.getElementById('drilldownCount');
    const bodyEl    = document.getElementById('drilldownBody');
    const loadingEl = document.getElementById('drilldownLoading');
    const emptyEl   = document.getElementById('drilldownEmpty');

    const PAGE_SIZE = 20;
    let ddRows = [], ddPage = 1;
    const ddPagerEl = document.getElementById('drilldownPager');

    /** Pager sederhana: Sebelumnya | Halaman x dari y | Berikutnya */
    function mountPager(el, page, pages, total, noun, onGo) {
        el.innerHTML = '';
        if (pages <= 1) return;
        const btn = (txt, target, disabled) =>
            `<button type="button" data-go="${target}" ${disabled ? 'disabled' : ''}
                class="px-3 py-1 rounded-lg text-xs font-semibold border ${disabled ? 'text-gray-300 border-gray-100 cursor-not-allowed' : 'text-gray-700 border-gray-200 hover:bg-gray-50'}">${txt}</button>`;
        el.innerHTML = `<div class="flex items-center justify-between gap-2 flex-wrap">
            <span class="text-xs text-gray-500">Halaman ${page} dari ${pages} &middot; ${total} ${noun}</span>
            <div class="flex gap-1">${btn('&laquo; Sebelumnya', page - 1, page <= 1)}${btn('Berikutnya &raquo;', page + 1, page >= pages)}</div>
        </div>`;
        el.querySelectorAll('button[data-go]:not([disabled])').forEach(b =>
            b.addEventListener('click', () => onGo(parseInt(b.dataset.go, 10))));
    }

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
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 font-medium">${esc(r.no_transaksi)}</td>
                    <td class="px-3 py-2">${esc(r.nama_pengirim)}</td>
                    <td class="px-3 py-2">${esc(r.nama_barang)}</td>
                    <td class="px-3 py-2">${esc(r.penerima)}</td>
                    <td class="px-3 py-2">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold ${badgeClass(r.status)}">${esc(r.status)}</span>
                    </td>
                    <td class="px-3 py-2">${esc(r.tanggal)}</td>
                    <td class="px-3 py-2">
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
            countEl.textContent = json.count + ' transaksi';

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

    const withStatus = (params) => {
        if (statusFilter) params.set('status', statusFilter);
        const q = params.toString();
        return q ? '?' + q : '';
    };

    /* ---------------- UTIL GRAFIK ---------------- */
    // Warna tiap batang berbeda & tetap (tidak berubah saat ada yang di-hide)
    const palette = ['#2563eb','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4',
                     '#ec4899','#84cc16','#f97316','#14b8a6','#6366f1','#a855f7'];
    const colorAt = (i) => palette[i % palette.length];

    function niceMaxOf(values) {
        const maxVal = Math.max(1, ...values);
        const rough = maxVal / 4;
        const mag = Math.pow(10, Math.floor(Math.log10(rough || 1)));
        const step = Math.ceil(rough / mag) * mag;
        return Math.ceil(maxVal / step) * step || maxVal;
    }
    const shorten = (t, n) => String(t).length > n ? String(t).slice(0, n - 1) + '…' : String(t);

    /** items: [{idx, label, value}] (hanya yang tampil) */
    function drawVertical(container, items, onClick) {
        if (!items.length) { container.innerHTML = '<p class="text-sm text-gray-400 py-8 text-center">Semua Grafik disembunyikan.</p>'; return; }

        const W = Math.max(320, container.clientWidth || 720), H = 190;
        const padL = 30, padB = 26, padT = 20, padR = 8;
        const cw = W - padL - padR, ch = H - padT - padB;
        const niceMax = niceMaxOf(items.map(i => i.value));
        const slot = cw / items.length;
        const barW = Math.min(36, Math.max(8, slot - 6));
        const showVal = slot >= 20;
        const smallLbl = slot < 34;

        let grid = '';
        for (let i = 0; i <= 4; i++) {
            const val = Math.round((niceMax / 4) * i);
            const y = padT + ch - (val / niceMax) * ch;
            grid += `<line x1="${padL}" y1="${y}" x2="${W - padR}" y2="${y}" stroke="#eef2f7"/>`
                 +  `<text x="${padL - 6}" y="${y + 3}" font-size="9" fill="#94a3b8" text-anchor="end">${val}</text>`;
        }

        let bars = '';
        items.forEach((it, n) => {
            const bh = (it.value / niceMax) * ch;
            const x = padL + n * slot + (slot - barW) / 2;
            const y = padT + ch - bh;
            bars += `<g class="bar-group" data-idx="${it.idx}" style="cursor:pointer">
                <rect x="${padL + n * slot}" y="${padT}" width="${slot}" height="${ch}" fill="transparent"/>
                <rect x="${x}" y="${y}" width="${barW}" height="${Math.max(bh, 0)}" rx="3" fill="${colorAt(it.idx)}" fill-opacity="0.85" class="bar-rect"><title>${esc(it.label)}: ${it.value}</title></rect>
                ${showVal ? `<text x="${x + barW / 2}" y="${y - 4}" font-size="10" font-weight="600" fill="#334155" text-anchor="middle">${it.value}</text>` : ''}
                <text x="${x + barW / 2}" y="${H - padB + 14}" font-size="${smallLbl ? 9 : 10}" fill="#64748b" text-anchor="middle">${esc(shorten(it.label, smallLbl ? 3 : 8))}</text>
            </g>`;
        });

        container.innerHTML = `<svg viewBox="0 0 ${W} ${H}" style="display:block;width:100%;height:auto">${grid}
            <line x1="${padL}" y1="${padT + ch}" x2="${W - padR}" y2="${padT + ch}" stroke="#cbd5e1"/>${bars}</svg>`;
        bindBars(container, onClick);
    }

    /** Batang HORIZONTAL (ke kanan) */
    function drawHorizontal(container, items, onClick) {
        if (!items.length) { container.innerHTML = '<p class="text-sm text-gray-400 py-8 text-center">Semua Grafik disembunyikan.</p>'; return; }

        const W = Math.max(320, container.clientWidth || 720), rowH = 26;
        const padL = 130, padR = 44, padT = 4, padB = 20;
        const H = padT + padB + items.length * rowH;
        const cw = W - padL - padR;
        const niceMax = niceMaxOf(items.map(i => i.value));

        let grid = '';
        for (let i = 0; i <= 4; i++) {
            const val = Math.round((niceMax / 4) * i);
            const x = padL + (val / niceMax) * cw;
            grid += `<line x1="${x}" y1="${padT}" x2="${x}" y2="${H - padB}" stroke="#eef2f7"/>`
                 +  `<text x="${x}" y="${H - 6}" font-size="9" fill="#94a3b8" text-anchor="middle">${val}</text>`;
        }

        let bars = '';
        items.forEach((it, n) => {
            const w = (it.value / niceMax) * cw;
            const y = padT + n * rowH + 4, h = rowH - 8;
            bars += `<g class="bar-group" data-idx="${it.idx}" style="cursor:pointer">
                <rect x="0" y="${padT + n * rowH}" width="${W}" height="${rowH}" fill="transparent"/>
                <text x="${padL - 8}" y="${y + h / 2 + 3.5}" font-size="10.5" fill="#475569" text-anchor="end">${esc(shorten(it.label, 20))}</text>
                <rect x="${padL}" y="${y}" width="${Math.max(w, 0)}" height="${h}" rx="3" fill="${colorAt(it.idx)}" fill-opacity="0.85" class="bar-rect"><title>${esc(it.label)}: ${it.value}</title></rect>
                <text x="${padL + w + 5}" y="${y + h / 2 + 3.5}" font-size="10.5" font-weight="600" fill="#334155">${it.value}</text>
            </g>`;
        });

        container.innerHTML = `<svg viewBox="0 0 ${W} ${H}" style="display:block;width:100%;height:auto">${grid}
            <line x1="${padL}" y1="${padT}" x2="${padL}" y2="${H - padB}" stroke="#cbd5e1"/>${bars}</svg>`;
        bindBars(container, onClick);
    }

    function bindBars(container, onClick) {
        container.querySelectorAll('.bar-group').forEach(g => {
            const rect = g.querySelector('.bar-rect');
            g.addEventListener('mouseenter', () => rect.setAttribute('fill-opacity', '1'));
            g.addEventListener('mouseleave', () => rect.setAttribute('fill-opacity', '0.85'));
            g.addEventListener('click', () => onClick(parseInt(g.dataset.idx, 10)));
        });
    }

    /**
     * Pasang grafik + legend chip (hide/tampilkan) + opsional pencarian & halaman.
     * opts: { pageSize, searchEl, pagerEl, noun }
     */
    function setupChart(chartEl, legendEl, labels, values, drawFn, onClick, opts = {}) {
        if (!chartEl) return;
        const pageSize = opts.pageSize || 0;
        const hidden = new Set();
        let page = 1, query = '';

        const render = () => {
            const all = labels.map((_, i) => i).filter(i => !query || String(labels[i]).toLowerCase().includes(query));
            const pages = pageSize ? Math.max(1, Math.ceil(all.length / pageSize)) : 1;
            if (page > pages) page = pages;
            const slice = pageSize ? all.slice((page - 1) * pageSize, page * pageSize) : all;

            if (legendEl) {
                legendEl.innerHTML = '';
                slice.forEach(idx => {
                    const chip = document.createElement('button');
                    chip.type = 'button';
                    chip.title = labels[idx];
                    chip.className = 'flex items-center gap-1 px-2 py-0.5 rounded-full border text-[11px] select-none transition ' +
                        (hidden.has(idx) ? 'border-gray-200 text-gray-400 opacity-40' : 'border-gray-200 text-gray-700 bg-white shadow-sm');
                    chip.innerHTML = `<span class="inline-block w-2.5 h-2.5 rounded-full" style="background:${colorAt(idx)}"></span><span>${esc(shorten(labels[idx], 16))}</span>`;
                    chip.addEventListener('click', () => { hidden.has(idx) ? hidden.delete(idx) : hidden.add(idx); render(); });
                    legendEl.appendChild(chip);
                });
            }

            if (!all.length) {
                chartEl.innerHTML = '<p class="text-sm text-gray-400 py-8 text-center">Data tidak ditemukan.</p>';
            } else {
                const items = slice.filter(i => !hidden.has(i)).map(idx => ({ idx, label: labels[idx], value: values[idx] || 0 }));
                drawFn(chartEl, items, onClick);
            }

            if (opts.pagerEl) {
                mountPager(opts.pagerEl, page, pages, all.length, opts.noun || 'data', (p) => { page = p; render(); });
            }
        };

        if (opts.searchEl) {
            opts.searchEl.addEventListener('input', () => {
                query = opts.searchEl.value.trim().toLowerCase();
                page = 1;
                render();
            });
        }

        render();

        // Otomatis mengikuti ukuran: zoom browser, resize window, buka/tutup sidebar
        let lastW = chartEl.clientWidth, t;
        const onResize = () => {
            clearTimeout(t);
            t = setTimeout(() => {
                const w = chartEl.clientWidth;
                if (Math.abs(w - lastW) > 2) { lastW = w; render(); }
            }, 60);
        };
        if (window.ResizeObserver) new ResizeObserver(onResize).observe(chartEl);
        window.addEventListener('resize', onResize);
    }

    // ---------- GRAFIK 1: LAPORAN BULANAN / HARIAN ----------
    setupChart(
        document.getElementById('chartTimeline'),
        document.getElementById('legendTimeline'),
        tlLabels, tlData, drawVertical,
        (idx) => loadDrilldown("{{ url('messenger/dashboard/data/bulan') }}/" + tlKeys[idx] + withStatus(new URLSearchParams()))
    );

    // ---------- GRAFIK 2: TOTAL DIAMBIL PER KURIR ----------
    setupChart(
        document.getElementById('chartKurir'),
        document.getElementById('legendKurir'),
        kurirLabels, kurirData, drawHorizontal,
        (idx) => loadDrilldown("{{ url('messenger/dashboard/data/kurir') }}/" + kurirIds[idx]
            + withStatus(new URLSearchParams({ from: periodFrom, to: periodTo }))),
        {
            pageSize: 20,
            searchEl: document.getElementById('searchKurir'),
            pagerEl: document.getElementById('pagerKurir'),
            noun: 'kurir'
        }
    );
})();
</script>
@endsection