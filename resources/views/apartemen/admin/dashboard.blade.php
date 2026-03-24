@extends('layouts.app_apartadmin_sidebar')
@section('content')
<div class="p-4 md:p-6">

    {{-- HEADER --}}
    <div class="mb-6 md:mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Dashboard Apartemen</h1>
            </div>

            <!-- {{-- Quick Stats --}}
            <div class="flex flex-wrap gap-2">
                <div class="bg-white border border-gray-200 rounded-lg px-4 py-2">
                    <div class="text-xs text-gray-500">Total Unit</div>
                    <div class="text-lg font-bold">{{ $stats['total_unit'] }}</div>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg px-4 py-2">
                    <div class="text-xs text-gray-500">Terisi</div>
                    <div class="text-lg font-bold text-blue-600">{{ $stats['unit_terisi'] }}</div>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg px-4 py-2">
                    <div class="text-xs text-gray-500">Tersedia</div>
                    <div class="text-lg font-bold text-green-600">{{ $stats['unit_tersedia'] }}</div>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg px-4 py-2">
                    <div class="text-xs text-gray-500">Maintenance</div>
                    <div class="text-lg font-bold text-yellow-600">{{ $stats['unit_maintenance'] }}</div>
                </div>
            </div> -->
        </div>
    </div>

    {{-- STATS CARDS --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="flex items-center">
                <div class="p-2 rounded-lg bg-blue-100 mr-3">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total Apartemen</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['total_apartemen'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="flex items-center">
                <div class="p-2 rounded-lg bg-yellow-100 mr-3">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Permintaan Pending</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['permintaan_pending'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="flex items-center">
                <div class="p-2 rounded-lg bg-green-100 mr-3">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Penghuni Aktif</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['penghuni_aktif'] }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm">
            <div class="flex items-center">
                <div class="p-2 rounded-lg bg-red-100 mr-3">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.928-.833-2.698 0L4.392 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Maintenance</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $stats['unit_maintenance'] }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ROW: PERMINTAAN PENDING & CHECKOUT MENDATANG --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Pending Requests --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Permintaan Pending</h3>
            </div>
            <div class="p-4">
                @if($pendingRequests->count() > 0)
                    <div class="space-y-3">
                        @foreach($pendingRequests as $req)
                        <div class="flex items-center justify-between p-3 bg-yellow-50 rounded-lg border border-yellow-100">
                            <div>
                                <p class="font-medium text-gray-900">{{ $req->user->name ?? 'N/A' }}</p>
                                <p class="text-xs text-gray-500">{{ $req->created_at->format('d/m/Y H:i') }}</p>
                                <p class="text-sm text-gray-600 mt-1">{{ $req->penghuni->count() }} penghuni</p>
                            </div>
                            <a href="{{ route('apartemen.admin.approve', $req->id) }}" 
                               class="px-3 py-1 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700">
                                Review
                            </a>
                        </div>
                        @endforeach
                    </div>
                    @if($pendingRequests->count() > 5)
                    <div class="mt-4 text-center">
                        <a href="{{ route('apartemen.admin.index') }}" class="text-blue-600 text-sm hover:underline">Lihat semua →</a>
                    </div>
                    @endif
                @else
                    <p class="text-gray-500 text-center py-4">Tidak ada permintaan pending</p>
                @endif
            </div>
        </div>

        {{-- Upcoming Checkouts --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Check-out Mendatang (7 hari)</h3>
            </div>
            <div class="p-4">
                @if($upcomingCheckouts->count() > 0)
                    <div class="space-y-3">
                        @foreach($upcomingCheckouts as $assign)
                        @php
                            $hari = now()->diffInDays($assign->tanggal_selesai, false);
                            $warna = $hari <= 2 ? 'text-red-600' : ($hari <= 5 ? 'text-orange-600' : 'text-yellow-600');
                        @endphp
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <p class="font-medium text-gray-900">{{ $assign->unit->apartemen->nama_apartemen }} - Unit {{ $assign->unit->nomor_unit }}</p>
                                <p class="text-xs text-gray-500">Penghuni: {{ $assign->penghuni->pluck('nama')->join(', ') }}</p>
                                <p class="text-xs {{ $warna }} font-medium">Sisa {{ $hari }} hari ({{ $assign->tanggal_selesai->format('d/m/Y') }})</p>
                            </div>
                            <a href="{{ route('apartemen.admin.monitoring') }}?search={{ $assign->penghuni->first()->nama ?? '' }}" 
                               class="text-blue-600 text-sm hover:underline">Detail</a>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500 text-center py-4">Tidak ada check-out dalam 7 hari ke depan</p>
                @endif
            </div>
        </div>
    </div>

    {{-- KALENDER FULL WIDTH (tanpa Unit Maintenance) --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Agenda & Penempatan</h3>
        <div id="calendar"></div>
    </div>

</div>

{{-- CSS & JS FullCalendar --}}
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',     // ubah ke tampilan bulan
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        events: '{{ route("apartemen.admin.calendar.events") }}',
        eventClick: function(info) {
            let props = info.event.extendedProps;
            if (props.type === 'unit') {
                alert(`Unit ${info.event.title}\nPenghuni: ${props.penghuni}`);
            } else if (props.type === 'facility') {
                alert(`Fasilitas: ${info.event.title}\nJumlah orang: ${props.jumlah_orang}`);
            }
        },
        height: 'auto'
    });
    calendar.render();
});
</script>
@endsection