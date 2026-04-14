@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-xl font-semibold text-gray-800">Stok ATK</h2>
        <div class="flex gap-2">
            <a href="{{ route('stock-ctl.stok.awal') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700">
                <i class="fas fa-plus mr-1"></i> Stok Awal
            </a>
            <a href="{{ route('stock-ctl.transaksi.masuk') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">
                <i class="fas fa-arrow-down mr-1"></i> Barang Masuk
            </a>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white border rounded-xl p-4">
        <form method="GET" action="{{ route('stock-ctl.stok.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Area Kerja</label>
                <select name="id_area_kerja" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Area</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id_area_kerja }}" {{ request('id_area_kerja') == $area->id_area_kerja ? 'selected' : '' }}>
                            {{ $area->nama_area }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Barang</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari barang" 
                       class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">Filter</button>
                <a href="{{ route('stock-ctl.stok.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg text-sm">Reset</a>
            </div>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm min-w-[768px]">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Area</th>
                    <th class="px-4 py-3 text-left">Kode Barang</th>
                    <th class="px-4 py-3 text-left">Nama Barang</th>
                    <th class="px-4 py-3 text-left">Jumlah</th>
                    <th class="px-4 py-3 text-left">Satuan</th>
                    <th class="px-4 py-3 text-left">Stok Minimum</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Update Terakhir</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($stok as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $item->areaKerja->nama_area ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $item->barang->kode_barang ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $item->barang->nama_barang ?? '-' }}</td>
                    <td class="px-4 py-3">{{ number_format($item->jumlah) }}</td>
                    <td class="px-4 py-3">{{ $item->barang->satuan ?? '-' }}</td>
                    <td class="px-4 py-3">{{ number_format($item->stok_minimum) }}</td>
                    <td class="px-4 py-3">
                        @if($item->jumlah <= $item->stok_minimum)
                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Habis / Menipis</span>
                        @else
                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Aman</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $item->last_update ? \Carbon\Carbon::parse($item->last_update)->format('d M Y H:i') : '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="py-10 text-center text-gray-500">Belum ada data stok</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($stok->hasPages())
        <div class="mt-4">{{ $stok->links() }}</div>
    @endif
</div>
@endsection