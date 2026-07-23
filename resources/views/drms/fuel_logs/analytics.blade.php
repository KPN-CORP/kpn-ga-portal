@extends('layouts.app_car_sidebar')

@php
    use Carbon\Carbon;
@endphp

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex flex-wrap justify-between items-center mb-6 gap-2">
        <h1 class="text-2xl font-bold">📊 Analisis Konsumsi</h1>
        <a href="{{ route('drms.fuel-logs.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition">
            ⛽ Kembali ke Log Pengisian
        </a>
    </div>

    {{-- FILTER --}}
    @php
        $today = Carbon::now();
        $thisMonthFrom = $today->copy()->startOfMonth()->format('Y-m-d');
        $thisMonthTo   = $today->copy()->endOfMonth()->format('Y-m-d');
        $lastMonth     = $today->copy()->subMonthNoOverflow();
        $lastMonthFrom = $lastMonth->copy()->startOfMonth()->format('Y-m-d');
        $lastMonthTo   = $lastMonth->copy()->endOfMonth()->format('Y-m-d');

        $isThisMonth = request('date_from') === $thisMonthFrom && request('date_to') === $thisMonthTo;
        $isLastMonth = request('date_from') === $lastMonthFrom && request('date_to') === $lastMonthTo;

        $bulanIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];
        $thisMonthLabel = $bulanIndo[$today->month] . ' ' . $today->year;
        $lastMonthLabel = $bulanIndo[$lastMonth->month] . ' ' . $lastMonth->year;
    @endphp
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-4">
        <form method="GET" action="{{ route('drms.fuel-logs.analytics') }}" class="flex flex-wrap gap-3 items-end">
            <div class="relative">
                <label class="block text-xs font-medium text-gray-600 mb-1">🚗 Kendaraan</label>
                @php
                    $selectedVehicle = request('vehicle_id') ? $vehicles->firstWhere('id', (int) request('vehicle_id')) : null;
                    $selectedVehicleLabel = $selectedVehicle ? $selectedVehicle->plate_number . ' - ' . $selectedVehicle->type : '';
                @endphp
                <input type="text" id="vehicle_search" autocomplete="off"
                       placeholder="Ketik plat nomor / tipe..."
                       value="{{ $selectedVehicleLabel }}"
                       class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 w-56">
                <input type="hidden" name="vehicle_id" id="vehicle_id" value="{{ request('vehicle_id') }}">
                <div id="vehicle_suggestions"
                     class="hidden absolute z-20 mt-1 w-56 bg-white border rounded shadow-lg max-h-60 overflow-y-auto"></div>
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
                @if(request()->anyFilled(['vehicle_id', 'date_from', 'date_to']))
                    <a href="{{ route('drms.fuel-logs.analytics') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        {{-- Quick filter periode --}}
        <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t">
            <span class="text-xs text-gray-500 self-center mr-1">Periode cepat:</span>
            <a href="{{ route('drms.fuel-logs.analytics', array_filter(['vehicle_id' => request('vehicle_id'), 'date_from' => $thisMonthFrom, 'date_to' => $thisMonthTo])) }}"
               class="px-3 py-1.5 rounded-full text-xs font-medium {{ $isThisMonth ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Bulan Ini ({{ $thisMonthLabel }})
            </a>
            <a href="{{ route('drms.fuel-logs.analytics', array_filter(['vehicle_id' => request('vehicle_id'), 'date_from' => $lastMonthFrom, 'date_to' => $lastMonthTo])) }}"
               class="px-3 py-1.5 rounded-full text-xs font-medium {{ $isLastMonth ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Bulan Lalu ({{ $lastMonthLabel }})
            </a>
            @if(request()->filled('date_from') || request()->filled('date_to'))
                <a href="{{ route('drms.fuel-logs.analytics', array_filter(['vehicle_id' => request('vehicle_id')])) }}"
                   class="px-3 py-1.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">
                    Semua Periode
                </a>
            @endif
        </div>
    </div>

    {{-- RINGKASAN TOTAL (sesuai filter aktif) --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase">Jumlah Pengisian</p>
            <p class="text-2xl font-bold">{{ $summary['count'] }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase">Total Liter/kWh</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($summary['total_liters'], 2, ',', '.') }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-purple-500">
            <p class="text-xs text-gray-500 uppercase">Total Biaya</p>
            <p class="text-2xl font-bold text-purple-600">Rp {{ number_format($summary['total_cost'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-orange-500">
            <p class="text-xs text-gray-500 uppercase">Total Jarak (km)</p>
            <p class="text-2xl font-bold text-orange-600">{{ number_format($summary['total_distance'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- TABEL PER KENDARAAN --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kendaraan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Rata-rata Konsumsi (L/100km atau kWh/100km)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Liter/kWh</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Biaya</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Jarak (km)</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah Isi</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jenis</th>
                </tr>
            </thead>
            <tbody>
                @forelse($result as $data)
                <tr>
                    <td class="px-6 py-4 font-medium">
                        <a href="{{ route('drms.fuel-logs.analytics', array_filter(['vehicle_id' => $data['vehicle_id'], 'date_from' => request('date_from'), 'date_to' => request('date_to')])) }}"
                           class="text-blue-600 hover:underline">
                            {{ $data['plate_number'] }}
                        </a>
                    </td>
                    <td class="px-6 py-4">
                        @if($data['avg_consumption'] !== null)
                            {{ number_format($data['avg_consumption'], 2) }}
                        @else
                            <span class="text-gray-400" title="Butuh minimal 2 riwayat pengisian untuk menghitung konsumsi">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        {{ number_format($data['total_liters'], 2, ',', '.') }}
                        {{ ($data['fuel_type'] ?? 'Bensin') == 'Listrik' ? 'kWh' : 'Liter' }}
                    </td>
                    <td class="px-6 py-4">Rp {{ number_format($data['total_cost'], 0, ',', '.') }}</td>
                    <td class="px-6 py-4">{{ number_format($data['total_distance'], 0, ',', '.') }}</td>
                    <td class="px-6 py-4">{{ $data['count'] }}</td>
                    <td class="px-6 py-4">{{ $data['fuel_type'] ?? 'Bensin' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">Belum ada data terverifikasi untuk filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-400 mt-3">
        Catatan: rata-rata konsumsi dihitung dari selisih odometer antar pengisian yang berurutan.
        Liter pengisian pertama pada setiap periode tidak dihitung ke rata-rata konsumsi (karena belum ada jarak pembanding),
        namun tetap dihitung ke Total Liter/kWh dan Total Biaya.
    </p>
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
