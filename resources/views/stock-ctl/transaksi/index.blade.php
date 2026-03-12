@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-xl font-semibold text-gray-800">Riwayat Transaksi</h2>
        <div class="flex gap-2">
            <a href="{{ route('stock-ctl.transaksi.masuk') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700">Barang Masuk</a>
            <a href="{{ route('stock-ctl.transaksi.keluar') }}" class="px-4 py-2 bg-yellow-600 text-white rounded-lg text-sm font-semibold hover:bg-yellow-700">Barang Keluar</a>
            <a href="{{ route('stock-ctl.transaksi.transfer') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">Transfer</a>
        </div>
    </div>

    {{-- Filter --}}
    <div class="bg-white border rounded-xl p-4">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Jenis</label>
                <select name="jenis" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    @foreach($jenisList as $j)
                        <option value="{{ $j }}" {{ request('jenis') == $j ? 'selected' : '' }}>{{ ucfirst($j) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Tanggal Awal</label>
                <input type="date" name="tanggal_awal" value="{{ request('tanggal_awal') }}" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Tanggal Akhir</label>
                <input type="date" name="tanggal_akhir" value="{{ request('tanggal_akhir') }}" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">Filter</button>
                <a href="{{ route('stock-ctl.transaksi.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg text-sm">Reset</a>
            </div>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Tanggal</th>
                    <th class="px-4 py-3 text-left">Jenis</th>
                    <th class="px-4 py-3 text-left">Barang</th>
                    <th class="px-4 py-3 text-left">Jumlah</th>
                    <th class="px-4 py-3 text-left">Area Asal</th>
                    <th class="px-4 py-3 text-left">Area Tujuan</th>
                    <th class="px-4 py-3 text-left">Keterangan</th>
                    <th class="px-4 py-3 text-left">User</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($transaksi as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y H:i') }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            @if($item->jenis == 'masuk') bg-green-100 text-green-800
                            @elseif($item->jenis == 'keluar') bg-red-100 text-red-800
                            @elseif($item->jenis == 'transfer') bg-blue-100 text-blue-800
                            @else bg-gray-100 text-gray-800 @endif">
                            {{ ucfirst($item->jenis) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">{{ $item->barang->nama_barang ?? '-' }}</td>
                    <td class="px-4 py-3">{{ number_format($item->jumlah) }} {{ $item->barang->satuan ?? '' }}</td>
                    <td class="px-4 py-3">{{ $item->areaAsal->nama_area ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $item->areaTujuan->nama_area ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $item->keterangan ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $item->user->name ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="py-10 text-center text-gray-500">Belum ada transaksi</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($transaksi->hasPages())
        <div class="mt-4">{{ $transaksi->links() }}</div>
    @endif
</div>
@endsection