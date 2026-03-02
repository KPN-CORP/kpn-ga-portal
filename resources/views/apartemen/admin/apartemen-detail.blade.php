@extends('layouts.app-sidebar')

@section('content')
<div class="p-4 md:p-6">

    {{-- NOTIFICATION --}}
    @if(session('success'))
    <div class="mb-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center justify-between">
        <span>{{ session('success') }}</span>
        <button onclick="this.parentElement.remove()" class="text-green-700 hover:text-green-900">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        {{ session('error') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        <ul class="list-disc pl-4">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- HEADER --}}
    <div class="mb-6 md:mb-8">
        {{-- Mobile: Judul di atas semua --}}
        <div class="lg:hidden mb-4">
            <h1 class="text-xl font-bold text-gray-800">Detail Apartemen</h1>
            <p class="text-gray-600 text-xs mt-1">Informasi detail apartemen dan unit</p>
        </div>

        {{-- ACTION BAR --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-4 md:mb-6">
            {{-- Desktop: Judul + Search --}}
            <div class="hidden lg:flex items-center space-x-4 flex-1">
                {{-- Judul Halaman --}}
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Detail Apartemen</h1>
                    <p class="text-gray-600 text-sm mt-1">Informasi detail apartemen dan unit</p>
                </div>
            </div>

            {{-- Search Bar --}}
            <div class="w-full lg:w-auto lg:mx-4 lg:flex-1 lg:max-w-md order-first lg:order-none">
                <div class="relative">
                    <form action="{{ route('apartemen.admin.apartemen.detail', $apartemen->id) }}" method="GET">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               class="pl-10 pr-4 py-2 md:py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full" 
                               placeholder="Cari unit...">
                    </form>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="flex flex-wrap items-center gap-2 lg:gap-3 w-full lg:w-auto">
                @php
                    $pendingCount = \App\Models\Apartemen\ApartemenRequest::where('status', 'PENDING')->count();
                    $unitCount = \App\Models\Apartemen\ApartemenUnit::count();
                    $penghuniCount = \App\Models\Apartemen\ApartemenPenghuni::whereHas('assign', function($q) {
                        $q->where('status', 'AKTIF');
                    })->count();
                @endphp
                
                {{-- Permintaan --}}
                <a href="{{ route('apartemen.admin.index') }}" 
                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <span class="font-medium text-gray-700 text-sm truncate">Permintaan</span>
                    @if($pendingCount > 0)
                    <span class="ml-1 md:ml-2 bg-blue-100 text-blue-800 text-xs px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap">{{ $pendingCount }}</span>
                    @endif
                </a>

                {{-- Unit --}}
                <a href="{{ route('apartemen.admin.apartemen') }}"
                   class="inline-flex items-center px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span class="font-medium text-blue-700 text-sm truncate">Unit</span>
                    @if($unitCount > 0)
                    <span class="ml-1 md:ml-2 bg-blue-100 text-blue-800 text-xs px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap">{{ $unitCount }}</span>
                    @endif
                </a>

                {{-- Penghuni --}}
                <a href="{{ route('apartemen.admin.monitoring') }}"
                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-10A2.5 2.5 0 1121 10.5 2.5 2.5 0 0118.5 8z" />
                    </svg>
                    <span class="font-medium text-gray-700 text-sm truncate">Penghuni</span>
                    @if($penghuniCount > 0)
                    <span class="ml-1 md:ml-2 bg-blue-100 text-blue-800 text-xs px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap">{{ $penghuniCount }}</span>
                    @endif
                </a>

                {{-- Riwayat Button --}}
                <a href="{{ route('apartemen.admin.history') }}"
                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium text-gray-700 text-sm truncate">Riwayat</span>
                </a>

                {{-- Scan Barcode Button --}}
                <a href="{{ route('apartemen.admin.scan') }}" 
                   class="inline-flex items-center px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    <span class="font-medium text-sm truncate">Scan QR</span>
                </a>
            </div>
        </div>

        {{-- Breadcrumb --}}
        <div class="flex items-center text-sm text-gray-600 mb-4">
            <a href="{{ route('apartemen.admin.apartemen') }}" class="hover:text-blue-600">Apartemen</a>
            <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            <span class="font-medium text-gray-800">{{ $apartemen->nama_apartemen }}</span>
        </div>
    </div>

    {{-- APARTEMEN INFO CARD --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6 hover:shadow-md transition-shadow">
        <div class="p-6">
            <div class="flex flex-col md:flex-row md:items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800">{{ $apartemen->nama_apartemen }}</h2>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label class="text-xs text-gray-500 uppercase tracking-wider">Alamat</label>
                            <p class="font-medium text-gray-900 mt-1">{{ $apartemen->alamat ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 uppercase tracking-wider">Penanggung Jawab</label>
                            <p class="font-medium text-gray-900 mt-1">{{ $apartemen->penanggung_jawab ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 uppercase tracking-wider">Telepon</label>
                            <p class="font-medium text-gray-900 mt-1">{{ $apartemen->telepon ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="text-xs text-gray-500 uppercase tracking-wider">Email</label>
                            <p class="font-medium text-gray-900 mt-1 break-all">{{ $apartemen->email ?? '-' }}</p>
                        </div>
                    </div>

                    @if($apartemen->kontak_darurat)
                    <div class="mt-3 pt-3 border-t border-gray-100">
                        <label class="text-xs text-gray-500 uppercase tracking-wider">Kontak Darurat</label>
                        <p class="font-medium text-gray-900 mt-1">{{ $apartemen->kontak_darurat }}</p>
                    </div>
                    @endif
                </div>
                
                {{-- Status Ringkasan --}}
                <div class="bg-gradient-to-br from-gray-50 to-blue-50 rounded-lg p-4 min-w-[200px] border border-blue-100">
                    <div class="text-center mb-3">
                        <label class="text-sm font-medium text-gray-700">Status Unit</label>
                    </div>
                    <div class="grid grid-cols-3 gap-2">
                        <div class="text-center p-2 bg-white rounded-lg shadow-sm">
                            <div class="text-xl font-bold text-gray-900">{{ $apartemen->units_count ?? 0 }}</div>
                            <div class="text-xs text-gray-500">Total</div>
                        </div>
                        <div class="text-center p-2 bg-green-50 rounded-lg shadow-sm border border-green-100">
                            <div class="text-xl font-bold text-green-600">{{ $apartemen->units_ready ?? 0 }}</div>
                            <div class="text-xs text-green-600">Tersedia</div>
                        </div>
                        <div class="text-center p-2 bg-blue-50 rounded-lg shadow-sm border border-blue-100">
                            <div class="text-xl font-bold text-blue-600">{{ $apartemen->units_terisi ?? 0 }}</div>
                            <div class="text-xs text-blue-600">Terisi</div>
                        </div>
                    </div>
                    @if(($apartemen->units_maintenance ?? 0) > 0)
                    <div class="mt-2 text-center text-xs text-yellow-600 bg-yellow-50 py-1 rounded">
                        {{ $apartemen->units_maintenance }} unit dalam maintenance
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- UNIT LIST CARD --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        {{-- Table Header --}}
        <div class="px-4 md:px-6 py-4 border-b border-gray-200 bg-gray-50">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h3 class="text-lg font-semibold text-gray-800">Daftar Unit</h3>
                <div class="flex items-center gap-3">
                    <div class="text-sm text-gray-600">
                        Total: <span class="font-bold">{{ $units->total() }}</span> unit
                    </div>
                    <button onclick="toggleAddUnitForm()"
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all hover:shadow-md flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span class="hidden sm:inline">Tambah Unit</span>
                        <span class="sm:hidden">Tambah</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- Add Unit Form (Hidden by default) --}}
        <div id="addUnitForm" class="hidden p-6 border-b border-gray-200 bg-blue-50">
            <div class="flex items-center gap-2 mb-4">
                <div class="p-1.5 bg-blue-100 rounded-lg">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </div>
                <h4 class="font-medium text-gray-800">Tambah Unit Baru</h4>
            </div>
            
            <form action="{{ route('apartemen.admin.unit.store') }}" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                @csrf
                <input type="hidden" name="apartemen_id" value="{{ $apartemen->id }}">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Nomor Unit <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="nomor_unit" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Contoh: A101">
                    @error('nomor_unit')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Kapasitas <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="kapasitas" required min="1" max="10"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Jumlah orang" value="2">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Status <span class="text-red-500">*</span>
                    </label>
                    <select name="status" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="READY">Tersedia</option>
                        <option value="MAINTENANCE">Maintenance</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Catatan (opsional)
                    </label>
                    <input type="text" name="catatan" 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Catatan unit">
                </div>
                
                <div class="flex items-end gap-2">
                    <button type="submit" 
                            class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-all hover:shadow-md">
                        Simpan
                    </button>
                    <button type="button" onclick="toggleAddUnitForm()"
                            class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-all">
                        Batal
                    </button>
                </div>
            </form>
        </div>

        {{-- Table Content --}}
        <div class="p-4 md:p-6">
            @if($units->count() > 0)
            <div class="overflow-x-auto -mx-4 md:mx-0">
                <div class="inline-block min-w-full align-middle">
                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nomor Unit</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kapasitas</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Penghuni Aktif</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">QR Code</th>
                                    <th class="px-4 md:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($units as $unit)
                                @php
                                    $activePenghuni = $unit->assigns->flatMap->penghuni->where('status', 'AKTIF');
                                    $activeCount = $activePenghuni->count();
                                    $kodeUnik = $unit->kodeUnik;
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900">Unit {{ $unit->nomor_unit }}</div>
                                        @if($unit->catatan)
                                        <div class="text-xs text-gray-500 mt-1">{{ Str::limit($unit->catatan, 30) }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">{{ $unit->kapasitas }} orang</div>
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                        @switch($unit->status)
                                            @case('READY')
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    Tersedia
                                                </span>
                                                @break
                                            @case('TERISI')
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-10A2.5 2.5 0 1121 10.5 2.5 2.5 0 0118.5 8z" />
                                                    </svg>
                                                    Terisi
                                                </span>
                                                @break
                                            @case('MAINTENANCE')
                                                <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                    </svg>
                                                    Maintenance
                                                </span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                        @if($activeCount > 0)
                                            <div class="text-sm font-medium text-gray-900">{{ $activeCount }} orang</div>
                                            <div class="text-xs text-gray-500 mt-1">
                                                @foreach($activePenghuni->take(2) as $p)
                                                {{ $p->nama }}@if(!$loop->last), @endif
                                                @endforeach
                                                @if($activeCount > 2)
                                                <span class="text-gray-400">+{{ $activeCount - 2 }} lainnya</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-sm text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap">
                                        @if($kodeUnik && $kodeUnik->qr_path)
                                            <button onclick="showQRCode('{{ $kodeUnik->kode_unik }}', '{{ Storage::url($kodeUnik->qr_path) }}', '{{ $unit->nomor_unit }}')"
                                                    class="text-purple-600 hover:text-purple-800 text-sm font-medium flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                                </svg>
                                                Lihat QR
                                            </button>
                                        @else
                                            <button onclick="generateQR({{ $unit->id }})"
                                                    class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                                Generate QR
                                            </button>
                                        @endif
                                    </td>
                                    <td class="px-4 md:px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                                            @if($unit->status == 'READY' || $unit->status == 'MAINTENANCE')
                                            <button onclick="toggleMaintenance({{ $unit->id }}, '{{ $unit->status }}')"
                                                    class="text-yellow-600 hover:text-yellow-800 transition-colors text-sm">
                                                @if($unit->status == 'READY')
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                                    </svg>
                                                    Maintenance
                                                </span>
                                                @else
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                    Tersedia
                                                </span>
                                                @endif
                                            </button>
                                            @endif
                                            
                                            @if($unit->status == 'READY' && $activeCount == 0)
                                            <button onclick="deleteUnit({{ $unit->id }}, '{{ $unit->nomor_unit }}')"
                                                    class="text-red-600 hover:text-red-800 transition-colors text-sm flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                                Hapus
                                            </button>
                                            @endif

                                            <a href="{{ route('apartemen.detail', $unit->id) }}" 
                                               class="text-blue-600 hover:text-blue-800 text-sm flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                                Detail
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- PAGINATION --}}
            <div class="mt-6">
                {{ $units->links() }}
            </div>
            @else
            {{-- EMPTY STATE --}}
            <div class="text-center py-12">
                <div class="mx-auto w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Belum ada unit</h3>
                <p class="text-gray-500 max-w-md mx-auto mb-6">
                    Tambahkan unit baru untuk apartemen {{ $apartemen->nama_apartemen }}.
                </p>
                <button onclick="toggleAddUnitForm()"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-all hover:shadow-md">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    + Tambah Unit Pertama
                </button>
            </div>
            @endif
        </div>
    </div>

</div>

{{-- MODAL: Maintenance --}}
<div id="maintenanceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden transition-opacity">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white transform transition-all">
        <div class="mt-3">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Update Status Unit</h3>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <form id="maintenanceForm" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" id="unitId" name="unit_id">
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status *</label>
                    <select name="status" id="unitStatus" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors">
                        <option value="READY">Tersedia</option>
                        <option value="MAINTENANCE">Maintenance</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan (Opsional)</label>
                    <textarea name="catatan" rows="3" 
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-colors"
                              placeholder="Catatan maintenance..."></textarea>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeModal()"
                            class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium transition-colors">
                        Batal
                    </button>
                    <button type="submit" id="submitMaintenanceBtn"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL: QR Code --}}
<div id="qrModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50 hidden transition-opacity">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white transform transition-all">
        <div class="text-center">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="qrModalTitle">QR Code Unit</h3>
                <button onclick="closeQRModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div id="qrImageContainer" class="mb-4 p-4 bg-white rounded-lg border border-gray-200">
                <img id="qrImage" src="" alt="QR Code" class="mx-auto w-48 h-48">
            </div>
            
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-1">Kode Unik:</p>
                <p id="qrKodeUnik" class="font-mono text-sm bg-gray-100 p-2 rounded break-all"></p>
            </div>
            
            <div class="flex justify-center gap-3">
                <button onclick="downloadQR()" 
                        class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition-colors flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Download
                </button>
                <button onclick="closeQRModal()" 
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg text-sm font-medium transition-colors">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

{{-- JavaScript --}}
<script>
// Toggle Add Unit Form
function toggleAddUnitForm() {
    const form = document.getElementById('addUnitForm');
    form.classList.toggle('hidden');
    if (!form.classList.contains('hidden')) {
        document.querySelector('input[name="nomor_unit"]').focus();
    }
}

// Toggle Maintenance Modal
function toggleMaintenance(unitId, currentStatus) {
    document.getElementById('unitId').value = unitId;
    document.getElementById('maintenanceForm').action = "{{ route('apartemen.admin.setMaintenance') }}";
    
    // Set current status in dropdown
    const statusSelect = document.getElementById('unitStatus');
    if (currentStatus === 'READY') {
        statusSelect.value = 'MAINTENANCE';
        document.getElementById('modalTitle').textContent = 'Set Unit ke Maintenance';
    } else if (currentStatus === 'MAINTENANCE') {
        statusSelect.value = 'READY';
        document.getElementById('modalTitle').textContent = 'Set Unit ke Tersedia';
    }
    
    // Show modal with animation
    const modal = document.getElementById('maintenanceModal');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.add('opacity-100');
    }, 10);
}

// Close Modal
function closeModal() {
    const modal = document.getElementById('maintenanceModal');
    modal.classList.remove('opacity-100');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.getElementById('maintenanceForm').reset();
    }, 200);
}

// Delete Unit
function deleteUnit(unitId, unitName) {
    if (confirm(`Apakah Anda yakin ingin menghapus Unit ${unitName}?`)) {
        // Show loading
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = 'Menghapus...';
        button.disabled = true;
        
        fetch("{{ route('apartemen.admin.unit.delete') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                unit_id: unitId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Terjadi kesalahan');
                button.innerHTML = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            alert('Terjadi kesalahan: ' + error.message);
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }
}

// Generate QR Code
function generateQR(unitId) {
    const button = event.target;
    const originalText = button.innerHTML;
    button.innerHTML = 'Memproses...';
    button.disabled = true;
    
    fetch(`/apartemen/admin/unit/${unitId}/generate-qr`, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('QR Code berhasil digenerate!');
            location.reload();
        } else {
            alert(data.message || 'Gagal generate QR Code');
            button.innerHTML = originalText;
            button.disabled = false;
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error.message);
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Show QR Code Modal
function showQRCode(kodeUnik, qrPath, unitNomor) {
    document.getElementById('qrModalTitle').textContent = `QR Code Unit ${unitNomor}`;
    document.getElementById('qrImage').src = qrPath;
    document.getElementById('qrKodeUnik').textContent = kodeUnik;
    
    const modal = document.getElementById('qrModal');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.add('opacity-100');
    }, 10);
}

// Close QR Modal
function closeQRModal() {
    const modal = document.getElementById('qrModal');
    modal.classList.remove('opacity-100');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 200);
}

