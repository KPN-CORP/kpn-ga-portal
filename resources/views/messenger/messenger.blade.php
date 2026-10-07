@extends('layouts.app_messenger_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">

    {{-- HEADER --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Messenger</h2>
            <p class="text-xs text-gray-500">Daftar pengiriman & status</p>
            @if($hasAccessAll)
                <span class="inline-block mt-1 px-2 py-1 bg-purple-100 text-purple-700 
                           rounded-full text-xs font-semibold">
                    <i class="fas fa-eye mr-1"></i> Akses Semua Data
                </span>
            @endif
        </div>

        <div class="flex gap-2 w-full sm:w-auto">
            <a href="{{ route('messenger.request') }}"
               class="flex-1 sm:flex-none inline-flex items-center justify-center
                      px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold
                      hover:bg-blue-700 transition">
                + New Request
            </a>

            <button id="toggleFilterBtn"
                class="flex-1 sm:flex-none px-4 py-2 bg-gray-100 text-gray-700
                       rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
                Filters
            </button>
        </div>
    </div>

    {{-- FILTER --}}
    @php
        $selectedPengirim = null;
        if ($hasAccessAll && request()->filled('pengirim') && request('pengirim') !== 'all') {
            $selectedPengirim = collect($pelangganList)->firstWhere('id_pelanggan', request('pengirim'));
        }

        $lbl = 'block mb-1.5 text-xs font-semibold text-gray-500 uppercase tracking-wide';
        $ctl = 'w-full h-10 px-3 text-sm bg-white border border-gray-300 rounded-lg
                focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500';
    @endphp

    <div id="filterSection"
         class="bg-white border border-gray-200 rounded-xl shadow-sm
                {{ request()->hasAny(['search','status','date','pengirim','kurir','date_from','date_to']) ? '' : 'hidden' }}">

        {{-- Judul panel --}}
        <div class="flex items-center gap-2 px-5 py-3 border-b border-gray-100">
            <i class="fas fa-sliders-h text-gray-400"></i>
            <h3 class="text-sm font-semibold text-gray-700">Filter Data</h3>
        </div>

        <form method="GET" action="{{ route('messenger.index') }}" id="filterForm">

            {{-- Isi filter --}}
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 {{ $hasAccessAll ? 'lg:grid-cols-4' : 'lg:grid-cols-2 lg:max-w-2xl' }} gap-x-4 gap-y-4">

                @if($hasAccessAll)
                {{-- PENGIRIM: ketik -> dropdown nama muncul --}}
                <div class="relative" id="pengirimBox">
                    <label class="{{ $lbl }}">Pengirim</label>
                    <input type="hidden" name="pengirim" id="pengirimId" value="{{ $selectedPengirim ? $selectedPengirim->id_pelanggan : 'all' }}">
                    <input type="text" id="pengirimText" autocomplete="off"
                           placeholder="Ketik nama pengirim..."
                           value="{{ $selectedPengirim ? $selectedPengirim->nama_pelanggan : '' }}"
                           class="{{ $ctl }}">
                    <div id="pengirimList"
                         class="hidden absolute z-30 mt-1 w-full max-h-60 overflow-y-auto
                                bg-white border rounded-lg shadow-lg"></div>
                </div>

                {{-- KURIR YANG MEMPROSES --}}
                <div>
                    <label class="{{ $lbl }}">Kurir yang Memproses</label>
                    <select name="kurir" class="{{ $ctl }}">
                        <option value="all">Semua Kurir</option>
                        @foreach($kurirList as $k)
                            <option value="{{ $k->id_pelanggan }}"
                                    {{ request('kurir') == $k->id_pelanggan ? 'selected' : '' }}>
                                {{ $k->nama_pelanggan }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- STATUS --}}
                <div>
                    <label class="{{ $lbl }}">Status</label>
                    <select name="status" class="{{ $ctl }}">
                        <option value="">Semua Status</option>
                        @foreach(['Belum Terkirim','Proses Pengiriman','Dokumen Belum Tersedia','Terkirim','Ditolak','Batal'] as $s)
                            <option value="{{ $s }}" {{ request('status')==$s?'selected':'' }}>
                                {{ $s }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- PERIODE (tanggal / bulan / tahun dalam 1 kalender) --}}
                <div>
                    <label class="{{ $lbl }}">Periode</label>
                    <div class="relative">
                        <input type="hidden" name="date_from" id="dateFrom" value="{{ request('date_from') }}">
                        <input type="hidden" name="date_to" id="dateTo" value="{{ request('date_to') }}">
                        <input type="text" id="dateRange" readonly placeholder="Pilih tanggal / bulan / tahun"
                               class="{{ $ctl }} pr-9 cursor-pointer">
                        <button type="button" id="dateRangeClear" title="Hapus periode"
                                class="hidden absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Footer: search (ikon -> klik muncul) + tombol aksi --}}
            <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3
                        px-5 py-3 bg-gray-50 border-t border-gray-100 rounded-b-xl">

                {{-- SEARCH --}}
                <div id="searchBox" class="flex items-center">
                    <button type="button" id="searchBtn" title="Cari"
                            class="{{ request()->filled('search') ? 'hidden' : '' }} inline-flex items-center gap-2
                                   h-10 px-3 text-sm text-gray-600 bg-white border border-gray-300 rounded-lg
                                   hover:text-blue-600 hover:border-blue-400 transition">
                        <i class="fas fa-search"></i>
                    </button>
                    <div id="searchWrap"
                         class="{{ request()->filled('search') ? '' : 'hidden' }} relative w-full sm:w-80">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                        <input type="text" name="search" id="searchInput" value="{{ request('search') }}"
                               autocomplete="off"
                               placeholder="No. transaksi, penerima, barang, alamat, kurir..."
                               class="{{ $ctl }} pl-9">
                    </div>
                </div>

                {{-- TOMBOL --}}
                <div class="flex items-center gap-2">
                    <a href="{{ route('messenger.index') }}"
                       class="flex-1 sm:flex-none inline-flex items-center justify-center h-10 px-5
                              text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg
                              hover:bg-gray-100 transition">
                        Reset
                    </a>
                    <button type="submit"
                            class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 h-10 px-6
                                   text-sm font-semibold text-white bg-blue-600 rounded-lg
                                   hover:bg-blue-700 transition">
                        <i class="fas fa-filter text-xs"></i> Terapkan
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ================= MOBILE CARD ================= --}}
    <div class="block sm:hidden space-y-3">
        @foreach($transaksi as $item)

        @php
        $badge = match($item->status){
            'Belum Terkirim'=>'bg-blue-100 text-blue-700',
            'Proses Pengiriman'=>'bg-orange-100 text-orange-700',
            'Dokumen Belum Tersedia'=>'bg-purple-100 text-purple-700',
            'Terkirim'=>'bg-green-100 text-green-700',
            'Ditolak'=>'bg-red-100 text-red-700',
            default=>'bg-gray-100 text-gray-700'
        };
        $isOwner = $pelanggan && $item->pengirim == $pelanggan->id_pelanggan;
        @endphp

        <div class="bg-white rounded-xl border p-4 shadow-sm">

            {{-- HEADER HP (VISIBLE) --}}
            <div class="flex justify-between items-center cursor-pointer toggleCardHeader">

                {{-- LEFT --}}
                <div>
                    <div class="text-sm font-semibold text-gray-800">
                        {{ $item->no_transaksi }}
                    </div>
                    <div class="text-xs text-gray-500">
                        {{ $item->penerima ?? '-' }}
                    </div>
                    @if($hasAccessAll)
                    <div class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-user mr-1"></i>{{ $item->nama_pengirim ?? '-' }}
                    </div>
                    @endif
                </div>

                {{-- RIGHT --}}
                <div class="flex items-center gap-2">
                    <div class="text-right">
                        <div class="text-xs text-gray-500">
                            {{ \Carbon\Carbon::parse($item->created_at)->format('d M Y') }}
                        </div>
                        <span class="inline-block mt-1 px-2 py-0.5 rounded-full
                                     text-xs font-semibold {{ $badge }}">
                            {{ $item->status }}
                        </span>
                    </div>
                    <i class="fas fa-chevron-down text-gray-400 transition-transform"></i>
                </div>
            </div>

            {{-- DROPDOWN DETAIL --}}
            <div class="mt-3 hidden toggleCardContent">

                <div class="bg-gray-50/70 border border-gray-200 rounded-lg p-3 space-y-3">

                    <div>
                        <div class="text-xs text-gray-500">Jenis Barang</div>
                        <div class="text-sm font-medium text-gray-800">
                            {{ $item->nama_barang ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-500">Nama Kurir</div>
                        <div class="text-sm font-medium text-gray-800">
                            {{ $item->nama_kurir ?: '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-500">Alamat Asal</div>
                        <div class="text-sm font-medium text-gray-800 leading-relaxed">
                            {{ $item->alamat_asal ?? '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-500">Alamat Tujuan</div>
                        <div class="text-sm font-medium text-gray-800 leading-relaxed">
                            {{ $item->alamat_tujuan ?? '-' }}
                        </div>
                    </div>

                </div>

                <div class="pt-3 flex items-center justify-between gap-2">
                    <a href="{{ route('messenger.detail',$item->no_transaksi) }}"
                    class="inline-block text-blue-600 font-semibold text-sm">
                    Lihat Detail →
                    </a>

                    @if($isOwner && $item->status=='Dokumen Belum Tersedia')
                    <form method="POST" action="{{ route('messenger.kirimUlang', $item->no_transaksi) }}"
                          onsubmit="return confirm('Kirim ulang pengiriman ini karena dokumen sudah tersedia?');">
                        @csrf
                        <button type="submit"
                                class="px-3 py-1.5 bg-purple-600 text-white rounded-lg text-xs font-semibold hover:bg-purple-700 transition">
                            Kirim Ulang
                        </button>
                    </form>
                    @endif
                </div>

            </div>


        </div>
        @endforeach
    </div>

    {{-- ================= DESKTOP TABLE ================= --}}
    <div class="hidden sm:block bg-white rounded-xl border overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left font-bold">No. Transaksi</th>
                    @if($hasAccessAll)
                    <th class="px-4 py-3 text-left font-bold">Pengirim</th>
                    @endif
                    <th class="px-4 py-3 text-left font-bold">Jenis</th>
                    <th class="px-4 py-3 text-left font-bold">Penerima</th>
                    <th class="px-4 py-3 text-left font-bold">Alamat</th>
                    <th class="px-4 py-3 text-left font-bold">Tanggal</th>
                    <th class="px-4 py-3 text-left font-bold">Status</th>
                    <th class="px-4 py-3 text-left font-bold">Nama Kurir</th>
                    <th class="px-4 py-3 text-left font-bold">Action</th>
                </tr>
            </thead>

            <tbody class="divide-y">
                @foreach($transaksi as $item)

                @php
                $badge = match($item->status){
                    'Belum Terkirim'=>'bg-blue-100 text-blue-700',
                    'Proses Pengiriman'=>'bg-orange-100 text-orange-700',
                    'Dokumen Belum Tersedia'=>'bg-purple-100 text-purple-700',
                    'Terkirim'=>'bg-green-100 text-green-700',
                    'Ditolak'=>'bg-red-100 text-red-700',
                    default=>'bg-gray-100 text-gray-700'
                };
                $isOwner = $pelanggan && $item->pengirim == $pelanggan->id_pelanggan;
                @endphp

                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3 font-medium">{{ $item->no_transaksi }}</td>
                    @if($hasAccessAll)
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ $item->nama_pengirim }}</div>
                        <div class="text-xs text-gray-500">{{ $item->hp_pengirim ?? '-' }}</div>
                    </td>
                    @endif
                    <td class="px-4 py-3">{{ $item->nama_barang }}</td>
                    <td class="px-4 py-3">{{ $item->penerima }}</td>
                    <td class="px-4 py-3">{{ $item->alamat_tujuan }}</td>
                    <td class="px-4 py-3">
                        {{ \Carbon\Carbon::parse($item->created_at)->format('d M Y') }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $badge }}">
                            {{ $item->status }}
                        </span>
                    </td>
                    <td class="px-4 py-3">{{ $item->nama_kurir ?: '-' }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('messenger.detail',$item->no_transaksi) }}"
                               class="text-blue-600 font-semibold hover:underline">
                                Detail
                            </a>
                            @if($isOwner && $item->status=='Dokumen Belum Tersedia')
                            <form method="POST" action="{{ route('messenger.kirimUlang', $item->no_transaksi) }}"
                                  onsubmit="return confirm('Kirim ulang pengiriman ini karena dokumen sudah tersedia?');">
                                @csrf
                                <button type="submit"
                                        class="px-2 py-1 bg-purple-600 text-white rounded text-xs font-semibold hover:bg-purple-700 transition">
                                    Kirim Ulang
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between
                gap-3 text-sm text-gray-600">
        <div>
            Showing {{ $transaksi->firstItem() }} – {{ $transaksi->lastItem() }}
            of {{ $transaksi->total() }}
        </div>
        {{ $transaksi->links() }}
    </div>

</div>

{{-- SCRIPT --}}
<script>
document.getElementById('toggleFilterBtn')?.addEventListener('click', () => {
    document.getElementById('filterSection').classList.toggle('hidden')
})

const searchBtn   = document.getElementById('searchBtn')
const searchWrap  = document.getElementById('searchWrap')
const searchInput = document.getElementById('searchInput')
searchBtn?.addEventListener('click', () => {
    searchBtn.classList.add('hidden')
    searchWrap.classList.remove('hidden')
    searchInput.focus()
})
searchInput?.addEventListener('blur', () => {
    if (!searchInput.value.trim()) {
        searchWrap.classList.add('hidden')
        searchBtn.classList.remove('hidden')
    }
})

document.querySelectorAll('.toggleCardHeader').forEach(header => {
    header.addEventListener('click', () => {
        const content = header.nextElementSibling
        const icon = header.querySelector('i')

        content.classList.toggle('hidden')
        icon.classList.toggle('rotate-180')
    })
})
</script>

@if($hasAccessAll)
{{-- COMBOBOX PENGIRIM: fokus/ketik -> daftar nama muncul & terfilter --}}
<script>
(function () {
    const data = @json(collect($pelangganList)->map(fn ($p) => ['id' => $p->id_pelanggan, 'name' => $p->nama_pelanggan])->values());

    const box  = document.getElementById('pengirimBox');
    const txt  = document.getElementById('pengirimText');
    const hid  = document.getElementById('pengirimId');
    const list = document.getElementById('pengirimList');
    const form = document.getElementById('filterForm');
    if (!box || !txt || !hid || !list) return;

    const esc = (t) => String(t ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    function render(q) {
        const qq = (q || '').trim().toLowerCase();
        const items = data.filter(p => !qq || String(p.name).toLowerCase().includes(qq));
        const rows = items.map(p =>
            `<div data-id="${esc(p.id)}" data-name="${esc(p.name)}"
                  class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50">${esc(p.name)}</div>`).join('');
        list.innerHTML =
            `<div data-id="all" data-name=""
                  class="px-3 py-2 text-sm text-gray-500 cursor-pointer hover:bg-gray-50 border-b">Semua Pengirim</div>`
            + (rows || '<div class="px-3 py-2 text-sm text-gray-400">Nama tidak ditemukan</div>');
        list.classList.remove('hidden');
    }

    txt.addEventListener('focus', () => render(''));          // klik kolom -> semua nama muncul
    txt.addEventListener('input', () => {                     // ketik -> terfilter
        hid.value = 'all';
        render(txt.value);
    });

    list.addEventListener('mousedown', (e) => {
        const el = e.target.closest('[data-id]');
        if (!el) return;
        e.preventDefault();
        hid.value = el.dataset.id;
        txt.value = el.dataset.id === 'all' ? '' : el.dataset.name;
        list.classList.add('hidden');
    });

    document.addEventListener('click', (e) => {
        if (!box.contains(e.target)) list.classList.add('hidden');
    });

    // Kalau diketik persis sama dengan satu nama lalu Apply, tetap terpilih
    form?.addEventListener('submit', () => {
        const v = txt.value.trim().toLowerCase();
        if (hid.value === 'all' && v) {
            const hit = data.find(p => String(p.name).toLowerCase() === v);
            if (hit) hid.value = hit.id;
        }
    });
})();
</script>
@endif

{{-- PERIODE: kalender rentang + pilih seluruh bulan / seluruh tahun (flatpickr) --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/l10n/id.min.js"></script>
<script>
(function () {
    const input = document.getElementById('dateRange');
    const from  = document.getElementById('dateFrom');
    const to    = document.getElementById('dateTo');
    const clear = document.getElementById('dateRangeClear');
    if (!input || !from || !to || typeof flatpickr === 'undefined') return;

    const BULAN = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const pad = (n) => String(n).padStart(2, '0');
    const ymd = (d) => d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    const tgl = (d) => d.getDate() + ' ' + BULAN[d.getMonth()].slice(0, 3) + ' ' + d.getFullYear();

    // teks yang tampil di input: "Oktober 2026", "Tahun 2026", atau "1 Okt 2026 – 6 Okt 2026"
    function label(sel) {
        if (!sel.length) return '';
        const a = sel[0], b = sel[1] || sel[0];
        if (a.getDate() === 1 && a.getFullYear() === b.getFullYear()) {
            const lastDay = new Date(b.getFullYear(), b.getMonth() + 1, 0).getDate();
            if (a.getMonth() === 0 && b.getMonth() === 11 && b.getDate() === 31) return 'Tahun ' + a.getFullYear();
            if (a.getMonth() === b.getMonth() && b.getDate() === lastDay) return BULAN[a.getMonth()] + ' ' + a.getFullYear();
        }
        return a.getTime() === b.getTime() ? tgl(a) : tgl(a) + ' – ' + tgl(b);
    }

    const initial = [from.value, to.value].filter(Boolean);
    let btnMonth, btnYear;

    function refreshBar(inst) {
        if (!btnMonth) return;
        btnMonth.textContent = 'Pilih seluruh ' + BULAN[inst.currentMonth] + ' ' + inst.currentYear;
        btnYear.textContent  = 'Pilih seluruh tahun ' + inst.currentYear;
    }

    const fp = flatpickr(input, {
        mode: 'range',
        dateFormat: 'Y-m-d',
        altInput: true,
        altInputClass: 'w-full h-10 px-3 pr-9 text-sm bg-white border border-gray-300 rounded-lg cursor-pointer focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
        locale: (flatpickr.l10ns && flatpickr.l10ns.id) ? 'id' : 'default',
        defaultDate: initial.length === 1 ? [initial[0], initial[0]] : initial,
        showMonths: 1,

        onReady: (sel, str, inst) => {
            // bar pintasan di bawah kalender
            const bar = document.createElement('div');
            bar.className = 'flex flex-col gap-2 p-2 border-t border-gray-100';
            const mk = (fn) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.className = 'w-full h-9 text-xs font-semibold text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition';
                b.addEventListener('click', fn);
                return b;
            };
            btnMonth = mk(() => {
                inst.setDate([new Date(inst.currentYear, inst.currentMonth, 1),
                              new Date(inst.currentYear, inst.currentMonth + 1, 0)], true);
                inst.close();
            });
            btnYear = mk(() => {
                inst.setDate([new Date(inst.currentYear, 0, 1),
                              new Date(inst.currentYear, 11, 31)], true);
                inst.close();
            });
            bar.append(btnMonth, btnYear);
            inst.calendarContainer.appendChild(bar);
            refreshBar(inst);
            if (inst.altInput) inst.altInput.value = label(sel);
            clear.classList.toggle('hidden', !sel.length);
        },
        onOpen:        (sel, str, inst) => refreshBar(inst),
        onMonthChange: (sel, str, inst) => refreshBar(inst),
        onYearChange:  (sel, str, inst) => refreshBar(inst),

        onValueUpdate: (sel, str, inst) => {
            if (inst.altInput) inst.altInput.value = label(sel);
        },
        onChange: (sel) => {
            if (sel.length === 2) {
                from.value = ymd(sel[0]);
                to.value   = ymd(sel[1]);
            } else if (sel.length === 0) {
                from.value = to.value = '';
            }
            clear.classList.toggle('hidden', sel.length === 0);
        },
        onClose: (sel, str, inst) => {
            // hanya 1 tanggal dipilih -> dianggap 1 hari
            if (sel.length === 1) {
                inst.setDate([sel[0], sel[0]], true);
            }
        }
    });

    clear.addEventListener('click', () => {
        fp.clear();
        from.value = to.value = '';
        clear.classList.add('hidden');
    });
})();
</script>
@endsection