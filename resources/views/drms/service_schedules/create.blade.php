@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto max-w-2xl">
    <h1 class="text-2xl font-bold mb-4">Tambah Servis Rutin</h1>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('drms.service-schedules.store') }}" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded shadow">
        @csrf

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Kendaraan <span class="text-red-500">*</span></label>
            @include('drms.partials.vehicle-search', [
                'vehicles' => $vehicles,
                'name' => 'vehicle_id',
                'selectedId' => old('vehicle_id'),
                'placeholder' => 'Cari plat nomor / tipe kendaraan...',
                'required' => true,
                'allowAll' => false,
                'uid' => 'service_schedules_create_vehicle',
            ])
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Tanggal Servis <span class="text-red-500">*</span></label>
            <input type="date" name="service_date" value="{{ old('service_date') }}" class="w-full border rounded px-3 py-2" required>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Odometer (km)</label>
            <input type="number" name="odometer_at_service" value="{{ old('odometer_at_service') }}" class="w-full border rounded px-3 py-2" min="0">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Jenis Servis <span class="text-red-500">*</span></label>
            <select name="service_type" class="w-full border rounded px-3 py-2" required>
                <option value="">Pilih</option>
                <option value="oil_change" {{ old('service_type') == 'oil_change' ? 'selected' : '' }}>Ganti Oli</option>
                <option value="filter_change" {{ old('service_type') == 'filter_change' ? 'selected' : '' }}>Ganti Filter</option>
                <option value="tune_up" {{ old('service_type') == 'tune_up' ? 'selected' : '' }}>Tune Up</option>
                <option value="spooring" {{ old('service_type') == 'spooring' ? 'selected' : '' }}>Spooring</option>
                <option value="balancing" {{ old('service_type') == 'balancing' ? 'selected' : '' }}>Balancing</option>
                <option value="general" {{ old('service_type') == 'general' ? 'selected' : '' }}>General</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Bengkel</label>
            <input type="text" name="workshop_name" value="{{ old('workshop_name') }}" class="w-full border rounded px-3 py-2" placeholder="Nama bengkel">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Biaya <span class="text-red-500">*</span></label>
            <input type="number" name="cost" value="{{ old('cost') }}" class="w-full border rounded px-3 py-2" min="0" step="0.01" required>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Upload Invoice</label>
            <input type="file" name="invoice_file" accept="image/*" class="w-full border rounded px-3 py-2">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Servis Berikutnya (Odometer)</label>
            <input type="number" name="next_service_odometer" value="{{ old('next_service_odometer') }}" class="w-full border rounded px-3 py-2" min="0">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Servis Berikutnya (Tanggal)</label>
            <input type="date" name="next_service_date" value="{{ old('next_service_date') }}" class="w-full border rounded px-3 py-2">
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Catatan</label>
            <textarea name="notes" rows="3" class="w-full border rounded px-3 py-2">{{ old('notes') }}</textarea>
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('drms.service-schedules.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Simpan</button>
        </div>
    </form>
</div>
@endsection