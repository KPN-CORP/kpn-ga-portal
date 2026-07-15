@extends('layouts.app_stock_sidebar')
@section('content')
<div class="max-w-2xl">
    <h2 class="text-xl font-semibold mb-4">Ajukan Permintaan Antar Unit</h2>
    <form method="POST" action="{{ route('stock-ctl.antar-unit.store') }}" class="bg-white p-6 rounded-xl border">
        @csrf
        <div class="mb-4">
            <label>Barang</label>
            <select name="id_barang" id="id_barang" class="w-full border rounded-lg p-2" required>
                <option value="">Pilih Barang</option>
                @foreach($barang as $b)
                <option value="{{ $b->id_barang }}" data-satuan="{{ $b->satuan }}">
                    {{ $b->kode_barang }} - {{ $b->nama_barang }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="mb-4">
            <label>Jumlah</label>
            <div class="flex items-center gap-2">
                <input type="number" step="0.01" name="jumlah" id="jumlah" class="flex-1 border rounded-lg p-2" required>
                <span id="satuan_tampilan" class="text-sm text-gray-600 w-16 bg-gray-100 px-2 py-2 rounded-lg text-center">-</span>
            </div>
        </div>
        <div class="mb-4">
            <label>Unit Asal (Anda)</label>
            <input type="text" class="w-full border rounded-lg p-2 bg-gray-100" value="{{ \App\Models\BisnisUnit::find($userUnitId)->nama_bisnis_unit }}" disabled>
        </div>
        <div class="mb-4">
            <label>Unit Tujuan</label>
            <select name="id_bisnis_unit_tujuan" class="w-full border rounded-lg p-2" required>
                <option value="">Pilih Unit Tujuan</option>
                @foreach($units as $unit)
                    @if($unit->id_bisnis_unit != $userUnitId)
                    <option value="{{ $unit->id_bisnis_unit }}">{{ $unit->nama_bisnis_unit }}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="mb-4">
            <label>Keterangan</label>
            <textarea name="keterangan" class="w-full border rounded-lg p-2" rows="3"></textarea>
        </div>
        <div class="flex justify-end gap-2">
            <a href="{{ route('stock-ctl.antar-unit.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Ajukan</button>
        </div>
    </form>
</div>

<script>
document.getElementById('id_barang').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    const satuan = selected.getAttribute('data-satuan') || '-';
    document.getElementById('satuan_tampilan').textContent = satuan;
});
</script>
@endsection