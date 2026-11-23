@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto max-w-full">
    <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
        <h1 class="text-xl sm:text-2xl font-bold">Daftar Driver</h1>
        <a href="{{ route('drms.drivers.create') }}" class="w-full sm:w-auto text-center bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Tambah Driver</a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif

    {{-- FILTER: HP satu kolom, tablet dua kolom, desktop sebaris --}}
    <div class="bg-white p-4 rounded-lg shadow border mb-4">
        <form method="GET" action="{{ route('drms.drivers.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:flex lg:flex-wrap gap-3 lg:items-end">
            @if(auth()->user()->isDrmsSuperAdmin())
            <div class="w-full lg:w-auto">
                <label class="block text-xs font-medium text-gray-600 mb-1">🏢 Business Unit</label>
                <select name="business_unit_id" id="bu_filter" class="w-full lg:w-48 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua BU</option>
                    @foreach($businessUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}" {{ request('business_unit_id') == $bu->id_bisnis_unit ? 'selected' : '' }}>
                            {{ $bu->nama_bisnis_unit }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="w-full lg:w-auto">
                <label class="block text-xs font-medium text-gray-600 mb-1">📌 Status</label>
                <select name="status" class="w-full lg:w-36 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Status</option>
                    <option value="available" {{ request('status') == 'available' ? 'selected' : '' }}>✅ Available</option>
                    <option value="on_trip" {{ request('status') == 'on_trip' ? 'selected' : '' }}>🔄 On Trip</option>
                    <option value="off_duty" {{ request('status') == 'off_duty' ? 'selected' : '' }}>⏸️ Off Duty</option>
                </select>
            </div>
            <div class="relative w-full sm:col-span-2 lg:col-span-1 lg:w-64">
                <label class="block text-xs font-medium text-gray-600 mb-1">🔍 Cari</label>
                @include('drms.partials.search-suggest', [
                    'items'       => $driverNames,
                    'name'        => 'search',
                    'value'       => request('search'),
                    'placeholder' => 'Cari nama atau telepon...',
                    'emptyText'   => 'Nama driver tidak ditemukan',
                    'uid'         => 'driver_search',
                    'busSelect'   => '#bu_filter',
                ])
            </div>
            <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                <button type="submit" class="flex-1 lg:flex-none bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    🔍 Filter
                </button>
                @if(request()->anyFilled(['search', 'status', 'business_unit_id']))
                    <a href="{{ route('drms.drivers.index') }}" class="flex-1 lg:flex-none text-center bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- QUICK STATS --}}
    @php
        $totalDrivers = $drivers->total();
        $available = $drivers->where('status', 'available')->count();
        $onTrip = $drivers->where('status', 'on_trip')->count();
        $offDuty = $drivers->where('status', 'off_duty')->count();

        $statusColors = [
            'available' => 'bg-green-100 text-green-800',
            'on_trip' => 'bg-yellow-100 text-yellow-800',
            'off_duty' => 'bg-gray-100 text-gray-800',
        ];
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 mb-4">
        <div class="bg-white rounded-lg shadow p-3 border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase">Total Driver</p>
            <p class="text-xl font-bold">{{ $totalDrivers }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-3 border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase">Available</p>
            <p class="text-xl font-bold text-green-600">{{ $available }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-3 border-l-4 border-yellow-500">
            <p class="text-xs text-gray-500 uppercase">On Trip</p>
            <p class="text-xl font-bold text-yellow-600">{{ $onTrip }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-3 border-l-4 border-gray-500">
            <p class="text-xs text-gray-500 uppercase">Off Duty</p>
            <p class="text-xl font-bold text-gray-600">{{ $offDuty }}</p>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        {{-- DESKTOP / TABLET: TABEL --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Telepon</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Business Unit</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($drivers as $driver)
                    <tr>
                        <td class="px-6 py-4 font-medium">{{ $driver->name }}</td>
                        <td class="px-6 py-4">{{ $driver->phone ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-full text-xs {{ $statusColors[$driver->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ ucfirst(str_replace('_', ' ', $driver->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">{{ $driver->businessUnit->nama_bisnis_unit ?? $driver->business_unit_id ?? '-' }}</td>
                        <td class="px-6 py-4 space-x-2">
                            <a href="{{ route('drms.drivers.edit', $driver) }}" class="text-blue-600 hover:underline">Edit</a>
                            <form action="{{ route('drms.drivers.destroy', $driver) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:underline">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            <div class="text-4xl mb-2">🚗</div>
                            <p>Belum ada driver.</p>
                            @if(request()->anyFilled(['search', 'status', 'business_unit_id']))
                                <p class="text-sm mt-1">Coba ubah filter pencarian.</p>
                            @endif
                            <a href="{{ route('drms.drivers.create') }}" class="mt-2 inline-block text-blue-600 hover:underline">+ Tambah Driver</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- HP: KARTU PER DRIVER (tanpa geser samping) --}}
        <div class="md:hidden divide-y divide-gray-200">
            @forelse($drivers as $driver)
            <div class="p-4 space-y-2 break-words min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <span class="font-semibold text-gray-800">{{ $driver->name }}</span>
                    <span class="shrink-0 px-2 py-1 rounded-full text-xs {{ $statusColors[$driver->status] ?? 'bg-gray-100 text-gray-800' }}">
                        {{ ucfirst(str_replace('_', ' ', $driver->status)) }}
                    </span>
                </div>
                <div class="text-sm text-gray-600">
                    📞
                    @if($driver->phone)
                        <a href="tel:{{ $driver->phone }}" class="text-blue-600">{{ $driver->phone }}</a>
                    @else
                        -
                    @endif
                </div>
                <div class="text-sm text-gray-600">🏢 {{ $driver->businessUnit->nama_bisnis_unit ?? $driver->business_unit_id ?? '-' }}</div>
                <div class="flex gap-2 pt-1">
                    <a href="{{ route('drms.drivers.edit', $driver) }}" class="flex-1 text-center bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-xs font-semibold">Edit</a>
                    <form action="{{ route('drms.drivers.destroy', $driver) }}" method="POST" class="flex-1" onsubmit="return confirm('Yakin ingin menghapus?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded text-xs font-semibold">Hapus</button>
                    </form>
                </div>
            </div>
            @empty
            <div class="px-4 py-8 text-center text-gray-500">
                <div class="text-4xl mb-2">🚗</div>
                <p>Belum ada driver.</p>
                @if(request()->anyFilled(['search', 'status', 'business_unit_id']))
                    <p class="text-sm mt-1">Coba ubah filter pencarian.</p>
                @endif
                <a href="{{ route('drms.drivers.create') }}" class="mt-2 inline-block text-blue-600 hover:underline">+ Tambah Driver</a>
            </div>
            @endforelse
        </div>

        {{-- PAGINATION --}}
        @if($drivers->hasPages())
        <div class="px-4 sm:px-6 py-3 border-t">
            {{ $drivers->links() }}
        </div>
        @endif
    </div>
</div>
@endsection