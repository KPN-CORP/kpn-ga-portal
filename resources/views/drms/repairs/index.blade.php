@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- HEADER --}}
    <div class="flex flex-wrap justify-between items-center mb-6 gap-2">
        <h1 class="text-2xl font-bold">🔧 Perbaikan / Kerusakan</h1>
        <a href="{{ route('drms.repairs.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
            <span>+</span> Tambah Laporan
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    {{-- FILTER --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <form method="GET" action="{{ route('drms.repairs.index') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🔍 Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Cari kendaraan / keluhan..." 
                       class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 w-44">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🚗 Kendaraan</label>
                <select name="vehicle_id" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    @foreach($vehicles as $v)
                        <option value="{{ $v->id }}" {{ request('vehicle_id') == $v->id ? 'selected' : '' }}>
                            {{ $v->plate_number }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📌 Status</label>
                <select name="status" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>🟡 Open</option>
                    <option value="progress" {{ request('status') == 'progress' ? 'selected' : '' }}>🔵 Progress</option>
                    <option value="done" {{ request('status') == 'done' ? 'selected' : '' }}>✅ Done</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📅 Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" 
                       class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📅 Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" 
                       class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    🔍 Tampilkan
                </button>
                @if(request()->anyFilled(['search', 'vehicle_id', 'status', 'date_from', 'date_to']))
                    <a href="{{ route('drms.repairs.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- QUICK STATS --}}
    @php
        $total = $repairs->total();
        $open = $repairs->where('status', 'open')->count();
        $progress = $repairs->where('status', 'progress')->count();
        $done = $repairs->where('status', 'done')->count();
        $totalCost = $repairs->sum('total_cost');
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase">Total Laporan</p>
            <p class="text-2xl font-bold">{{ $total }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-yellow-500">
            <p class="text-xs text-gray-500 uppercase">🟡 Open</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $open }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase">🔵 Progress</p>
            <p class="text-2xl font-bold text-blue-600">{{ $progress }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase">✅ Done</p>
            <p class="text-2xl font-bold text-green-600">{{ $done }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-purple-500">
            <p class="text-xs text-gray-500 uppercase">Total Biaya</p>
            <p class="text-2xl font-bold text-purple-600">Rp {{ number_format($totalCost, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- TABEL --}}
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kendaraan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Keluhan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Biaya</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($repairs as $repair)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <span class="font-medium">{{ $repair->vehicle->plate_number }}</span>
                            <span class="text-xs text-gray-400 block">{{ $repair->vehicle->type }}</span>
                        </td>
                        <td class="px-6 py-4">{{ $repair->report_date->format('d M Y') }}</td>
                        <td class="px-6 py-4">{{ Str::limit($repair->complaint, 50) }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs 
                                @if($repair->status == 'open') bg-yellow-100 text-yellow-800
                                @elseif($repair->status == 'progress') bg-blue-100 text-blue-800
                                @else bg-green-100 text-green-800 @endif">
                                {{ ucfirst($repair->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 font-semibold text-red-600">Rp {{ number_format($repair->total_cost, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 space-x-2">
                            <a href="{{ route('drms.repairs.show', $repair->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">Detail</a>
                            <a href="{{ route('drms.repairs.edit', $repair->id) }}" class="text-green-600 hover:text-green-800 text-sm">Edit</a>
                            <form action="{{ route('drms.repairs.destroy', $repair->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                            <div class="text-4xl mb-2">🔧</div>
                            <p>Belum ada laporan perbaikan.</p>
                            @if(request()->anyFilled(['search', 'vehicle_id', 'status', 'date_from', 'date_to']))
                                <p class="text-sm mt-1">Coba ubah filter pencarian.</p>
                            @endif
                            <a href="{{ route('drms.repairs.create') }}" class="mt-2 inline-block text-blue-600 hover:underline">+ Tambah Laporan</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($repairs->hasPages())
        <div class="px-6 py-3 border-t">
            {{ $repairs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection