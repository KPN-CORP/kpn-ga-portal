@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Monitoring Log Driver</h1>
        <a href="{{ route('drms.admin.operational.dashboard') }}" class="bg-blue-600 text-white px-4 py-2 rounded">Dashboard Grafik</a>
    </div>

    <div class="mb-4 flex gap-2">
        <a href="{{ route('drms.admin.monitoring.logs', ['status' => 'pending']) }}" class="px-4 py-2 bg-yellow-100 text-yellow-800 rounded">Menunggu Verifikasi</a>
        <a href="{{ route('drms.admin.monitoring.logs', ['status' => 'verified']) }}" class="px-4 py-2 bg-green-100 text-green-800 rounded">Sudah Diverifikasi</a>
        <a href="{{ route('drms.admin.monitoring.logs', ['status' => 'draft']) }}" class="px-4 py-2 bg-gray-100 text-gray-800 rounded">Draft</a>
        <a href="{{ route('drms.admin.monitoring.logs') }}" class="px-4 py-2 bg-blue-100 text-blue-800 rounded">Semua</a>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2 text-left">Request</th>
                    <th class="px-4 py-2 text-left">Driver</th>
                    <th class="px-4 py-2 text-left">Odometer</th>
                    <th class="px-4 py-2 text-left">Jarak</th>
                    <th class="px-4 py-2 text-left">Efisiensi</th>
                    <th class="px-4 py-2 text-left">Status</th>
                    <th class="px-4 py-2 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr class="border-t">
                    <td class="px-4 py-2">{{ $log->request->request_no ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $log->request->driver->name ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $log->odometer_start ?? '-' }} → {{ $log->odometer_finish ?? '-' }}</td>
                    <td class="px-4 py-2">{{ $log->distance ?? '-' }} km</td>
                    <td class="px-4 py-2">{{ $log->efficiency ?? '-' }} {{ $log->fuel_type == 'listrik' ? 'km/kWh' : 'km/liter' }}</td>
                    <td class="px-4 py-2">
                        @if($log->is_submitted && !$log->is_verified)
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Menunggu Verifikasi</span>
                        @elseif($log->is_verified)
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Diverifikasi</span>
                        @else
                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">Draft</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        @if($log->is_submitted && !$log->is_verified)
                            <a href="{{ route('drms.admin.verify.log', $log->id) }}" class="text-blue-600">Verifikasi</a>
                        @else
                            <a href="{{ route('drms.admin.verify.log', $log->id) }}" class="text-gray-600">Detail</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-4 py-6 text-center text-gray-500">Belum ada log.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $logs->links() }}
    </div>
</div>
@endsection