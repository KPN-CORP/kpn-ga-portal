@extends('layouts.app-sidebar-card')
@section('content')
@php
    $hasSpecialAccess = isset($hasSpecialAccess) && $hasSpecialAccess;
    // Hitung filter yang sedang aktif supaya panel filter tidak "menyembunyikan" data yang terfilter
    $activeFilters = collect(['status', 'kategori', 'bisnis_unit_id', 'periode'])
        ->filter(fn ($k) => request($k) && request($k) !== 'all')->count()
        + (request('search') ? 1 : 0) + (request('nomor_kartu') ? 1 : 0);
@endphp
<div class="space-y-6 text-sm text-gray-800 font-sans">

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Daftar Request ID Card</h2>
            @if($hasSpecialAccess)
                <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Akses Penuh</span>
            @else
                <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">Request Saya</span>
            @endif
        </div>
        <div class="flex gap-2 w-full sm:w-auto">
            <a href="{{ route('idcard.request') }}" class="flex-1 sm:flex-none inline-flex items-center justify-center px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold hover:bg-blue-700 transition">+ Request Baru</a>
            <button type="button" id="toggleFilterBtn" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-semibold hover:bg-gray-200 transition">
                Filter
                @if($activeFilters > 0)
                    <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full bg-blue-600 text-white text-xs">{{ $activeFilters }}</span>
                @endif
            </button>
        </div>
    </div>

    <div id="filterSection" class="bg-white border rounded-xl p-4 {{ $activeFilters > 0 ? '' : 'hidden' }}">
        <form method="GET" action="{{ route('idcard.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Nama / NIK" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Status</label>
                <select name="status" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    @include('idcard.partials.status-options', ['selected' => request('status'), 'allLabel' => 'Semua'])
                </select>
            </div>
            @if($hasSpecialAccess)
            <div>
                <label class="text-sm font-medium text-gray-600">Bisnis Unit</label>
                <select name="bisnis_unit_id" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="all">Semua Unit</option>
                    @foreach($bisnisUnits as $unit)
                        <option value="{{ $unit->id_bisnis_unit }}" {{ request('bisnis_unit_id')==$unit->id_bisnis_unit?'selected':'' }}>{{ $unit->nama_bisnis_unit }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div>
                <label class="text-sm font-medium text-gray-600">Kategori</label>
                <select name="kategori" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    @include('idcard.partials.kategori-options', ['selected' => request('kategori'), 'allLabel' => 'Semua'])
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Periode</label>
                <select name="periode" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="all">Semua Periode</option>
                    <option value="masa_aktif" {{ request('periode')=='masa_aktif'?'selected':'' }}>Masa Aktif</option>
                    <option value="masa_tidak_aktif" {{ request('periode')=='masa_tidak_aktif'?'selected':'' }}>Masa Tidak Aktif</option>
                    <option value="masa_habis_segera" {{ request('periode')=='masa_habis_segera'?'selected':'' }}>Habis dalam 30 Hari</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Nomor Kartu</label>
                <input type="text" name="nomor_kartu" value="{{ request('nomor_kartu') }}" placeholder="Cari nomor kartu" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="sm:col-span-2 lg:col-span-4 flex flex-col sm:flex-row gap-2 justify-end">
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">Terapkan</button>
                <a href="{{ route('idcard.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg text-sm font-semibold text-center">Reset</a>
            </div>
        </form>
    </div>

    <p class="text-xs text-gray-500">
        @if($data->total() > 0)
            Menampilkan {{ $data->firstItem() }}-{{ $data->lastItem() }} dari {{ $data->total() }} data
        @else
            Tidak ada data
        @endif
    </p>

    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left hidden sm:table-cell">NIK</th>
                    <th class="px-4 py-3 text-left">Nama</th>
                    @if($hasSpecialAccess)
                        <th class="px-4 py-3 text-left hidden md:table-cell">Bisnis Unit</th>
                    @endif
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Kategori</th>
                    <th class="px-4 py-3 text-left">Periode</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left hidden xl:table-cell">No. Kartu</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Tanggal Request</th>
                    <th class="px-4 py-3 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($data as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 hidden sm:table-cell">{{ $item->nik ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ $item->nama }}</div>
                        {{-- Info yang disembunyikan di layar kecil ditampilkan di bawah nama --}}
                        <div class="sm:hidden text-xs text-gray-500">NIK: {{ $item->nik ?? '-' }}</div>
                        <div class="lg:hidden text-xs text-gray-500">{{ $item->kategori_text }}</div>
                        @if($item->nomor_kartu)
                            <div class="xl:hidden text-xs text-gray-500 font-mono">{{ $item->nomor_kartu }}</div>
                        @endif
                    </td>
                    @if($hasSpecialAccess)
                        <td class="px-4 py-3 hidden md:table-cell">{{ optional($bisnisUnits->firstWhere('id_bisnis_unit', $item->bisnis_unit_id))->nama_bisnis_unit ?? '-' }}</td>
                    @endif
                    <td class="px-4 py-3 hidden lg:table-cell">{{ $item->kategori_text }}</td>
                    <td class="px-4 py-3">@include('idcard.partials.periode', ['item' => $item])</td>
                    <td class="px-4 py-3">@include('idcard.partials.status-badge', ['status' => $item->status])</td>
                    <td class="px-4 py-3 hidden xl:table-cell">
                        @if($item->nomor_kartu)
                            <span class="font-mono text-sm bg-gray-50 px-2 py-1 rounded border">{{ $item->nomor_kartu }}</span>
                        @else
                            <span class="text-gray-400 text-xs">-</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 hidden lg:table-cell">{{ $item->created_at?->format('d-m-Y') ?? '-' }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('idcard.detail', $item->id) }}" class="text-blue-600 font-semibold hover:underline">Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="{{ $hasSpecialAccess ? 9 : 8 }}" class="py-10 text-center text-gray-500">Data tidak ditemukan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($data->hasPages())
        <div class="mt-4">{{ $data->withQueryString()->links() }}</div>
    @endif
</div>

<script>
document.getElementById('toggleFilterBtn')?.addEventListener('click', () => {
    document.getElementById('filterSection').classList.toggle('hidden');
});
</script>
@endsection
