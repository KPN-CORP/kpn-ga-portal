@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Daftar Kendaraan</h1>
        <div>
            @can('superadmin')
                <a href="{{ route('drms.vehicles.map') }}" class="bg-green-600 text-white px-4 py-2 rounded mr-2">
                    🗺️ Lihat Semua Mobil di Peta
                </a>
            @endcan
            <a href="{{ route('drms.vehicles.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">
                Tambah Kendaraan
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    {{-- Toggle filter --}}
    <div class="mb-4">
        <label class="inline-flex items-center cursor-pointer">
            <input type="checkbox" id="toggleGpsOnly" class="form-checkbox h-4 w-4 text-blue-600">
            <span class="ml-2 text-sm text-gray-700">Hanya tampilkan kendaraan dengan GPS aktif</span>
        </label>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">Tipe</th>
                    <th class="px-4 py-2">Plat Nomor</th>
                    <th class="px-4 py-2">Live GPS</th>
                    <th class="px-4 py-2">Kapasitas</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Business Unit</th>
                    <th class="px-4 py-2">Aksi</th>
                </tr>
            </thead>
            <tbody id="vehicleTableBody">
                @foreach($vehicles as $vehicle)
                <tr class="vehicle-row" data-gps-enabled="{{ $vehicle->gps_enabled ? 'yes' : 'no' }}">
                    <td class="px-4 py-2">{{ $vehicle->type }}</td>
                    <td class="px-4 py-2">{{ $vehicle->plate_number }}</td>
                    <td class="px-4 py-2">
                        @if($vehicle->gps_enabled)
                            <a href="{{ route('drms.vehicles.map.single', $vehicle) }}" class="text-purple-600" title="Lihat di Peta">
                                🗺️ Tracking
                            </a>
                        @else
                            <span class="text-gray-400"></span>
                        @endif
                    </td>
                    <td class="px-4 py-2">{{ $vehicle->capacity }}</td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-1 rounded-full text-xs {{ $vehicle->status == 'available' ? 'bg-green-100 text-green-800' : ($vehicle->status == 'in_use' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                            {{ ucfirst(str_replace('_', ' ', $vehicle->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-2">{{ $vehicle->businessUnit->nama_bisnis_unit ?? $vehicle->business_unit_id ?? '-' }}</td>
                    <td class="px-4 py-2">
                        <a href="{{ route('drms.vehicles.edit', $vehicle) }}" class="text-blue-600">Edit</a>
                        <form action="{{ route('drms.vehicles.destroy', $vehicle) }}" method="POST" class="inline" onsubmit="return confirm('Yakin?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 ml-2">Hapus</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<script>
    document.getElementById('toggleGpsOnly').addEventListener('change', function(e) {
        const rows = document.querySelectorAll('#vehicleTableBody .vehicle-row');
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