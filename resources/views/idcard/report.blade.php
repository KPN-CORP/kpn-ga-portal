@extends('layouts.app-sidebar-card')
@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-xl font-semibold text-gray-800">Report ID Card</h2>
        <a href="{{ route('idcard.report.download', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700">
            <i class="fas fa-file-excel mr-2"></i> Download Excel
        </a>
    </div>

    <div class="bg-white border rounded-xl p-4">
        <form method="GET" action="{{ route('idcard.report') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <select name="status" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="all">All</option>
                    <option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending</option>
                    <option value="approved" {{ request('status')=='approved'?'selected':'' }}>Approved</option>
                    <option value="rejected" {{ request('status')=='rejected'?'selected':'' }}>Rejected</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Kategori</label>
                <select name="kategori" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="all">All</option>
                    <option value="karyawan_baru" {{ request('kategori')=='karyawan_baru'?'selected':'' }}>Karyawan Baru</option>
                    <option value="karyawan_mutasi" {{ request('kategori')=='karyawan_mutasi'?'selected':'' }}>Karyawan Mutasi</option>
                    <option value="ganti_kartu" {{ request('kategori')=='ganti_kartu'?'selected':'' }}>Ganti Kartu</option>
                    <option value="magang" {{ request('kategori')=='magang'?'selected':'' }}>Magang</option>
                    <option value="magang_extend" {{ request('kategori')=='magang_extend'?'selected':'' }}>Magang Extend</option>
                    <option value="perubahan_lantai" {{ request('kategori')=='perubahan_lantai'?'selected':'' }}>Perubahan Lantai</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Bisnis Unit</label>
                <select name="bisnis_unit_id" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="all">All Units</option>
                    @foreach($bisnisUnits as $unit)
                        <option value="{{ $unit->id_bisnis_unit }}" {{ request('bisnis_unit_id')==$unit->id_bisnis_unit?'selected':'' }}>{{ $unit->nama_bisnis_unit }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Periode Awal</label>
                <input type="date" name="periode_awal" value="{{ request('periode_awal') }}" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Periode Akhir</label>
                <input type="date" name="periode_akhir" value="{{ request('periode_akhir') }}" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="lg:col-span-2 flex flex-col sm:flex-row gap-2 justify-end">
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">Apply</button>
                <a href="{{ route('idcard.report') }}" class="px-4 py-2 bg-gray-200 rounded-lg text-sm font-semibold text-center">Reset</a>
            </div>
        </form>
    </div>

    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">ID</th>
                    <th class="px-4 py-3 text-left">NIK</th>
                    <th class="px-4 py-3 text-left">Nama</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">Kategori</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Bisnis Unit</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left hidden xl:table-cell">No. Kartu</th>
                    <th class="px-4 py-3 text-left">Tanggal</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($data as $item)
                <tr>
                    <td class="px-4 py-3">{{ $item->id }}</td>
                    <td class="px-4 py-3">{{ $item->nik ?? '-' }}</td>
                    <td class="px-4 py-3 font-medium">{{ $item->nama }}</td>
                    <td class="px-4 py-3 hidden md:table-cell">{{ $item->kategori }}</td>
                    <td class="px-4 py-3 hidden lg:table-cell">{{ optional($bisnisUnits->firstWhere('id_bisnis_unit',$item->bisnis_unit_id))->nama_bisnis_unit ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <span class="px-2 py-1 rounded-full text-xs font-semibold
                            {{ $item->status=='pending'?'bg-yellow-100 text-yellow-800':
                               ($item->status=='approved'?'bg-green-100 text-green-800':
                               'bg-red-100 text-red-800') }}">
                            {{ ucfirst($item->status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 hidden xl:table-cell">{{ $item->nomor_kartu ?? '-' }}</td>
                    <td class="px-4 py-3">{{ \Carbon\Carbon::parse($item->created_at)->format('d M Y') }}</td>
                </tr>
                @empty
                <tr><td colspan="8" class="py-10 text-center text-gray-500">Data tidak ditemukan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($data->hasPages())
        <div class="mt-4">{{ $data->withQueryString()->links() }}</div>
    @endif
</div>
@endsection