@extends('layouts.app-sidebar-card')
@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h2 class="text-xl font-semibold text-gray-800">Report ID Card</h2>
        <a href="{{ route('idcard.report.download', request()->query()) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-semibold hover:bg-green-700">
            <i class="fas fa-file-excel mr-2"></i> Download Excel
        </a>
    </div>

    {{-- Ringkasan stok (superadmin) dan kartu pinjam --}}
    @if($stokRingkasan->isNotEmpty() || $kartuRingkas)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @if($stokRingkasan->isNotEmpty())
                <div class="bg-white border rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-800">Stok Anda</h3>
                        <a href="{{ route('idcard.stok') }}" class="text-xs text-blue-600 hover:underline">Kelola stok</a>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach($stokRingkasan as $r)
                            @php
                                $cls = ['aman' => 'text-green-700', 'menipis' => 'text-orange-600', 'habis' => 'text-red-600'][$r->status];
                            @endphp
                            <div>
                                <p class="text-xs text-gray-500">{{ $r->nama }}</p>
                                <p class="text-2xl font-semibold {{ $cls }}">{{ number_format($r->stok) }}</p>
                                <p class="text-xs text-gray-500">
                                    min {{ number_format($r->min_stok) }}@if($r->perlu_beli) <span class="text-red-600">| perlu beli</span>@endif
                                </p>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
            @if($kartuRingkas)
                <div class="bg-white border rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-semibold text-gray-800">Kartu Pinjam (Magang &amp; Visitor)</h3>
                        <a href="{{ route('idcard.kartu-pinjam') }}" class="text-xs text-blue-600 hover:underline">Lihat daftar</a>
                    </div>
                    <div class="grid grid-cols-4 gap-3">
                        <div><p class="text-xs text-gray-500">Total</p><p class="text-2xl font-semibold">{{ $kartuRingkas['total'] }}</p></div>
                        <div><p class="text-xs text-gray-500">Digunakan</p><p class="text-2xl font-semibold text-blue-700">{{ $kartuRingkas['digunakan'] }}</p></div>
                        <div><p class="text-xs text-gray-500">Ada</p><p class="text-2xl font-semibold text-green-700">{{ $kartuRingkas['ada'] }}</p></div>
                        <div><p class="text-xs text-gray-500">Tidak beredar</p><p class="text-2xl font-semibold text-gray-600">{{ $kartuRingkas['nonaktif'] }}</p></div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    <div class="bg-white border rounded-xl p-4">
        <form method="GET" action="{{ route('idcard.report') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
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
            <div>
                <label class="text-sm font-medium text-gray-600">Kategori</label>
                <select name="kategori" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    @include('idcard.partials.kategori-options', ['selected' => request('kategori'), 'allLabel' => 'Semua'])
                </select>
            </div>
            <div>
                <label class="text-sm font-medium text-gray-600">Bisnis Unit</label>
                <select name="bisnis_unit_id" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="all">Semua Unit</option>
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
                <button class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">Terapkan</button>
                <a href="{{ route('idcard.report') }}" class="px-4 py-2 bg-gray-200 rounded-lg text-sm font-semibold text-center">Reset</a>
            </div>
        </form>
    </div>

    <p class="text-xs text-gray-500">
        @if($data->total() > 0)
            Menampilkan {{ $data->firstItem() }}-{{ $data->lastItem() }} dari {{ $data->total() }} data
            (file Excel berisi seluruh {{ $data->total() }} data sesuai filter, ditambah sheet Stok Saat Ini, Mutasi Stok, dan Kartu Pinjam; superadmin melihat stok semua admin, admin lain hanya stok sendiri)
        @else
            Tidak ada data
        @endif
    </p>

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
                    <th class="px-4 py-3 text-left hidden lg:table-cell">Item</th>
                    <th class="px-4 py-3 text-left hidden xl:table-cell">Pengembalian</th>
                    <th class="px-4 py-3 text-left">Tanggal</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($data as $item)
                <tr>
                    <td class="px-4 py-3">{{ $item->id }}</td>
                    <td class="px-4 py-3">{{ $item->nik ?? '-' }}</td>
                    <td class="px-4 py-3 font-medium">{{ $item->nama }}</td>
                    <td class="px-4 py-3 hidden md:table-cell">{{ $item->kategori_text }}</td>
                    <td class="px-4 py-3 hidden lg:table-cell">{{ optional($bisnisUnits->firstWhere('id_bisnis_unit',$item->bisnis_unit_id))->nama_bisnis_unit ?? '-' }}</td>
                    <td class="px-4 py-3">
                        @include('idcard.partials.status-badge', ['status' => $item->status])
                    </td>
                    <td class="px-4 py-3 hidden xl:table-cell">{{ $item->nomor_kartu ?? '-' }}</td>
                    <td class="px-4 py-3 hidden lg:table-cell">{{ $item->item_text ?: '-' }}</td>
                    <td class="px-4 py-3 hidden xl:table-cell">
                        @if($item->status === 'approved' && in_array($item->kategori, \App\Models\IDCard\RequestIdCard::KATEGORI_KEMBALI, true))
                            {{ $item->return_status_text }}
                            @if(($item->return_status ?? 'none') !== 'none' && $item->return_kondisi)
                                <span class="text-xs text-gray-500">({{ \App\Models\IDCard\RequestIdCard::RETURN_KONDISI_LABELS[$item->return_kondisi] ?? $item->return_kondisi }})</span>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td class="px-4 py-3">{{ $item->created_at?->format('d-m-Y') ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="10" class="py-10 text-center text-gray-500">Data tidak ditemukan</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($data->hasPages())
        <div class="mt-4">{{ $data->withQueryString()->links() }}</div>
    @endif
</div>
@endsection