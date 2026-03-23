@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto">
    <h1 class="text-2xl font-bold mb-4">Jadwal Driver</h1>

    {{-- Filter berdasarkan tanggal --}}
    <div class="mb-4 flex space-x-2">
        <input type="date" id="filterDate" class="border rounded px-3 py-2" value="{{ request('date', now()->format('Y-m-d')) }}">
        <button onclick="filterByDate()" class="bg-blue-600 text-white px-4 py-2 rounded">Filter</button>
    </div>

    @forelse($drivers as $driver)
        @php
            $driverRequests = $requests->get($driver->id, collect());
        @endphp
        @if($driverRequests->isNotEmpty())
        <div class="mb-8">
            <h2 class="text-lg font-semibold mb-2">{{ $driver->name }} ({{ $driver->phone ?? 'No Phone' }})</h2>
            <div class="bg-white shadow rounded-lg overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left">Waktu</th>
                            <th class="px-4 py-2 text-left">Tujuan</th>
                            <th class="px-4 py-2 text-left">Pemohon</th>
                            <th class="px-4 py-2 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($driverRequests as $req)
                            @php
                                // Gabungkan tanggal dan jam dengan aman
                                $start = \Carbon\Carbon::parse($req->usage_date->format('Y-m-d') . ' ' . $req->start_time);
                                $end = $req->end_time ? \Carbon\Carbon::parse($req->usage_date->format('Y-m-d') . ' ' . $req->end_time) : null;
                                $now = now();

                                // Tentukan status dinamis
                                if ($req->status == 'completed') {
                                    $statusText = 'Selesai';
                                    $statusColor = 'bg-gray-500';
                                } elseif ($req->status == 'approved_admin') {
                                    if ($now->lessThan($start)) {
                                        $statusText = 'Scheduled';
                                        $statusColor = 'bg-blue-500';
                                    } elseif ($end && $now->between($start, $end)) {
                                        $statusText = 'Dalam Perjalanan';
                                        $statusColor = 'bg-green-500';
                                    } elseif ($end && $now->greaterThan($end)) {
                                        $statusText = 'Selesai (Belum Diupdate)';
                                        $statusColor = 'bg-yellow-500';
                                    } else {
                                        // Jika tidak ada end_time, asumsikan perjalanan 2 jam setelah start
                                        $estimatedEnd = $start->copy()->addHours(2);
                                        if ($now->between($start, $estimatedEnd)) {
                                            $statusText = 'Dalam Perjalanan';
                                            $statusColor = 'bg-green-500';
                                        } else {
                                            $statusText = 'Selesai (Belum Diupdate)';
                                            $statusColor = 'bg-yellow-500';
                                        }
                                    }
                                } else {
                                    $statusText = ucfirst(str_replace('_', ' ', $req->status));
                                    $statusColor = 'bg-gray-400';
                                }
                            @endphp
                            <tr class="border-t">
                                <td class="px-4 py-2">
                                {{ $start->format('H:i') }}
                                @if($req->end_time)
                                    - {{ \Carbon\Carbon::parse($req->end_time)->format('H:i') }}
                                @endif
                            </td>
                                <td class="px-4 py-2">{{ $req->destination }}</td>
                                <td class="px-4 py-2">{{ $req->requester->name }}</td>
                                <td class="px-4 py-2">
                                    <span class="text-white text-xs px-2 py-1 rounded {{ $statusColor }}">
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
    @empty
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
            Tidak ada driver ditemukan.
        </div>
    @endforelse

    @if($drivers->isNotEmpty() && $requests->isEmpty())
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
            Tidak ada jadwal untuk tanggal ini.
        </div>
    @endif
</div>

<script>
    function filterByDate() {
        const date = document.getElementById('filterDate').value;
        if (date) {
            window.location.href = '{{ route("drms.drivers.schedule") }}?date=' + date;
        }
    }
</script>
@endsection