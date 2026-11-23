@extends('layouts.app_apartadmin_sidebar')
@section('content')
<div class="p-4 md:p-6 max-w-3xl mx-auto">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">
                @if(isset($unit)) Edit Unit @else Tambah Unit Baru @endif
            </h1>
            <a href="{{ route('apartemen.admin.apartemen.detail', $apartemen_id ?? $unit->apartemen_id) }}" class="text-gray-600 hover:text-gray-800">← Kembali</a>
        </div>

        <form action="{{ isset($unit) ? route('unit.update', $unit->id) : route('unit.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @if(isset($unit)) @method('PUT') @endif
            <input type="hidden" name="apartemen_id" value="{{ $apartemen_id ?? $unit->apartemen_id }}">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Unit *</label>
                    <input type="text" name="nomor_unit" value="{{ old('nomor_unit', $unit->nomor_unit ?? '') }}" required class="w-full border rounded-md px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kapasitas *</label>
                    <input type="number" name="kapasitas" value="{{ old('kapasitas', $unit->kapasitas ?? 2) }}" required min="1" class="w-full border rounded-md px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select name="status" class="w-full border rounded-md px-3 py-2">
                        <option value="READY" {{ (old('status', $unit->status ?? '') == 'READY') ? 'selected' : '' }}>Tersedia</option>
                        <option value="TERISI" {{ (old('status', $unit->status ?? '') == 'TERISI') ? 'selected' : '' }}>Terisi</option>
                        <option value="MAINTENANCE" {{ (old('status', $unit->status ?? '') == 'MAINTENANCE') ? 'selected' : '' }}>Maintenance</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bisnis Unit</label>
                    <select name="bisnis_unit_id" class="w-full border rounded-md px-3 py-2">
                        <option value="">-- Tidak Dipilih --</option>
                        @foreach($bisnisUnits as $bu)
                            <option value="{{ $bu->id_bisnis_unit }}" {{ (old('bisnis_unit_id', $unit->bisnis_unit_id ?? '') == $bu->id_bisnis_unit) ? 'selected' : '' }}>
                                {{ $bu->nama_bisnis_unit }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Gambar 360° (equirectangular)</label>
                    <input type="file" name="gambar_360" accept="image/jpeg,image/png" class="w-full">
                    @if(isset($unit) && $unit->gambar_360)
                        <div class="mt-2">
                            <a href="{{ Storage::url($unit->gambar_360) }}" target="_blank" class="text-blue-600">Lihat gambar saat ini</a>
                            <span class="text-xs text-gray-500 ml-2">(Upload baru akan mengganti)</span>
                        </div>
                    @endif
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan</label>
                    <textarea name="catatan" rows="3" class="w-full border rounded-md px-3 py-2">{{ old('catatan', $unit->catatan ?? '') }}</textarea>
                </div>
            </div>

            <div class="flex justify-end space-x-3 mt-8">
                <a href="{{ route('apartemen.admin.apartemen.detail', $apartemen_id ?? $unit->apartemen_id) }}" class="px-4 py-2 bg-gray-200 rounded-md hover:bg-gray-300">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection