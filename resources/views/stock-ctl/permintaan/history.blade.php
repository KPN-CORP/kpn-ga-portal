@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">
    <div>
        <h2 class="text-xl font-semibold text-gray-800">Riwayat Permintaan</h2>
    </div>

    {{-- Filter --}}
    <div class="bg-white border rounded-xl p-4">
        <form method="GET" action="{{ route('stock-ctl.permintaan.history') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <select name="status" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    <option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending</option>
                    <option value="disetujui" {{ request('status')=='disetujui'?'selected':'' }}>Disetujui</option>
                    <option value="ditolak" {{ request('status')=='ditolak'?'selected':'' }}>Ditolak</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Dari Tanggal</label>
                <input type="date" name="dari" value="{{ request('dari') }}" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Sampai Tanggal</label>
                <input type="date" name="sampai" value="{{ request('sampai') }}" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">Filter</button>
                <a href="{{ route('stock-ctl.permintaan.history') }}" class="px-4 py-2 bg-gray-200 rounded-lg text-sm font-semibold">Reset</a>
            </div>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Tanggal</th>
                    <th class="px-4 py-3 text-left">Barang</th>
                    <th class="px-4 py-3 text-left">Jumlah</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Approver</th>
                    <th class="px-4 py-3 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($history as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ \Carbon\Carbon::parse($item->tanggal_permintaan)->format('d M Y H:i') }}</td>
                    <td class="px-4 py-3">{{ $item->barang->nama_barang ?? '-' }}</td>
                    <td class="px-4 py-3">{{ number_format($item->jumlah) }} {{ $item->barang->satuan ?? '' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            @if($item->status == 'pending') bg-yellow-100 text-yellow-800
                            @elseif($item->status == 'disetujui') bg-green-100 text-green-800
                            @else bg-red-100 text-red-800 @endif">
                            {{ ucfirst($item->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">{{ $item->approver->name ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('stock-ctl.permintaan.show', $item->id_permintaan) }}" 
                           class="text-blue-600 font-semibold hover:underline">Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="py-10 text-center text-gray-500">Tidak ada riwayat</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($history->hasPages())
        <div class="mt-4">{{ $history->links() }}</div>
    @endif
</div>
@endsection