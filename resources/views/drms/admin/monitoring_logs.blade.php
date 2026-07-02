@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold">📋 Monitoring Log Driver</h1>
            <p class="text-gray-600 text-sm">Kelola dan verifikasi log perjalanan driver</p>
        </div>
        <a href="{{ route('drms.admin.operational.dashboard') }}" 
           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow transition flex items-center gap-2">
            📊 Dashboard Grafik
        </a>
    </div>

    {{-- Filter & Pencarian --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" action="{{ route('drms.admin.monitoring.logs') }}" class="space-y-4">
            <div class="flex flex-wrap gap-3 items-end">
                <div class="flex-1 min-w-[200px]">
                    <label class="block text-xs font-medium text-gray-700 mb-1">Cari</label>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Cari request, driver..." 
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>⏳ Menunggu Verifikasi</option>
                        <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>✅ Diverifikasi</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>📝 Draft</option>
                        <option value="revision" {{ request('status') == 'revision' ? 'selected' : '' }}>🔄 Perlu Revisi</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Dari Tanggal</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" 
                           class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" 
                           class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">
                        🔍 Filter
                    </button>
                    @if(request()->anyFilled(['search', 'status', 'date_from', 'date_to']))
                        <a href="{{ route('drms.admin.monitoring.logs') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition">
                            Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Quick Stats --}}
    @php
        $total = $logs->total();
        $pendingCount = $logs->where('is_submitted', 1)->where('is_verified', 0)->count();
        $verifiedCount = $logs->where('is_verified', 1)->count();
        $draftCount = $logs->where('is_submitted', 0)->where('is_verified', 0)->count();
        $revisionCount = $logs->filter(function($log) { return $log->needsRevision(); })->count();
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase">Total Log</p>
            <p class="text-2xl font-bold">{{ $total }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
            <p class="text-xs text-gray-500 uppercase">Menunggu Verifikasi</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $pendingCount }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase">Diverifikasi</p>
            <p class="text-2xl font-bold text-green-600">{{ $verifiedCount }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
            <p class="text-xs text-gray-500 uppercase">Perlu Revisi</p>
            <p class="text-2xl font-bold text-red-600">{{ $revisionCount }}</p>
        </div>
    </div>

    {{-- Daftar Log dalam Kartu --}}
    <div class="space-y-4">
        @forelse($logs as $log)
            @php
                $statusText = 'Draft';
                $statusColor = 'bg-gray-100 text-gray-700';
                $badgeIcon = '📝';
                if ($log->is_verified) {
                    $statusText = 'Diverifikasi';
                    $statusColor = 'bg-green-100 text-green-700';
                    $badgeIcon = '✅';
                } elseif ($log->is_submitted && !$log->is_verified) {
                    $statusText = 'Menunggu Verifikasi';
                    $statusColor = 'bg-yellow-100 text-yellow-700';
                    $badgeIcon = '⏳';
                } elseif ($log->needsRevision()) {
                    $statusText = 'Perlu Revisi';
                    $statusColor = 'bg-red-100 text-red-700';
                    $badgeIcon = '🔄';
                }

                $isRevisionDeadlinePassed = $log->revision_requested_at && \Carbon\Carbon::now()->diffInDays($log->revision_requested_at) >= 7;
                $revisionDeadline = $log->revision_requested_at ? \Carbon\Carbon::parse($log->revision_requested_at)->addDays(7)->format('d M Y H:i') : null;
            @endphp
            <div class="bg-white rounded-lg shadow hover:shadow-md transition border {{ $log->needsRevision() ? 'border-red-300' : 'border-gray-200' }}">
                <div class="px-6 py-4 flex flex-col md:flex-row md:items-center justify-between gap-3">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 flex-wrap">
                            <span class="font-semibold text-lg">#{{ $log->request->request_no ?? '-' }}</span>
                            <span class="text-sm text-gray-500">🚗 {{ $log->request->driver->name ?? '-' }}</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColor }}">{{ $badgeIcon }} {{ $statusText }}</span>
                            @if($log->needsRevision() && $revisionDeadline)
                                <span class="text-xs text-gray-400">
                                    ⏱️ Batas revisi: {{ $revisionDeadline }}
                                    @if($isRevisionDeadlinePassed)
                                        <span class="text-red-600 font-bold">(LEWAT!)</span>
                                    @endif
                                </span>
                            @endif
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mt-2 text-sm">
                            <div><span class="text-gray-500">📟 Odometer:</span> {{ $log->odometer_start ?? '-' }} → {{ $log->odometer_finish ?? '-' }}</div>
                            <div><span class="text-gray-500">📏 Jarak:</span> {{ $log->distance ?? '-' }} km</div>
                            <div><span class="text-gray-500">⛽ Efisiensi:</span> {{ $log->efficiency ?? '-' }} {{ $log->fuel_type == 'listrik' ? 'km/kWh' : 'km/liter' }}</div>
                            <div><span class="text-gray-500">💰 Biaya:</span> Rp {{ number_format($log->fuel_cost, 0, ',', '.') }}</div>
                        </div>
                        @if($log->verification_notes)
                            <div class="mt-2 text-sm bg-gray-50 p-2 rounded border border-gray-200">
                                <span class="font-medium text-gray-600">📝 Catatan:</span> {{ $log->verification_notes }}
                            </div>
                        @endif
                        @if($log->revision_note)
                            <div class="mt-2 text-sm bg-red-50 p-2 rounded border border-red-200">
                                <span class="font-medium text-red-600">🔄 Catatan Revisi:</span> {{ $log->revision_note }}
                            </div>
                        @endif
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('drms.admin.verify.log', $log->id) }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                            @if($log->is_submitted && !$log->is_verified)
                                🔍 Verifikasi
                            @else
                                👁️ Detail
                            @endif
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg shadow p-8 text-center text-gray-500">
                <div class="text-4xl mb-2">📭</div>
                <p>Tidak ada log yang ditemukan.</p>
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $logs->links() }}
    </div>
</div>
@endsection