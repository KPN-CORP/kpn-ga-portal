@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans max-w-2xl">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold text-gray-800">Transfer Antar Area</h2>
        <a href="{{ route('stock-ctl.stok.index') }}" class="text-blue-600 hover:underline">← Kembali</a>
    </div>

    {{-- Notifikasi error/success dari server --}}
    @if(session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
            {{ session('error') }}
        </div>
    @endif
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded" role="alert">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white border rounded-xl p-6">
        <form method="POST" action="{{ route('stock-ctl.transaksi.transfer.store') }}" id="formTransfer">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Area Asal</label>
                <select name="id_area_asal" id="id_area_asal" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                    <option value="">-- Pilih Area Asal --</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id_area_kerja }}" {{ old('id_area_asal') == $area->id_area_kerja ? 'selected' : '' }}>
                            {{ $area->nama_area }} ({{ $area->bisnisUnit->nama_bisnis_unit ?? '-' }})
                        </option>
                    @endforeach
                </select>
                @error('id_area_asal') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Area Tujuan</label>
                <select name="id_area_tujuan" id="id_area_tujuan" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                    <option value="">-- Pilih Area Tujuan --</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id_area_kerja }}" {{ old('id_area_tujuan') == $area->id_area_kerja ? 'selected' : '' }}>
                            {{ $area->nama_area }} ({{ $area->bisnisUnit->nama_bisnis_unit ?? '-' }})
                        </option>
                    @endforeach
                </select>
                @error('id_area_tujuan') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Barang</label>
                <select name="id_barang" id="id_barang" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                    <option value="">-- Pilih Barang --</option>
                    @foreach($barang as $b)
                        <option value="{{ $b->id_barang }}" {{ old('id_barang') == $b->id_barang ? 'selected' : '' }}>
                            {{ $b->kode_barang }} - {{ $b->nama_barang }}
                        </option>
                    @endforeach
                </select>
                @error('id_barang') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Tampilkan stok tersedia di area asal --}}
            <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                <span class="font-medium">Stok tersedia di area asal:</span>
                <span id="stok_tersedia" class="ml-2 text-blue-600 font-bold">-</span>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Jumlah Transfer</label>
                <input type="number" step="0.01" name="jumlah" id="jumlah" value="{{ old('jumlah') }}" 
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" required>
                @error('jumlah') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-600 mb-1">Keterangan</label>
                <textarea name="keterangan" rows="3" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">{{ old('keterangan') }}</textarea>
            </div>

            <div class="flex justify-end gap-2">
                <a href="{{ route('stock-ctl.stok.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Transfer</button>
            </div>
        </form>
    </div>
</div>

<script>
    function fetchStok() {
        const idBarang = document.getElementById('id_barang').value;
        const idAreaAsal = document.getElementById('id_area_asal').value;
        const stokSpan = document.getElementById('stok_tersedia');
        const jumlahInput = document.getElementById('jumlah');

        if (!idBarang || !idAreaAsal) {
            stokSpan.innerText = '-';
            return;
        }

        stokSpan.innerText = 'Loading...';

        fetch(`{{ route('stock-ctl.cek-stok') }}?id_barang=${idBarang}&id_area=${idAreaAsal}`)
            .then(response => response.json())
            .then(data => {
                stokSpan.innerText = data.stok;
                if (jumlahInput.value && parseFloat(jumlahInput.value) > data.stok) {
                    jumlahInput.setCustomValidity('Jumlah melebihi stok tersedia');
                } else {
                    jumlahInput.setCustomValidity('');
                }
            })
            .catch(error => {
                console.error('Error fetching stock:', error);
                stokSpan.innerText = 'Gagal mengambil stok';
            });
    }

    document.getElementById('id_barang').addEventListener('change', fetchStok);
    document.getElementById('id_area_asal').addEventListener('change', fetchStok);
    document.addEventListener('DOMContentLoaded', fetchStok);

    document.getElementById('jumlah').addEventListener('input', function() {
        const stokTersedia = parseFloat(document.getElementById('stok_tersedia').innerText);
        if (!isNaN(stokTersedia) && this.value && parseFloat(this.value) > stokTersedia) {
            this.setCustomValidity('Jumlah melebihi stok tersedia');
        } else {
            this.setCustomValidity('');
        }
    });
</script>
@endsection