@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold text-gray-800">Buat Opname Baru</h2>
        <a href="{{ route('stock-ctl.opname.index') }}" class="text-blue-600 hover:underline">← Kembali</a>
    </div>

    <div class="bg-white border rounded-xl p-6">
        <form method="POST" action="{{ route('stock-ctl.opname.store') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Area Kerja</label>
                <select name="id_area_kerja" id="area_select" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                    <option value="">-- Pilih Area --</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id_area_kerja }}" {{ old('id_area_kerja') == $area->id_area_kerja ? 'selected' : '' }}>
                            {{ $area->nama_area }}
                        </option>
                    @endforeach
                </select>
                @error('id_area_kerja') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Tanggal Opname</label>
                <input type="date" name="tanggal_opname" value="{{ old('tanggal_opname', date('Y-m-d')) }}" 
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                @error('tanggal_opname') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div id="stok-list" class="hidden">
                <h3 class="font-medium mb-2">Daftar Stok</h3>
                <div id="stok-items" class="space-y-2"></div>
            </div>

            <div class="flex justify-end gap-2 mt-4">
                <a href="{{ route('stock-ctl.opname.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Buat Draft</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('area_select').addEventListener('change', function() {
    const areaId = this.value;
    if (!areaId) {
        document.getElementById('stok-list').classList.add('hidden');
        return;
    }

    fetch(`/stock-ctl/opname/get-stok/${areaId}`)
        .then(res => res.json())
        .then(data => {
            const container = document.getElementById('stok-items');
            container.innerHTML = '';
            data.forEach(item => {
                container.innerHTML += `
                    <div class="flex items-center gap-2 border p-2 rounded">
                        <span class="w-1/3">${item.barang.kode_barang} - ${item.barang.nama_barang}</span>
                        <span class="w-1/6">Stok: ${item.jumlah}</span>
                        <input type="number" name="stok_fisik[${item.id_barang}]" placeholder="Stok Fisik" 
                               class="w-1/4 border rounded px-2 py-1 text-sm">
                        <input type="text" name="keterangan[${item.id_barang}]" placeholder="Keterangan" 
                               class="w-1/4 border rounded px-2 py-1 text-sm">
                    </div>
                `;
            });
            document.getElementById('stok-list').classList.remove('hidden');
        });
});
</script>
@endsection