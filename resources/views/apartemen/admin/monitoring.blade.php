{{-- resources/views/apartemen/admin/monitoring.blade.php --}}
@extends('layouts.app-sidebar')

@section('content')
<div class="p-4 md:p-6">

    {{-- HEADER --}}
    <div class="mb-6 md:mb-8">
        {{-- Mobile: Judul di atas semua --}}
        <div class="lg:hidden mb-4">
            <h1 class="text-xl font-bold text-gray-800">Monitoring Penghuni</h1>
            <p class="text-gray-600 text-xs mt-1">Pantau penghuni di semua apartemen</p>
        </div>

        {{-- ACTION BAR --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-4 md:mb-6">
            {{-- Desktop: Judul + Search --}}
            <div class="hidden lg:flex items-center space-x-4 flex-1">
                {{-- Judul Halaman --}}
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Monitoring Penghuni</h1>
                    <p class="text-gray-600 text-sm mt-1">Pantau penghuni di semua apartemen</p>
                </div>
            </div>

            {{-- Search Bar (Tengah di desktop, full width di mobile) --}}
            <div class="w-full lg:w-auto lg:mx-4 lg:flex-1 lg:max-w-md order-first lg:order-none">
                <div class="relative">
                    <form action="{{ route('apartemen.admin.monitoring') }}" method="GET">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="search" value="{{ request('search') }}" 
                               class="pl-10 pr-4 py-2 md:py-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-full" 
                               placeholder="Cari nama penghuni...">
                    </form>
                </div>
            </div>

            {{-- Quick Actions - Sama seperti halaman lain --}}
            <div class="flex flex-wrap items-center gap-2 lg:gap-3 w-full lg:w-auto">
                @php
                    $pendingCount = \App\Models\Apartemen\ApartemenRequest::where('status', 'PENDING')->count();
                    $unitCount = \App\Models\Apartemen\ApartemenUnit::count();
                    
                    // Hitung penghuni dengan status AKTIF (sudah mulai dan belum selesai)
                    $activePenghuniCount = \App\Models\Apartemen\ApartemenPenghuni::whereHas('assign', function($q) {
                        $q->where('status', 'AKTIF')
                          ->where('tanggal_mulai', '<=', now())
                          ->where('tanggal_selesai', '>=', now());
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
                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-green-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <span class="font-medium text-gray-700 text-sm truncate">Unit</span>
                    @if($unitCount > 0)
                    <span class="ml-1 md:ml-2 bg-green-100 text-green-800 text-xs px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap">{{ $unitCount }}</span>
                    @endif
                </a>

                {{-- Penghuni --}}
                <a href="{{ route('apartemen.admin.monitoring') }}"
                class="inline-flex items-center px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-10A2.5 2.5 0 1121 10.5 2.5 2.5 0 0118.5 8z" />
                    </svg>
                    <span class="font-medium text-blue-700 text-sm truncate">Penghuni</span>
                    @if($activePenghuniCount > 0)
                    <span class="ml-1 md:ml-2 bg-blue-100 text-blue-800 text-xs px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap">{{ $activePenghuniCount }}</span>
                    @endif
                </a>

                {{-- Riwayat Button --}}
                <a href="{{ route('apartemen.admin.history') }}"
                class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium text-gray-700 text-sm truncate">Riwayat</span>
                    @php
                        $historyCount = \App\Models\Apartemen\ApartemenHistory::count();
                    @endphp
                    @if($historyCount > 0)
                    <span class="ml-1 md:ml-2 bg-blue-100 text-blue-800 text-xs px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap">{{ $historyCount }}</span>
                    @endif
                </a>
            </div>
        </div>

        {{-- FILTER SECTION --}}
        <div class="bg-white border border-gray-200 rounded-lg p-3 md:p-4 mb-4 md:mb-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
                <div class="w-full">
                    <h3 class="text-xs md:text-sm font-medium text-gray-700 mb-2">Filter Penghuni</h3>
                    <form action="{{ route('apartemen.admin.monitoring') }}" method="GET" class="space-y-3">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 w-full">
                            <div>
                                <select name="apartemen_id" class="border border-gray-300 rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-full" onchange="this.form.submit()">
                                    <option value="">Semua Apartemen</option>
                                    @foreach($apartemen as $apt)
                                    <option value="{{ $apt->id }}" {{ request('apartemen_id') == $apt->id ? 'selected' : '' }}>
                                        {{ $apt->nama_apartemen }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-full" onchange="this.form.submit()">
                                    <option value="">Semua Status</option>
                                    <option value="belum_aktif" {{ request('status') == 'belum_aktif' ? 'selected' : '' }}>Belum Aktif</option>
                                    <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif</option>
                                    <option value="belum_checkout" {{ request('status') == 'belum_checkout' ? 'selected' : '' }}>Belum Check-out</option>
                                </select>
                            </div>
                            
                            <div>
                                <select name="sort" class="border border-gray-300 rounded-lg px-3 py-2 text-xs md:text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-full" onchange="this.form.submit()">
                                    <option value="nama_asc" {{ request('sort') == 'nama_asc' ? 'selected' : '' }}>Nama A-Z</option>
                                    <option value="nama_desc" {{ request('sort') == 'nama_desc' ? 'selected' : '' }}>Nama Z-A</option>
                                    <option value="tanggal_mulai" {{ request('sort') == 'tanggal_mulai' ? 'selected' : '' }}>Tanggal Mulai</option>
                                    <option value="tanggal_selesai" {{ request('sort') == 'tanggal_selesai' ? 'selected' : '' }}>Tanggal Selesai</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
                            <div class="flex items-center gap-2 w-full sm:w-auto">
                                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-3 md:px-4 py-1.5 md:py-2 rounded-lg text-xs md:text-sm font-medium whitespace-nowrap w-full sm:w-auto">
                                    Terapkan Filter
                                </button>
                                
                                @if(request()->anyFilled(['apartemen_id', 'status', 'search', 'sort']))
                                <a href="{{ route('apartemen.admin.monitoring') }}" class="text-gray-600 hover:text-gray-800 text-xs md:text-sm font-medium whitespace-nowrap w-full sm:w-auto text-center">
                                    Reset Filter
                                </a>
                                @endif
                            </div>
                            
                            {{-- Info Filter Aktif --}}
                            @if(request()->anyFilled(['apartemen_id', 'status']))
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-1 pt-2 sm:pt-0 border-t sm:border-t-0 border-gray-100 sm:border-none w-full">
                                <span class="text-xs text-gray-500">Filter aktif:</span>
                                <div class="flex flex-wrap gap-1">
                                    @if(request('apartemen_id'))
                                        @php
                                            $selectedApt = $apartemen->firstWhere('id', request('apartemen_id'));
                                        @endphp
                                        @if($selectedApt)
                                        <span class="inline-flex items-center px-1.5 md:px-2 py-0.5 md:py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 whitespace-nowrap">
                                            {{ $selectedApt->nama_apartemen }}
                                        </span>
                                        @endif
                                    @endif
                                    @if(request('status'))
                                    <span class="inline-flex items-center px-1.5 md:px-2 py-0.5 md:py-1 rounded-full text-xs font-medium 
                                        {{ request('status') == 'aktif' ? 'bg-green-100 text-green-800' : 
                                           (request('status') == 'belum_aktif' ? 'bg-yellow-100 text-yellow-800' : 
                                           'bg-red-100 text-red-800') }} whitespace-nowrap">
                                        {{ request('status') == 'aktif' ? 'Aktif' : 
                                         (request('status') == 'belum_aktif' ? 'Belum Aktif' : 
                                         'Belum Check-out') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- STATS --}}
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3 md:gap-4 mb-4 md:mb-6">
        @php
            $now = now();
            
            $belumAktifCount = \App\Models\Apartemen\ApartemenPenghuni::whereHas('assign', function($q) use ($now) {
                $q->where('status', 'AKTIF')
                  ->where('tanggal_mulai', '>', $now);
            })->count();
            
            $activeCount = \App\Models\Apartemen\ApartemenPenghuni::whereHas('assign', function($q) use ($now) {
                $q->where('status', 'AKTIF')
                  ->where('tanggal_mulai', '<=', $now)
                  ->where('tanggal_selesai', '>=', $now);
            })->count();
            
            $expiredCount = \App\Models\Apartemen\ApartemenPenghuni::whereHas('assign', function($q) use ($now) {
                $q->where('status', 'AKTIF')
                  ->where('tanggal_selesai', '<', $now);
            })->count();
        @endphp
        
        <div class="bg-white rounded-lg border border-gray-200 p-3 md:p-4">
            <div class="flex items-center">
                <div class="p-1.5 md:p-2 rounded-lg bg-yellow-100 mr-2 md:mr-3">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-500 truncate">Belum Aktif</p>
                    <p class="text-lg md:text-xl font-bold text-gray-900 truncate">{{ $belumAktifCount }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg border border-gray-200 p-3 md:p-4">
            <div class="flex items-center">
                <div class="p-1.5 md:p-2 rounded-lg bg-green-100 mr-2 md:mr-3">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-500 truncate">Aktif</p>
                    <p class="text-lg md:text-xl font-bold text-gray-900 truncate">{{ $activeCount }}</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg border border-gray-200 p-3 md:p-4">
            <div class="flex items-center">
                <div class="p-1.5 md:p-2 rounded-lg bg-red-100 mr-2 md:mr-3">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <p class="text-xs md:text-sm text-gray-500 truncate">Belum Check-out</p>
                    <p class="text-lg md:text-xl font-bold text-gray-900 truncate">{{ $expiredCount }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- PENGHUNI LIST --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        {{-- Table Header --}}
        <div class="px-3 md:px-4 lg:px-6 py-3 md:py-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <h3 class="text-base md:text-lg font-semibold text-gray-800">Daftar Penghuni</h3>
                <div class="text-xs md:text-sm text-gray-500">
                    Total: <span class="font-medium">{{ $penghuni->total() ?? 0 }}</span> penghuni
                </div>
            </div>
        </div>

        {{-- Table Content --}}
        <div class="p-3 md:p-4 lg:p-6">
            @if($penghuni->count() > 0)
            <div class="overflow-x-auto -mx-3 md:mx-0">
                <div class="min-w-full inline-block align-middle">
                    <div class="overflow-hidden border border-gray-200 rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Penghuni</th>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden md:table-cell">Apartemen & Unit</th>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap hidden lg:table-cell">Periode</th>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Status</th>
                                    <th class="py-2 px-2 md:px-3 lg:px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider whitespace-nowrap">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($penghuni as $p)
                                @php
                                    $assign = $p->assign;
                                    $now = now();
                                    $today = now()->startOfDay();
                                    
                                    // Cek apakah sudah check-in
                                    $sudahCheckin = $assign && $assign->checkin_at ? true : false;
                                    
                                    // Tentukan status berdasarkan tanggal
                                    if ($assign) {
                                        $startDate = \Carbon\Carbon::parse($assign->tanggal_mulai)->startOfDay();
                                        $endDate = \Carbon\Carbon::parse($assign->tanggal_selesai)->startOfDay();
                                        
                                        if ($assign->tanggal_mulai > $now) {
                                            $status = 'belum_aktif';
                                            $statusLabel = 'Belum Aktif';
                                            $statusColor = 'yellow';
                                            // Hanya bisa check-in jika sudah H-0 atau lebih
                                            $canCheckin = $today->greaterThanOrEqualTo($startDate) && !$sudahCheckin;
                                            $canCheckout = false;
                                            $daysUntilStart = $today->diffInDays($startDate, false); // negatif jika sudah lewat
                                        } elseif ($assign->tanggal_selesai < $now) {
                                            $status = 'expired';
                                            $statusLabel = 'Belum Check-out';
                                            $statusColor = 'red';
                                            $canCheckin = false;
                                            $canCheckout = $sudahCheckin;
                                            $daysUntilStart = null;
                                        } else {
                                            $status = 'active';
                                            $statusLabel = 'Aktif';
                                            $statusColor = 'green';
                                            $canCheckin = !$sudahCheckin;
                                            $canCheckout = $sudahCheckin;
                                            $daysUntilStart = null;
                                        }
                                    } else {
                                        $status = null;
                                        $statusLabel = '-';
                                        $statusColor = 'gray';
                                        $canCheckin = false;
                                        $canCheckout = false;
                                        $daysUntilStart = null;
                                    }
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    {{-- Penghuni --}}
                                    <td class="py-3 px-2 md:px-3 lg:px-4">
                                        <div class="min-w-0">
                                            <div class="font-medium text-gray-900 text-sm truncate">{{ $p->nama }}</div>
                                            <div class="text-xs text-gray-500 truncate max-w-[100px] md:max-w-none">{{ $p->id_karyawan }}</div>
                                            <div class="text-xs text-gray-400 truncate max-w-[80px] md:max-w-none">{{ $p->unit_kerja ?? '-' }}</div>
                                        </div>
                                    </td>

                                    {{-- Apartemen & Unit (Mobile) --}}
                                    <td class="py-3 px-2 md:px-3 lg:px-4 md:hidden">
                                        @if($assign && $assign->unit && $assign->unit->apartemen)
                                        <div class="text-xs">
                                            <div class="font-medium text-gray-900 truncate max-w-[100px]">{{ $assign->unit->apartemen->nama_apartemen }}</div>
                                            <div class="text-gray-500">Unit {{ $assign->unit->nomor_unit }}</div>
                                            @if($assign->checkin_at)
                                            <div class="text-green-600 text-[10px] mt-1">
                                                <i class="fas fa-check-circle mr-1"></i>Sudah Check-in {{ $assign->checkin_at->format('d/m H:i') }}
                                            </div>
                                            @endif
                                        </div>
                                        @else
                                        <span class="text-gray-400 text-xs">-</span>
                                        @endif
                                    </td>

                                    {{-- Apartemen & Unit (Desktop) --}}
                                    <td class="py-3 px-2 md:px-3 lg:px-4 hidden md:table-cell">
                                        @if($assign && $assign->unit && $assign->unit->apartemen)
                                        <div class="font-medium text-gray-900 text-sm truncate">{{ $assign->unit->apartemen->nama_apartemen }}</div>
                                        <div class="text-xs text-gray-500 truncate">Unit {{ $assign->unit->nomor_unit }}</div>
                                        @if($assign->checkin_at)
                                        <div class="text-green-600 text-[10px] mt-1">
                                            <i class="fas fa-check-circle mr-1"></i>Check-in {{ $assign->checkin_at->format('d/m H:i') }}
                                        </div>
                                        @endif
                                        @else
                                        <span class="text-gray-400 text-sm">-</span>
                                        @endif
                                    </td>

                                    {{-- Periode (Mobile) --}}
                                    <td class="py-3 px-2 md:px-3 lg:px-4 lg:hidden">
                                        @if($assign)
                                        <div class="text-xs">
                                            <div class="text-gray-900">{{ $assign->tanggal_mulai->format('d/m') }}</div>
                                            <div class="text-gray-500">s/d {{ $assign->tanggal_selesai->format('d/m') }}</div>
                                        </div>
                                        @else
                                        <span class="text-gray-400 text-xs">-</span>
                                        @endif
                                    </td>

                                    {{-- Periode (Desktop) --}}
                                    <td class="py-3 px-2 md:px-3 lg:px-4 hidden lg:table-cell">
                                        @if($assign)
                                        <div class="text-sm text-gray-900 whitespace-nowrap">{{ $assign->tanggal_mulai->format('d/m/Y') }}</div>
                                        <div class="text-xs text-gray-500 whitespace-nowrap">s/d</div>
                                        <div class="text-sm text-gray-900 whitespace-nowrap">{{ $assign->tanggal_selesai->format('d/m/Y') }}</div>
                                        @else
                                        <span class="text-gray-400 text-sm">-</span>
                                        @endif
                                    </td>

                                    {{-- Status --}}
                                    <td class="py-3 px-2 md:px-3 lg:px-4">
                                        @if($assign)
                                            @if($status == 'belum_aktif')
                                            <span class="inline-flex items-center px-1.5 md:px-2 py-0.5 md:py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 whitespace-nowrap">
                                                Belum Aktif
                                            </span>
                                            @elseif($status == 'active')
                                            <span class="inline-flex items-center px-1.5 md:px-2 py-0.5 md:py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 whitespace-nowrap">
                                                Aktif
                                            </span>
                                            @elseif($status == 'expired')
                                            <span class="inline-flex items-center px-1.5 md:px-2 py-0.5 md:py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 whitespace-nowrap">
                                                Belum Check-out
                                            </span>
                                            @endif
                                            
                                            @if($assign->checkin_at)
                                            <div class="text-[10px] text-green-600 mt-1 text-center">
                                                ✓ Check-in
                                            </div>
                                            @endif
                                        @else
                                        <span class="text-gray-400 text-xs">-</span>
                                        @endif
                                    </td>

                                    {{-- Aksi --}}
                                    {{-- Aksi --}}
                                    <td class="py-3 px-2 md:px-3 lg:px-4">
                                        @if($canCheckin)
                                            <button type="button" 
                                                    onclick="confirmCheckin({{ $p->id }}, '{{ addslashes($p->nama) }}'); return false;"
                                                    class="text-green-600 hover:text-green-800 text-xs md:text-sm font-medium whitespace-nowrap checkin-btn"
                                                    data-id="{{ $p->id }}">
                                                Check-in
                                            </button>
                                        @elseif($status == 'belum_aktif' && isset($daysUntilStart) && $daysUntilStart > 0)
                                            {{-- Belum waktunya check-in --}}
                                            <div class="relative group inline-block">
                                                <button type="button" 
                                                        disabled
                                                        class="text-gray-400 text-xs md:text-sm font-medium whitespace-nowrap cursor-not-allowed">
                                                    Check-in
                                                </button>
                                                {{-- Tooltip --}}
                                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-800 text-white text-xs rounded-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 whitespace-nowrap pointer-events-none z-50 shadow-lg min-w-[180px]">
                                                    <div class="flex items-center">
                                                        <svg class="w-3 h-3 mr-1 text-yellow-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span class="font-medium">Belum waktunya check-in</span>
                                                    </div>
                                                    <div class="text-gray-300 text-[10px] mt-1 leading-relaxed">
                                                        <div>Mulai: {{ $assign->tanggal_mulai->format('d/m/Y') }}</div>
                                                        <div>{{ $daysUntilStart }} hari lagi</div>
                                                        <div class="border-t border-gray-600 mt-1 pt-1">Check-in hanya bisa H-0</div>
                                                    </div>
                                                </div>
                                            </div>
                                        @elseif($canCheckout)
                                            <button type="button" 
                                                    onclick="confirmCheckout({{ $p->id }}, '{{ addslashes($p->nama) }}'); return false;"
                                                    class="text-red-600 hover:text-red-800 text-xs md:text-sm font-medium whitespace-nowrap checkout-btn"
                                                    data-id="{{ $p->id }}">
                                                Check-out
                                            </button>
                                        @else
                                            <span class="text-gray-400 text-xs">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- PAGINATION --}}
            <div class="flex flex-col sm:flex-row items-center justify-between px-2 md:px-3 lg:px-4 py-3 border-t border-gray-200 gap-2 md:gap-3">
                <div class="text-xs md:text-sm text-gray-700 text-center sm:text-left">
                    <span class="font-medium">{{ $penghuni->firstItem() }}</span> - 
                    <span class="font-medium">{{ $penghuni->lastItem() }}</span> dari 
                    <span class="font-medium">{{ $penghuni->total() }}</span>
                </div>
                <div class="flex space-x-1 md:space-x-2">
                    @if($penghuni->previousPageUrl())
                        <a href="{{ $penghuni->previousPageUrl() }}" 
                           class="px-2 md:px-3 py-1.5 md:py-2 border border-gray-300 rounded-md text-xs md:text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors whitespace-nowrap">
                            ← Prev
                        </a>
                    @endif

                    @if($penghuni->nextPageUrl())
                        <a href="{{ $penghuni->nextPageUrl() }}" 
                           class="px-2 md:px-3 py-1.5 md:py-2 border border-gray-300 rounded-md text-xs md:text-sm font-medium text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors whitespace-nowrap">
                            Next →
                        </a>
                    @endif
                </div>
            </div>
            @else
            {{-- EMPTY STATE --}}
            <div class="text-center py-8 md:py-12">
                <div class="mx-auto w-12 h-12 md:w-16 md:h-16 lg:w-24 lg:h-24 bg-gray-100 rounded-full flex items-center justify-center mb-3 md:mb-4 lg:mb-6">
                    <svg class="w-6 h-6 md:w-8 md:h-8 lg:w-12 lg:h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-10A2.5 2.5 0 1121 10.5 2.5 2.5 0 0118.5 8z" />
                    </svg>
                </div>
                <h3 class="text-base md:text-lg font-medium text-gray-900 mb-1 md:mb-2">Belum ada penghuni</h3>
                <p class="text-gray-500 max-w-xs md:max-w-md mx-auto text-xs md:text-sm">
                    @if(request()->filled('search') || request()->filled('apartemen_id') || request()->filled('status'))
                    Tidak ditemukan penghuni yang sesuai dengan filter.
                    @else
                    Belum ada data penghuni.
                    @endif
                </p>
                @if(request()->filled('search') || request()->filled('apartemen_id') || request()->filled('status'))
                <a href="{{ route('apartemen.admin.monitoring') }}" 
                   class="mt-3 md:mt-4 inline-flex items-center px-3 md:px-4 py-1.5 md:py-2 border border-transparent text-xs md:text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Reset Filter
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>

</div>

{{-- Hidden Form untuk Checkin dan Checkout --}}
<form id="checkinForm" method="POST" style="display: none;">
    @csrf
</form>

<form id="checkoutForm" method="POST" style="display: none;">
    @csrf
</form>

<style>
/* Smooth transitions */
* {
    transition-property: background-color, border-color, color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}

/* Better table styling */
table tbody tr {
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

/* Responsive adjustments */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
    }
}

/* Ensure buttons are touch-friendly on mobile */
@media (max-width: 640px) {
    button, a.button-like {
        min-height: 36px;
    }
    
    .touch-target {
        padding: 0.5rem;
    }
}

/* Better truncation */
.truncate-2-lines {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Button styling */
.checkin-btn, .checkout-btn {
    transition: color 0.2s ease;
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    font-family: inherit;
    font-size: inherit;
}

.checkin-btn:hover {
    color: #16a34a; /* green-600 */
}

.checkout-btn:hover {
    color: #dc2626; /* red-800 */
}

.checkin-btn:disabled, .checkout-btn:disabled {
    color: #9ca3af; /* gray-400 */
    cursor: not-allowed;
}

/* Loading state */
.checkin-btn.loading, .checkout-btn.loading {
    position: relative;
    color: transparent !important;
}

.checkin-btn.loading::after, .checkout-btn.loading::after {
    content: '';
    position: absolute;
    left: 50%;
    top: 50%;
    width: 12px;
    height: 12px;
    margin-left: -6px;
    margin-top: -6px;
    border: 2px solid currentColor;
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}

.checkin-btn.loading::after {
    border-color: #16a34a;
    border-top-color: transparent;
}

.checkout-btn.loading::after {
    border-color: #dc2626;
    border-top-color: transparent;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
let actionInProgress = false;

function confirmCheckin(id, name) {
    console.log('=== 🚀 CHECKIN START ===');
    console.log('Penghuni ID:', id, 'Name:', name);
    
    if (actionInProgress) {
        console.log('⚠️ Aksi sedang berjalan, mohon tunggu...');
        alert('Aksi sedang diproses, mohon tunggu...');
        return false;
    }
    
    if (!confirm(`Yakin melakukan check-in untuk ${name}?`)) {
        console.log('❌ User membatalkan check-in');
        return false;
    }
    
    console.log('✅ User mengkonfirmasi check-in');
    actionInProgress = true;
    
    const button = document.querySelector(`.checkin-btn[data-id="${id}"]`);
    if (button) {
        button.disabled = true;
        button.classList.add('loading');
        button.textContent = 'Memproses...';
    }
    
    // Buat form dinamis untuk check-in
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/apartemen/admin/penghuni/${id}/checkin`;
    form.style.display = 'none';
    
    // CSRF token
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    form.appendChild(csrfToken);
    
    // Tambahkan ke body
    document.body.appendChild(form);
    
    console.log('📤 Submitting check-in form to:', form.action);
    
    try {
        form.submit();
        console.log('✅ Check-in form submitted successfully');
    } catch (error) {
        console.error('❌ Form submission error:', error);
        alert('Terjadi kesalahan: ' + error.message);
        resetAction(button);
    }
    
    return false;
}

function confirmCheckout(id, name) {
    console.log('=== 🚀 CHECKOUT START ===');
    console.log('Penghuni ID:', id, 'Name:', name);
    
    if (actionInProgress) {
        console.log('⚠️ Aksi sedang berjalan, mohon tunggu...');
        alert('Aksi sedang diproses, mohon tunggu...');
        return false;
    }
    
    if (!confirm(`Yakin melakukan check-out untuk ${name}?`)) {
        console.log('❌ User membatalkan checkout');
        return false;
    }
    
    console.log('✅ User mengkonfirmasi checkout');
    actionInProgress = true;
    
    const button = document.querySelector(`.checkout-btn[data-id="${id}"]`);
    if (button) {
        button.disabled = true;
        button.classList.add('loading');
        button.textContent = 'Memproses...';
    }
    
    // Buat form dinamis untuk check-out
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = `/apartemen/admin/penghuni/${id}/checkout`;
    form.style.display = 'none';
    
    // CSRF token
    const csrfToken = document.createElement('input');
    csrfToken.type = 'hidden';
    csrfToken.name = '_token';
    csrfToken.value = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
    form.appendChild(csrfToken);
    
    // Tambahkan ke body
    document.body.appendChild(form);
    
    console.log('📤 Submitting check-out form to:', form.action);
    
    try {
        form.submit();
        console.log('✅ Check-out form submitted successfully');
    } catch (error) {
        console.error('❌ Form submission error:', error);
        alert('Terjadi kesalahan: ' + error.message);
        resetAction(button);
    }
    
    return false;
}

function resetAction(button) {
    if (button) {
        button.disabled = false;
        button.classList.remove('loading');
        if (button.classList.contains('checkin-btn')) {
            button.textContent = 'Check-in';
        } else {
            button.textContent = 'Check-out';
        }
    }
    actionInProgress = false;
}

// Debug info
document.addEventListener('DOMContentLoaded', function() {
    console.log('📄 DOM loaded, checkin/checkout system ready');
    console.log('Found checkin buttons:', document.querySelectorAll('.checkin-btn').length);
    console.log('Found checkout buttons:', document.querySelectorAll('.checkout-btn').length);
});

// Auto-reset setelah 10 detik
setTimeout(() => {
    if (actionInProgress) {
        console.log('🕐 Auto-resetting action progress');
        actionInProgress = false;
        
        document.querySelectorAll('.checkin-btn.loading, .checkout-btn.loading').forEach(button => {
            button.disabled = false;
            button.classList.remove('loading');
            if (button.classList.contains('checkin-btn')) {
                button.textContent = 'Check-in';
            } else {
                button.textContent = 'Check-out';
            }
        });
    }
}, 10000);
</script>
@endsection