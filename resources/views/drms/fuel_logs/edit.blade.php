@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto max-w-2xl">
    <h1 class="text-2xl font-bold mb-4">{{ $log->is_draft ? 'Lengkapi Pengisian' : 'Edit Log' }}</h1>

    @if($log->is_draft)
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
            <p class="font-semibold mb-1">📝 Draft — belum dikirim</p>
            <p>Foto sebelum pengisian sudah tersimpan. Setelah selesai mengisi, lengkapi jumlah, harga, foto km dan indikator setelah pengisian, dan struk, lalu tekan <strong>Selesaikan &amp; Kirim</strong>.</p>
            <p class="text-xs mt-1 text-amber-700">Belum selesai? Tekan <strong>Simpan Draft</strong> untuk menyimpan yang sudah diisi.</p>
        </div>
    @endif

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
                <label class="block text-sm font-medium text-gray-700">Harga Total <span class="text-red-500">*</span></label>
                <input type="number" name="fuel_total_price" value="{{ old('fuel_total_price', $log->total_cost) }}" class="w-full border rounded px-3 py-2" min="0" step="0.01" required>
            </div>
        </div>

        {{-- Foto km + indikator BBM/baterai SEBELUM & SETELAH pengisian.
             Log lama (sebelum fitur ini ada) belum punya foto ini, jadi saat edit log final tidak
             dipaksa. Draft yang sedang dilengkapi: foto sesudah + struk wajib (dikunci server). --}}
        {{-- Foto SEBELUM pengisian: hanya bisa dilihat, tidak bisa diganti setelah tersimpan. --}}
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Foto Km dan Indikator BBM/Baterai Sebelum Pengisian</label>
            @if($log->photo_dashboard_before)
                <a href="{{ route('drms.private.image', $log->photo_dashboard_before) }}" target="_blank" class="text-blue-600 text-sm">Lihat foto</a>
                <p class="text-xs text-gray-400 mt-1">Foto ini terkunci dan tidak bisa diganti.</p>
            @else
                <p class="text-xs text-gray-400 mt-1">Log ini tidak punya foto sebelum pengisian.</p>
            @endif
        </div>

        {{-- Foto SETELAH pengisian: log lama tidak dipaksa; draft yang dilengkapi wajib. --}}
        @php $mustFill = $log->is_draft && !$log->photo_dashboard_after; @endphp
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">
                Foto Km dan Indikator BBM/Baterai Setelah Pengisian
                @if($log->photo_dashboard_after)
                    <span class="text-gray-400 font-normal">(biarkan kosong jika tidak mengganti)</span>
                @elseif($mustFill)
                    <span class="text-red-500">*</span>
                @endif
            </label>
            <input type="file" name="photo_dashboard_after" accept="image/*" capture="environment"
                   @if($mustFill) required @endif
                   class="w-full border rounded px-3 py-2 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            @if($log->photo_dashboard_after)
                <a href="{{ route('drms.private.image', $log->photo_dashboard_after) }}" target="_blank" class="text-blue-600 text-sm">Lihat foto saat ini</a>
            @elseif($mustFill)
                <p class="text-xs text-red-500 mt-1">Foto ini wajib diisi untuk menyelesaikan pengisian.</p>
            @else
                <p class="text-xs text-gray-400 mt-1">Log ini belum punya foto ini.</p>
            @endif
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">
                Upload Struk
                @if($log->receipt_file)
                    <span class="text-gray-400 font-normal">(biarkan kosong jika tidak mengganti)</span>
                @else
                    <span class="text-red-500">*</span>
                @endif
            </label>
            <input type="file" name="receipt_file" accept="image/*" capture="environment"
                   @if(!$log->receipt_file) required @endif
                   class="w-full border rounded px-3 py-2 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            @if($log->receipt_file)
                <a href="{{ route('drms.private.image', $log->receipt_file) }}" target="_blank" class="text-blue-600 text-sm">Lihat struk saat ini</a>
            @else
                <p class="text-xs text-red-500 mt-1">Log ini belum punya foto struk, wajib diupload.</p>
            @endif
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Catatan</label>
            <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2">{{ old('notes', $log->notes) }}</textarea>
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('drms.fuel-logs.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</a>
            @if($log->is_draft)
                {{-- formnovalidate: simpan draft tidak memaksa semua kolom/foto terisi --}}
                <button type="submit" name="save_mode" value="draft" formnovalidate class="px-4 py-2 bg-amber-500 text-white rounded hover:bg-amber-600">Simpan Draft</button>
                <button type="submit" name="save_mode" value="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Selesaikan &amp; Kirim</button>
            @else
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update</button>
            @endif
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
            $('#fuel_unit_label').html(label + ' <span class="text-red-500">*</span>');
        });

        if ($('#vehicle_id').val()) {
            $('#vehicle_id').trigger('change');
        }
    });
</script>
@endsection