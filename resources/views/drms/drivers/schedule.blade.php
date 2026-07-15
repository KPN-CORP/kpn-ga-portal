@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- HEADER --}}
    <div class="flex flex-wrap justify-between items-center mb-6 gap-2">
        <h1 class="text-2xl font-bold">📅 Jadwal Driver</h1>
        <span class="text-sm text-gray-500">{{ $date ? \Carbon\Carbon::parse($date)->format('d M Y') : 'Hari ini' }}</span>
    </div>

    {{-- FILTER --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <form method="GET" action="{{ route('drms.drivers.schedule') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📅 Tanggal</label>
                <input type="date" name="date" value="{{ $date ?? now()->format('Y-m-d') }}" 
                       class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">👤 Cari Driver</label>
                <input type="text" name="search" value="{{ $searchDriver ?? '' }}" 
                       placeholder="Nama driver..." 
                       class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 w-48">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📌 Status</label>
                <select name="status" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="all" {{ ($statusFilter ?? 'all') == 'all' ? 'selected' : '' }}>Semua</option>
                    <option value="scheduled" {{ ($statusFilter ?? '') == 'scheduled' ? 'selected' : '' }}>⏳ Terjadwal</option>
                    <option value="on_trip" {{ ($statusFilter ?? '') == 'on_trip' ? 'selected' : '' }}>🚗 Dalam Perjalanan</option>
                    <option value="completed" {{ ($statusFilter ?? '') == 'completed' ? 'selected' : '' }}>✅ Selesai</option>
                </select>
            </div>
            @if(auth()->user()->isDrmsSuperAdmin())
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🏢 Business Unit</label>
                <select name="business_unit_id" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua BU</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}" {{ request('business_unit_id') == $bu->id_bisnis_unit ? 'selected' : '' }}>
                            {{ $bu->nama_bisnis_unit }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    🔍 Tampilkan
                </button>
                @if(request()->anyFilled(['date', 'search', 'status', 'business_unit_id']))
                    <a href="{{ route('drms.drivers.schedule') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- QUICK STATS --}}
    @php
        $totalDrivers = $drivers->count();
        $totalRequests = $requests->flatten()->count();
        $scheduled = $requests->flatten()->filter(function($req) {
            return $req->status == 'approved_admin' && \Carbon\Carbon::parse($req->usage_date->format('Y-m-d') . ' ' . $req->start_time)->isFuture();
        })->count();
        $onTrip = $requests->flatten()->filter(function($req) {
            if ($req->status != 'approved_admin') return false;
            $start = \Carbon\Carbon::parse($req->usage_date->format('Y-m-d') . ' ' . $req->start_time);
            $end = \Carbon\Carbon::parse($req->usage_date->format('Y-m-d') . ' ' . ($req->end_time ?? '23:59'));
            return now()->between($start, $end);
        })->count();
        $completed = $requests->flatten()->where('status', 'completed')->count();
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
        <div class="bg-white p-3 rounded-lg shadow-sm border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase">Total Driver</p>
            <p class="text-xl font-bold">{{ $totalDrivers }}</p>
        </div>
        <div class="bg-white p-3 rounded-lg shadow-sm border-l-4 border-indigo-500">
            <p class="text-xs text-gray-500 uppercase">Total Jadwal</p>
            <p class="text-xl font-bold">{{ $totalRequests }}</p>
        </div>
        <div class="bg-white p-3 rounded-lg shadow-sm border-l-4 border-yellow-500">
            <p class="text-xs text-gray-500 uppercase">⏳ Terjadwal</p>
            <p class="text-xl font-bold text-yellow-600">{{ $scheduled }}</p>
        </div>
        <div class="bg-white p-3 rounded-lg shadow-sm border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase">🚗 On Trip</p>
            <p class="text-xl font-bold text-green-600">{{ $onTrip }}</p>
        </div>
        <div class="bg-white p-3 rounded-lg shadow-sm border-l-4 border-gray-500">
            <p class="text-xs text-gray-500 uppercase">✅ Selesai</p>
            <p class="text-xl font-bold text-gray-600">{{ $completed }}</p>
        </div>
    </div>

    {{-- JADWAL PER DRIVER --}}
    @if($drivers->isNotEmpty())
        @foreach($drivers as $driver)
            @php
                $driverRequests = $requests->get($driver->id, collect());
            @endphp
            @if($driverRequests->isNotEmpty())
            <div class="mb-6 bg-white rounded-lg shadow-sm border overflow-hidden">
                <div class="bg-gray-50 px-4 py-3 border-b flex flex-wrap justify-between items-center">
                    <div class="flex items-center gap-3">
                        <span class="font-semibold text-lg">{{ $driver->name }}</span>
                        <span class="text-sm text-gray-500">({{ $driver->phone ?? '-' }})</span>
                        <span class="px-2 py-0.5 rounded-full text-xs 
                            @if($driver->status == 'available') bg-green-100 text-green-800
                            @elseif($driver->status == 'on_trip') bg-yellow-100 text-yellow-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ ucfirst(str_replace('_', ' ', $driver->status)) }}
                        </span>
                    </div>
                    <span class="text-xs text-gray-400">{{ $driverRequests->count() }} jadwal</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="px-4 py-2 text-left">Waktu</th>
                                <th class="px-4 py-2 text-left">Tujuan</th>
                                <th class="px-4 py-2 text-left">Pemohon</th>
                                <th class="px-4 py-2 text-left">BU Pemohon</th>
                                <th class="px-4 py-2 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            @foreach($driverRequests as $req)
                                @php
                                    $start = \Carbon\Carbon::parse($req->usage_date->format('Y-m-d') . ' ' . $req->start_time);
                                    $end = $req->end_time ? \Carbon\Carbon::parse($req->usage_date->format('Y-m-d') . ' ' . $req->end_time) : null;
                                    $now = now();
                                    $buPemohon = $req->requester->drmsProfile->businessUnit->nama_bisnis_unit ?? '-';

                                    if ($req->status == 'completed') {
                                        $statusText = '✅ Selesai';
                                        $statusColor = 'bg-gray-100 text-gray-700';
                                    } elseif ($req->status == 'approved_admin') {
                                        if ($now->lessThan($start)) {
                                            $statusText = '⏳ Terjadwal';
                                            $statusColor = 'bg-blue-100 text-blue-700';
                                        } elseif ($end && $now->between($start, $end)) {
                                            $statusText = '🚗 Dalam Perjalanan';
                                            $statusColor = 'bg-green-100 text-green-700';
                                        } else {
                                            $statusText = '⏰ Selesai (Belum Diupdate)';
                                            $statusColor = 'bg-yellow-100 text-yellow-700';
                                        }
                                    } else {
                                        $statusText = ucfirst(str_replace('_', ' ', $req->status));
                                        $statusColor = 'bg-gray-100 text-gray-700';
                                    }
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm">
                                        {{ $start->format('H:i') }}
                                        @if($req->end_time)
                                            - {{ \Carbon\Carbon::parse($req->end_time)->format('H:i') }}
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm">{{ $req->destination }}</td>
                                    <td class="px-4 py-2 text-sm">{{ $req->requester->name }}</td>
                                    <td class="px-4 py-2 text-sm">{{ $buPemohon }}</td>
                                    <td class="px-4 py-2">
                                        <span class="px-2 py-0.5 rounded-full text-xs {{ $statusColor }}">
                                            {{ $statusText }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        @endforeach

        @if($requests->isEmpty())
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center text-yellow-700">
                <div class="text-3xl mb-2">📭</div>
                <p>Tidak ada jadwal untuk tanggal ini.</p>
                <p class="text-sm mt-1">Coba filter tanggal lain atau ubah pencarian.</p>
            </div>
        @endif
    @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center text-yellow-700">
            <div class="text-3xl mb-2">🚗</div>
            <p>Tidak ada driver ditemukan.</p>
            @if(auth()->user()->isDrmsSuperAdmin())
                <p class="text-sm mt-1">Pastikan filter Business Unit tidak membatasi.</p>
            @endif
        </div>
    @endif
</div>
@endsection