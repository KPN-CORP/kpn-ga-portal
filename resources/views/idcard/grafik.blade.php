@extends('layouts.app-sidebar-card')
@section('content')
<div class="space-y-6 text-sm text-gray-800 font-sans">

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-xl font-semibold text-gray-800">Grafik Request ID Card</h2>
            <p class="text-xs text-gray-500">Ringkasan request per bisnis unit, kategori, dan bulan</p>
        </div>
        <form method="GET" action="{{ route('idcard.grafik') }}" class="flex items-center gap-2">
            <label for="tahun" class="text-sm font-medium text-gray-600">Tahun</label>
            <select name="tahun" id="tahun" onchange="this.form.submit()" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                <option value="all" {{ $tahun === 'all' ? 'selected' : '' }}>Semua tahun</option>
                @foreach($tahunList as $y)
                    <option value="{{ $y }}" {{ (string) $tahun === (string) $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
            <noscript><button class="px-3 py-2 bg-blue-600 text-white rounded-lg text-sm">Terapkan</button></noscript>
        </form>
    </div>

    {{-- Kartu ringkasan --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white border rounded-xl p-4">
            <p class="text-xs text-gray-500">Total Request</p>
            <p class="mt-1 text-2xl font-semibold text-gray-800">{{ number_format($summary['total']) }}</p>
        </div>
        <div class="bg-white border rounded-xl p-4">
            <p class="text-xs text-gray-500">Menunggu</p>
            <p class="mt-1 text-2xl font-semibold text-yellow-700">{{ number_format($summary['pending']) }}</p>
        </div>
        <div class="bg-white border rounded-xl p-4">
            <p class="text-xs text-gray-500">Disetujui</p>
            <p class="mt-1 text-2xl font-semibold text-green-700">{{ number_format($summary['approved']) }}</p>
        </div>
        <div class="bg-white border rounded-xl p-4">
            <p class="text-xs text-gray-500">Ditolak</p>
            <p class="mt-1 text-2xl font-semibold text-red-700">{{ number_format($summary['rejected']) }}</p>
        </div>
    </div>

    @if($summary['total'] === 0)
        <div class="bg-white border rounded-xl p-10 text-center text-gray-500">
            Belum ada data request{{ $tahun !== 'all' ? ' pada tahun ' . $tahun : '' }}.
        </div>
    @else
        <div class="bg-white border rounded-xl p-4">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Request per Bisnis Unit</h3>
            <div class="relative" style="height: 320px;"><canvas id="buChart"></canvas></div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white border rounded-xl p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Request per Kategori</h3>
                <div class="relative" style="height: 300px;"><canvas id="kategoriChart"></canvas></div>
            </div>
            <div class="bg-white border rounded-xl p-4">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">
                    Tren Bulanan {{ $tahun === 'all' ? '(12 bulan terakhir)' : $tahun }}
                </h3>
                <div class="relative" style="height: 300px;"><canvas id="trenChart"></canvas></div>
            </div>
        </div>
    @endif
</div>

@if($summary['total'] > 0)
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js"></script>
<script>
(function () {
    const common = { responsive: true, maintainAspectRatio: false };
    const intTicks = { beginAtZero: true, ticks: { precision: 0 } };

    // Layout memakai `html { zoom: 0.8 }`, sehingga posisi mouse yang dihitung Chart.js meleset
    // (hover/tooltip tidak pas). Plugin ini menghitung ulang posisi mouse relatif terhadap canvas
    // dengan skala ukuran tampil vs ukuran chart, jadi tetap benar dengan atau tanpa zoom.
    const zoomFix = {
        id: 'zoomFix',
        beforeEvent(chart, args) {
            const e = args.event, n = e.native;
            if (!n || e.type === 'mouseout') return;
            const src = (n.touches && n.touches[0]) || (n.changedTouches && n.changedTouches[0]) || n;
            if (src.clientX === undefined) return;
            const rect = chart.canvas.getBoundingClientRect();
            if (!rect.width || !rect.height) return;
            e.x = (src.clientX - rect.left) * (chart.width / rect.width);
            e.y = (src.clientY - rect.top) * (chart.height / rect.height);
            args.inChartArea = chart.isPointInArea(e);
        }
    };

    new Chart(document.getElementById('buChart'), {
        type: 'bar',
        plugins: [zoomFix],
        data: {
            labels: @json($chartLabels),
            datasets: [
                { label: 'Menunggu',  data: @json($pendingData),  backgroundColor: 'rgba(255,206,86,0.6)', borderColor: 'rgb(255,206,86)', borderWidth: 1 },
                { label: 'Disetujui', data: @json($approvedData), backgroundColor: 'rgba(75,192,192,0.6)', borderColor: 'rgb(75,192,192)', borderWidth: 1 },
                { label: 'Ditolak',   data: @json($rejectedData), backgroundColor: 'rgba(255,99,132,0.6)', borderColor: 'rgb(255,99,132)', borderWidth: 1 }
            ]
        },
        options: { ...common, plugins: { legend: { position: 'top' } }, scales: { y: intTicks } }
    });

    new Chart(document.getElementById('kategoriChart'), {
        type: 'bar',
        plugins: [zoomFix],
        data: {
            labels: @json($kategoriLabels),
            datasets: [{ label: 'Jumlah request', data: @json($kategoriData), backgroundColor: 'rgba(59,130,246,0.6)', borderColor: 'rgb(59,130,246)', borderWidth: 1 }]
        },
        options: { ...common, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: intTicks } }
    });

    new Chart(document.getElementById('trenChart'), {
        type: 'line',
        plugins: [zoomFix],
        data: {
            labels: @json($trendLabels),
            datasets: [{ label: 'Jumlah request', data: @json($trendData), borderColor: 'rgb(59,130,246)', backgroundColor: 'rgba(59,130,246,0.15)', fill: true, tension: 0.25 }]
        },
        options: { ...common, plugins: { legend: { display: false } }, scales: { y: intTicks } }
    });
})();
</script>
@endif
@endsection