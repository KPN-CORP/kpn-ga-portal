@extends('layouts.app_supplies_sidebar')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap justify-between items-center gap-3">
        <h2 class="text-xl font-semibold">Master Barang Supplies</h2>
        <a href="{{ route('supplies.barang.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">+ Barang</a>
    </div>

    <div class="bg-white border rounded-xl p-4">
        <form method="GET" class="flex flex-col sm:flex-row gap-3 items-end">
            <div class="w-full sm:flex-1">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode/nama barang" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-2 w-full sm:w-auto">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm flex-1 sm:flex-none">Cari</button>
                <a href="{{ route('supplies.barang.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm flex-1 sm:flex-none text-center">Reset</a>
            </div>
        </form>
    </div>

    <!-- Desktop Table -->
    <div class="bg-white border rounded-xl overflow-x-auto shadow-sm hidden md:block">
        <table class="w-full text-sm min-w-[700px]">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Kode</th>
                    <th class="px-4 py-3 text-left">Nama Barang</th>
                    <th class="px-4 py-3 text-left">Satuan</th>
                    <th class="px-4 py-3 text-right">Harga (Rp)</th>
                    <th class="px-4 py-3 text-left">Area Simpan</th>
                    <th class="px-4 py-3 text-right">Stok Minimum</th>
                    <th class="px-4 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($barang as $b)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $b->kode_barang }}</td>
                    <td class="px-4 py-3 font-medium">{{ $b->nama_barang }}</td>
                    <td class="px-4 py-3">{{ $b->satuan ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($b->harga ?? 0, 0, ',', '.') }}</td>
                    <td class="px-4 py-3">{{ $b->lokasi_rak ?? '-' }}</td>
                    <td class="px-4 py-3 text-right">{{ number_format($b->stok_minimum) }}</td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('supplies.barang.edit', $b->id) }}" class="text-blue-600 hover:underline mr-2">Edit</a>
                        <form action="{{ route('supplies.barang.destroy', $b->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin hapus?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="py-10 text-center text-gray-500">Belum ada data</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile Cards -->
    <div class="md:hidden space-y-4">
        @forelse($barang as $b)
        <div class="bg-white border rounded-xl p-4 shadow-sm">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-semibold text-lg">{{ $b->nama_barang }}</p>
                    <p class="text-sm text-gray-500">Kode: {{ $b->kode_barang }}</p>
                </div>
                <span class="text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded-full">{{ $b->satuan ?? '-' }}</span>
            </div>
            <div class="mt-2 text-sm text-gray-600 grid grid-cols-2 gap-1">
                <span>Harga: Rp {{ number_format($b->harga ?? 0, 0, ',', '.') }}</span>
                <span>Area: {{ $b->lokasi_rak ?? '-' }}</span>
                <span>Stok Min: {{ number_format($b->stok_minimum) }}</span>
            </div>
            <div class="mt-3 flex gap-2">
                <a href="{{ route('supplies.barang.edit', $b->id) }}" class="flex-1 text-center bg-blue-600 text-white px-3 py-1.5 rounded-lg text-sm">Edit</a>
                <form action="{{ route('supplies.barang.destroy', $b->id) }}" method="POST" class="flex-1" onsubmit="return confirm('Yakin hapus?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full bg-red-600 text-white px-3 py-1.5 rounded-lg text-sm">Hapus</button>
                </form>
            </div>
        </div>
        @empty
        <div class="py-10 text-center text-gray-500">Belum ada data</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $barang->links() }}</div>
</div>
@endsection