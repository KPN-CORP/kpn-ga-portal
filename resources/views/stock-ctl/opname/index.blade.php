@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-xl font-semibold text-gray-800">Stok Opname</h2>
        <a href="{{ route('stock-ctl.opname.create') }}" 
           class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700">
            + Buat Opname Baru
        </a>
    </div>

    {{-- Filter --}}
    <div class="bg-white border rounded-xl p-4">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Area</label>
                <select name="id_area" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Area</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id_area_kerja }}" {{ request('id_area') == $area->id_area_kerja ? 'selected' : '' }}>
                            {{ $area->nama_area }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <select name="status" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    <option value="draft" {{ request('status')=='draft'?'selected':'' }}>Draft</option>
                    <option value="selesai" {{ request('status')=='selesai'?'selected':'' }}>Selesai</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Filter</button>
                <a href="{{ route('stock-ctl.opname.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg">Reset</a>
            </div>
        </form>
    </div>

    {{-- Tabel --}}
    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Tanggal Opname</th>
                    <th class="px-4 py-3 text-left">Area</th>
                    <th class="px-4 py-3 text-left">Dibuat Oleh</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($opname as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ \Carbon\Carbon::parse($item->tanggal_opname)->format('d M Y') }}</td>
                    <td class="px-4 py-3">{{ $item->areaKerja->nama_area ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $item->user->name ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            @if($item->status == 'draft') bg-yellow-100 text-yellow-800
                            @else bg-green-100 text-green-800 @endif">
                            {{ ucfirst($item->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('stock-ctl.opname.show', $item->id_opname) }}" 
                           class="text-blue-600 hover:underline mr-2">Detail</a>
                        @if($item->status == 'draft')
                        <a href="{{ route('stock-ctl.opname.edit', $item->id_opname) }}" 
                           class="text-green-600 hover:underline">Lanjutkan</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-10 text-center text-gray-500">Belum ada data opname</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($opname->hasPages())
        <div class="mt-4">{{ $opname->links() }}</div>
    @endif
</div>
@endsection