// Download QR Code
function downloadQR() {
    const img = document.getElementById('qrImage');
    const kode = document.getElementById('qrKodeUnik').textContent;
    
    const link = document.createElement('a');
    link.href = img.src;
    link.download = `qrcode_${kode}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Handle maintenance form submission
document.getElementById('maintenanceForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Show loading state
    submitBtn.innerHTML = 'Menyimpan...';
    submitBtn.disabled = true;
    submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
    
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal();
            location.reload();
        } else {
            alert(data.message || 'Terjadi kesalahan');
            submitBtn.innerHTML = 'Simpan';
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error.message);
        submitBtn.innerHTML = 'Simpan';
        submitBtn.disabled = false;
        submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    });
});

// Form validation for add unit form
document.addEventListener('DOMContentLoaded', function() {
    const addUnitForm = document.querySelector('#addUnitForm form');
    if (addUnitForm) {
        addUnitForm.addEventListener('submit', function(e) {
            const nomorUnit = this.querySelector('input[name="nomor_unit"]').value.trim();
            const kapasitas = this.querySelector('input[name="kapasitas"]').value;
            
            if (!nomorUnit) {
                e.preventDefault();
                alert('Nomor unit harus diisi');
                this.querySelector('input[name="nomor_unit"]').focus();
                return false;
            }
            
            if (!kapasitas || kapasitas < 1) {
                e.preventDefault();
                alert('Kapasitas harus minimal 1 orang');
                this.querySelector('input[name="kapasitas"]').focus();
                return false;
            }
            
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = 'Menyimpan...';
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
            
            return true;
        });
    }
});

// Close modals on outside click
document.getElementById('maintenanceModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

document.getElementById('qrModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeQRModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        if (!document.getElementById('maintenanceModal').classList.contains('hidden')) {
            closeModal();
        }
        if (!document.getElementById('qrModal').classList.contains('hidden')) {
            closeQRModal();
        }
    }
});
</script>

<style>
/* Smooth transitions */
* {
    transition-property: background-color, border-color, color, fill, stroke, opacity, transform;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 200ms;
}

/* Modal animation */
#maintenanceModal, #qrModal {
    opacity: 0;
    transition: opacity 0.2s ease;
}

#maintenanceModal.opacity-100, #qrModal.opacity-100 {
    opacity: 1;
}

#maintenanceModal .transform, #qrModal .transform {
    transform: scale(0.95);
    transition: transform 0.2s ease;
}

#maintenanceModal.opacity-100 .transform, #qrModal.opacity-100 .transform {
    transform: scale(1);
}

/* Hover effects */
tr:hover {
    transition: background-color 0.15s ease;
}

/* Custom scrollbar */
.overflow-x-auto::-webkit-scrollbar {
    height: 4px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 2px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 2px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #a1a1a1;
}

/* Button styles */
button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Loading spinner */
@keyframes spin {
    to { transform: rotate(360deg); }
}

.loading {
    position: relative;
    color: transparent !important;
}

.loading::after {
    content: '';
    position: absolute;
    left: 50%;
    top: 50%;
    width: 16px;
    height: 16px;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid currentColor;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}
</style>
@endsection