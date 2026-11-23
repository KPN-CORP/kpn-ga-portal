@extends('layouts.app_apartadmin_sidebar')
@section('content')
@php
    // Rentang bulan berjalan (default) tidak dihitung sebagai filter aktif.
    $nFilter = collect(['status', 'request_user', 'unit_kerja', 'search'])->filter(fn($k) => request()->filled($k))->count() + (!$isDefaultRange ? 1 : 0);
    $initials = function ($name) {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $out = '';
        foreach (array_slice($parts, 0, 2) as $w) { $out .= mb_strtoupper(mb_substr($w, 0, 1)); }
        return $out ?: '?';
    };
    $tint = function ($name) { $h = crc32((string) $name) % 360; return "background:hsl($h 70% 93%);color:hsl($h 50% 30%)"; };
@endphp
<div class="w-full">

    <x-apartemen.page-header title="Laporan Apartemen" subtitle="Penempatan penghuni pada periode yang dipilih: jadwal beririsan dengan periode, atau sudah lewat jadwal tapi belum check-out">
        <a href="{{ route('apartemen.admin.report.export', request()->query()) }}" class="ap-btn ap-btn-lg ap-btn-green w-full sm:w-auto"><i class="fas fa-file-excel"></i>Export Excel</a>
    </x-apartemen.page-header>

    {{-- RINGKASAN: dihitung dari SEMUA data yang cocok filter (bukan hanya halaman ini), sama dengan isi export --}}
    <div class="ap-kpis c5">
        <x-apartemen.stat-card :label="'Total penempatan · ' . $periodeLabel" :value="$stats['total']" color="blue"><i class="fas fa-list"></i></x-apartemen.stat-card>
        <x-apartemen.stat-card label="Selesai" :value="$stats['selesai']" color="gray"><i class="fas fa-flag-checkered"></i></x-apartemen.stat-card>
        <x-apartemen.stat-card label="Aktif (menginap)" :value="$stats['aktif']" color="green"><i class="fas fa-bed"></i></x-apartemen.stat-card>
        <x-apartemen.stat-card label="Belum aktif" :value="$stats['belum_aktif']" color="yellow"><i class="far fa-clock"></i></x-apartemen.stat-card>
        <x-apartemen.stat-card label="Belum check-out" :value="$stats['belum_checkout']" color="red"><i class="fas fa-door-open"></i></x-apartemen.stat-card>
    </div>

    {{-- FILTER --}}
    <form method="GET" action="{{ route('apartemen.admin.report') }}" class="ap-card ap-filters p-3 md:p-4 mb-4">
        <div class="flex gap-2 lg:hidden mb-2.5">
            <div class="flex-1 text-sm text-gray-600 self-center">Filter laporan</div>
            <button type="button" class="ap-fbtn" aria-expanded="false" onclick="const f=this.closest('form');f.classList.toggle('open');this.setAttribute('aria-expanded',f.classList.contains('open'))">
                <i class="fas fa-sliders-h"></i>Filter @if($nFilter)<span class="n">{{ $nFilter }}</span>@endif
            </button>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5">
            <div class="ap-fld sm:col-span-2">
                <label class="ap-label" for="r_q">Cari</label>
                <div class="ap-search"><i class="fas fa-search"></i><input id="r_q" type="search" name="search" value="{{ request('search') }}" placeholder="Nama, NIK, atau unit kerja" class="ap-input"></div>
            </div>
            <div class="ap-fld"><label class="ap-label" for="r_tm">Dari tanggal</label><input id="r_tm" type="date" name="tanggal_mulai" value="{{ $rangeMulai }}" class="ap-input"></div>
            <div class="ap-fld"><label class="ap-label" for="r_ts">Sampai tanggal</label><input id="r_ts" type="date" name="tanggal_selesai" value="{{ $rangeSelesai }}" class="ap-input"></div>
            <div class="ap-fld">
                <label class="ap-label" for="r_st">Status</label>
                <select id="r_st" name="status" class="ap-input">
                    <option value="">Semua status</option>
                    <option value="SELESAI" {{ request('status') == 'SELESAI' ? 'selected' : '' }}>Selesai</option>
                    <option value="MENGINAP" {{ request('status') == 'MENGINAP' ? 'selected' : '' }}>Aktif (menginap)</option>
                    <option value="BELUM_AKTIF" {{ request('status') == 'BELUM_AKTIF' ? 'selected' : '' }}>Belum aktif</option>
                    <option value="BELUM_CHECKOUT" {{ request('status') == 'BELUM_CHECKOUT' ? 'selected' : '' }}>Belum check-out</option>
                    <!-- <option value="AKTIF" {{ request('status') == 'AKTIF' ? 'selected' : '' }}>Masih tercatat aktif (semua)</option> -->
                </select>
            </div>
            <div class="ap-fld">
                <label class="ap-label" for="r_ru">Pemohon</label>
                <select id="r_ru" name="request_user" class="ap-input">
                    <option value="">Semua pemohon</option>
                    @foreach($requesters as $r)
                        <option value="{{ $r->id }}" {{ (string) request('request_user') === (string) $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ap-fld">
                <label class="ap-label" for="r_uk">Unit kerja</label>
                <select id="r_uk" name="unit_kerja" class="ap-input">
                    <option value="">Semua unit kerja</option>
                    @foreach($unitKerjaList as $uk)
                        <option value="{{ $uk }}" {{ request('unit_kerja') === $uk ? 'selected' : '' }}>{{ $uk }}</option>
                    @endforeach
                </select>
            </div>
            <div class="ap-fld lg:!flex items-end gap-2">
                <button type="submit" class="ap-btn ap-btn-lg ap-btn-blue flex-1 w-full"><i class="fas fa-filter"></i>Terapkan</button>
                <a href="{{ route('apartemen.admin.report') }}" class="ap-btn ap-btn-lg ap-btn-ghost">Reset</a>
            </div>
        </div>
    </form>

    {{-- DAFTAR --}}
    <div class="ap-card overflow-hidden">
        <div class="ap-listhead">
            <h3>Riwayat apartemen</h3>
            <p>@if($histories->total() > 0)<b>{{ $histories->firstItem() }}–{{ $histories->lastItem() }}</b> dari <b>{{ number_format($histories->total()) }}</b> data · {{ $periodeLabel }} @else Tidak ada data @endif</p>
        </div>

        @if($histories->count() > 0)
        <div class="md:overflow-x-auto">
            <table class="ap-table">
                <thead>
                    <tr>
                        <th>Penghuni</th>
                        <th>Pemohon</th>
                        <th>Apartemen &amp; unit</th>
                        <th>Periode</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($histories as $penghuni)
                    @php
                        $assign    = $penghuni->assign;
                        $unit      = $assign->unit;
                        $apartemen = $unit->apartemen ?? null;
                        $pemohon   = $assign->request && $assign->request->user ? $assign->request->user->name : '-';
                        $st        = $penghuni->laporan_status;
                        $periode   = $assign->tanggal_mulai->format('d/m/Y') . ' – ' . $assign->tanggal_selesai->format('d/m/Y');
                        $tanggal   = $assign->created_at ? $assign->created_at->format('d/m/Y H:i') : '-';
                    @endphp
                    <tr class="ap-row {{ $st['key'] === 'belum_checkout' ? 'ap-late' : '' }}">
                        <td class="c-person">
                            <div class="ap-person">
                                <span class="ap-avatar" style="{{ $tint($penghuni->nama) }}">{{ $initials($penghuni->nama) }}</span>
                                <div class="min-w-0">
                                    <div class="ap-name">{{ $penghuni->nama }}</div>
                                    <div class="ap-sub">{{ $penghuni->id_karyawan }}@if($penghuni->unit_kerja) · {{ $penghuni->unit_kerja }}@endif @if($penghuni->gol) · Gol. {{ $penghuni->gol }}@endif</div>
                                </div>
                            </div>
                        </td>
                        <td class="c-pemohon">
                            <div class="ap-cell-ico"><i class="far fa-user ap-ico"></i><div class="text-gray-800"><span class="md:hidden ap-sub">Pemohon: </span>{{ $pemohon }}</div></div>
                        </td>
                        <td class="c-apt">
                            <div class="ap-cell-ico"><i class="fas fa-building ap-ico"></i><div>
                                <div class="ap-strong">{{ $apartemen->nama_apartemen ?? '-' }}</div>
                                <div class="ap-sub">Unit {{ $unit->nomor_unit ?? '-' }}</div>
                            </div></div>
                        </td>
                        <td class="c-period whitespace-nowrap">
                            <div class="ap-cell-ico"><i class="far fa-calendar ap-ico"></i><div class="text-gray-800">{{ $periode }}</div></div>
                        </td>
                        <td class="c-status"><span class="ap-tag t-{{ $st['tone'] }}">{{ $st['text'] }}</span></td>
                        <td class="c-time whitespace-nowrap ap-sub">{{ $tanggal }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <x-apartemen.pager :paginator="$histories" />
        @else
            <x-apartemen.empty-state :title="'Tidak ada penempatan pada ' . $periodeLabel" text="Coba ubah rentang tanggal atau filter lainnya." :resetUrl="$nFilter ? route('apartemen.admin.report') : null" />
        @endif
    </div>
</div>

<style>
    /* Laporan: kolom Pemohon & Dibuat ikut tampil sebagai baris tambahan di HP */
    @media (max-width:767px){
        .ap-table td.c-pemohon{grid-column:1/-1;margin-top:.45rem}
        .ap-table td.c-time{grid-column:1/-1;margin-top:.45rem}
    }

    /* Warna status (sama dengan kartu ringkasan): Selesai abu-abu, Aktif hijau, Belum aktif kuning, Belum check-out merah */
    .ap-table .ap-tag{display:inline-flex;align-items:center;gap:.4rem;padding:.2rem .6rem;border-radius:999px;font-size:.75rem;font-weight:600;line-height:1.2;white-space:nowrap;border:1px solid transparent}
    .ap-table .ap-tag::before{content:"";width:.45rem;height:.45rem;border-radius:50%;background:currentColor;flex:none}
    .ap-table .ap-tag.t-gray{background:#f1f5f9;color:#475569;border-color:#e2e8f0}
    .ap-table .ap-tag.t-green{background:#dcfce7;color:#166534;border-color:#bbf7d0}
    .ap-table .ap-tag.t-yellow{background:#fef9c3;color:#854d0e;border-color:#fde68a}
    .ap-table .ap-tag.t-red{background:#fee2e2;color:#b91c1c;border-color:#fecaca}
    .ap-table .ap-tag.t-blue{background:#dbeafe;color:#1d4ed8;border-color:#bfdbfe}
</style>
@endsection