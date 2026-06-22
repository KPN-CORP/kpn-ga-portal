@extends('layouts.app_car_drive_sidebar')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Dashboard Driver: {{ auth()->user()->driver->name ?? 'Driver' }}</h1>
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

        {{-- Notifikasi Revisi --}}
        @php
            $revisionLogs = $upcomingRequests->filter(function($req) {
                return $req->tripLog && $req->tripLog->needsRevision();
            });
        @endphp
        @if($revisionLogs->count() > 0)
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4 rounded">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-800">
                            <span class="font-bold">{{ $revisionLogs->count() }}</span> log perjalanan memerlukan revisi.
                            Klik <span class="font-semibold">"📝 Perbaiki Log"</span> di kartu perjalanan.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        @forelse($upcomingRequests as $req)
            @php
                $log = $req->tripLog;
                $needsRevision = $log && $log->needsRevision();
                $isSubmitted = $log && $log->is_submitted;
            @endphp
            <div class="bg-white rounded-lg shadow mb-4 overflow-hidden {{ $needsRevision ? 'border-2 border-yellow-400' : '' }}">
                <div class="bg-green-50 px-4 py-2 border-b flex justify-between items-center">
                    <div>
                        <span class="font-bold">#{{ $req->request_no }}</span>
                        <span class="ml-3 text-sm text-gray-600">
                            {{ $req->trip_type == 'round_trip' ? '🔄 Pulang Pergi' : '➡️ Sekali Jalan' }}
                        </span>
                        @if($needsRevision)
                            <span class="ml-3 px-2 py-1 bg-yellow-400 text-yellow-800 rounded-full text-xs font-bold">
                                ⚠️ Perlu Revisi
                            </span>
                        @elseif($isSubmitted && !$log->is_verified)
                            <span class="ml-3 px-2 py-1 bg-blue-200 text-blue-800 rounded-full text-xs">
                                ⏳ Menunggu Verifikasi Admin
                            </span>
                        @elseif($log && $log->is_verified)
                            <span class="ml-3 px-2 py-1 bg-green-200 text-green-800 rounded-full text-xs">
                                ✅ Terverifikasi
                            </span>
                        @endif
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

                    {{-- Catatan Admin jika revisi --}}
                    @if($needsRevision && $log && $log->revision_note)
                        <div class="mt-3 bg-yellow-50 border border-yellow-200 rounded p-2 text-sm">
                            <span class="font-semibold text-yellow-800">📌 Catatan Admin:</span>
                            <span class="text-yellow-700">{{ $log->revision_note }}</span>
                        </div>
                    @endif

                    <div class="mt-3 flex justify-between items-center flex-wrap gap-2">
                        <a href="{{ route('drms.driver.requests.show', $req->id) }}" class="text-blue-600 hover:underline">Detail Lengkap →</a>
                        <div class="flex gap-2">
                            {{-- TOMBOL ISI / PERBAIKI LOG --}}
                            <a href="{{ route('drms.driver.trip.log.create', $req->id) }}" 
                               class="{{ $needsRevision ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-blue-600 hover:bg-blue-700' }} text-white px-3 py-1 rounded text-xs font-semibold transition">
                                {{ $needsRevision ? '📝 Perbaiki Log' : '📝 Isi Log' }}
                            </a>
                            @if($req->status === 'approved_admin' && !$needsRevision)
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
            </div>
        @empty
            <div class="bg-gray-100 p-4 rounded text-center text-gray-600">Tidak ada jadwal aktif.</div>
        @endforelse
    </div>

    {{-- HISTORY --}}
    <div>
        <h2 class="text-xl font-semibold mb-3 border-b pb-1">History Perjalanan</h2>
        @forelse($historyRequests as $req)
            @php
                $log = $req->tripLog;
                $needsRevision = $log && $log->needsRevision();
            @endphp
            <div class="bg-white rounded-lg shadow mb-4 overflow-hidden {{ $needsRevision ? 'border-2 border-yellow-400' : '' }}">
                <div class="bg-gray-100 px-4 py-2 border-b flex justify-between items-center">
                    <div>
                        <span class="font-bold">#{{ $req->request_no }}</span>
                        <span class="ml-3 text-sm text-gray-600">
                            {{ $req->trip_type == 'round_trip' ? '🔄 Pulang Pergi' : '➡️ Sekali Jalan' }}
                        </span>
                        @if($needsRevision)
                            <span class="ml-3 px-2 py-1 bg-yellow-400 text-yellow-800 rounded-full text-xs font-bold">
                                ⚠️ Perlu Revisi
                            </span>
                        @else
                            <span class="ml-3 px-2 py-1 rounded-full text-xs 
                                {{ $req->status == 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800' }}">
                                {{ $req->status == 'completed' ? 'Selesai' : 'Ditolak Admin' }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="p-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div><span class="font-medium">Tanggal:</span> {{ \Carbon\Carbon::parse($req->usage_date)->format('d M Y') }}</div>
                        <div><span class="font-medium">Tujuan:</span> {{ $req->destination }}</div>
                        <div><span class="font-medium">Penjemputan:</span> {{ $req->pickup_location }}</div>
                        <div><span class="font-medium">Keperluan:</span> {{ \Illuminate\Support\Str::limit($req->purpose, 60) }}</div>
                        <div><span class="font-medium">Pemohon:</span> {{ $req->requester->name ?? '-' }}</div>
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

                    {{-- Catatan Admin jika revisi --}}
                    @if($needsRevision && $log && $log->revision_note)
                        <div class="mt-3 bg-yellow-50 border border-yellow-200 rounded p-2 text-sm">
                            <span class="font-semibold text-yellow-800">📌 Catatan Admin:</span>
                            <span class="text-yellow-700">{{ $log->revision_note }}</span>
                        </div>
                    @endif

                    <div class="mt-3 flex justify-between items-center flex-wrap gap-2">
                        <a href="{{ route('drms.driver.requests.show', $req->id) }}" class="text-blue-600 hover:underline">Detail →</a>
                        @if($needsRevision)
                            <a href="{{ route('drms.driver.trip.log.create', $req->id) }}" 
                               class="bg-yellow-500 text-white px-3 py-1 rounded text-xs font-semibold hover:bg-yellow-600 transition">
                                📝 Perbaiki Log
                            </a>
                        @endif
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