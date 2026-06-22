@extends('layouts.app_supplies_sidebar')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Master Barang Supplies</h2>
        <a href="{{ route('supplies.barang.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-700">+ Barang</a>
    </div>

    <div class="bg-white border rounded-xl p-4">
        <form method="GET" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari kode/nama barang" class="w-full border rounded-lg px-3 py-2 text-sm">
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">Cari</button>
            <a href="{{ route('supplies.barang.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">Reset</a>
        </form>
    </div>

    <div class="bg-white border rounded-xl overflow-x-auto shadow-sm">
        <table class="w-full text-sm min-w-[800px]">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-3 py-3 text-left">Kode</th>
                    <th class="px-3 py-3 text-left">Nama Barang</th>
                    <th class="px-3 py-3 text-left">Satuan</th>
                    <th class="px-3 py-3 text-right">Harga (Rp)</th>
                    <th class="px-3 py-3 text-left">Area Simpan</th>
                    <th class="px-3 py-3 text-right">Stok Minimum</th>
                    <th class="px-3 py-3 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($barang as $b)
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2">{{ $b->kode_barang }}</td>
                    <td class="px-3 py-2 font-medium">{{ $b->nama_barang }}</td>
                    <td class="px-3 py-2">{{ $b->satuan ?? '-' }}</td>
                    <td class="px-3 py-2 text-right">{{ number_format($b->harga ?? 0, 0, ',', '.') }}</td>
                    <td class="px-3 py-2">{{ $b->lokasi_rak ?? '-' }}</td>
                    <td class="px-3 py-2 text-right">{{ number_format($b->stok_minimum) }}</td>
                    <td class="px-3 py-2 text-center">
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
    <div class="mt-4">{{ $barang->links() }}</div>
</div>
@endsection