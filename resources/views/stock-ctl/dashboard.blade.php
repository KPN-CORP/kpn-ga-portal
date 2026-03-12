@extends('layouts.app_stock_sidebar')

@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">
    <div>
        <h2 class="text-xl font-semibold text-gray-800">Dashboard ATK</h2>
        @php $access = session('stock_ctl_access', []); @endphp
        @if($access['is_super'] ?? false)
            <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                Superadmin Mode
            </span>
        @elseif($access['is_admin'] ?? false)
            <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                Admin Area
            </span>
        @else
            <span class="inline-flex items-center mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                User Mode
            </span>
        @endif
    </div>

    <!-- <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl p-6 soft-border soft-shadow">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-boxes text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Item Stok</p>
                    <p class="text-2xl font-semibold">{{ $totalStok ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 soft-border soft-shadow">
            <div class="flex items-center">
                <div class="p-3 bg-yellow-100 rounded-lg">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Permintaan Pending</p>
                    <p class="text-2xl font-semibold">{{ $permintaanPending ?? 0 }}</p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl p-6 soft-border soft-shadow">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-exchange-alt text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Transaksi Hari Ini</p>
                    <p class="text-2xl font-semibold">{{ $transaksiHariIni ?? 0 }}</p>
                </div>
            </div>
        </div>
    </div> -->

    {{-- Tabel stok terbaru --}}
    <div class="bg-white border rounded-xl overflow-x-auto">
        <div class="p-4 soft-border-bottom flex justify-between items-center">
            <h3 class="font-medium">Stok Terkini</h3>
            <a href="{{ route('stock-ctl.stok.index') }}" class="text-blue-600 text-sm hover:underline">Lihat Semua</a>
        </div>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Barang</th>
                    <th class="px-4 py-3 text-left">Area</th>
                    <th class="px-4 py-3 text-left">Jumlah</th>
                    <th class="px-4 py-3 text-left">Stok Minimum</th>
                    <th class="px-4 py-3 text-left">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($stokTerbaru ?? [] as $item)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $item->barang->nama_barang ?? '-' }}</td>
                    <td class="px-4 py-3">{{ $item->areaKerja->nama_area ?? '-' }}</td>
                    <td class="px-4 py-3">{{ number_format($item->jumlah) }} {{ $item->barang->satuan ?? '' }}</td>
                    <td class="px-4 py-3">{{ number_format($item->stok_minimum) }}</td>
                    <td class="px-4 py-3">
                        @if($item->jumlah <= $item->stok_minimum)
                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">Stok Habis</span>
                        @else
                            <span class="px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">Aman</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="py-10 text-center text-gray-500">Belum ada data stok</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection