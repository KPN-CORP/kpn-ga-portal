@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- HEADER --}}
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">📊 Dashboard Operasional</h1>
            <p class="text-sm text-gray-500">
                Ringkasan biaya, efisiensi, dan aktivitas kendaraan
                @if($isSuperAdmin ?? false)
                    <span class="ml-2 px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold">🔓 Superadmin - Semua BU</span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('drms.admin.operational.export', ['month' => $month, 'year' => $year]) }}" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </a>
            <a href="{{ route('drms.admin.monitoring.logs') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                📋 Log Driver
            </a>
        </div>
    </div>

    {{-- FILTER BULAN/TAHUN --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <form method="GET" action="{{ route('drms.admin.operational.dashboard') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600">Bulan</label>
                <select name="month" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    @foreach($months as $key => $label)
                        <option value="{{ $key }}" {{ $month == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600">Tahun</label>
                <select name="year" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                Tampilkan
            </button>
            <a href="{{ route('drms.admin.operational.dashboard') }}" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg text-sm font-semibold transition">
                Reset
            </a>
        </form>
    </div>

    {{-- STATISTIK UTAMA --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase">Total Biaya</p>
            <p class="text-xl font-bold text-blue-600">Rp {{ number_format($stats['total_operational_cost'] ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-yellow-500">
            <p class="text-xs text-gray-500 uppercase">BBM/Charge</p>
            <p class="text-xl font-bold text-yellow-600">Rp {{ number_format($stats['total_fuel_cost'] ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-orange-500">
            <p class="text-xs text-gray-500 uppercase">Service</p>
            <p class="text-xl font-bold text-orange-600">Rp {{ number_format($stats['total_service_cost'] ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 {{ ($stats['pending_verification'] ?? 0) > 0 ? 'border-red-500' : 'border-green-500' }}">
            <p class="text-xs text-gray-500 uppercase">Menunggu Verifikasi</p>
            <p class="text-xl font-bold {{ ($stats['pending_verification'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ $stats['pending_verification'] ?? 0 }}
            </p>
        </div>
    </div>

    {{-- STATISTIK TAMBAHAN --}}
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm border">
            <p class="text-xs text-gray-500 uppercase">Total Perjalanan (Terverifikasi)</p>
            <p class="text-xl font-bold text-purple-600">{{ $stats['total_trips'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border">
            <p class="text-xs text-gray-500 uppercase">Total Jarak Tempuh</p>
            <p class="text-xl font-bold text-indigo-600">{{ number_format($stats['total_distance'] ?? 0, 0, ',', '.') }} km</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border">
            <p class="text-xs text-gray-500 uppercase">Rata-rata Efisiensi</p>
            <p class="text-xl font-bold text-teal-600">
                @if(isset($stats['avg_efficiency']))
                    {{ number_format($stats['avg_efficiency'], 2) }} {{ $stats['fuel_unit'] ?? 'km/liter' }}
                @else
                    -
                @endif
            </p>
        </div>
    </div>

    {{-- GRAFIK BIAYA PER BULAN --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <h3 class="font-semibold text-gray-700 mb-3">📈 Biaya Operasional per Bulan (12 bulan terakhir)</h3>
        <div class="relative" style="height: 250px;">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>

    {{-- GRAFIK EFISIENSI & DISTRIBUSI --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Grafik Efisiensi -->
        <div class="bg-white p-4 rounded-lg shadow-sm border">
            <h3 class="font-semibold text-gray-700 mb-3">⛽ Efisiensi Kendaraan (Top 5)</h3>
            <div class="relative" style="height: 200px;">
                <canvas id="efficiencyChart"></canvas>
            </div>
            @if($efficiencyData->isEmpty())
                <p class="text-center text-gray-400 text-sm mt-2">Belum ada data efisiensi</p>
            @endif
        </div>

        <!-- Grafik Distribusi Transportasi per Bulan -->
        <div class="bg-white p-4 rounded-lg shadow-sm border">
            <h3 class="font-semibold text-gray-700 mb-3">🚗 Distribusi Transportasi ({{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }})</h3>
            <div class="relative" style="height: 200px;">
                <canvas id="transportChart"></canvas>
            </div>
            @if($transportDistribution->isEmpty())
                <p class="text-center text-gray-400 text-sm mt-2">Belum ada data distribusi untuk periode ini</p>
            @endif
        </div>
    </div>

    {{-- TABEL LOG TERBARU --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border">
        <div class="flex justify-between items-center mb-3">
            <h3 class="font-semibold text-gray-700">📋 Log Terbaru</h3>
            <a href="{{ route('drms.admin.monitoring.logs') }}" class="text-blue-600 hover:underline text-sm">Lihat semua →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Request</th>
                        <th class="px-3 py-2 text-left">Driver</th>
                        <th class="px-3 py-2 text-left">Jarak</th>
                        <th class="px-3 py-2 text-left">Biaya</th>
                        <th class="px-3 py-2 text-left">Status</th>
                        <th class="px-3 py-2 text-left">Tgl Verifikasi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentLogs ?? [] as $log)
                    <tr class="border-t hover:bg-gray-50">
                        <td class="px-3 py-2 font-medium">{{ $log->request->request_no ?? '-' }}</td>
                        <td class="px-3 py-2">{{ $log->request->driver->name ?? '-' }}</td>
                        <td class="px-3 py-2">{{ $log->distance ?? '-' }} km</td>
                        <td class="px-3 py-2">Rp {{ number_format($log->fuel_cost ?? 0, 0, ',', '.') }}</td>
                        <td class="px-3 py-2">
                            @if($log->is_verified)
                                <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs">✅ Diverifikasi</span>
                            @elseif($log->is_submitted)
                                <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs">⏳ Pending</span>
                            @elseif($log->needsRevision())
                                <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs">🔄 Revisi</span>
                            @else
                                <span class="px-2 py-0.5 bg-gray-100 text-gray-700 rounded-full text-xs">📝 Draft</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">{{ $log->verified_at ? $log->verified_at->format('d M Y') : '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-3 py-4 text-center text-gray-400">Belum ada log</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- CHART.JS --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Data dari Laravel
        const monthlyData = @json($chartData);
        const efficiencyData = @json($efficiencyData);
        const transportData = @json($transportDistribution);

        // ========== CHART BIAYA PER BULAN ==========
        const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctxMonthly, {
            type: 'bar',
            data: {
                labels: monthlyData.map(d => d.month),
                datasets: [
                    {
                        label: 'BBM/Charge',
                        data: monthlyData.map(d => d.fuel),
                        backgroundColor: 'rgba(59,130,246,0.7)',
                        borderColor: 'rgba(59,130,246,1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Service',
                        data: monthlyData.map(d => d.service),
                        backgroundColor: 'rgba(249,115,22,0.7)',
                        borderColor: 'rgba(249,115,22,1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'Rp ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // ========== CHART EFISIENSI ==========
        const efficiencyContainer = document.getElementById('efficiencyChart').parentElement;
        if (efficiencyData.length > 0) {
            const ctxEff = document.getElementById('efficiencyChart').getContext('2d');
            new Chart(ctxEff, {
                type: 'bar',
                data: {
                    labels: efficiencyData.map(d => d.vehicle),
                    datasets: [{
                        label: 'Rata-rata Efisiensi (km/liter / km/kWh)',
                        data: efficiencyData.map(d => d.avg_efficiency),
                        backgroundColor: 'rgba(16,185,129,0.7)',
                        borderColor: 'rgba(16,185,129,1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
        } else {
            efficiencyContainer.innerHTML = '<p class="text-center text-gray-400 text-sm mt-6">Belum ada data efisiensi</p>';
        }

        // ========== CHART DISTRIBUSI TRANSPORTASI ==========
        const transportContainer = document.getElementById('transportChart').parentElement;
        const labels = transportData.map(d => d.transport_type ? d.transport_type.replace('_', ' ').toUpperCase() : 'Tidak Diketahui');
        const values = transportData.map(d => d.total);
        const colors = ['#3b82f6', '#22c55e', '#f59e0b', '#8b5cf6', '#ec4899'];

        if (values.length > 0) {
            const ctxTrans = document.getElementById('transportChart').getContext('2d');
            new Chart(ctxTrans, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors.slice(0, values.length)
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                padding: 10
                            }
                        }
                    }
                }
            });
        } else {
            transportContainer.innerHTML = '<p class="text-center text-gray-400 text-sm mt-6">Belum ada data distribusi untuk periode ini</p>';
        }
    });
</script>
@endsection