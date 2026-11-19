@extends('layouts.app_car_drive_sidebar')

@section('content')
<div class="container mx-auto max-w-2xl px-4 py-6" x-data="{ type: '{{ old('type', request('type') === 'repair' ? 'repair' : 'service') }}' }">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold">Ajukan Servis / Perbaikan</h1>
        <a href="{{ route('drms.driver.service.index') }}" class="text-blue-600 hover:underline text-sm">← Kembali</a>
    </div>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('drms.driver.service.store') }}" method="POST" class="bg-white p-6 rounded shadow">
        @csrf

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Jenis Pengajuan <span class="text-red-500">*</span></label>
            <div class="flex gap-4">
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="type" value="service" x-model="type"> Servis Rutin
                </label>
                <label class="inline-flex items-center gap-2">
                    <input type="radio" name="type" value="repair" x-model="type"> Perbaikan / Kerusakan
                </label>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Kendaraan <span class="text-red-500">*</span></label>
            @include('drms.partials.vehicle-search', [
                'vehicles' => $vehicles,
                'name' => 'vehicle_id',
                'selectedId' => old('vehicle_id'),
                'placeholder' => 'Cari plat nomor / tipe kendaraan...',
                'required' => true,
                'allowAll' => false,
                'uid' => 'driver_service_request_vehicle',
            ])
        </div>

        {{-- ===== SERVIS RUTIN ===== --}}
        <div x-show="type === 'service'" x-cloak>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Rencana Tanggal Servis <span class="text-red-500">*</span></label>
                <input type="date" name="service_date" value="{{ old('service_date') }}" min="{{ now()->toDateString() }}"
                       :required="type === 'service'" class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Jenis Servis <span class="text-red-500">*</span></label>
                <select name="service_type[]" id="service_type" class="w-full border rounded px-3 py-2" multiple>
                    @php $oldTypes = old('service_type', []); @endphp
                    @foreach(['oil_change' => 'Ganti Oli', 'filter_change' => 'Ganti Filter', 'tune_up' => 'Tune Up', 'spooring' => 'Spooring', 'balancing' => 'Balancing', 'general' => 'General'] as $val => $label)
                        <option value="{{ $val }}" {{ in_array($val, $oldTypes) ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                    @foreach($oldTypes as $t)
                        @if(!in_array($t, ['oil_change','filter_change','tune_up','spooring','balancing','general']))
                            <option value="{{ $t }}" selected>{{ $t }}</option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Odometer saat ini (km)</label>
                <input type="number" name="odometer_at_service" value="{{ old('odometer_at_service') }}" min="0" class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Bengkel (jika sudah ditentukan)</label>
                <input type="text" name="workshop_name" value="{{ old('workshop_name') }}" maxlength="255" class="w-full border rounded px-3 py-2">
            </div>
        </div>

        {{-- ===== PERBAIKAN ===== --}}
        <div x-show="type === 'repair'" x-cloak>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Keluhan / Kerusakan <span class="text-red-500">*</span></label>
                <textarea name="complaint" rows="4" maxlength="2000" :required="type === 'repair'"
                          class="w-full border rounded px-3 py-2" placeholder="Jelaskan kerusakan / keluhan kendaraan">{{ old('complaint') }}</textarea>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Catatan</label>
            <textarea name="notes" rows="3" maxlength="2000" class="w-full border rounded px-3 py-2">{{ old('notes') }}</textarea>
        </div>

        <p class="text-xs text-gray-500 mb-4">Pengajuan akan dikirim ke admin GA untuk disetujui. Biaya diisi admin saat pekerjaan diselesaikan.</p>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('drms.driver.service.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Ajukan</button>
        </div>
    </form>
</div>

<style>[x-cloak]{display:none !important;}</style>
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
