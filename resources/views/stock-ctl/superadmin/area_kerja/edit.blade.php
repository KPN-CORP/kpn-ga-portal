@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans max-w-2xl">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold text-gray-800">Edit Area Kerja</h2>
        <a href="{{ route('stock-ctl.area.index') }}" class="text-blue-600 hover:underline">← Kembali</a>
    </div>

    <div class="bg-white border rounded-xl p-6">
        <form method="POST" action="{{ route('stock-ctl.area.update', $area->id_area_kerja) }}">
            @csrf
            @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Nama Area</label>
                <input type="text" name="nama_area" value="{{ old('nama_area', $area->nama_area) }}" 
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                @error('nama_area') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Bisnis Unit</label>
                <select name="id_bisnis_unit" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                    <option value="">-- Pilih --</option>
                    @foreach($bisnisUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}" {{ old('id_bisnis_unit', $area->id_bisnis_unit) == $bu->id_bisnis_unit ? 'selected' : '' }}>
                            {{ $bu->nama_bisnis_unit }}
                        </option>
                    @endforeach
                </select>
                @error('id_bisnis_unit') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end gap-2">
                <a href="{{ route('stock-ctl.area.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Update</button>
            </div>
        </form>
    </div>
</div>
@endsection