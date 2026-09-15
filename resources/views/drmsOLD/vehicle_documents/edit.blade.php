@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto max-w-2xl">
    <h1 class="text-2xl font-bold mb-4">Edit Dokumen Kendaraan</h1>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('drms.vehicle-documents.update', $document->id) }}" method="POST" enctype="multipart/form-data" class="bg-white p-6 rounded shadow">
        @csrf @method('PUT')

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Kendaraan <span class="text-red-500">*</span></label>
            @include('drms.partials.vehicle-search', [
                'vehicles' => $vehicles,
                'name' => 'vehicle_id',
                'selectedId' => old('vehicle_id', $document->vehicle_id),
                'placeholder' => 'Cari plat nomor / tipe kendaraan...',
                'required' => true,
                'allowAll' => false,
                'uid' => 'vehicle_documents_edit_vehicle',
            ])
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">STNK Berlaku Sampai</label>
                <input type="date" name="stnk_expiry" value="{{ old('stnk_expiry', $document->stnk_expiry ? $document->stnk_expiry->format('Y-m-d') : '') }}" class="w-full border rounded px-3 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Upload STNK (biarkan kosong jika tidak mengganti)</label>
                <input type="file" name="stnk_file" accept=".jpg,.jpeg,.png,.pdf" class="w-full border rounded px-3 py-2">
                @if($document->stnk_file) <a href="{{ route('drms.private.image', $document->stnk_file) }}" target="_blank" class="text-blue-600 text-sm">Lihat file</a> @endif
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Pajak Tahunan</label>
                <input type="date" name="tax_yearly_expiry" value="{{ old('tax_yearly_expiry', $document->tax_yearly_expiry ? $document->tax_yearly_expiry->format('Y-m-d') : '') }}" class="w-full border rounded px-3 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Upload Pajak (biarkan kosong jika tidak mengganti)</label>
                <input type="file" name="tax_file" accept=".jpg,.jpeg,.png,.pdf" class="w-full border rounded px-3 py-2">
                @if($document->tax_file) <a href="{{ route('drms.private.image', $document->tax_file) }}" target="_blank" class="text-blue-600 text-sm">Lihat file</a> @endif
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Pajak 5 Tahunan</label>
            <input type="date" name="tax_5year_expiry" value="{{ old('tax_5year_expiry', $document->tax_5year_expiry ? $document->tax_5year_expiry->format('Y-m-d') : '') }}" class="w-full border rounded px-3 py-2">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Asuransi</label>
                <input type="date" name="insurance_expiry" value="{{ old('insurance_expiry', $document->insurance_expiry ? $document->insurance_expiry->format('Y-m-d') : '') }}" class="w-full border rounded px-3 py-2">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">Upload Asuransi (biarkan kosong jika tidak mengganti)</label>
                <input type="file" name="insurance_file" accept=".jpg,.jpeg,.png,.pdf" class="w-full border rounded px-3 py-2">
                @if($document->insurance_file) <a href="{{ route('drms.private.image', $document->insurance_file) }}" target="_blank" class="text-blue-600 text-sm">Lihat file</a> @endif
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700">Catatan</label>
            <textarea name="notes" rows="2" class="w-full border rounded px-3 py-2">{{ old('notes', $document->notes) }}</textarea>
        </div>

        <div class="flex justify-end space-x-2">
            <a href="{{ route('drms.vehicle-documents.index') }}" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Update</button>
        </div>
    </form>
</div>
@endsection