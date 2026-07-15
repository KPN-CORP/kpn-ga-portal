@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- HEADER --}}
    <div class="flex flex-wrap justify-between items-center mb-6 gap-2">
        <h1 class="text-2xl font-bold">🔧 Servis Rutin</h1>
        <a href="{{ route('drms.service-schedules.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
            <span>+</span> Tambah Servis
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    {{-- FILTER --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <form method="GET" action="{{ route('drms.service-schedules.index') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🔍 Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Cari kendaraan..." 
                       class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 w-40">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🚗 Kendaraan</label>
                <select name="vehicle_id" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    @foreach($vehicles as $v)
                        <option value="{{ $v->id }}" {{ request('vehicle_id') == $v->id ? 'selected' : '' }}>
                            {{ $v->plate_number }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📌 Jenis Servis</label>
                <select name="service_type" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    <option value="oil_change" {{ request('service_type') == 'oil_change' ? 'selected' : '' }}>Ganti Oli</option>
                    <option value="filter_change" {{ request('service_type') == 'filter_change' ? 'selected' : '' }}>Ganti Filter</option>
                    <option value="tune_up" {{ request('service_type') == 'tune_up' ? 'selected' : '' }}>Tune Up</option>
                    <option value="spooring" {{ request('service_type') == 'spooring' ? 'selected' : '' }}>Spooring</option>
                    <option value="balancing" {{ request('service_type') == 'balancing' ? 'selected' : '' }}>Balancing</option>
                    <option value="general" {{ request('service_type') == 'general' ? 'selected' : '' }}>General</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📅 Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" 
                       class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📅 Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" 
                       class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    🔍 Tampilkan
                </button>
                @if(request()->anyFilled(['search', 'vehicle_id', 'service_type', 'date_from', 'date_to']))
                    <a href="{{ route('drms.service-schedules.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- QUICK STATS --}}
    @php
        $total = $services->total();
        $totalCost = $services->sum('cost');
        $totalOilChange = $services->where('service_type', 'oil_change')->count();
        $totalGeneral = $services->where('service_type', 'general')->count();
        $uniqueVehicles = $services->pluck('vehicle_id')->unique()->count();
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase">Total Servis</p>
            <p class="text-2xl font-bold">{{ $total }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase">Total Biaya</p>
            <p class="text-2xl font-bold text-green-600">Rp {{ number_format($totalCost, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-yellow-500">
            <p class="text-xs text-gray-500 uppercase">Ganti Oli</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $totalOilChange }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-purple-500">
            <p class="text-xs text-gray-500 uppercase">General</p>
            <p class="text-2xl font-bold text-purple-600">{{ $totalGeneral }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-indigo-500">
            <p class="text-xs text-gray-500 uppercase">Kendaraan Unik</p>
            <p class="text-2xl font-bold text-indigo-600">{{ $uniqueVehicles }}</p>
        </div>
    </div>

    {{-- TABEL --}}
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kendaraan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenis</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Biaya</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Servis Berikutnya</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($services as $service)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <span class="font-medium">{{ $service->vehicle->plate_number }}</span>
                            <span class="text-xs text-gray-400 block">{{ $service->vehicle->type }}</span>
                        </td>
                        <td class="px-6 py-4">{{ $service->service_date->format('d M Y') }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs 
                                @if($service->service_type == 'oil_change') bg-blue-100 text-blue-800
                                @elseif($service->service_type == 'filter_change') bg-yellow-100 text-yellow-800
                                @elseif($service->service_type == 'tune_up') bg-purple-100 text-purple-800
                                @elseif($service->service_type == 'spooring') bg-green-100 text-green-800
                                @elseif($service->service_type == 'balancing') bg-pink-100 text-pink-800
                                @else bg-gray-100 text-gray-800 @endif">
                                {{ ucfirst(str_replace('_', ' ', $service->service_type)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 font-semibold text-red-600">Rp {{ number_format($service->cost, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            @if($service->next_service_date)
                                <span class="text-sm">{{ $service->next_service_date->format('d M Y') }}</span>
                            @elseif($service->next_service_odometer)
                                <span class="text-sm">{{ $service->next_service_odometer }} km</span>
                            @else
                                <span class="text-gray-400 text-sm">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 space-x-2">
                            <a href="{{ route('drms.service-schedules.show', $service->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">Detail</a>
                            <a href="{{ route('drms.service-schedules.edit', $service->id) }}" class="text-green-600 hover:text-green-800 text-sm">Edit</a>
                            <form action="{{ route('drms.service-schedules.destroy', $service->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                            <div class="text-4xl mb-2">🔧</div>
                            <p>Belum ada data servis.</p>
                            @if(request()->anyFilled(['search', 'vehicle_id', 'service_type', 'date_from', 'date_to']))
                                <p class="text-sm mt-1">Coba ubah filter pencarian.</p>
                            @endif
                            <a href="{{ route('drms.service-schedules.create') }}" class="mt-2 inline-block text-blue-600 hover:underline">+ Tambah Servis</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($services->hasPages())
        <div class="px-6 py-3 border-t">
            {{ $services->links() }}
        </div>
        @endif
    </div>
</div>
@endsection