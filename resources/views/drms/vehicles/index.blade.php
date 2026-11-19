@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto max-w-full px-4 py-6">
    <div class="flex flex-wrap justify-between items-center mb-4 gap-2">
        <h1 class="text-xl sm:text-2xl font-bold">Daftar Kendaraan</h1>
        <div class="flex flex-wrap gap-2 w-full sm:w-auto">
            @can('superadmin')
                <a href="{{ route('drms.vehicles.map') }}" class="flex-1 sm:flex-none text-center bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    🗺️ Lihat Semua Mobil di Peta
                </a>
            @endcan
            <a href="{{ route('drms.vehicles.create') }}" class="flex-1 sm:flex-none text-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                + Tambah Kendaraan
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    {{-- FILTER: HP satu kolom, tablet dua kolom, desktop sebaris --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-4">
        <form method="GET" action="{{ route('drms.vehicles.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:flex lg:flex-wrap gap-3 lg:items-end">
            <!-- <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🔍 Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Plat atau tipe..." 
                       class="w-full md:w-40 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div> -->
            @if(auth()->user()->isDrmsSuperAdmin())
            <div class="w-full lg:w-auto">
                <label class="block text-xs font-medium text-gray-600 mb-1">🏢 Business Unit</label>
                <select name="business_unit_id" class="w-full lg:w-40 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua BU</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}" {{ request('business_unit_id') == $bu->id_bisnis_unit ? 'selected' : '' }}>
                            {{ $bu->nama_bisnis_unit }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="w-full lg:w-auto">
                <label class="block text-xs font-medium text-gray-600 mb-1">📌 Status</label>
                <select name="status" class="w-full lg:w-32 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>✅ Available</option>
                    <option value="in_use" {{ request('status') == 'in_use' ? 'selected' : '' }}>🔄 In Use</option>
                    <option value="maintenance" {{ request('status') == 'maintenance' ? 'selected' : '' }}>🔧 Maintenance</option>
                </select>
            </div>
            <div class="w-full lg:w-auto">
                <label class="block text-xs font-medium text-gray-600 mb-1">⛽ Bahan Bakar</label>
                <select name="fuel_type" class="w-full lg:w-32 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    <option value="none" {{ request('fuel_type') == 'none' ? 'selected' : '' }}>❌ Tidak Ada</option>
                    <option value="Bensin" {{ request('fuel_type') == 'Bensin' ? 'selected' : '' }}>⛽ Bensin</option>
                    <option value="Solar" {{ request('fuel_type') == 'Solar' ? 'selected' : '' }}>⛽ Solar</option>
                    <option value="Listrik" {{ request('fuel_type') == 'Listrik' ? 'selected' : '' }}>⚡ Listrik</option>
                    <option value="Hybrid" {{ request('fuel_type') == 'Hybrid' ? 'selected' : '' }}>🔄 Hybrid</option>
                    <option value="Lainnya" {{ request('fuel_type') == 'Lainnya' ? 'selected' : '' }}>🔄 Lainnya</option>
                </select>
            </div>
            <div class="w-full lg:w-auto">
                <label class="block text-xs font-medium text-gray-600 mb-1">🛰️ GPS</label>
                <select name="gps" class="w-full lg:w-28 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    <option value="1" {{ request('gps') == '1' ? 'selected' : '' }}>✅ Aktif</option>
                    <option value="0" {{ request('gps') == '0' ? 'selected' : '' }}>❌ Nonaktif</option>
                </select>
            </div>
            <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                <button type="submit" class="flex-1 lg:flex-none bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    🔍 Filter
                </button>
                @if(request()->anyFilled(['search', 'status', 'fuel_type', 'gps', 'business_unit_id']))
                    <a href="{{ route('drms.vehicles.index') }}" class="flex-1 lg:flex-none text-center bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- QUICK STATS --}}
    @php
        $total = $vehicles->total();
        $available = $vehicles->where('status', 'available')->count();
        $inUse = $vehicles->where('status', 'in_use')->count();
        $maintenance = $vehicles->where('status', 'maintenance')->count();
        $gpsActive = $vehicles->where('gps_enabled', 1)->count();

        // Warna badge (dipakai tabel desktop dan kartu HP)
        $fuelClass = function ($fuel) {
            if ($fuel == 'Listrik') return 'bg-blue-100 text-blue-800';
            if ($fuel == 'Solar') return 'bg-yellow-100 text-yellow-800';
            if ($fuel) return 'bg-gray-100 text-gray-800';
            return 'bg-gray-50 text-gray-400';
        };
        $statusClass = function ($status) {
            if ($status == 'available') return 'bg-green-100 text-green-800';
            if ($status == 'in_use') return 'bg-yellow-100 text-yellow-800';
            return 'bg-red-100 text-red-800';
        };
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
        <div class="bg-white p-3 rounded-lg shadow-sm border-l-4 border-blue-500 col-span-2 md:col-span-1">
            <p class="text-xs text-gray-500 uppercase">Total Kendaraan</p>
            <p class="text-xl font-bold">{{ $total }}</p>
        </div>
        <div class="bg-white p-3 rounded-lg shadow-sm border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase">✅ Available</p>
            <p class="text-xl font-bold text-green-600">{{ $available }}</p>
        </div>
        <div class="bg-white p-3 rounded-lg shadow-sm border-l-4 border-yellow-500">
            <p class="text-xs text-gray-500 uppercase">🔄 In Use</p>
            <p class="text-xl font-bold text-yellow-600">{{ $inUse }}</p>
        </div>
        <div class="bg-white p-3 rounded-lg shadow-sm border-l-4 border-red-500">
            <p class="text-xs text-gray-500 uppercase">🔧 Maintenance</p>
            <p class="text-xl font-bold text-red-600">{{ $maintenance }}</p>
        </div>
        <div class="bg-white p-3 rounded-lg shadow-sm border-l-4 border-purple-500">
            <p class="text-xs text-gray-500 uppercase">🛰️ GPS Aktif</p>
            <p class="text-xl font-bold text-purple-600">{{ $gpsActive }}</p>
        </div>
    </div>

    {{-- Toggle filter GPS (tetap dipertahankan) --}}
    <div class="mb-3">
        <label class="inline-flex items-start cursor-pointer">
            <input type="checkbox" id="toggleGpsOnly" class="form-checkbox h-4 w-4 mt-0.5 text-blue-600 rounded">
            <span class="ml-2 text-sm text-gray-700">Hanya tampilkan kendaraan dengan GPS aktif (filter client-side)</span>
        </label>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        {{-- DESKTOP / TABLET: TABEL --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Merek</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Plat Nomor</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Live GPS</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Kapasitas</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Bahan Bakar</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Business Unit</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody id="vehicleTableBody">
                    @forelse($vehicles as $vehicle)
                    <tr class="vehicle-row hover:bg-gray-50" data-gps-enabled="{{ $vehicle->gps_enabled ? 'yes' : 'no' }}">
                        <td class="px-4 py-2">{{ $vehicle->type }}</td>
                        <td class="px-4 py-2 font-medium">{{ $vehicle->plate_number }}</td>
                        <td class="px-4 py-2">
                            @if($vehicle->gps_enabled)
                                <a href="{{ route('drms.vehicles.map.single', $vehicle) }}" class="text-purple-600 hover:underline" title="Lihat di Peta">
                                    🗺️ Tracking
                                </a>
                            @else
                                <span class="text-gray-400 text-xs">Tidak Aktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">{{ $vehicle->capacity }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 rounded-full text-xs {{ $fuelClass($vehicle->fuel_type) }}">
                                {{ $vehicle->fuel_type ?? '-' }}
                            </span>
                        </td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 rounded-full text-xs {{ $statusClass($vehicle->status) }}">
                                {{ ucfirst(str_replace('_', ' ', $vehicle->status)) }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-sm">{{ $vehicle->businessUnit->nama_bisnis_unit ?? $vehicle->business_unit_id ?? '-' }}</td>
                        <td class="px-4 py-2 space-x-2">
                            <a href="{{ route('drms.vehicles.edit', $vehicle) }}" class="text-blue-600 hover:underline">Edit</a>
                            <form action="{{ route('drms.vehicles.destroy', $vehicle) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus kendaraan ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline ml-1">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-4 py-8 text-center text-gray-500">
                            <div class="text-4xl mb-2">🚗</div>
                            <p>Belum ada kendaraan.</p>
                            @if(request()->anyFilled(['search', 'status', 'fuel_type', 'gps', 'business_unit_id']))
                                <p class="text-sm mt-1">Coba ubah filter pencarian.</p>
                            @endif
                            <a href="{{ route('drms.vehicles.create') }}" class="mt-2 inline-block text-blue-600 hover:underline">+ Tambah Kendaraan</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- HP: KARTU PER KENDARAAN (tanpa geser samping) --}}
        <div class="md:hidden divide-y divide-gray-200" id="vehicleCardList">
            @forelse($vehicles as $vehicle)
            <div class="vehicle-row p-4 space-y-2 break-words min-w-0" data-gps-enabled="{{ $vehicle->gps_enabled ? 'yes' : 'no' }}">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold text-gray-800">{{ $vehicle->type }}</p>
                        <p class="text-sm font-mono text-gray-600">{{ $vehicle->plate_number }}</p>
                    </div>
                    <span class="shrink-0 px-2 py-1 rounded-full text-xs {{ $statusClass($vehicle->status) }}">
                        {{ ucfirst(str_replace('_', ' ', $vehicle->status)) }}
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-600">
                    <span>👥 {{ $vehicle->capacity }} kursi</span>
                    <span>⛽ <span class="px-2 py-0.5 rounded-full {{ $fuelClass($vehicle->fuel_type) }}">{{ $vehicle->fuel_type ?? '-' }}</span></span>
                </div>
                <div class="text-xs text-gray-600">🏢 {{ $vehicle->businessUnit->nama_bisnis_unit ?? $vehicle->business_unit_id ?? '-' }}</div>
                <div class="text-xs">
                    🛰️
                    @if($vehicle->gps_enabled)
                        <a href="{{ route('drms.vehicles.map.single', $vehicle) }}" class="text-purple-600 font-semibold">🗺️ Tracking</a>
                    @else
                        <span class="text-gray-400">GPS Tidak Aktif</span>
                    @endif
                </div>
                <div class="flex gap-2 pt-1">
                    <a href="{{ route('drms.vehicles.edit', $vehicle) }}" class="flex-1 text-center bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-xs font-semibold">Edit</a>
                    <form action="{{ route('drms.vehicles.destroy', $vehicle) }}" method="POST" class="flex-1" onsubmit="return confirm('Yakin ingin menghapus kendaraan ini?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded text-xs font-semibold">Hapus</button>
                    </form>
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-gray-500">
                <div class="text-4xl mb-2">🚗</div>
                <p>Belum ada kendaraan.</p>
                @if(request()->anyFilled(['search', 'status', 'fuel_type', 'gps', 'business_unit_id']))
                    <p class="text-sm mt-1">Coba ubah filter pencarian.</p>
                @endif
                <a href="{{ route('drms.vehicles.create') }}" class="mt-2 inline-block text-blue-600 hover:underline">+ Tambah Kendaraan</a>
            </div>
            @endforelse
        </div>

        @if($vehicles->hasPages())
        <div class="px-4 py-3 border-t">
            {{ $vehicles->links() }}
        </div>
        @endif
    </div>
</div>

{{-- Script untuk toggle GPS (berlaku untuk baris tabel desktop dan kartu HP) --}}
<script>
    document.getElementById('toggleGpsOnly').addEventListener('change', function(e) {
        const rows = document.querySelectorAll('.vehicle-row');
        const onlyGps = e.target.checked;
        rows.forEach(row => {
            const gpsActive = row.getAttribute('data-gps-enabled') === 'yes';
            if (onlyGps && !gpsActive) {
                row.style.display = 'none';
            } else {
                row.style.display = '';
            }
        });
    });
</script>
@endsection