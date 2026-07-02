@extends('layouts.app_supplies_sidebar')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap justify-between items-center gap-3">
        <h2 class="text-xl font-semibold">Permintaan Supplies Saya</h2>
        <a href="{{ route('supplies.permintaan.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">+ Buat Permintaan</a>
    </div>

    <div class="bg-white p-4 rounded-xl border">
        <form method="GET" class="flex flex-col sm:flex-row gap-3 items-end">
            <div class="w-full sm:w-auto">
                <label class="text-sm text-gray-600">Filter Status</label>
                <select name="status" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    <option value="pending" {{ request('status')=='pending' ? 'selected' : '' }}>Pending</option>
                    <option value="disetujui" {{ request('status')=='disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="ditolak" {{ request('status')=='ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            <div class="flex gap-2 w-full sm:w-auto">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm flex-1 sm:flex-none">Filter</button>
                <a href="{{ route('supplies.permintaan.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm flex-1 sm:flex-none text-center">Reset</a>
            </div>
        </form>
    </div>

    <!-- Desktop Table -->
    <div class="bg-white rounded-xl border overflow-x-auto hidden md:block">
        <table class="w-full text-sm min-w-[700px]">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">No.</th>
                    <th class="px-4 py-3 text-left">Tanggal</th>
                    <th class="px-4 py-3 text-left">Barang</th>
                    <th class="px-4 py-3 text-left">Jumlah</th>
                    <th class="px-4 py-3 text-left">Unit Tujuan</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($permintaan as $p)
                <tr class="border-t">
                    <td class="px-4 py-2">#{{ $p->id }}</td>
                    <td class="px-4 py-2">{{ $p->created_at->format('d M Y H:i') }}</td>
                    <td class="px-4 py-2">{{ $p->barang->nama_barang ?? '-' }}</td>
                    <td class="px-4 py-2">{{ number_format($p->jumlah) }} {{ $p->barang->satuan ?? '' }}</td>
                    <td class="px-4 py-2">{{ $p->bisnisUnit->nama_bisnis_unit ?? '-' }}</td>
                    <td class="px-4 py-2">
                        @if($p->status == 'pending')
                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">Pending</span>
                        @elseif($p->status == 'disetujui')
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">Disetujui</span>
                        @else
                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">Ditolak</span>
                        @endif
                    </td>
                    <td class="px-4 py-2">
                        <a href="{{ route('supplies.permintaan.show', $p->id) }}" class="text-blue-600 hover:underline">Detail</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-10 text-gray-500">Belum ada permintaan</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Mobile Cards -->
    <div class="md:hidden space-y-4">
        @forelse($permintaan as $p)
        <div class="bg-white rounded-xl border p-4 shadow-sm">
            <div class="flex justify-between items-start">
                <div>
                    <p class="font-semibold">#{{ $p->id }}</p>
                    <p class="text-sm text-gray-500">{{ $p->created_at->format('d M Y H:i') }}</p>
                </div>
                @if($p->status == 'pending')
                    <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">Pending</span>
                @elseif($p->status == 'disetujui')
                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">Disetujui</span>
                @else
                    <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">Ditolak</span>
                @endif
            </div>
            <div class="mt-2 text-sm text-gray-600">
                <p>Barang: {{ $p->barang->nama_barang ?? '-' }} ({{ number_format($p->jumlah) }} {{ $p->barang->satuan ?? '' }})</p>
                <p>Unit Tujuan: {{ $p->bisnisUnit->nama_bisnis_unit ?? '-' }}</p>
            </div>
            <div class="mt-3">
                <a href="{{ route('supplies.permintaan.show', $p->id) }}" class="text-blue-600 hover:underline text-sm">Detail →</a>
            </div>
        </div>
        @empty
        <div class="text-center py-10 text-gray-500">Belum ada permintaan</div>
        @endforelse
    </div>

    {{ $permintaan->links() }}
</div>
@endsection