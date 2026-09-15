@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto max-w-2xl">
    <h1 class="text-2xl font-bold mb-4">Tambah Dokumen Kendaraan</h1>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('drms.vehicle-documents.store') }}" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded shadow">
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
                'uid' => 'vehicle_documents_create_vehicle',
            ])
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">STNK Berlaku Sampai</label>
                <input type="date" name="stnk_expiry" value="{{ old('stnk_expiry') }}" class="w-full border rounded px-3 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Upload STNK</label>
                <input type="file" name="stnk_file" accept=".jpg,.jpeg,.png,.pdf" class="w-full border rounded px-3 py-2">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Pajak Tahunan</label>
                <input type="date" name="tax_yearly_expiry" value="{{ old('tax_yearly_expiry') }}" class="w-full border rounded px-3 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Upload Pajak</label>
                <input type="file" name="tax_file" accept=".jpg,.jpeg,.png,.pdf" class="w-full border rounded px-3 py-2">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Pajak 5 Tahunan</label>
            <input type="date" name="tax_5year_expiry" value="{{ old('tax_5year_expiry') }}" class="w-full border rounded px-3 py-2">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Asuransi</label>
                <input type="date" name="insurance_expiry" value="{{ old('insurance_expiry') }}" class="w-full border rounded px-3 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Upload Asuransi</label>
                <input type="file" name="insurance_file" accept=".jpg,.jpeg,.png,.pdf" class="w-full border rounded px-3 py-2">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Catatan</label>
            <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2">{{ old('notes') }}</textarea>
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('drms.vehicle-documents.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Simpan</button>
        </div>
    </form>
</div>
@endsection