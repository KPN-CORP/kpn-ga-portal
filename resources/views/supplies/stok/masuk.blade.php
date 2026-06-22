@extends('layouts.app_supplies_sidebar')
@section('content')
<div class="max-w-2xl">
    <h2 class="text-xl font-semibold mb-4">Barang Masuk (Tambah Stok)</h2>
    <div class="bg-white border rounded-xl p-6">
        <form method="POST" action="{{ route('supplies.stok.masuk.store') }}">
            @csrf
            <div class="mb-4"><label>Barang</label><select name="id_barang" class="w-full border rounded-lg p-2" required>@foreach($barang as $b)<option value="{{ $b->id }}">{{ $b->kode_barang }} - {{ $b->nama_barang }}</option>@endforeach</select></div>
            <div class="mb-4"><label>Bisnis Unit</label><select name="id_bisnis_unit" class="w-full border rounded-lg p-2" required>@foreach($bisnisUnits as $bu)<option value="{{ $bu->id_bisnis_unit }}">{{ $bu->nama_bisnis_unit }}</option>@endforeach</select></div>
            <div class="mb-4"><label>Jumlah</label><input type="number" step="0.01" name="jumlah" class="w-full border rounded-lg p-2" required></div>
            <div class="mb-4"><label>Nomor Referensi (Faktur)</label><input type="text" name="no_ref" class="w-full border rounded-lg p-2"></div>
            <div class="mb-4"><label>Keterangan</label><textarea name="keterangan" rows="3" class="w-full border rounded-lg p-2"></textarea></div>
            <div class="flex justify-end gap-2"><a href="{{ route('supplies.stok.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</a><button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Simpan</button></div>
        </form>
    </div>
</div>
@endsection