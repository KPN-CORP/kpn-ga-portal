@extends('layouts.app-sidebar')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Dashboard Driver: {{ auth()->user()->driver->name }}</h1>
        <p class="text-gray-600">Jadwal perjalanan Anda</p>
    </div>

    {{-- Filter Tanggal --}}
    <div class="bg-white p-4 rounded-lg shadow mb-6">
        <form method="GET" action="{{ route('drms.driver.dashboard') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700">Lihat mulai tanggal</label>
                <input type="date" name="date" value="{{ $date ?? '' }}" class="border rounded px-3 py-2">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Filter</button>
                @if(request('date'))
                    <a href="{{ route('drms.driver.dashboard') }}" class="bg-gray-300 text-gray-700 px-4 py-2 rounded hover:bg-gray-400">Reset</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Jadwal Aktif --}}
    <div class="mb-10">
        <h2 class="text-xl font-semibold mb-3 border-b pb-1">Jadwal Perjalanan Aktif</h2>
        @forelse($upcomingRequests as $req)
            <div class="bg-white rounded-lg shadow mb-4 overflow-hidden">
                <div class="bg-green-50 px-4 py-2 border-b flex justify-between items-center">
                    <div>
                        <span class="font-bold">#{{ $req->request_no }}</span>
                        <span class="ml-3 text-sm text-gray-600">
                            {{ $req->trip_type == 'round_trip' ? '🔄 Pulang Pergi' : '➡️ Sekali Jalan' }}
                        </span>
                    </div>
                    <span class="px-2 py-1 rounded-full text-xs bg-green-200 text-green-800">On Schedule</span>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div><span class="font-medium">Tanggal & Waktu:</span> 
                            {{ \Carbon\Carbon::parse($req->usage_date)->format('d M Y') }} {{ $req->start_time }}
                        </div>
                        <div><span class="font-medium">Tujuan:</span> {{ $req->destination }}</div>
                        <div><span class="font-medium">Penjemputan:</span> {{ $req->pickup_location }}</div>
                        <div><span class="font-medium">Keperluan:</span> {{ \Illuminate\Support\Str::limit($req->purpose, 60) }}</div>
                        <div><span class="font-medium">Pemohon:</span> {{ $req->requester->name ?? '-' }}</div>
                        @if($req->vehicle)
                            <div><span class="font-medium">Kendaraan:</span> {{ $req->vehicle->type }} ({{ $req->vehicle->plate_number }})</div>
                        @endif
                    </div>

                    {{-- Link Maps --}}
                    <div class="mt-3 flex flex-wrap gap-3 border-t pt-3">
                        @if($req->pickup_maps_link)
                            <a href="{{ $req->pickup_maps_link }}" target="_blank" class="inline-flex items-center text-blue-600 hover:underline text-sm">
                                📍 Link Penjemputan (Maps)
                            </a>
                        @endif
                        @if($req->destination_maps_link)
                            <a href="{{ $req->destination_maps_link }}" target="_blank" class="inline-flex items-center text-blue-600 hover:underline text-sm">
                                📍 Link Tujuan (Maps)
                            </a>
                        @endif
                        @if(!$req->pickup_maps_link && !$req->destination_maps_link)
                            <span class="text-gray-400 text-sm">Tidak ada link Maps</span>
                        @endif
                    </div>

                    <div class="mt-3 flex justify-between items-center">
                        <a href="{{ route('drms.driver.requests.show', $req->id) }}" class="text-blue-600 hover:underline">Detail Lengkap →</a>

                        {{-- Tombol Selesaikan --}}
                        @if($req->status === 'approved_admin')
                            <form action="{{ route('drms.driver.requests.complete', $req->id) }}" method="POST" onsubmit="return confirm('Tandai perjalanan ini selesai?')">
                                @csrf
                                <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded text-xs font-semibold hover:bg-green-700">
                                    ✅ Selesaikan
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-gray-100 p-4 rounded text-center text-gray-600">Tidak ada jadwal aktif.</div>
        @endforelse
    </div>

    {{-- History --}}
    <div>
        <h2 class="text-xl font-semibold mb-3 border-b pb-1">History Perjalanan</h2>
        @forelse($historyRequests as $req)
            <div class="bg-white rounded-lg shadow mb-4 overflow-hidden">
                <div class="bg-gray-100 px-4 py-2 border-b flex justify-between items-center">
                    <div>
                        <span class="font-bold">#{{ $req->request_no }}</span>
                        <span class="ml-3 text-sm text-gray-600">
                            {{ $req->trip_type == 'round_trip' ? '🔄 Pulang Pergi' : '➡️ Sekali Jalan' }}
                        </span>
                    </div>
                    <span class="px-2 py-1 rounded-full text-xs 
                        {{ $req->status == 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800' }}">
                        {{ $req->status == 'completed' ? 'Selesai' : 'Ditolak Admin' }}
                    </span>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div><span class="font-medium">Tanggal:</span> {{ \Carbon\Carbon::parse($req->usage_date)->format('d M Y') }}</div>
                        <div><span class="font-medium">Tujuan:</span> {{ $req->destination }}</div>
                        <div><span class="font-medium">Penjemputan:</span> {{ $req->pickup_location }}</div>
                        <div><span class="font-medium">Keperluan:</span> {{ \Illuminate\Support\Str::limit($req->purpose, 60) }}</div>
                        <div><span class="font-medium">Pemohon:</span> {{ $req->requester->name ?? '-' }}</div>
                    </div>

                    {{-- Link Maps untuk history --}}
                    <div class="mt-3 flex flex-wrap gap-3 border-t pt-3">
                        @if($req->pickup_maps_link)
                            <a href="{{ $req->pickup_maps_link }}" target="_blank" class="inline-flex items-center text-blue-600 hover:underline text-sm">
                                📍 Link Penjemputan (Maps)
                            </a>
                        @endif
                        @if($req->destination_maps_link)
                            <a href="{{ $req->destination_maps_link }}" target="_blank" class="inline-flex items-center text-blue-600 hover:underline text-sm">
                                📍 Link Tujuan (Maps)
                            </a>
                        @endif
                        @if(!$req->pickup_maps_link && !$req->destination_maps_link)
                            <span class="text-gray-400 text-sm">Tidak ada link Maps</span>
                        @endif
                    </div>

                    <div class="mt-3 text-right">
                        <a href="{{ route('drms.driver.requests.show', $req->id) }}" class="text-blue-600 hover:underline">Detail →</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-gray-100 p-4 rounded text-center text-gray-600">Belum ada history.</div>
        @endforelse
        {{ $historyRequests->links() }}
    </div>
</div>
@endsection