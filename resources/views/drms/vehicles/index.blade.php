@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Daftar Kendaraan</h1>
        <a href="{{ route('drms.vehicles.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded">Tambah Kendaraan</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">Tipe</th>
                    <th class="px-4 py-2">Plat Nomor</th>
                    <th class="px-4 py-2">Kapasitas</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($vehicles as $vehicle)
                <tr>
                    <td class="px-4 py-2">{{ $vehicle->type }}</td>
                    <td class="px-4 py-2">{{ $vehicle->plate_number }}</td>
                    <td class="px-4 py-2">{{ $vehicle->capacity }}</td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-1 rounded-full text-xs {{ $vehicle->status == 'available' ? 'bg-green-100 text-green-800' : ($vehicle->status == 'in_use' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800') }}">
                            {{ ucfirst(str_replace('_', ' ', $vehicle->status)) }}
                        </span>
                    </td>
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
@endsection