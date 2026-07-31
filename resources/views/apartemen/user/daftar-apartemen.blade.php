@extends('layouts.app_apartemen_sidebar')

@section('content')
<div class="p-4 md:p-6">

    {{-- NOTIFICATION --}}
    @if(session('success'))
    <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        {{ session('error') }}
    </div>
    @endif

    {{-- HEADER --}}
    <div class="mb-6 md:mb-8">
        <div class="lg:hidden mb-4">
            <h1 class="text-xl font-bold text-gray-900">Daftar Apartemen</h1>
            <p class="text-gray-700 text-xs mt-1">
                Apartemen &amp; unit yang tersedia untuk bisnis unit Anda
            </p>
        </div>

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-4 md:mb-6">
            <div class="hidden lg:flex items-center space-x-4 flex-1">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Daftar Apartemen</h1>
                    <p class="text-gray-700 text-sm mt-1">
                        Apartemen &amp; unit yang tersedia untuk bisnis unit Anda
                    </p>
                </div>
            </div>

            {{-- Search --}}
            <div class="w-full lg:w-auto lg:mx-4 lg:flex-1 lg:max-w-md order-first lg:order-none">
                <form action="{{ route('apartemen.user.daftar') }}" method="GET">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}"
                               class="pl-10 pr-4 py-2 md:py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full"
                               placeholder="Cari nama apartemen...">
                    </div>
                </form>
            </div>
        </div>

        {{-- BISNIS UNIT BADGE --}}
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-sm font-medium bg-blue-50 text-blue-700 border border-blue-200">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                Bisnis Unit: {{ $bisnisUnit->nama_bisnis_unit ?? 'Semua Bisnis Unit' }}
            </span>
            @if(!$bisnisUnit)
            <span class="text-xs text-gray-500">
                (bisnis unit Anda belum terdeteksi — menampilkan semua apartemen)
            </span>
            @endif
        </div>
    </div>

    {{-- LIST APARTEMEN --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="p-4 md:p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-900">Apartemen Tersedia</h2>
                <div class="text-sm text-gray-600">Total: {{ $apartemen->total() }} apartemen</div>
            </div>
        </div>

        <div class="p-4 md:p-6">
            @if($apartemen->count() > 0)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($apartemen as $item)
                @php
                    // Ambil unit pertama yang punya foto/gambar 360 sebagai foto sampul
                    $coverUnit = $item->units->firstWhere('gambar_360', '!=', null);
                @endphp
                <a href="{{ route('apartemen.user.daftar.detail', $item->id) }}"
                   class="group border border-gray-200 rounded-xl overflow-hidden hover:shadow-md transition-all duration-200 hover:border-blue-300 bg-white flex flex-col">

                    {{-- FOTO --}}
                    <div class="relative h-40 bg-gray-100 overflow-hidden">
                        @if($coverUnit)
                            <img src="{{ Storage::url($coverUnit->gambar_360) }}"
                                 alt="Foto {{ $item->nama_apartemen }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-gray-300">
                                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </div>
                        @endif
                        <span class="absolute top-2 right-2 bg-white/90 text-gray-700 text-xs font-medium px-2 py-1 rounded-md shadow-sm">
                            {{ $item->units_count }} unit
                        </span>
                    </div>

                    {{-- INFO --}}
                    <div class="p-4 flex-1 flex flex-col">
                        <h3 class="font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">
                            {{ $item->nama_apartemen }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $item->alamat ?? '-' }}</p>

                        <div class="mt-3 flex items-center gap-3 text-xs">
                            <span class="inline-flex items-center text-green-700 bg-green-50 border border-green-200 px-2 py-1 rounded-md font-medium">
                                {{ $item->units_ready }} Tersedia
                            </span>
                            <span class="inline-flex items-center text-blue-700 bg-blue-50 border border-blue-200 px-2 py-1 rounded-md font-medium">
                                {{ $item->units_terisi }} Terisi
                            </span>
                        </div>

                        <div class="mt-4 pt-3 border-t border-gray-100 flex items-center justify-between">
                            <span class="text-sm text-blue-600 font-medium group-hover:underline">Lihat Detail &amp; Penghuni</span>
                            <svg class="w-4 h-4 text-blue-600 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>

            {{-- PAGINATION --}}
            <div class="flex flex-col sm:flex-row items-center justify-between px-1 py-4 mt-4 border-t border-gray-200 gap-2">
                <div class="text-xs md:text-sm text-gray-700 text-center sm:text-left">
                    <span class="font-medium">{{ $apartemen->firstItem() }}</span> -
                    <span class="font-medium">{{ $apartemen->lastItem() }}</span> dari
                    <span class="font-medium">{{ $apartemen->total() }}</span>
                </div>
                <div class="flex space-x-1 md:space-x-2">
                    @if($apartemen->previousPageUrl())
                        <a href="{{ $apartemen->previousPageUrl() }}"
                           class="px-2 md:px-3 py-1.5 md:py-2 border border-gray-300 rounded-md text-xs md:text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            ← Prev
                        </a>
                    @endif
                    @if($apartemen->nextPageUrl())
                        <a href="{{ $apartemen->nextPageUrl() }}"
                           class="px-2 md:px-3 py-1.5 md:py-2 border border-gray-300 rounded-md text-xs md:text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                            Next →
                        </a>
                    @endif
                </div>
            </div>
            @else
            {{-- EMPTY STATE --}}
            <div class="text-center py-12">
                <div class="flex flex-col items-center justify-center text-gray-400">
                    <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4 border border-gray-200">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">Belum ada apartemen ditemukan</h3>
                    <p class="text-gray-500 mb-2 max-w-md mx-auto text-sm">
                        @if(request()->filled('search'))
                            Tidak ada apartemen yang cocok dengan pencarian Anda.
                        @else
                            Belum ada unit apartemen yang tersedia untuk bisnis unit Anda saat ini.
                        @endif
                    </p>
                    @if(request()->filled('search'))
                    <a href="{{ route('apartemen.user.daftar') }}"
                       class="mt-2 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                        Reset Pencarian
                    </a>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
