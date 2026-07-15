@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto max-w-2xl">
    <h1 class="text-2xl font-bold mb-4">Edit Log BBM</h1>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('drms.fuel-logs.update', $log->id) }}" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded shadow">
        @csrf @method('PUT')

        {{-- Kendaraan dengan Select2 --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Kendaraan <span class="text-red-500">*</span></label>
            <select name="vehicle_id" id="vehicle_id" class="w-full border rounded px-3 py-2 select2" required>
                <option value="">Cari Kendaraan...</option>
                @foreach($vehicles as $v)
                    <option value="{{ $v->id }}" data-fuel="{{ $v->fuel_type }}" {{ old('vehicle_id', $log->vehicle_id) == $v->id ? 'selected' : '' }}>
                        {{ $v->plate_number }} - {{ $v->type }} ({{ $v->fuel_type ?? 'Bensin' }})
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
            <input type="date" name="filling_date" value="{{ old('filling_date', $log->filling_date->format('Y-m-d')) }}" class="w-full border rounded px-3 py-2" required>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Odometer Saat Ini (km) <span class="text-red-500">*</span></label>
            <input type="number" name="odometer_start" value="{{ old('odometer_start', $log->odometer_start) }}" class="w-full border rounded px-3 py-2" min="0" required>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700" id="fuel_unit_label">Liter <span class="text-red-500">*</span></label>
                <input type="number" name="fuel_liters" id="fuel_liters" value="{{ old('fuel_liters', $log->fuel_liters) }}" class="w-full border rounded px-3 py-2" min="0.01" step="0.01" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Harga/unit <span class="text-red-500">*</span></label>
                <input type="number" name="fuel_price_per_liter" value="{{ old('fuel_price_per_liter', $log->fuel_price_per_liter) }}" class="w-full border rounded px-3 py-2" min="0" step="0.01" required>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Upload Struk (biarkan kosong jika tidak mengganti)</label>
            <input type="file" name="receipt_file" accept="image/*" capture="environment" class="w-full border rounded px-3 py-2 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            @if($log->receipt_file)
                <a href="{{ route('drms.private.image', $log->receipt_file) }}" target="_blank" class="text-blue-600 text-sm">Lihat struk saat ini</a>
            @endif
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Catatan</label>
            <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2">{{ old('notes', $log->notes) }}</textarea>
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('drms.fuel-logs.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update</button>
        </div>
    </form>
</div>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function() {
        $('#vehicle_id').select2({
            placeholder: 'Cari kendaraan...',
            allowClear: true,
            width: '100%'
        });

        $('#vehicle_id').on('change', function() {
            const selected = $(this).find(':selected');
            const fuelType = selected.data('fuel');
            let label = 'Liter';
            if (fuelType && fuelType.toLowerCase() === 'listrik') {
                label = 'kWh';
            }
            $('#fuel_unit_label').text(label + ' <span class="text-red-500">*</span>');
        });

        if ($('#vehicle_id').val()) {
            $('#vehicle_id').trigger('change');
        }
    });
</script>
@endsection