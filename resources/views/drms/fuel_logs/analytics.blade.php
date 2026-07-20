@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-6">📊 Analisis Konsumsi</h1>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kendaraan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rata-rata Konsumsi (L/100km atau kWh/100km)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Liter/kWh</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Biaya</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Jarak (km)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah Isi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenis</th>
                </tr>
            </thead>
            <tbody>
                @forelse($result as $data)
                <tr>
                    <td class="px-6 py-4 font-medium">{{ $data['plate_number'] }}</td>
                    <td class="px-6 py-4">
                        @if($data['avg_consumption'] !== null)
                            {{ number_format($data['avg_consumption'], 2) }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        {{ number_format($data['total_liters'], 2, ',', '.') }}
                        {{ ($data['fuel_type'] ?? 'Bensin') == 'Listrik' ? 'kWh' : 'Liter' }}
                    </td>
                    <td class="px-6 py-4">Rp {{ number_format($data['total_cost'], 0, ',', '.') }}</td>
                    <td class="px-6 py-4">{{ number_format($data['total_distance'], 0, ',', '.') }}</td>
                    <td class="px-6 py-4">{{ $data['count'] }}</td>
                    <td class="px-6 py-4">{{ $data['fuel_type'] ?? 'Bensin' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">Belum ada data terverifikasi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection