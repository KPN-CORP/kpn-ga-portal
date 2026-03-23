@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Daftar Driver</h1>
        <a href="{{ route('drms.drivers.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Tambah Driver</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left">Nama</th>
                    <th class="px-6 py-3 text-left">Telepon</th>
                    <th class="px-6 py-3 text-left">Status</th>
                    <th class="px-6 py-3 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($drivers as $driver)
                <tr>
                    <td class="px-6 py-4">{{ $driver->name }}</td>
                    <td class="px-6 py-4">{{ $driver->phone }}</td>
                    <td class="px-6 py-4">
                        @php
                            $statusColors = [
                                'available' => 'bg-green-100 text-green-800',
                                'on_trip' => 'bg-yellow-100 text-yellow-800',
                                'off_duty' => 'bg-gray-100 text-gray-800',
                            ];
                        @endphp
                        <span class="px-2 py-1 rounded-full text-xs {{ $statusColors[$driver->status] }}">
                            {{ ucfirst(str_replace('_', ' ', $driver->status)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 space-x-2">
                        <a href="{{ route('drms.drivers.edit', $driver) }}" class="text-blue-600 hover:underline">Edit</a>
                        <form action="{{ route('drms.drivers.destroy', $driver) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection