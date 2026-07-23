@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">⛽ Log Pengisian</h1>
        <div class="space-x-2">
            <a href="{{ route('drms.fuel-logs.analytics') }}" class="bg-purple-600 text-white px-4 py-2 rounded-lg">📊 Analisis</a>
            <a href="{{ route('drms.fuel-logs.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg">+ Tambah</a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">{{ session('error') }}</div>
    @endif

    {{-- FILTER --}}
    <div class="bg-white rounded-lg shadow p-4 mb-6 border">
        <form method="GET" action="{{ route('drms.fuel-logs.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🔍 Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Cari kendaraan, driver..." 
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="relative">
                <label class="block text-xs font-medium text-gray-600 mb-1">🚗 Kendaraan</label>
                @php
                    $selectedVehicle = request('vehicle_id') ? $vehicles->firstWhere('id', (int) request('vehicle_id')) : null;
                    $selectedVehicleLabel = $selectedVehicle ? $selectedVehicle->plate_number . ' - ' . $selectedVehicle->type : '';
                @endphp
                <input type="text" id="vehicle_search" autocomplete="off"
                       placeholder="Ketik plat nomor / tipe..."
                       value="{{ $selectedVehicleLabel }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                <input type="hidden" name="vehicle_id" id="vehicle_id" value="{{ request('vehicle_id') }}">
                <div id="vehicle_suggestions"
                     class="hidden absolute z-20 mt-1 w-full bg-white border rounded-lg shadow-lg max-h-60 overflow-y-auto"></div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📌 Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Status</option>
                    <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>✅ Terverifikasi</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>⏳ Pending</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📅 Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" 
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📅 Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" 
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-2 items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    🔍 Filter
                </button>
                @if(request()->anyFilled(['search', 'vehicle_id', 'status', 'date_from', 'date_to']))
                    <a href="{{ route('drms.fuel-logs.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- Quick Stats --}}
    @php
        $total = $logs->total();
        $verifiedCount = $logs->where('is_verified', 1)->count();
        $pendingCount = $logs->where('is_verified', 0)->count();
        $totalFuel = $logs->sum('fuel_liters');
        $totalCost = $logs->sum('total_cost');
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase">Total Log</p>
            <p class="text-2xl font-bold">{{ $total }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase">Terverifikasi</p>
            <p class="text-2xl font-bold text-green-600">{{ $verifiedCount }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
            <p class="text-xs text-gray-500 uppercase">Pending</p>
            <p class="text-2xl font-bold text-yellow-600">{{ $pendingCount }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-purple-500">
            <p class="text-xs text-gray-500 uppercase">Total Pengisian</p>
            <p class="text-2xl font-bold text-purple-600">{{ number_format($totalFuel, 2, ',', '.') }}</p>
            <p class="text-xs text-gray-500">Rp {{ number_format($totalCost, 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- Tabel --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kendaraan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tanggal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Driver</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Odometer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Liter / kWh</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <span class="font-medium">{{ $log->vehicle->plate_number }}</span>
                            <span class="text-xs text-gray-400 block">{{ $log->vehicle->type }}</span>
                        </td>
                        <td class="px-6 py-4">{{ $log->filling_date->format('d M Y') }}</td>
                        <td class="px-6 py-4">{{ $log->driver->name ?? '-' }}</td>
                        <td class="px-6 py-4">{{ number_format($log->odometer_start, 0, ',', '.') }} km</td>
                        <td class="px-6 py-4">
                            {{ number_format($log->fuel_liters, 2, ',', '.') }}
                            {{ $log->vehicle->fuel_type == 'Listrik' ? 'kWh' : 'Liter' }}
                        </td>
                        <td class="px-6 py-4 font-semibold">Rp {{ number_format($log->total_cost, 0, ',', '.') }}</td>
                        <td class="px-6 py-4">
                            @if($log->is_verified)
                                <span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">✅ Terverifikasi</span>
                            @else
                                <span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">⏳ Pending</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 space-x-2 whitespace-nowrap">
                            <a href="{{ route('drms.fuel-logs.show', $log->id) }}" class="text-blue-600 hover:text-blue-800 text-sm">Detail</a>
                            @if(!$log->is_verified && auth()->user()->isDrmsAdmin())
                                <form action="{{ route('drms.fuel-logs.verify', $log->id) }}" method="POST" class="inline">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="text-green-600 hover:text-green-800 text-sm">Verifikasi</button>
                                </form>
                            @endif
                            <a href="{{ route('drms.fuel-logs.edit', $log->id) }}" class="text-green-600 hover:text-green-800 text-sm">Edit</a>
                            <form action="{{ route('drms.fuel-logs.destroy', $log->id) }}" method="POST" class="inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-gray-500">
                            <div class="text-4xl mb-2">⛽</div>
                            <p>Belum ada log.</p>
                            @if(request()->anyFilled(['search', 'vehicle_id', 'status', 'date_from', 'date_to']))
                                <p class="text-sm mt-1">Coba ubah filter pencarian.</p>
                            @endif
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-3 border-t">
            {{ $logs->links() }}
        </div>
    </div>
</div>

{{-- Autocomplete pencarian kendaraan untuk filter (tanpa dropdown) --}}
<script>
    const VEHICLES_FILTER_DATA = [
        @foreach($vehicles as $v)
        { id: {{ $v->id }}, label: @json($v->plate_number . ' - ' . $v->type) },
        @endforeach
    ];

    document.addEventListener('DOMContentLoaded', function () {
        const searchInput   = document.getElementById('vehicle_search');
        const hiddenInput   = document.getElementById('vehicle_id');
        const suggestionBox = document.getElementById('vehicle_suggestions');

        function hideSuggestions() {
            suggestionBox.innerHTML = '';
            suggestionBox.classList.add('hidden');
        }

        function renderSuggestions(list) {
            const items = [{ id: '', label: 'Semua Kendaraan' }, ...list];
            suggestionBox.innerHTML = items.map(v => `
                <div class="vehicle-option px-3 py-2 text-sm hover:bg-blue-50 cursor-pointer border-b last:border-b-0"
                     data-id="${v.id}" data-label="${v.label.replace(/"/g, '&quot;')}">
                    ${v.label}
                </div>
            `).join('');
            suggestionBox.classList.remove('hidden');

            suggestionBox.querySelectorAll('.vehicle-option').forEach(function (el) {
                el.addEventListener('click', function () {
                    hiddenInput.value = this.getAttribute('data-id');
                    searchInput.value = this.getAttribute('data-id') ? this.getAttribute('data-label') : '';
                    hideSuggestions();
                });
            });
        }

        function search(term) {
            const q = term.trim().toLowerCase();
            if (!q) return VEHICLES_FILTER_DATA;
            return VEHICLES_FILTER_DATA.filter(v => v.label.toLowerCase().includes(q));
        }

        searchInput.addEventListener('input', function () {
            hiddenInput.value = '';
            renderSuggestions(search(this.value));
        });

        searchInput.addEventListener('focus', function () {
            renderSuggestions(search(this.value));
        });

        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !suggestionBox.contains(e.target)) {
                hideSuggestions();
            }
        });
    });
</script>
@endsection