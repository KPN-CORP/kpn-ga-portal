@extends('layouts.app_stock_sidebar')
@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold">Permintaan Antar Unit</h2>
        <a href="{{ route('stock-ctl.antar-unit.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg">+ Ajukan Permintaan</a>
    </div>

    {{-- FORM FILTER --}}
    <form method="GET" action="{{ route('stock-ctl.antar-unit.index') }}" class="bg-white p-4 rounded-xl border flex flex-wrap items-end gap-4">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-sm font-medium text-gray-600 mb-1">Cari</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama/kode barang atau pemohon..." class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>

        <div class="flex-1 min-w-[150px]">
            <label class="block text-sm font-medium text-gray-600 mb-1">Unit Asal</label>
            <select name="unit_asal" class="w-full border rounded-lg px-3 py-2 text-sm">
                <option value="">Semua Unit</option>
                @foreach($units as $unit)
                    <option value="{{ $unit->id_bisnis_unit }}" {{ request('unit_asal') == $unit->id_bisnis_unit ? 'selected' : '' }}>
                        {{ $unit->nama_bisnis_unit }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex-1 min-w-[150px]">
            <label class="block text-sm font-medium text-gray-600 mb-1">Unit Tujuan</label>
            <select name="unit_tujuan" class="w-full border rounded-lg px-3 py-2 text-sm">
                <option value="">Semua Unit</option>
                @foreach($units as $unit)
                    <option value="{{ $unit->id_bisnis_unit }}" {{ request('unit_tujuan') == $unit->id_bisnis_unit ? 'selected' : '' }}>
                        {{ $unit->nama_bisnis_unit }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="flex-1 min-w-[150px]">
            <label class="block text-sm font-medium text-gray-600 mb-1">Status</label>
            <select name="status" class="w-full border rounded-lg px-3 py-2 text-sm">
                <option value="">Semua Status</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Menunggu</option>
                <option value="disetujui" {{ request('status') == 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                <option value="ditolak" {{ request('status') == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
            </select>
        </div>

        <div class="w-32">
            <label class="block text-sm font-medium text-gray-600 mb-1">Tampilkan</label>
            <select name="per_page" class="w-full border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
                <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
                <option value="25" {{ request('per_page') == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ request('per_page') == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ request('per_page') == 100 ? 'selected' : '' }}>100</option>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">Filter</button>
            <a href="{{ route('stock-ctl.antar-unit.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg text-sm">Reset</a>
        </div>
    </form>

    {{-- TABEL --}}
    <div class="bg-white rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th>ID</th>
                    <th>Barang</th>
                    <th>Jumlah</th>
                    <th>Unit Asal</th>
                    <th>Unit Tujuan</th>
                    <th>Status</th>
                    <th>Tgl Pengajuan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $r)
                <tr>
                    <td class="px-4 py-2">#{{ $r->id }}</td>
                    <td>{{ $r->barang->nama_barang }}</td>
                    <td>{{ number_format($r->jumlah) }} {{ $r->barang->satuan }}</td>
                    <td>{{ $r->unitAsal->nama_bisnis_unit }}</td>
                    <td>{{ $r->unitTujuan->nama_bisnis_unit }}</td>
                    <td>
                        @if($r->status == 'pending')
                            <span class="bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full text-xs">Menunggu</span>
                        @elseif($r->status == 'disetujui')
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">Disetujui</span>
                        @else
                            <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-xs">Ditolak</span>
                        @endif
                    </td>
                    <td>{{ $r->created_at->format('d M Y H:i') }}</td>
                    <td><a href="{{ route('stock-ctl.antar-unit.show', $r->id) }}" class="text-blue-600">Detail</a></td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-6 text-gray-500">Tidak ada data yang cocok.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3">
            {{ $requests->appends(request()->query())->links() }}
        </div>
    </div>
</div>
@endsection