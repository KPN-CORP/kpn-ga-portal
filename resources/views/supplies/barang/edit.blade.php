@extends('layouts.app_supplies_sidebar')
@section('content')
<div class="max-w-2xl">
    <h2 class="text-xl font-semibold mb-4">Edit Barang</h2>
    <div class="bg-white border rounded-xl p-6">
        <form method="POST" action="{{ route('supplies.barang.update', $barang->id) }}">
            @csrf @method('PUT')
            <div class="mb-4">
                <label>Kode Barang</label>
                <input type="text" name="kode_barang" value="{{ old('kode_barang', $barang->kode_barang) }}" class="w-full border rounded-lg p-2" required>
            </div>
            <div class="mb-4">
                <label>Nama Barang</label>
                <input type="text" name="nama_barang" value="{{ old('nama_barang', $barang->nama_barang) }}" class="w-full border rounded-lg p-2" required>
            </div>
            <div class="mb-4">
                <label>Satuan</label>
                <select name="satuan" class="w-full border rounded-lg p-2">
                    <option value="">Pilih</option>
                    <option value="pcs" {{ old('satuan', $barang->satuan) == 'pcs' ? 'selected' : '' }}>Pcs</option>
                    <option value="unit" {{ old('satuan', $barang->satuan) == 'unit' ? 'selected' : '' }}>Unit</option>
                    <option value="box" {{ old('satuan', $barang->satuan) == 'box' ? 'selected' : '' }}>Box</option>
                    <option value="pack" {{ old('satuan', $barang->satuan) == 'pack' ? 'selected' : '' }}>Pack</option>
                    <option value="set" {{ old('satuan', $barang->satuan) == 'set' ? 'selected' : '' }}>Set</option>
                    <option value="lusin" {{ old('satuan', $barang->satuan) == 'lusin' ? 'selected' : '' }}>Lusin</option>
                    <option value="rim" {{ old('satuan', $barang->satuan) == 'rim' ? 'selected' : '' }}>Rim</option>
                    <option value="kg" {{ old('satuan', $barang->satuan) == 'kg' ? 'selected' : '' }}>Kg</option>
                    <option value="gram" {{ old('satuan', $barang->satuan) == 'gram' ? 'selected' : '' }}>Gram</option>
                    <option value="liter" {{ old('satuan', $barang->satuan) == 'liter' ? 'selected' : '' }}>Liter</option>
                    <option value="meter" {{ old('satuan', $barang->satuan) == 'meter' ? 'selected' : '' }}>Meter</option>
                    <option value="roll" {{ old('satuan', $barang->satuan) == 'roll' ? 'selected' : '' }}>Roll</option>
                    <option value="galon" {{ old('satuan', $barang->satuan) == 'galon' ? 'selected' : '' }}>Galon</option>
                    <option value="botol" {{ old('satuan', $barang->satuan) == 'botol' ? 'selected' : '' }}>Botol</option>
                </select>
            </div>
            <div class="mb-4">
                <label>Harga (Rp)</label>
                <input type="number" step="100" name="harga" value="{{ old('harga', $barang->harga) }}" class="w-full border rounded-lg p-2">
            </div>
            <div class="mb-4">
                <label>Area Simpan</label>
                <input type="text" name="lokasi_rak" value="{{ old('lokasi_rak', $barang->lokasi_rak) }}" class="w-full border rounded-lg p-2">
            </div>
            <div class="mb-4">
                <label>Stok Minimum</label>
                <input type="number" step="0.01" name="stok_minimum" value="{{ old('stok_minimum', $barang->stok_minimum) }}" class="w-full border rounded-lg p-2">
            </div>
            <div class="mb-4">
                <label>Deskripsi</label>
                <textarea name="deskripsi" rows="3" class="w-full border rounded-lg p-2">{{ old('deskripsi', $barang->deskripsi) }}</textarea>
            </div>
            <div class="flex justify-end gap-2">
                <a href="{{ route('supplies.barang.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Update</button>
            </div>
        </form>
    </div>
</div>
@endsection