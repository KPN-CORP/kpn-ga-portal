@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto max-w-2xl">
    <h1 class="text-2xl font-bold mb-4">Edit Servis Rutin</h1>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('drms.service-schedules.update', $service->id) }}" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded shadow">
        @csrf @method('PUT')

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Kendaraan <span class="text-red-500">*</span></label>
            @include('drms.partials.vehicle-search', [
                'vehicles' => $vehicles,
                'name' => 'vehicle_id',
                'selectedId' => old('vehicle_id', $service->vehicle_id),
                'placeholder' => 'Cari plat nomor / tipe kendaraan...',
                'required' => true,
                'allowAll' => false,
                'uid' => 'service_schedules_edit_vehicle',
            ])
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Tanggal Servis <span class="text-red-500">*</span></label>
            <input type="date" name="service_date" value="{{ old('service_date', $service->service_date->format('Y-m-d')) }}" class="w-full border rounded px-3 py-2" required>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Odometer (km)</label>
            <input type="number" name="odometer_at_service" value="{{ old('odometer_at_service', $service->odometer_at_service) }}" class="w-full border rounded px-3 py-2" min="0">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Jenis Servis <span class="text-red-500">*</span></label>
            @php
                $oldTypes = old('service_type', $service->service_type ?? []);
            @endphp
            <select name="service_type[]" id="service_type" class="w-full border rounded px-3 py-2" multiple required>
                <option value="oil_change" {{ in_array('oil_change', $oldTypes) ? 'selected' : '' }}>Ganti Oli</option>
                <option value="filter_change" {{ in_array('filter_change', $oldTypes) ? 'selected' : '' }}>Ganti Filter</option>
                <option value="tune_up" {{ in_array('tune_up', $oldTypes) ? 'selected' : '' }}>Tune Up</option>
                <option value="spooring" {{ in_array('spooring', $oldTypes) ? 'selected' : '' }}>Spooring</option>
                <option value="balancing" {{ in_array('balancing', $oldTypes) ? 'selected' : '' }}>Balancing</option>
                <option value="general" {{ in_array('general', $oldTypes) ? 'selected' : '' }}>General</option>
                @foreach($oldTypes as $t)
                    @if(!in_array($t, ['oil_change','filter_change','tune_up','spooring','balancing','general']))
                        <option value="{{ $t }}" selected>{{ $t }}</option>
                    @endif
                @endforeach
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Bengkel</label>
            <input type="text" name="workshop_name" value="{{ old('workshop_name', $service->workshop_name) }}" class="w-full border rounded px-3 py-2">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Biaya <span class="text-red-500">*</span></label>
            <input type="number" name="cost" value="{{ old('cost', $service->cost) }}" class="w-full border rounded px-3 py-2" min="0" step="0.01" required>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Upload Invoice (biarkan kosong jika tidak mengganti)</label>
            <input type="file" name="invoice_file" accept="image/*,application/pdf" class="w-full border rounded px-3 py-2">
            @if($service->invoice_file)
                <a href="{{ route('drms.private.image', $service->invoice_file) }}" target="_blank" class="text-blue-600 text-sm">Lihat invoice saat ini</a>
            @endif
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Servis Berikutnya (Odometer)</label>
            <input type="number" name="next_service_odometer" value="{{ old('next_service_odometer', $service->next_service_odometer) }}" class="w-full border rounded px-3 py-2" min="0">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Servis Berikutnya (Tanggal)</label>
            <input type="date" name="next_service_date" value="{{ old('next_service_date', $service->next_service_date ? $service->next_service_date->format('Y-m-d') : '') }}" class="w-full border rounded px-3 py-2">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Catatan</label>
            <textarea name="notes" rows="3" class="w-full border rounded px-3 py-2">{{ old('notes', $service->notes) }}</textarea>
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('drms.service-schedules.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update</button>
        </div>
    </form>
</div>

{{-- Tom Select: multi-pilih jenis servis + bisa ketik untuk tambah opsi baru --}}
<link href="https://cdnjs.cloudflare.com/ajax/libs/tom-select/2.3.1/css/tom-select.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/tom-select/2.3.1/js/tom-select.complete.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    new TomSelect('#service_type', {
        plugins: ['remove_button'],
        create: true,
        createOnBlur: true,
        persist: false,
        placeholder: 'Pilih atau ketik jenis servis (bisa lebih dari satu)...'
    });
});
</script>
@endsection