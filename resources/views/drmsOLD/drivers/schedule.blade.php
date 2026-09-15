@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- HEADER --}}
    <div class="flex flex-wrap justify-between items-center mb-6 gap-2">
        <h1 class="text-2xl font-bold">📅 Jadwal Driver</h1>
        <span class="text-sm text-gray-500">{{ $monthStart->translatedFormat('F Y') }}</span>
    </div>

    {{-- FILTER --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <form method="GET" action="{{ route('drms.drivers.schedule') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📅 Bulan</label>
                <input type="month" name="month" value="{{ $month }}"
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
                @if(request()->anyFilled(['month', 'search', 'status', 'business_unit_id']))
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
        $totalRequests = $allRequests->count();
        $now = now();
        $scheduled = $allRequests->filter(function($req) use ($now) {
            return $req->status == 'approved_admin' && \Carbon\Carbon::parse($req->usage_date->format('Y-m-d') . ' ' . $req->start_time)->isFuture();
        })->count();
        $onTrip = $allRequests->filter(function($req) use ($now) {
            if ($req->status != 'approved_admin') return false;
            $start = \Carbon\Carbon::parse($req->usage_date->format('Y-m-d') . ' ' . $req->start_time);
            $endDate = ($req->trip_type === 'round_trip' && $req->return_date) ? $req->return_date : $req->usage_date;
            $end = \Carbon\Carbon::parse($endDate->format('Y-m-d') . ' ' . ($req->end_time ?? '23:59'));
            return $now->between($start, $end);
        })->count();
        $completed = $allRequests->where('status', 'completed')->count();
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

    {{-- TABEL JADWAL BULANAN (Gantt-style: bar membentang sesuai rentang tanggal request) --}}
    @if($drivers->isNotEmpty())
        <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
            {{-- max-h + overflow-y-auto: biar header tanggal "freeze" (nempel di atas) waktu discroll ke bawah,
                 mirip freeze panes di Excel. sticky top-0 pada thead nempel relatif ke div scroll ini. --}}
            <div class="overflow-auto max-h-[75vh]">
                <table class="min-w-full border-collapse text-sm table-fixed">
                    <colgroup>
                        <col style="width:160px">
                        @foreach($daysInMonth as $day)
                            <col style="width:40px">
                        @endforeach
                    </colgroup>
                    <thead class="bg-gray-50 text-xs text-gray-500 uppercase sticky top-0 z-20">
                        <tr>
                            <th class="px-3 py-2 text-left sticky left-0 top-0 bg-gray-50 z-30 border-b-2 border-r-2 border-gray-300">
                                Driver
                            </th>
                            @foreach($daysInMonth as $day)
                                <th class="py-2 text-center border-b-2 border-l border-gray-300 {{ $day->isToday() ? 'bg-blue-50 text-blue-700' : '' }}">
                                    {{ $day->format('d') }}
                                    <div class="text-[9px] font-normal normal-case text-gray-400">{{ $day->translatedFormat('D') }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($drivers as $driver)
                            @php
                                $lanes = $driverLanes[$driver->id] ?? [[['type' => 'gap', 'colspan' => $totalDays]]];
                                $laneCount = count($lanes);
                            @endphp
                            @foreach($lanes as $laneIndex => $cells)
                                <tr class="hover:bg-gray-50 border-b border-gray-200">
                                    @if($laneIndex === 0)
                                        <td rowspan="{{ $laneCount }}" class="px-3 py-2 sticky left-0 bg-white z-10 border-r-2 border-gray-300 align-top">
                                            <div class="font-semibold">{{ $driver->name }}</div>
                                            <div class="text-xs text-gray-400">{{ $driver->phone ?? '-' }}</div>
                                            <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-[10px]
                                                @if($driver->status == 'available') bg-green-100 text-green-800
                                                @elseif($driver->status == 'on_trip') bg-yellow-100 text-yellow-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst(str_replace('_', ' ', $driver->status)) }}
                                            </span>
                                        </td>
                                    @endif
                                    @foreach($cells as $cell)
                                        @if($cell['type'] === 'gap')
                                            <td colspan="{{ $cell['colspan'] }}" class="border-l border-gray-200 h-14"></td>
                                        @else
                                            @php
                                                $req = $cell['request'];
                                                $start = \Carbon\Carbon::parse($req->usage_date->format('Y-m-d') . ' ' . $req->start_time);
                                                $endDate = ($req->trip_type === 'round_trip' && $req->return_date) ? $req->return_date : $req->usage_date;
                                                $end = $req->end_time ? \Carbon\Carbon::parse($endDate->format('Y-m-d') . ' ' . $req->end_time) : null;
                                                $completedTooEarly = false;

                                                if ($req->status == 'completed') {
                                                    $statusColor = 'bg-gray-100 text-gray-700 border-gray-300';
                                                    $completedTooEarly = $now->lessThan($start);
                                                } elseif ($req->status == 'approved_admin') {
                                                    if ($now->lessThan($start)) {
                                                        $statusColor = 'bg-blue-100 text-blue-700 border-blue-300';
                                                    } elseif ($end && $now->between($start, $end)) {
                                                        $statusColor = 'bg-green-100 text-green-700 border-green-300';
                                                    } else {
                                                        $statusColor = 'bg-yellow-100 text-yellow-700 border-yellow-300';
                                                    }
                                                } else {
                                                    $statusColor = 'bg-gray-100 text-gray-700 border-gray-300';
                                                }
                                            @endphp
                                            <td colspan="{{ $cell['colspan'] }}" class="border-l border-gray-200 align-top p-0.5">
                                                <div class="rounded px-1.5 py-1 text-[11px] leading-tight border {{ $statusColor }} h-full"
                                                     title="{{ $req->destination }} — {{ $req->requester->name ?? '-' }} ({{ $req->usage_date->format('d M') }}{{ $req->trip_type === 'round_trip' && $req->return_date ? ' - ' . $req->return_date->format('d M') : '' }})">
                                                    <div class="font-semibold truncate">
                                                        {{ $start->format('H:i') }}{{ $req->end_time ? '-' . \Carbon\Carbon::parse($req->end_time)->format('H:i') : '' }}
                                                        @if($req->trip_type === 'round_trip' && $req->return_date)
                                                            <span class="font-normal text-gray-500">(PP)</span>
                                                        @endif
                                                    </div>
                                                    <div class="truncate">{{ $req->destination }}</div>
                                                    <div class="truncate text-gray-500">{{ $req->requester->name ?? '-' }}</div>
                                                    @if($completedTooEarly)
                                                        <div class="text-red-600">⚠️ terlalu awal</div>
                                                    @endif
                                                </div>
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($totalRequests === 0)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center text-yellow-700 mt-4">
                <div class="text-3xl mb-2">📭</div>
                <p>Tidak ada jadwal pada bulan ini.</p>
                <p class="text-sm mt-1">Coba pilih bulan lain atau ubah pencarian.</p>
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