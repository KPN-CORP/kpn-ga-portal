@extends('layouts.app_apartemen_sidebar')

@section('content')
<div class="p-4 md:p-6">

    @if(session('success'))
    <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg">{{ session('success') }}</div>
    @endif
    @if(session('error'))
    <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">{{ session('error') }}</div>
    @endif

    {{-- BREADCRUMB --}}
    <div class="flex items-center text-sm text-gray-600 mb-4">
        <a href="{{ route('apartemen.user.daftar') }}" class="hover:text-blue-600">Daftar Apartemen</a>
        <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
        <span class="font-medium text-gray-800">{{ $apartemen->nama_apartemen }}</span>
    </div>

    {{-- APARTEMEN INFO --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6">
        <div class="p-4 md:p-6">
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                <div class="flex-1">
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900">{{ $apartemen->nama_apartemen }}</h1>
                    <p class="text-sm text-gray-600 mt-1">{{ $apartemen->alamat ?? '-' }}</p>

                    <div class="mt-4 grid grid-cols-2 md:grid-cols-3 gap-4">
                        <div>
                            <label class="text-xs text-gray-500">Penanggung Jawab</label>
                            <p class="text-sm font-medium text-gray-900">{{ $apartemen->penanggung_jawab ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Kontak Darurat</label>
                            <p class="text-sm font-medium text-gray-900">{{ $apartemen->kontak_darurat ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500">Telepon</label>
                            <p class="text-sm font-medium text-gray-900">{{ $apartemen->telepon ?? '-' }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-lg p-4 min-w-[220px]">
                    <div class="text-center mb-2">
                        <label class="text-xs text-gray-500">
                            Unit untuk {{ $bisnisUnit->nama_bisnis_unit ?? 'Bisnis Unit Anda' }}
                        </label>
                    </div>
                    <div class="text-center text-2xl font-bold text-gray-900">{{ $units->total() }}</div>
                    <div class="text-center text-xs text-gray-500">unit ditemukan</div>
                </div>
            </div>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6 p-4">
        <form action="{{ route('apartemen.user.daftar.detail', $apartemen->id) }}" method="GET"
              class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" name="search" value="{{ request('search') }}"
                       class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-full"
                       placeholder="Cari nomor unit...">
            </div>
            <select name="status" onchange="this.form.submit()"
                    class="border border-gray-300 rounded-lg text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Status</option>
                <option value="READY" {{ request('status') == 'READY' ? 'selected' : '' }}>Tersedia</option>
                <option value="TERISI" {{ request('status') == 'TERISI' ? 'selected' : '' }}>Terisi</option>
                <option value="MAINTENANCE" {{ request('status') == 'MAINTENANCE' ? 'selected' : '' }}>Maintenance</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                Cari
            </button>
        </form>
    </div>

    {{-- DAFTAR UNIT --}}
    @if($units->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @foreach($units as $unit)
        @php
            $penghuniAktif = $unit->activeAssigns->flatMap(fn($assign) => $assign->penghuniAktif);
        @endphp
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden hover:shadow-md transition-all duration-200">

            {{-- FOTO / GAMBAR 360 --}}
            <div class="relative h-44 bg-gray-100">
                @if($unit->gambar_360)
                    <img src="{{ Storage::url($unit->gambar_360) }}"
                         alt="Foto Unit {{ $unit->nomor_unit }}"
                         class="w-full h-full object-cover">
                    <button onclick="open360Modal('{{ Storage::url($unit->gambar_360) }}', '{{ $unit->nomor_unit }}')"
                            class="absolute bottom-2 right-2 bg-black/60 hover:bg-black/80 text-white text-xs px-2.5 py-1.5 rounded-md flex items-center gap-1 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12a9 9 0 1018 0 9 9 0 00-18 0z M12 8v4l3 3"/></svg>
                        Lihat 360°
                    </button>
                @else
                    <div class="w-full h-full flex items-center justify-center text-gray-300">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14M4 6h16v14H4V6z" />
                        </svg>
                    </div>
                @endif

                <span class="absolute top-2 left-2 px-2 py-1 rounded-full text-xs font-medium
                    @switch($unit->status)
                        @case('READY') bg-green-100 text-green-800 @break
                        @case('TERISI') bg-blue-100 text-blue-800 @break
                        @case('MAINTENANCE') bg-yellow-100 text-yellow-800 @break
                    @endswitch
                ">
                    {{ $unit->status_label }}
                </span>
            </div>

            {{-- INFO UNIT --}}
            <div class="p-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900">Unit {{ $unit->nomor_unit }}</h3>
                    <span class="text-xs text-gray-500">Kapasitas {{ $unit->kapasitas }} orang</span>
                </div>
                @if($unit->bisnisUnit)
                <p class="text-xs text-gray-500 mt-0.5">{{ $unit->bisnisUnit->nama_bisnis_unit }}</p>
                @endif

                {{-- PENGHUNI --}}
                <div class="mt-4 pt-3 border-t border-gray-100">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-gray-700 uppercase tracking-wide">Penghuni Saat Ini</span>
                        <span class="text-xs text-gray-500">{{ $penghuniAktif->count() }} orang</span>
                    </div>

                    @if($penghuniAktif->count() > 0)
                    <div class="space-y-2">
                        @foreach($penghuniAktif as $penghuni)
                        <div class="flex items-center bg-gray-50 border border-gray-200 rounded-lg p-2.5">
                            <div class="w-7 h-7 bg-blue-600 rounded-full flex items-center justify-center mr-2.5 flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $penghuni->nama }}</p>
                                <p class="text-xs text-gray-500 truncate">
                                    {{ $penghuni->id_karyawan }}
                                    @if($penghuni->unit_kerja) &middot; {{ $penghuni->unit_kerja }} @endif
                                </p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-4 text-xs text-gray-400 bg-gray-50 rounded-lg border border-dashed border-gray-200">
                        Belum ada penghuni aktif di unit ini
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- PAGINATION --}}
    <div class="flex flex-col sm:flex-row items-center justify-between px-1 py-4 mt-4 gap-2">
        <div class="text-xs md:text-sm text-gray-700 text-center sm:text-left">
            <span class="font-medium">{{ $units->firstItem() }}</span> -
            <span class="font-medium">{{ $units->lastItem() }}</span> dari
            <span class="font-medium">{{ $units->total() }}</span>
        </div>
        <div class="flex space-x-1 md:space-x-2">
            @if($units->previousPageUrl())
                <a href="{{ $units->previousPageUrl() }}"
                   class="px-2 md:px-3 py-1.5 md:py-2 border border-gray-300 rounded-md text-xs md:text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    ← Prev
                </a>
            @endif
            @if($units->nextPageUrl())
                <a href="{{ $units->nextPageUrl() }}"
                   class="px-2 md:px-3 py-1.5 md:py-2 border border-gray-300 rounded-md text-xs md:text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                    Next →
                </a>
            @endif
        </div>
    </div>
    @else
    {{-- EMPTY STATE --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="text-center py-12">
            <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-1">Tidak ada unit ditemukan</h3>
            <p class="text-gray-500 text-sm max-w-md mx-auto">
                Tidak ada unit di apartemen ini untuk bisnis unit Anda, atau sesuai filter yang dipilih.
            </p>
            <a href="{{ route('apartemen.user.daftar.detail', $apartemen->id) }}"
               class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                Reset Filter
            </a>
        </div>
    </div>
    @endif
</div>
@endsection
