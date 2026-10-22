@extends('layouts.app-sidebar-card')
@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">

    <div>
        <h2 class="text-xl font-semibold text-gray-800">Kartu Tidak Aktif</h2>
        <p class="text-xs text-gray-500">Kartu yang sudah dinonaktifkan oleh admin (tidak tergantung masa berlaku)</p>
    </div>

    <div class="bg-white border rounded-xl p-4">
        <form method="GET" action="{{ route('idcard.inaktif') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="text-sm font-medium text-gray-600">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Nama / NIK">
            </div>
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
                <input type="text" name="nomor_kartu" value="{{ request('nomor_kartu') }}" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500" placeholder="Cari nomor kartu">
            </div>
            @if(isset($bisnisUnits) && $bisnisUnits->count() > 0)
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
            <div class="sm:col-span-2 lg:col-span-3 flex flex-col sm:flex-row gap-2 justify-end">
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">Terapkan</button>
                <a href="{{ route('idcard.inaktif') }}" class="px-4 py-2 bg-gray-200 rounded-lg text-sm font-semibold text-center">Reset</a>
            </div>
        </form>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">{{ session('error') }}</div>
    @endif

    <p class="text-xs text-gray-500">
        @if($data->total() > 0)
            Menampilkan {{ $data->firstItem() }}-{{ $data->lastItem() }} dari {{ $data->total() }} kartu
        @else
            Tidak ada data
        @endif
    </p>

    <div class="bg-white border rounded-xl overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Nama</th>
                    <th class="px-4 py-3 text-left hidden sm:table-cell">NIK</th>
                    <th class="px-4 py-3 text-left hidden md:table-cell">No. Kartu</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Bisnis Unit</th>
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Kategori</th>
                    <th class="px-4 py-3 text-left">Periode</th>
                    <th class="px-4 py-3 text-left">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($data as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ $item->nama }}</div>
                        <div class="sm:hidden text-xs text-gray-500">NIK: {{ $item->nik ?? '-' }}</div>
                        @if($item->nomor_kartu)
                            <div class="md:hidden text-xs text-gray-500 font-mono">{{ $item->nomor_kartu }}</div>
                        @endif
                    </td>
                    <td class="px-4 py-3 hidden sm:table-cell">{{ $item->nik ?? '-' }}</td>
                    <td class="px-4 py-3 hidden md:table-cell"><span class="font-mono">{{ $item->nomor_kartu ?? '-' }}</span></td>
                    <td class="px-4 py-3 hidden lg:table-cell">{{ optional($bisnisUnits->firstWhere('id_bisnis_unit', $item->bisnis_unit_id))->nama_bisnis_unit ?? '-' }}</td>
                    <td class="px-4 py-3 hidden lg:table-cell">{{ $item->kategori_text }}</td>
                    <td class="px-4 py-3">@include('idcard.partials.periode', ['item' => $item])</td>
                    <td class="px-4 py-3">
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('idcard.detail', $item->id) }}" class="text-blue-600 hover:underline">Detail</a>
                            @if(!empty($canNonaktifkan))
                                <button type="button" data-toggle-card="aktif"
                                        data-id="{{ $item->id }}" data-nama="{{ $item->nama }}" data-nomor="{{ $item->nomor_kartu }}"
                                        class="text-green-600 hover:underline">Aktifkan</button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="py-10 text-center text-gray-500">Tidak ada kartu tidak aktif</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($data->hasPages())
        <div class="mt-4">{{ $data->withQueryString()->links() }}</div>
    @endif
</div>

@include('idcard.partials.toggle-modal')
@endsection
