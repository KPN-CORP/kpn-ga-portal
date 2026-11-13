@extends('layouts.app_apartadmin_sidebar')
@section('content')
@php
    // URL chip: hapus filter tertentu, pertahankan sisanya (dan reset halaman).
    $removeUrl = function (array $keys) {
        return route('apartemen.admin.index', request()->except(array_merge($keys, ['page'])));
    };
    $hasFilter = request()->anyFilled(['search', 'tanggal_mulai', 'tanggal_selesai']);
    $nFilter   = collect(['tanggal_mulai', 'tanggal_selesai'])->filter(fn($k) => request()->filled($k))->count();

    // Avatar inisial dengan warna stabil per nama.
    $initials = function ($name) {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $out = '';
        foreach (array_slice($parts, 0, 2) as $w) { $out .= mb_strtoupper(mb_substr($w, 0, 1)); }
        return $out ?: '?';
    };
    $tint = function ($name) {
        $h = crc32((string) $name) % 360;
        return "background:hsl($h 70% 93%);color:hsl($h 50% 30%)";
    };
@endphp

<div class="w-full">

    @if(session('success'))
        <div class="ap-flash ok" role="status"><i class="fas fa-check-circle mt-0.5"></i><span>{{ session('success') }}</span></div>
    @endif
    @if(session('error'))
        <div class="ap-flash err" role="alert"><i class="fas fa-exclamation-circle mt-0.5"></i><span>{{ session('error') }}</span></div>
    @endif
    @if($errors->any())
        <div class="ap-flash err" role="alert"><i class="fas fa-exclamation-circle mt-0.5"></i>
            <ul class="list-disc pl-4">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <x-apartemen.page-header title="Permintaan Apartemen" subtitle="Review dan proses permintaan pending dari penghuni" />

    {{-- FILTER (satu form: pencarian + tanggal tidak saling menghapus) --}}
    <form action="{{ route('apartemen.admin.index') }}" method="GET" class="ap-card ap-filters p-3 md:p-4 mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-2.5">
            <div class="sm:col-span-2 lg:col-span-6 flex gap-2">
                <div class="ap-search flex-1 min-w-0">
                    <i class="fas fa-search"></i>
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Cari pemohon, penghuni, atau NIK" class="ap-input" aria-label="Cari">
                    <button type="submit" class="ap-search-go">Cari</button>
                </div>
                <button type="button" class="ap-fbtn" aria-expanded="false" onclick="const f=this.closest('form');f.classList.toggle('open');this.setAttribute('aria-expanded',f.classList.contains('open'))">
                    <i class="fas fa-sliders-h"></i>Filter @if($nFilter)<span class="n">{{ $nFilter }}</span>@endif
                </button>
            </div>
            <div class="ap-fld sm:col-span-2 lg:col-span-6"><div class="grid grid-cols-2 gap-2">
                <input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}" class="ap-input" onchange="this.form.submit()" aria-label="Diajukan dari tanggal" title="Diajukan dari tanggal">
                <input type="date" name="tanggal_selesai" value="{{ request('tanggal_selesai') }}" class="ap-input" onchange="this.form.submit()" aria-label="Diajukan sampai tanggal" title="Diajukan sampai tanggal">
            </div></div>
        </div>

        @if($hasFilter)
            <div class="ap-filter-row">
                <span class="text-xs text-gray-500 mr-1">Filter aktif</span>
                @if(request('search'))
                    <a href="{{ $removeUrl(['search']) }}" class="chip">“{{ request('search') }}” <i class="fas fa-times"></i></a>
                @endif
                @if(request('tanggal_mulai') || request('tanggal_selesai'))
                    <a href="{{ $removeUrl(['tanggal_mulai', 'tanggal_selesai']) }}" class="chip">
                        {{ request('tanggal_mulai') ? \Carbon\Carbon::parse(request('tanggal_mulai'))->format('d/m/Y') : '…' }} – {{ request('tanggal_selesai') ? \Carbon\Carbon::parse(request('tanggal_selesai'))->format('d/m/Y') : '…' }}
                        <i class="fas fa-times"></i>
                    </a>
                @endif
                <a href="{{ route('apartemen.admin.index') }}" class="ap-link ml-auto">Reset semua</a>
            </div>
        @endif
    </form>

    {{-- DAFTAR PERMINTAAN PENDING --}}
    <div class="ap-card overflow-hidden">
        <div class="ap-listhead">
            <h3>Daftar permintaan pending</h3>
            <p><b>{{ number_format($requests->total()) }}</b> permintaan perlu direview{{ $hasFilter ? ' (sesuai filter)' : '' }}</p>
        </div>

        @if($requests->count() > 0)
        <div class="md:overflow-x-auto">
            <table class="ap-table">
                <thead>
                    <tr>
                        <th>Pemohon</th>
                        <th>Penghuni</th>
                        <th>Diajukan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($requests as $req)
                    @php
                        $nama      = $req->user->name ?? 'N/A';
                        $jumlah    = $req->penghuni->count();
                        $namaList  = $req->penghuni->take(2)->pluck('nama')->implode(', ');
                    @endphp
                    <tr class="ap-row">
                        <td class="c-person">
                            <div class="ap-person">
                                <span class="ap-avatar" style="{{ $tint($nama) }}">{{ $initials($nama) }}</span>
                                <div class="min-w-0">
                                    <div class="ap-name">{{ $nama }}</div>
                                    <div class="ap-sub">#{{ str_pad($req->id, 6, '0', STR_PAD_LEFT) }}</div>
                                </div>
                            </div>
                        </td>

                        <td class="c-apt">
                            <div class="ap-cell-ico"><i class="fas fa-users ap-ico"></i><div>
                                <div class="ap-strong">{{ $jumlah }} orang</div>
                                @if($jumlah > 0)
                                    <div class="ap-sub">{{ $namaList }}@if($jumlah > 2) <span class="text-gray-400">+{{ $jumlah - 2 }} lainnya</span>@endif</div>
                                @endif
                            </div></div>
                        </td>

                        <td class="c-period">
                            <div class="ap-cell-ico"><i class="far fa-calendar ap-ico"></i><div>
                                <span class="whitespace-nowrap text-gray-800">{{ $req->tanggal_pengajuan->format('d/m/Y') }}</span>
                                <div class="ap-sub" title="{{ $req->created_at->format('d/m/Y H:i:s') }}">{{ $req->created_at->diffForHumans() }}</div>
                            </div></div>
                        </td>

                        <td class="c-status">
                            <span class="ap-tag t-yellow">Pending</span>
                            <div class="ap-sub">Menunggu penempatan</div>
                        </td>

                        <td class="c-act">
                            <div class="ap-actions">
                                <a href="{{ route('apartemen.admin.approve', $req->id) }}" class="ap-btn ap-btn-green"><i class="fas fa-check"></i>Setujui</a>
                                <a href="{{ route('apartemen.admin.detail', $req->id) }}" class="ap-btn ap-btn-ghost"><i class="far fa-eye"></i>Detail</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <x-apartemen.pager :paginator="$requests" />
        @else
            <x-apartemen.empty-state
                :title="$hasFilter ? 'Tidak ada permintaan yang cocok' : 'Tidak ada permintaan pending'"
                :text="$hasFilter ? 'Coba ubah atau reset filter yang dipakai.' : 'Semua permintaan sudah diproses.'"
                :resetUrl="$hasFilter ? route('apartemen.admin.index') : null" />
        @endif
    </div>
</div>
@endsection