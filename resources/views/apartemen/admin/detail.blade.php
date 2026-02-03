@extends('layouts.app-sidebar')

@section('content')
<div class="p-4 md:p-6">

    {{-- HEADER --}}
    <div class="mb-6 md:mb-8">
        {{-- Mobile: Judul di atas semua --}}
        <div class="lg:hidden mb-4">
            <h1 class="text-xl font-bold text-gray-800">Detail Permintaan</h1>
            <p class="text-gray-600 text-xs mt-1">Lihat detail lengkap permintaan apartemen</p>
        </div>

        {{-- ACTION BAR --}}
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 mb-4 md:mb-6">
            {{-- Desktop: Judul + Search --}}
            <div class="hidden lg:flex items-center space-x-4 flex-1">
                {{-- Judul Halaman --}}
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Detail Permintaan</h1>
                    <p class="text-gray-600 text-sm mt-1">Lihat detail lengkap permintaan apartemen</p>
                </div>
            </div>

            {{-- Search Bar --}}
            <div class="w-full lg:w-auto lg:mx-4 lg:flex-1 lg:max-w-md order-first lg:order-none">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 md:w-5 md:h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input type="text" readonly
                           class="pl-10 pr-4 py-2 md:py-3 border border-gray-300 rounded-lg text-sm bg-gray-50 w-full cursor-not-allowed" 
                           placeholder="Detail permintaan...">
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
                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-10A2.5 2.5 0 1121 10.5 2.5 2.5 0 0118.5 8z" />
                    </svg>
                    <span class="font-medium text-gray-700 text-sm truncate">Penghuni</span>
                    @if($penghuniCount > 0)
                    <span class="ml-1 md:ml-2 bg-blue-100 text-blue-800 text-xs px-1.5 md:px-2 py-0.5 rounded-full whitespace-nowrap">{{ $penghuniCount }}</span>
                    @endif
                </a>

                {{-- Riwayat --}}
                <a href="{{ route('apartemen.admin.history') }}"
                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors flex-1 lg:flex-none justify-center min-w-[100px] md:min-w-0">
                    <svg class="w-4 h-4 md:w-5 md:h-5 text-blue-600 mr-1 md:mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="font-medium text-gray-700 text-sm truncate">Riwayat</span>
                </a>
            </div>
        </div>

        {{-- Kembali button untuk mobile --}}
        <div class="lg:hidden flex justify-end mb-4">
            <a href="{{ route('apartemen.admin.index') }}" 
               class="text-gray-600 hover:text-gray-800 text-sm flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Daftar
            </a>
        </div>
    </div>

    {{-- MAIN CONTENT --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        {{-- LEFT COLUMN --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- REQUEST DETAILS --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Detail Permintaan</h2>
                            <p class="text-sm text-gray-500 mt-1">ID: #{{ str_pad($request->id, 6, '0', STR_PAD_LEFT) }}</p>
                        </div>
                        @switch($request->status)
                            @case('PENDING')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    Menunggu Approval
                                </span>
                                @break
                            @case('APPROVED')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                    Disetujui
                                </span>
                                @break
                            @case('REJECTED')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    Ditolak
                                </span>
                                @break
                        @endswitch
                    </div>
                    
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Pemohon</label>
                                <p class="font-medium text-gray-900">{{ $request->user->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Tanggal Pengajuan</label>
                                <p class="font-medium text-gray-900">{{ $request->created_at->format('d F Y H:i') }}</p>
                            </div>
                        </div>

                        @if($request->alasan)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Alasan Pengajuan</label>
                            <div class="mt-1 p-3 bg-gray-50 rounded-lg">
                                <p class="text-gray-700">{{ $request->alasan }}</p>
                            </div>
                        </div>
                        @endif

                        @if($request->status == 'APPROVED' && $request->approved_at)
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Disetujui Oleh</label>
                                <p class="font-medium text-gray-900">{{ $request->approved_by ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-500 mb-1">Tanggal Disetujui</label>
                                <p class="font-medium text-gray-900">{{ $request->approved_at->format('d F Y H:i') }}</p>
                            </div>
                        </div>
                        @endif

                        @if($request->status == 'REJECTED')
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Alasan Penolakan</label>
                            <div class="mt-1 p-3 bg-red-50 rounded-lg border border-red-100">
                                <p class="text-red-700">{{ $request->reject_reason }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- PENGHUNI LIST --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-lg font-semibold text-gray-800">Daftar Penghuni</h2>
                        <span class="text-sm font-medium text-gray-700 bg-gray-100 px-3 py-1 rounded-full">
                            {{ $request->penghuni->count() }} orang
                        </span>
                    </div>

                    <div class="space-y-4">
                        @foreach($request->penghuni as $penghuni)
                        <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ $penghuni->nama }}</h4>
                                    <p class="text-sm text-gray-500 mt-1">{{ $penghuni->id_karyawan }}</p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    @if($penghuni->unit_kerja)
                                    <span class="text-xs font-medium text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                        {{ $penghuni->unit_kerja }}
                                    </span>
                                    @endif
                                    @if($penghuni->gol)
                                    <span class="text-xs font-medium text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                        Gol. {{ $penghuni->gol }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <label class="text-gray-500">Mulai</label>
                                    <p class="font-medium">{{ \Carbon\Carbon::parse($penghuni->tanggal_mulai)->format('d/m/Y') }}</p>
                                </div>
                                <div>
                                    <label class="text-gray-500">Selesai</label>
                                    <p class="font-medium">{{ \Carbon\Carbon::parse($penghuni->tanggal_selesai)->format('d/m/Y') }}</p>
                                </div>
                                <div>
                                    <label class="text-gray-500">No. HP</label>
                                    <p class="font-medium">{{ $penghuni->no_hp ?? '-' }}</p>
                                </div>
                                <!-- <div>
                                    <label class="text-gray-500">Status</label>
                                    <p class="font-medium">
                                        @if($penghuni->status == 'AKTIF')
                                            <span class="text-green-600">Aktif</span>
                                        @elseif($penghuni->status == 'SELESAI')
                                            <span class="text-gray-600">Selesai</span>
                                        @else
                                            <span class="text-gray-600">{{ $penghuni->status }}</span>
                                        @endif
                                    </p>
                                </div> -->
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>

        {{-- RIGHT COLUMN --}}
        <div class="space-y-6">
            
            {{-- UNIT INFORMATION --}}
            @if($request->status == 'APPROVED' && count($units) > 0)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Penempatan Unit</h2>
                    
                    <div class="space-y-4">
                        @foreach($units as $assign)
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex justify-between items-start mb-3">
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ $assign->unit->apartemen->nama_apartemen ?? 'N/A' }}</h4>
                                    <p class="text-sm text-gray-500">Unit: {{ $assign->unit->nomor_unit ?? 'N/A' }}</p>
                                </div>
                                <span class="text-xs font-medium text-green-600 bg-green-100 px-2 py-1 rounded">
                                    {{ $assign->penghuni->where('status', 'AKTIF')->count() }} aktif
                                </span>
                            </div>
                            
                            <div class="text-sm text-gray-600 mb-3">
                                Kapasitas: {{ $assign->unit->kapasitas }} orang
                            </div>
                            
                            <div class="text-sm">
                                <div class="flex justify-between mb-1">
                                    <span class="text-gray-500">Periode:</span>
                                    <span class="font-medium">{{ $assign->tanggal_mulai->format('d/m/Y') }} - {{ $assign->tanggal_selesai->format('d/m/Y') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500">Status:</span>
                                    <span class="font-medium">
                                        @if($assign->status == 'AKTIF')
                                            <span class="text-green-600">Aktif</span>
                                        @elseif($assign->status == 'SELESAI')
                                            <span class="text-gray-600">Selesai</span>
                                        @else
                                            {{ $assign->status }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- QUICK INFO --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Informasi</h2>
                    
                    <div class="space-y-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Total Penghuni</label>
                            <p class="font-medium text-gray-900">{{ $request->penghuni->count() }} orang</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Status Request</label>
                            <p class="font-medium text-gray-900">
                                @switch($request->status)
                                    @case('PENDING') <span class="text-yellow-600">Pending</span> @break
                                    @case('APPROVED') <span class="text-green-600">Disetujui</span> @break
                                    @case('REJECTED') <span class="text-red-600">Ditolak</span> @break
                                @endswitch
                            </p>
                        </div>
                        
                        @if($request->status == 'APPROVED' && count($units) > 0)
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Unit Ditempati</label>
                            <p class="font-medium text-gray-900">{{ count($units) }} unit</p>
                        </div>
                        @endif
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-500 mb-1">Durasi (requested)</label>
                            @php
                                $firstPenghuni = $request->penghuni->first();
                            @endphp
                            @if($firstPenghuni)
                            <p class="font-medium text-gray-900">
                                {{ \Carbon\Carbon::parse($firstPenghuni->tanggal_mulai)->diffInDays($firstPenghuni->tanggal_selesai) }} hari
                            </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- ACTIONS --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">Aksi</h2>
                    
                    <div class="space-y-3">
                        @if($request->status == 'PENDING')
                        <a href="{{ route('apartemen.admin.approve', $request->id) }}" 
                           class="w-full flex items-center justify-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Review & Approval
                        </a>
                        @endif

                        <a href="{{ route('apartemen.admin.index') }}" 
                           class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 bg-white hover:bg-gray-50 text-gray-700 rounded-lg text-sm font-medium transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Kembali ke Daftar
                        </a>

                        @if($request->status == 'APPROVED' && count($units) > 0)
                        <div class="pt-4 border-t border-gray-200">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Monitoring Penghuni</h3>
                            <a href="{{ route('apartemen.admin.monitoring') }}?search={{ $request->penghuni->first()->nama ?? '' }}" 
                               class="w-full flex items-center justify-center px-4 py-2 border border-blue-300 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg text-sm font-medium transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-10A2.5 2.5 0 1121 10.5 2.5 2.5 0 0118.5 8z" />
                                </svg>
                                Lihat di Monitoring
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection