@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto max-w-full px-4 py-6">
    {{-- HEADER --}}
    <div class="flex flex-wrap justify-between items-center mb-6 gap-2">
        <h1 class="text-xl sm:text-2xl font-bold">🔧 Perbaikan / Kerusakan</h1>
        <a href="{{ route('drms.repairs.create') }}" class="w-full sm:w-auto justify-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
            <span>+</span> Tambah Laporan
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    {{-- FILTER --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <form method="GET" action="{{ route('drms.repairs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            <!-- <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🔍 Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Cari kendaraan / keluhan..." 
                       class="w-full border rounded-lg px-3 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🔎 Plat + Merek</label>
                <input type="text" name="plate_brand" value="{{ request('plate_brand') }}"
                       placeholder="cth: B 1929 BYD / SDW M6"
                       class="w-full border rounded-lg px-3 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-blue-500">
            </div> -->
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🚗 Kendaraan</label>
                @include('drms.partials.vehicle-search', [
                    'vehicles' => $vehicles,
                    'name' => 'vehicle_id',
                    'selectedId' => request('vehicle_id'),
                    'placeholder' => 'Cari plat nomor...',
                    'required' => false,
                    'allowAll' => true,
                    'uid' => 'repairs_filter_vehicle',
                ])
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📌 Status</label>
                <select name="status" class="w-full border rounded-lg px-3 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua</option>
                    <option value="open" {{ request('status') == 'open' ? 'selected' : '' }}>🟡 Open</option>
                    <option value="progress" {{ request('status') == 'progress' ? 'selected' : '' }}>🔵 Progress</option>
                    <option value="done" {{ request('status') == 'done' ? 'selected' : '' }}>✅ Done</option>
                    <option value="rejected" {{ request('status')=='rejected'?'selected':'' }}>Rejected</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📅 Periode</label>
                <select name="month" class="w-full border rounded-lg px-3 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="all" {{ $month === 'all' ? 'selected' : '' }}>Semua</option>
                    <optgroup label="Per Tahun">
                        @foreach($yearOptions as $y)
                            <option value="{{ $y }}" {{ (string) $month === (string) $y ? 'selected' : '' }}>Tahun {{ $y }}</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Per Bulan">
                        @foreach($monthOptions as $opt)
                            <option value="{{ $opt }}" {{ $month === $opt ? 'selected' : '' }}>
                                {{ \Carbon\Carbon::createFromFormat('Y-m', $opt)->translatedFormat('F Y') }}
                            </option>
                        @endforeach
                    </optgroup>
                </select>
                <p class="text-xs text-gray-400 mt-1">Diabaikan jika "Dari/Sampai Tanggal" diisi.</p>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📅 Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" 
                       class="w-full border rounded-lg px-3 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📅 Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" 
                       class="w-full border rounded-lg px-3 py-2.5 text-base sm:text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-2 sm:col-span-2 lg:col-span-5">
                <button type="submit" class="flex-1 sm:flex-none bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-lg text-sm font-semibold transition">
                    🔍 Tampilkan
                </button>
                @if(request()->anyFilled(['search', 'plate_brand', 'vehicle_id', 'status', 'date_from', 'date_to']) || $month !== now()->format('Y'))
                    <a href="{{ route('drms.repairs.index') }}" class="flex-1 sm:flex-none text-center bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2.5 rounded-lg text-sm font-semibold transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- QUICK STATS --}}
    @php
        $total = $repairs->total();
        $open = $stats['open'];
        $progress = $stats['progress'];
        $done = $stats['done'];
        $totalCost = $stats['total_cost'];

        // Warna badge status (dipakai tabel desktop dan kartu HP)
        $statusClass = function ($status) {
            return match ($status) {
                'open'     => 'bg-yellow-100 text-yellow-800',
                'progress' => 'bg-blue-100 text-blue-800',
                'rejected' => 'bg-red-100 text-red-800',
                default    => 'bg-green-100 text-green-800',
            };
        };
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 md:gap-4 mb-6">
        <div class="bg-white p-3 md:p-4 rounded-lg shadow-sm border-l-4 border-blue-500 col-span-2 md:col-span-1">
            <p class="text-xs text-gray-500 uppercase">Total Laporan</p>
            <p class="text-xl md:text-2xl font-bold">{{ $total }}</p>
        </div>
        <div class="bg-white p-3 md:p-4 rounded-lg shadow-sm border-l-4 border-yellow-500">
            <p class="text-xs text-gray-500 uppercase">🟡 Open</p>
            <p class="text-xl md:text-2xl font-bold text-yellow-600">{{ $open }}</p>
        </div>
        <div class="bg-white p-3 md:p-4 rounded-lg shadow-sm border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase">🔵 Progress</p>
            <p class="text-xl md:text-2xl font-bold text-blue-600">{{ $progress }}</p>
        </div>
        <div class="bg-white p-3 md:p-4 rounded-lg shadow-sm border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase">✅ Done</p>
            <p class="text-xl md:text-2xl font-bold text-green-600">{{ $done }}</p>
        </div>
        <div class="bg-white p-3 md:p-4 rounded-lg shadow-sm border-l-4 border-purple-500">
            <p class="text-xs text-gray-500 uppercase">Total Biaya</p>
            <p class="text-lg md:text-2xl font-bold text-purple-600 break-words">Rp {{ number_format($totalCost, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        {{-- DESKTOP / TABLET: TABEL --}}
        <div class="hidden md:block overflow-x-auto">
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
                            <span class="px-2 py-1 rounded-full text-xs {{ $statusClass($repair->status) }}">
                                {{ ucfirst($repair->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 font-semibold text-red-600">Rp {{ number_format($repair->total_cost, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 space-x-2">
                            <a href="{{ route('drms.repairs.show', $repair->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">Detail</a>
                            <a href="{{ route('drms.repairs.edit', $repair->id) }}" class="text-green-600 hover:text-green-800 text-sm">Edit</a>
                            <!-- <form action="{{ route('drms.repairs.destroy', $repair->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Hapus</button>
                            </form> -->
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                            <div class="text-4xl mb-2">🔧</div>
                            <p>Belum ada laporan perbaikan.</p>
                            @if(request()->anyFilled(['search', 'plate_brand', 'vehicle_id', 'status', 'date_from', 'date_to']))
                                <p class="text-sm mt-1">Coba ubah filter pencarian.</p>
                            @endif
                            <a href="{{ route('drms.repairs.create') }}" class="mt-2 inline-block text-blue-600 hover:underline">+ Tambah Laporan</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- HP: KARTU PER LAPORAN (tanpa geser samping) --}}
        <div class="md:hidden divide-y divide-gray-200">
            @forelse($repairs as $repair)
            <div class="p-4 space-y-2 break-words min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="font-semibold text-gray-800">{{ $repair->vehicle->plate_number }}</p>
                        <p class="text-xs text-gray-400">{{ $repair->vehicle->type }}</p>
                    </div>
                    <span class="shrink-0 px-2 py-1 rounded-full text-xs {{ $statusClass($repair->status) }}">
                        {{ ucfirst($repair->status) }}
                    </span>
                </div>
                <div class="text-sm text-gray-700">{{ Str::limit($repair->complaint, 120) }}</div>
                <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-1 text-xs text-gray-600">
                    <span>📅 {{ $repair->report_date->format('d M Y') }}</span>
                    <span class="font-semibold text-red-600 text-sm">Rp {{ number_format($repair->total_cost, 0, ',', '.') }}</span>
                </div>
                <div class="flex gap-2 pt-1">
                    <a href="{{ route('drms.repairs.show', $repair->id) }}" class="flex-1 text-center bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-2 rounded text-xs font-semibold">Detail</a>
                    <a href="{{ route('drms.repairs.edit', $repair->id) }}" class="flex-1 text-center bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded text-xs font-semibold">Edit</a>
                </div>
            </div>
            @empty
            <div class="px-4 py-10 text-center text-gray-500">
                <div class="text-4xl mb-2">🔧</div>
                <p>Belum ada laporan perbaikan.</p>
                @if(request()->anyFilled(['search', 'plate_brand', 'vehicle_id', 'status', 'date_from', 'date_to']))
                    <p class="text-sm mt-1">Coba ubah filter pencarian.</p>
                @endif
                <a href="{{ route('drms.repairs.create') }}" class="mt-2 inline-block text-blue-600 hover:underline">+ Tambah Laporan</a>
            </div>
            @endforelse
        </div>

        @if($repairs->hasPages())
        <div class="px-4 sm:px-6 py-3 border-t">
            {{ $repairs->links() }}
        </div>
        @endif
    </div>
</div>
@endsection