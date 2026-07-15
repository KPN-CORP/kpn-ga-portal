@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto max-w-2xl">
    <h1 class="text-2xl font-bold mb-4">Tambah Log BBM</h1>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('drms.fuel-logs.store') }}" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded shadow">
        @csrf

        {{-- Kendaraan --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Kendaraan <span class="text-red-500">*</span></label>
            <select name="vehicle_id" id="vehicle_id" class="w-full border rounded px-3 py-2" required>
                <option value="">Pilih Kendaraan</option>
                @foreach($vehicles as $v)
                    <option value="{{ $v->id }}" 
                            data-fuel="{{ $v->fuel_type }}" 
                            {{ old('vehicle_id') == $v->id ? 'selected' : '' }}>
                        {{ $v->plate_number }} - {{ $v->type }} 
                        @if($v->fuel_type)
                            ({{ $v->fuel_type }})
                        @else
                            (Bensin)
                        @endif
                    </option>
                @endforeach
            </select>
        </div>

        <input type="hidden" name="driver_id" value="{{ $driver->id ?? '' }}">

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Driver</label>
            <input type="text" value="{{ $driver->name ?? 'Tidak ada driver' }}" disabled class="w-full border rounded px-3 py-2 bg-gray-100">
            <p class="text-xs text-gray-400 mt-1">Driver diambil dari user yang login</p>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Tanggal Pengisian <span class="text-red-500">*</span></label>
            <input type="date" name="filling_date" value="{{ old('filling_date', date('Y-m-d')) }}" class="w-full border rounded px-3 py-2" required>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Odometer Saat Ini (km) <span class="text-red-500">*</span></label>
            <input type="number" name="odometer_start" value="{{ old('odometer_start') }}" class="w-full border rounded px-3 py-2" min="0" required>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700" id="fuel_unit_label">Liter <span class="text-red-500">*</span></label>
                <input type="number" name="fuel_liters" id="fuel_liters" value="{{ old('fuel_liters') }}" class="w-full border rounded px-3 py-2" min="0.01" step="0.01" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Harga/unit <span class="text-red-500">*</span></label>
                <input type="number" name="fuel_price_per_liter" value="{{ old('fuel_price_per_liter') }}" class="w-full border rounded px-3 py-2" min="0" step="0.01" required>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Upload Struk (ambil dari kamera)</label>
            <input type="file" name="receipt_file" accept="image/*" capture="environment" class="w-full border rounded px-3 py-2 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            <p class="text-xs text-gray-400 mt-1">Untuk HP akan membuka kamera, untuk laptop bisa pilih file</p>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Catatan</label>
            <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2">{{ old('notes') }}</textarea>
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('drms.fuel-logs.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Simpan</button>
        </div>
    </form>
</div>

{{-- Script otomatis ganti Liter/kWh (TANPA SELECT2) --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const vehicleSelect = document.getElementById('vehicle_id');
        const fuelUnitLabel = document.getElementById('fuel_unit_label');

        function updateFuelUnit() {
            const selectedOption = vehicleSelect.options[vehicleSelect.selectedIndex];
            const fuelType = selectedOption ? selectedOption.getAttribute('data-fuel') : null;
            
            console.log('Selected fuel type:', fuelType); // Debug

            let unit = 'Liter';
            if (fuelType) {
                const lower = fuelType.toLowerCase();
                if (lower === 'listrik') {
                    unit = 'kWh';
                }
            }
            
            fuelUnitLabel.innerHTML = unit + ' <span class="text-red-500">*</span>';
        }

        // Jalankan saat pertama kali load (jika ada selected)
        updateFuelUnit();

        // Jalankan saat pilihan berubah
        vehicleSelect.addEventListener('change', updateFuelUnit);
    });
</script>
@endsection