@extends('layouts.app_work_sidebar')

@section('title', 'Statistik Laporan Pekerjaan')
@section('breadcrumb', 'Statistik')

@section('content')
<div class="container mx-auto px-4 py-4 h-full flex flex-col">

    <!-- FILTER -->
    <div class="bg-white rounded-3xl shadow-xl border p-4 md:p-6 mb-4 flex-shrink-0">
        <form method="GET" action="{{ route('work-reports.chart') }}" class="flex flex-wrap items-end gap-3">
            <div class="flex-1 min-w-[130px]">
                <label class="block text-sm font-bold text-gray-700 mb-1">Tampilan</label>
                <select name="view" id="viewSelect" class="w-full rounded-2xl border-0 bg-gray-100 py-2 px-4 focus:ring-2 focus:ring-green-400">
                    <option value="month" {{ ($view ?? 'month') == 'month' ? 'selected' : '' }}>Per Bulan</option>
                    <option value="year" {{ ($view ?? 'month') == 'year' ? 'selected' : '' }}>Per Tahun</option>
                </select>
            </div>
            <div class="flex-1 min-w-[150px]" id="monthField" style="{{ ($view ?? 'month') == 'year' ? 'display:none' : '' }}">
                <label class="block text-sm font-bold text-gray-700 mb-1">Bulan</label>
                <select name="month" class="w-full rounded-2xl border-0 bg-gray-100 py-2 px-4 focus:ring-2 focus:ring-blue-400">
                    @foreach($months as $key => $label)
                        <option value="{{ $key }}" {{ $month == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[130px]" id="yearField" style="{{ ($view ?? 'month') == 'year' ? '' : 'display:none' }}">
                <label class="block text-sm font-bold text-gray-700 mb-1">Tahun</label>
                <select name="year" class="w-full rounded-2xl border-0 bg-gray-100 py-2 px-4 focus:ring-2 focus:ring-blue-400">
                    @foreach($years ?? [] as $y)
                        <option value="{{ $y }}" {{ ($year ?? null) == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-sm font-bold text-gray-700 mb-1">Kategori</label>
                <select name="category_id" class="w-full rounded-2xl border-0 bg-gray-100 py-2 px-4 focus:ring-2 focus:ring-pink-400">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $categoryId == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2 rounded-2xl shadow transition">Tampilkan</button>
                <a href="{{ route('work-reports.chart', ($view ?? 'month') == 'year' ? ['view' => 'year', 'year' => $year] : ['month' => $month]) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-6 py-2 rounded-2xl transition">Reset</a>
            </div>
        </form>
        <script>
            // Toggle antara filter Bulan / Tahun tanpa reload
            document.getElementById('viewSelect').addEventListener('change', function () {
                const isYear = this.value === 'year';
                document.getElementById('monthField').style.display = isYear ? 'none' : '';
                document.getElementById('yearField').style.display = isYear ? '' : 'none';
            });
        </script>
    </div>

    <!-- CHART CARD - FLEXIBLE GROW -->
    <div class="bg-white rounded-3xl shadow-xl border p-4 md:p-6 flex-1 flex flex-col min-h-0">
        <div class="text-center mb-2 flex-shrink-0">
            <h2 class="text-2xl md:text-3xl font-black text-gray-800">Jumlah Laporan per Kategori</h2>
        </div>

        @if(count($chartLabels) > 0 && array_sum($chartData) > 0)
            <!-- LEGEND YANG BISA DIKLIK: sembunyikan/tampilkan kategori tertentu -->
            <div id="categoryLegend" class="flex flex-wrap justify-center gap-2 mb-3 flex-shrink-0"></div>

            <div class="bg-gray-50/80 rounded-2xl p-2 md:p-4 flex-1 min-h-0">
                <!-- Container grafik mengisi sisa ruang -->
                <div class="relative w-full h-full" style="min-height: 400px;">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        @else
            <div class="flex-1 flex items-center justify-center">
                <div class="text-center">
                    <div class="text-6xl mb-4">📉</div>
                    <h3 class="text-2xl font-bold text-gray-600">Belum ada data</h3>
                    <p class="text-gray-400">Coba ubah filter atau tambahkan laporan.</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<!-- Chart.js & Plugin Datalabels -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('categoryChart');
    if (!ctx) return;

    // Data ASLI (lengkap) - jangan diubah, dipakai sebagai sumber master
    const allLabels = @json($chartLabels);
    const allData = @json($chartData);
    const allColors = [
        '#3B82F6', '#8B5CF6', '#EC4899', '#F59E0B',
        '#10B981', '#EF4444', '#06B6D4', '#6366F1',
        '#84CC16', '#F97316'
    ];
    // pastikan tiap kategori dapat warna tetap meskipun kategori > 10
    const colors = allLabels.map((_, i) => allColors[i % allColors.length]);

    // index kategori yang sedang disembunyikan
    const hidden = new Set();

    function getVisibleDataset() {
        const labels = [];
        const data = [];
        const bg = [];
        allLabels.forEach((label, i) => {
            if (!hidden.has(i)) {
                labels.push(label);
                data.push(allData[i]);
                bg.push(colors[i]);
            }
        });
        return { labels, data, bg };
    }

    const initial = getVisibleDataset();

    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: initial.labels,
            datasets: [{
                label: 'Jumlah Laporan',
                data: initial.data,
                backgroundColor: initial.bg,
                borderRadius: 8,
                borderSkipped: false,
                barPercentage: 0.6,
                categoryPercentage: 0.7
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    top: 50,
                    bottom: 20,
                    left: 20,
                    right: 20
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: { enabled: false },
                datalabels: {
                    color: '#1F2937',
                    anchor: 'end',
                    align: 'end',
                    offset: 4,
                    font: {
                        weight: 'bold',
                        size: 14
                    },
                    formatter: function(value, ctx) {
                        // Persentase dihitung dari total bar yang sedang tampil saja
                        const visibleTotal = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                        let persen = visibleTotal > 0 ? ((value / visibleTotal) * 100).toFixed(1) : 0;
                        return value + ' (' + persen + '%)';
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        color: '#374151',
                        font: { size: 12, weight: 'bold' },
                        padding: 8
                    }
                },
                y: {
                    beginAtZero: true,
                    grace: '35%',
                    grid: {
                        color: 'rgba(0,0,0,0.06)',
                        drawBorder: false
                    },
                    ticks: {
                        stepSize: 1,
                        color: '#6B7280',
                        font: { size: 11 }
                    }
                }
            }
        },
        plugins: [ChartDataLabels]
    });

    // ===== LEGEND YANG BISA DIKLIK =====
    // Klik salah satu chip untuk menghapus/menampilkan kembali kategori itu
    // dari grafik, supaya bar yang tersisa otomatis merapat dan label
    // kategori yang disembunyikan ikut hilang.
    const legendEl = document.getElementById('categoryLegend');
    if (legendEl) {
        allLabels.forEach((label, i) => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.dataset.index = i;
            chip.title = label; // nama muncul saat kursor diarahkan (hover)

            const dot = document.createElement('span');
            dot.className = 'w-5 h-5 rounded-full flex-shrink-0';
            dot.style.backgroundColor = colors[i];

            chip.appendChild(dot);
            legendEl.appendChild(chip);

            function refreshChipStyle() {
                const visible = !hidden.has(i);
                chip.className = 'flex items-center justify-center w-8 h-8 rounded-full border-2 transition select-none ' +
                    (visible
                        ? 'border-white shadow'
                        : 'border-gray-200 opacity-30');
            }

            chip.addEventListener('click', () => {
                if (hidden.has(i)) {
                    hidden.delete(i);
                } else {
                    hidden.add(i);
                }

                const updated = getVisibleDataset();
                chart.data.labels = updated.labels;
                chart.data.datasets[0].data = updated.data;
                chart.data.datasets[0].backgroundColor = updated.bg;
                chart.update();

                refreshChipStyle();
            });

            refreshChipStyle();
        });
    }
});
</script>
@endpush