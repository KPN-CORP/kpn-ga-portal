@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto px-4">
    <div class="flex flex-wrap justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">📊 Dashboard Operasional</h1>
        <div class="flex gap-2">
            <a href="{{ route('drms.admin.operational.export', ['month' => $month, 'year' => $year]) }}" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </a>
            <a href="{{ route('drms.admin.monitoring.logs') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                📋 Log Driver
            </a>
        </div>
    </div>

    <!-- Filter -->
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <form method="GET" action="{{ route('drms.admin.operational.dashboard') }}" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-600">Bulan</label>
                <select name="month" class="border rounded-lg px-3 py-2 text-sm">
                    @foreach($months as $key => $label)
                        <option value="{{ $key }}" {{ $month == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600">Tahun</label>
                <select name="year" class="border rounded-lg px-3 py-2 text-sm">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold">
                Tampilkan
            </button>
            <a href="{{ route('drms.admin.operational.dashboard') }}" class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg text-sm font-semibold">
                Reset
            </a>
        </form>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm border">
            <p class="text-xs text-gray-500 uppercase">Total Biaya</p>
            <p class="text-xl font-bold text-blue-600">Rp {{ number_format($stats['total_operational_cost'],0,',','.') }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border">
            <p class="text-xs text-gray-500 uppercase">BBM/Charge</p>
            <p class="text-xl font-bold text-yellow-600">Rp {{ number_format($stats['total_fuel_cost'],0,',','.') }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border">
            <p class="text-xs text-gray-500 uppercase">Service</p>
            <p class="text-xl font-bold text-orange-600">Rp {{ number_format($stats['total_service_cost'],0,',','.') }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border">
            <p class="text-xs text-gray-500 uppercase">Menunggu Verifikasi</p>
            <p class="text-xl font-bold {{ $stats['pending_verification'] > 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ $stats['pending_verification'] }}
            </p>
        </div>
    </div>

    <!-- Grafik: Biaya per Bulan -->
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <h3 class="font-semibold text-gray-700 mb-3">📈 Biaya Operasional per Bulan</h3>
        <canvas id="monthlyChart" height="80"></canvas>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Grafik: Efisiensi -->
        <div class="bg-white p-4 rounded-lg shadow-sm border">
            <h3 class="font-semibold text-gray-700 mb-3">⛽ Efisiensi Kendaraan</h3>
            <canvas id="efficiencyChart" height="120"></canvas>
        </div>

        <!-- Grafik: Distribusi Transport -->
        <div class="bg-white p-4 rounded-lg shadow-sm border">
            <h3 class="font-semibold text-gray-700 mb-3">🚗 Distribusi Transportasi</h3>
            <canvas id="transportChart" height="120"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const monthlyData = @json($chartData);
    const efficiencyData = @json($efficiencyData);
    const transportData = @json($transportDistribution);

    // Chart bulanan
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: monthlyData.map(d => d.month),
            datasets: [
                { label: 'BBM/Charge', data: monthlyData.map(d => d.fuel), backgroundColor: 'rgba(59,130,246,0.6)' },
                { label: 'Service', data: monthlyData.map(d => d.service), backgroundColor: 'rgba(249,115,22,0.6)' },
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Chart efisiensi
    if (efficiencyData.length > 0) {
        new Chart(document.getElementById('efficiencyChart'), {
            type: 'bar',
            data: {
                labels: efficiencyData.map(d => d.vehicle),
                datasets: [{
                    label: 'Rata-rata Efisiensi (km/liter / km/kWh)',
                    data: efficiencyData.map(d => d.avg_efficiency),
                    backgroundColor: 'rgba(16,185,129,0.6)',
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // Chart distribusi
    const labels = transportData.map(d => d.transport_type ? d.transport_type.replace('_', ' ').toUpperCase() : 'Tidak Diketahui');
    const values = transportData.map(d => d.total);
    const colors = ['#3b82f6', '#22c55e', '#f59e0b', '#8b5cf6'];

    if (values.length > 0) {
        new Chart(document.getElementById('transportChart'), {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{ data: values, backgroundColor: colors.slice(0, values.length) }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
</script>
@endsection