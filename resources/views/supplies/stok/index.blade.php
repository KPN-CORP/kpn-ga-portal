@extends('layouts.app_supplies_sidebar')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap justify-between items-center gap-3">
        <h2 class="text-xl font-semibold text-gray-800">Stok Supplies</h2>
        <a href="{{ route('supplies.stok.masuk') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition flex items-center gap-1">
            <i class="fas fa-plus-circle"></i> Barang Masuk
        </a>
    </div>

    <div class="bg-white border rounded-xl p-4 shadow-sm">
        <form method="GET" class="flex flex-col sm:flex-row gap-3 items-end">
            <div class="w-full sm:w-auto">
                <label class="text-xs text-gray-500 mb-1 block">Bisnis Unit</label>
                <select name="id_bisnis_unit" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Semua Unit</option>
                    @foreach($bisnisUnits as $bu)
                    <option value="{{ $bu->id_bisnis_unit }}" {{ request('id_bisnis_unit')==$bu->id_bisnis_unit ? 'selected' : '' }}>
                        {{ $bu->nama_bisnis_unit }}
                    </option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 w-full">
                <label class="text-xs text-gray-500 mb-1 block">Cari Barang</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama / Kode barang" 
                       class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex gap-2 w-full sm:w-auto">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-5 py-2 rounded-lg text-sm transition flex-1 sm:flex-none">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
                <a href="{{ route('supplies.stok.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-5 py-2 rounded-lg text-sm transition flex-1 sm:flex-none text-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Desktop Table -->
    <div class="bg-white border rounded-xl overflow-x-auto shadow-sm hidden md:block">
        <table class="w-full text-sm min-w-[700px]">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Barang</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Kode Barang</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Satuan</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Bisnis Unit</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-600">Stok</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-600">Update Terakhir</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($stok as $s)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-4 py-3">{{ $s->barang->nama_barang }}</td>
                    <td class="px-4 py-3">{{ $s->barang->kode_barang }}</td>
                    <td class="px-4 py-3">{{ $s->barang->satuan }}</td>
                    <td class="px-4 py-3">{{ $s->bisnisUnit->nama_bisnis_unit ?? '-' }}</td>
                    <td class="px-4 py-3 text-right font-semibold">{{ number_format($s->jumlah) }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $s->last_update ? \Carbon\Carbon::parse($s->last_update)->format('d/m/Y H:i') : '-' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="py-12 text-center text-gray-500">
                        <i class="fas fa-box-open text-3xl mb-2 opacity-40 block"></i>
                        Belum ada data stok
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile Cards -->
    <div class="md:hidden space-y-4">
        @forelse($stok as $s)
        <div class="bg-white border rounded-xl p-4 shadow-sm">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-semibold">{{ $s->barang->nama_barang }}</p>
                    <p class="text-sm text-gray-500">{{ $s->barang->kode_barang }}</p>
                </div>
                <span class="text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded-full">{{ $s->barang->satuan }}</span>
            </div>
            <div class="mt-2 text-sm text-gray-600 grid grid-cols-2 gap-1">
                <span>Unit: {{ $s->bisnisUnit->nama_bisnis_unit ?? '-' }}</span>
                <span class="text-right font-semibold">Stok: {{ number_format($s->jumlah) }}</span>
                <span class="col-span-2 text-xs text-gray-400">Update: {{ $s->last_update ? \Carbon\Carbon::parse($s->last_update)->format('d/m/Y H:i') : '-' }}</span>
            </div>
        </div>
        @empty
        <div class="py-12 text-center text-gray-500">
            <i class="fas fa-box-open text-3xl mb-2 opacity-40 block"></i>
            Belum ada data stok
        </div>
        @endforelse
    </div>

    @if($stok->hasPages())
        <div class="mt-2">
            {{ $stok->links() }}
        </div>
    @endif
</div>
@endsection