@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto max-w-lg">
    <h1 class="text-2xl font-bold mb-4">Tambah Driver Baru</h1>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('drms.drivers.store') }}" method="POST" class="bg-white p-6 rounded shadow">
        @csrf
        <div class="mb-4">
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nama Driver</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                   class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Nomor Telepon</label>
            <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                   class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div class="mb-4">
            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" id="status" required
                    class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="available" {{ old('status') == 'available' ? 'selected' : '' }}>Available</option>
                <option value="on_trip" {{ old('status') == 'on_trip' ? 'selected' : '' }}>On Trip</option>
                <option value="off_duty" {{ old('status') == 'off_duty' ? 'selected' : '' }}>Off Duty</option>
            </select>
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('drms.drivers.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Simpan</button>
        </div>
    </form>
</div>
@endsection