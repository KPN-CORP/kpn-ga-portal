@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto max-w-lg">
    <h1 class="text-2xl font-bold mb-4">Tambah Kendaraan Baru</h1>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('drms.vehicles.store') }}" method="POST" class="bg-white p-6 rounded shadow">
        @csrf
        <div class="mb-4">
            <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Tipe Kendaraan</label>
            <input type="text" name="type" id="type" value="{{ old('type') }}" required
                   class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label for="plate_number" class="block text-sm font-medium text-gray-700 mb-1">Nomor Polisi</label>
            <input type="text" name="plate_number" id="plate_number" value="{{ old('plate_number') }}" required
                   class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label for="capacity" class="block text-sm font-medium text-gray-700 mb-1">Kapasitas (orang)</label>
            <input type="number" name="capacity" id="capacity" value="{{ old('capacity', 4) }}" min="1"
                   class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" id="status" required
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="available" {{ old('status') == 'available' ? 'selected' : '' }}>Available</option>
                <option value="in_use" {{ old('status') == 'in_use' ? 'selected' : '' }}>In Use</option>
                <option value="maintenance" {{ old('status') == 'maintenance' ? 'selected' : '' }}>Maintenance</option>
            </select>
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('drms.vehicles.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Simpan</button>
        </div>
    </form>
</div>
@endsection