@extends('layouts.app_car_sidebar')

@section('title', 'Edit Kendaraan')
@section('breadcrumb', 'Edit Kendaraan')

@section('content')
<div class="container mx-auto max-w-lg">
    <h1 class="text-2xl font-bold mb-4">Edit Kendaraan</h1>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('drms.vehicles.update', $vehicle->id) }}" method="POST" class="bg-white p-6 rounded shadow">
        @csrf
        @method('PUT')

        <div class="mb-4">
            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Tipe Kendaraan</label>
            <input type="text" name="type" id="type" value="{{ old('type', $vehicle->type) }}" required
                   class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label for="plate_number" class="block text-sm font-medium text-gray-700 mb-1">Nomor Polisi</label>
            <input type="text" name="plate_number" id="plate_number" value="{{ old('plate_number', $vehicle->plate_number) }}" required
                   class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label for="capacity" class="block text-sm font-medium text-gray-700 mb-1">Kapasitas (orang)</label>
            <input type="number" name="capacity" id="capacity" value="{{ old('capacity', $vehicle->capacity) }}" required min="1"
                   class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" id="status" required
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="available" {{ old('status', $vehicle->status) == 'available' ? 'selected' : '' }}>Available</option>
                <option value="in_use" {{ old('status', $vehicle->status) == 'in_use' ? 'selected' : '' }}>In Use</option>
                <option value="maintenance" {{ old('status', $vehicle->status) == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
            </select>
        </div>

        {{-- Business Unit bisa ditampilkan sebagai informasi (readonly) atau diisi jika diperlukan --}}
        @if(isset($vehicle->business_unit_id))
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Business Unit ID</label>
                <input type="text" value="{{ $vehicle->business_unit_id }}" disabled
                       class="w-full border rounded px-3 py-2 bg-gray-100 text-gray-600">
                <p class="text-xs text-gray-500 mt-1">Tidak dapat diubah</p>
            </div>
        @endif

        <div class="flex justify-end space-x-2">
            <a href="{{ route('drms.vehicles.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update</button>
        </div>
    </form>
</div>
@endsection