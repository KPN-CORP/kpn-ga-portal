@extends('layouts.app_supplies_sidebar')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Permintaan Supplies Saya</h2>
        <a href="{{ route('supplies.permintaan.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">+ Buat Permintaan</a>
    </div>

    {{-- Filter status --}}
    <div class="bg-white p-4 rounded-xl border">
        <form method="GET" class="flex gap-4 items-end">
            <div>
                <label class="text-sm text-gray-600">Filter Status</label>
                <select name="status" class="border rounded-lg px-3 py-2 text-sm">
                    <option value="">Semua</option>
                    <option value="pending" {{ request('status')=='pending' ? 'selected' : '' }}>Pending</option>
                    <option value="disetujui" {{ request('status')=='disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="ditolak" {{ request('status')=='ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>
            </div>
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm">Filter</button>
            <a href="{{ route('supplies.permintaan.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">Reset</a>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-xl border overflow-x-auto">
        <table class="w-full text-sm">
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

    {{ $permintaan->links() }}
</div>
@endsection