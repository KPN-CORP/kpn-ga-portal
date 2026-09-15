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
            <a href="{{ route('drms.admin.operational.export', array_merge(request()->query(), ['month' => $month, 'year' => $year])) }}" 
               class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Export CSV
            </a>
            <a href="{{ route('drms.admin.monitoring.logs') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                📋 Log Driver
            </a>
        </div>
    </div>

    {{-- FILTER --}}
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
            <div>
                <label class="block text-xs font-medium text-gray-600">🚗 Kendaraan</label>
                <select name="vehicle_id" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Kendaraan</option>
                    @foreach($vehicles as $v)
                        <option value="{{ $v->id }}" {{ $filterVehicleId == $v->id ? 'selected' : '' }}>
                            {{ $v->plate_number }} - {{ $v->type }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600">👤 Driver</label>
                <select name="driver_id" class="border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Driver</option>
                    @foreach($drivers as $d)
                        <option value="{{ $d->id }}" {{ $filterDriverId == $d->id ? 'selected' : '' }}>
                            {{ $d->name }}
                        </option>
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
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase">Total Biaya</p>
            <p class="text-xl font-bold text-blue-600">Rp {{ number_format($stats['total_operational_cost'] ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-yellow-500">
            <p class="text-xs text-gray-500 uppercase">BBM/Charge</p>
            <p class="text-xl font-bold text-yellow-600">Rp {{ number_format($stats['total_fuel_cost'] ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-orange-500">
            <p class="text-xs text-gray-500 uppercase">Service Rutin</p>
            <p class="text-xl font-bold text-orange-600">Rp {{ number_format($stats['total_service_cost'] ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-red-500">
            <p class="text-xs text-gray-500 uppercase">Perbaikan</p>
            <p class="text-xl font-bold text-red-600">Rp {{ number_format($stats['total_repair_cost'] ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 {{ ($stats['pending_verification'] ?? 0) > 0 ? 'border-red-500' : 'border-green-500' }}">
            <p class="text-xs text-gray-500 uppercase">Menunggu Verifikasi</p>
            <p class="text-xl font-bold {{ ($stats['pending_verification'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ $stats['pending_verification'] ?? 0 }}
            </p>
        </div>
    </div>

    {{-- STATISTIK TAMBAHAN --}}
    <div class="grid grid-cols-2 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm border">
            <p class="text-xs text-gray-500 uppercase">Total Perjalanan (Terverifikasi)</p>
            <p class="text-xl font-bold text-purple-600">{{ $stats['total_trips'] ?? 0 }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border">
            <p class="text-xs text-gray-500 uppercase">Total Jarak Tempuh</p>
            <p class="text-xl font-bold text-indigo-600">{{ number_format($stats['total_distance'] ?? 0, 0, ',', '.') }} km</p>
        </div>
    </div>

    {{-- GRAFIK BIAYA PER BULAN --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <h3 class="font-semibold text-gray-700 mb-3">📈 Biaya Operasional per Bulan (12 bulan terakhir)</h3>
        {{-- zoom:1.25 membatalkan efek "html { zoom:0.8 }" di layout khusus untuk kotak chart ini
             (0.8 * 1.25 = 1.0), supaya Chart.js membaca posisi mouse dengan benar.
             height dikecilkan jadi 200px (250*0.8) supaya ukuran tampilannya di layar tetap
             sama persis seperti sebelumnya, cuma perhitungan mouse-nya yang dibetulkan. --}}
        <div class="relative" style="height: 200px; zoom: 1.25;">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>

    {{-- GRAFIK PER KENDARAAN DENGAN TAB --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <h4 class="font-semibold text-gray-700 mb-2">📊 Grafik Biaya BBM/EV Charging Per Kendaraan</h4>
        
        @if(count($vehicleStats) > 0)
        <div class="flex border-b mb-3 flex-wrap">
            <button class="tab-btn active px-4 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600" data-tab="cost">💰 Biaya</button>
            <button class="tab-btn px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700" data-tab="distance">📏 Jarak</button>
        </div>

        <div class="relative" style="height: 200px; zoom: 1.25;">
            <canvas id="perVehicleChart"></canvas>
        </div>
        <p class="text-sm text-gray-500 mt-2 text-center" id="chartTotalLabel">Total: Rp {{ number_format($totals['total_operational_cost'] ?? 0, 0, ',', '.') }}</p>
        @else
        <p class="text-center text-gray-400 text-sm py-4">Tidak ada data untuk filter yang dipilih</p>
        @endif
    </div>

    {{-- TABEL RINGKASAN PER KENDARAAN --}}
    @if(count($vehicleStats) > 0)
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <h3 class="font-semibold text-gray-700 mb-3">📋 Rincian per Kendaraan (Bulan {{ date('F Y', mktime(0,0,0,$month,1,$year)) }})</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Kendaraan</th>
                        <th class="px-4 py-2 text-left">BBM/Charge (Rp)</th>
                        <th class="px-4 py-2 text-left">Service Rutin (Rp)</th>
                        <th class="px-4 py-2 text-left">Perbaikan (Rp)</th>
                        <th class="px-4 py-2 text-left">Total Biaya (Rp)</th>
                        <th class="px-4 py-2 text-left">Jarak (km)</th>
                        <th class="px-4 py-2 text-left">Liter/kWh</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vehicleStats as $v)
                    <tr class="border-t">
                        <td class="px-4 py-2 font-medium">{{ $v['plate_number'] }}</td>
                        <td class="px-4 py-2">{{ number_format($v['fuel_cost'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2">{{ number_format($v['service_cost'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2">{{ number_format($v['repair_cost'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-2">{{ number_format($v['total_cost'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2">{{ number_format($v['distance'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2">{{ number_format($v['fuel_liters'], 2, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="border-t font-semibold bg-gray-100">
                        <td class="px-4 py-2">TOTAL</td>
                        <td class="px-4 py-2">{{ number_format($totals['total_fuel_cost'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-2">{{ number_format($totals['total_service_cost'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-2">{{ number_format($totals['total_repair_cost'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-2">{{ number_format($totals['total_operational_cost'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-2">{{ number_format($totals['total_distance'] ?? 0, 0, ',', '.') }}</td>
                        <td class="px-4 py-2">{{ number_format($totals['total_fuel_liters'] ?? 0, 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- GRAFIK DISTRIBUSI TRANSPORTASI --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <h3 class="font-semibold text-gray-700 mb-3">🚗 Distribusi Transportasi ({{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }})</h3>
        <div class="relative" style="height: 250px; max-width: 400px; margin: 0 auto;">
            <canvas id="transportChart"></canvas>
        </div>
        @if($transportDistribution->isEmpty())
            <p class="text-center text-gray-400 text-sm mt-2">Belum ada data distribusi untuk periode ini</p>
        @endif
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
                        <td colspan="5" class="px-3 py-4 text-center text-gray-400">Belum ada log</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- CHART.JS & PLUGIN DATALABELS --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0/dist/chartjs-plugin-datalabels.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Daftarkan plugin datalabels secara global
    Chart.register(ChartDataLabels);

    const monthlyData = @json($chartData);
    const transportData = @json($transportDistribution);
    const vehicleStats = @json($vehicleStats);

    // ========== DRILL-DOWN: klik grafik batang -> halaman sumber data yang sesuai ==========
    // Base URL tiap halaman sumber data (tanpa query string, ditambahkan lewat JS sesuai
    // bar/segmen yang diklik supaya bulan & kendaraan yang relevan langsung ikut ter-filter).
    const drilldownBaseUrl = {
        fuel:    "{{ route('drms.fuel-logs.index') }}",
        service: "{{ route('drms.service-schedules.index') }}",
        repair:  "{{ route('drms.repairs.index') }}",
    };
    // Filter kendaraan yang sedang aktif di form filter dashboard (kalau ada), dibawa
    // serta ke halaman tujuan supaya konteksnya konsisten dengan yang sedang dilihat admin.
    const currentVehicleFilter = @json($filterVehicleId ?? null);
    // Bulan+tahun yang sedang aktif di dashboard, dalam format "YYYY-MM" (dipakai grafik
    // per-kendaraan, yang cuma menampilkan 1 bulan, beda dengan grafik bulanan yang 12 bulan).
    const currentPeriod = @json(sprintf('%04d-%02d', $year, $month));

    function goToDrilldown(baseUrl, params) {
        const url = new URL(baseUrl, window.location.origin);
        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                url.searchParams.set(key, value);
            }
        });
        window.location.href = url.toString();
    }

    // ========== CHART BIAYA PER BULAN ==========
    const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctxMonthly, {
        type: 'bar',
        data: {
            labels: monthlyData.map(d => d.month),
            datasets: [
                {
                    label: 'BBM/Charge',
                    data: monthlyData.map(d => Number(d.fuel)),
                    backgroundColor: 'rgba(59,130,246,0.7)',
                    borderColor: 'rgba(59,130,246,1)',
                    borderWidth: 1
                },
                {
                    label: 'Service Rutin',
                    data: monthlyData.map(d => Number(d.service)),
                    backgroundColor: 'rgba(249,115,22,0.7)',
                    borderColor: 'rgba(249,115,22,1)',
                    borderWidth: 1
                },
                {
                    label: 'Perbaikan',
                    data: monthlyData.map(d => Number(d.repair)),
                    backgroundColor: 'rgba(239,68,68,0.7)',
                    borderColor: 'rgba(239,68,68,1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { 
                legend: { position: 'top' },
                datalabels: { display: false } // matikan datalabels di grafik ini
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: function(value) { return 'Rp ' + value.toLocaleString(); } }
                }
            },
            // Klik salah satu batang (BBM/Service/Perbaikan) -> menuju halaman sumber
            // data yang sesuai, sudah difilter ke bulan batang yang diklik.
            onClick: (evt, elements) => {
                if (!elements.length) return;
                const el = elements[0];
                const datasetKeys = ['fuel', 'service', 'repair'];
                const key = datasetKeys[el.datasetIndex];
                if (!key) return;
                const monthValue = monthlyData[el.index]?.month; // format "YYYY-MM"
                goToDrilldown(drilldownBaseUrl[key], {
                    month: monthValue,
                    vehicle_id: currentVehicleFilter,
                });
            },
            onHover: (evt, elements) => {
                evt.native.target.style.cursor = elements.length ? 'pointer' : 'default';
            }
        }
    });

    // ========== CHART PER KENDARAAN ==========
    if (vehicleStats.length > 0) {
        let perVehicleChart = null;
        const chartDataMap = {
            cost: {
                label: 'Total Biaya (Rp)',
                data: vehicleStats.map(v => Number(v.total_cost)),
                color: 'rgba(59,130,246,0.7)',
                borderColor: 'rgba(59,130,246,1)',
                total: vehicleStats.reduce((a,b) => a + Number(b.total_cost), 0),
                format: (val) => 'Rp ' + val.toLocaleString()
            },
            distance: {
                label: 'Jarak (km)',
                data: vehicleStats.map(v => Number(v.distance)),
                color: 'rgba(16,185,129,0.7)',
                borderColor: 'rgba(16,185,129,1)',
                total: vehicleStats.reduce((a,b) => a + Number(b.distance), 0),
                format: (val) => val.toLocaleString() + ' km'
            }
        };

        function renderPerVehicleChart(tab) {
            const ctx = document.getElementById('perVehicleChart').getContext('2d');
            if (perVehicleChart) perVehicleChart.destroy();

            const labels = vehicleStats.map(v => v.plate_number);
            const data = chartDataMap[tab];
            const hoverColor = tab === 'distance' ? 'rgba(16,185,129,1)' : 'rgba(59,130,246,1)';

            perVehicleChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: data.label,
                        data: data.data,
                        backgroundColor: data.color,
                        borderColor: data.borderColor,
                        borderWidth: 1,
                        hoverBackgroundColor: hoverColor,
                        hoverBorderColor: data.borderColor,
                        hoverBorderWidth: 2,
                        maxBarThickness: 48
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    // hover/tooltip aktif begitu kursor masuk area kolom (x-axis),
                    // tidak perlu presisi kena bar-nya
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    plugins: { 
                        legend: { display: false },
                        datalabels: { display: false },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            displayColors: false,
                            callbacks: {
                                // judul tooltip = nomor plat kendaraan yang sedang di-hover
                                title: function(items) {
                                    return items.length ? items[0].label : '';
                                },
                                // isi tooltip = nilai yang sudah diformat sesuai tab aktif
                                label: function(item) {
                                    return data.format(item.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                autoSkip: false,
                                maxRotation: 45,
                                minRotation: 0
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    if (tab === 'distance') return value + ' km';
                                    return 'Rp ' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    // Klik batang kendaraan pada tab "Biaya" -> menuju Log Pengisian
                    // BBM/EV (drms.fuel-logs.index), difilter ke kendaraan + bulan yang
                    // sedang ditampilkan. Tab "Jarak" belum diarahkan ke halaman manapun
                    // karena datanya (jarak dari Log Perjalanan) belum punya halaman
                    // daftar dengan filter per-kendaraan.
                    onClick: (evt, elements) => {
                        if (tab !== 'cost' || !elements.length) return;
                        const idx = elements[0].index;
                        const vehicleId = vehicleStats[idx]?.id;
                        if (!vehicleId) return;
                        goToDrilldown(drilldownBaseUrl.fuel, {
                            vehicle_id: vehicleId,
                            month: currentPeriod,
                        });
                    },
                    onHover: (evt, elements) => {
                        evt.native.target.style.cursor = (tab === 'cost' && elements.length) ? 'pointer' : 'default';
                    }
                }
            });

            document.getElementById('chartTotalLabel').textContent = 'Total: ' + data.format(data.total);
        }

        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('active', 'text-blue-600', 'border-b-2', 'border-blue-600');
                    b.classList.add('text-gray-500');
                });
                this.classList.add('active', 'text-blue-600', 'border-b-2', 'border-blue-600');
                this.classList.remove('text-gray-500');
                renderPerVehicleChart(this.dataset.tab);
            });
        });

        renderPerVehicleChart('cost');

        // Fix: kalau canvas ini di-render sebelum lebar containernya "settle"
        // (misal sidebar/layout belum selesai reflow), Chart.js akan salah
        // memetakan posisi mouse ke bar (tooltip/hover nunjuk bar yang salah).
        // ResizeObserver ini memaksa chart resize ulang tiap kali ukuran
        // container-nya berubah, jadi pemetaan mouse-nya selalu sinkron
        // dengan yang benar-benar tampil di layar.
        const chartContainer = document.getElementById('perVehicleChart').parentElement;
        if (window.ResizeObserver) {
            const chartResizeObserver = new ResizeObserver(() => {
                if (perVehicleChart) perVehicleChart.resize();
            });
            chartResizeObserver.observe(chartContainer);
        }

        // Jaga-jaga tambahan: paksa resize sesaat setelah semua asset
        // (font, gambar, sidebar) selesai load, karena itu momen paling
        // sering bikin lebar container berubah setelah chart pertama digambar.
        window.addEventListener('load', function() {
            if (perVehicleChart) perVehicleChart.resize();
        });
    }

    // ========== CHART DISTRIBUSI TRANSPORTASI ==========
    const transportContainer = document.getElementById('transportChart').parentElement;
    const labels = transportData.map(d => d.transport_type ? d.transport_type.replace('_', ' ').toUpperCase() : 'Tidak Diketahui');
    const values = transportData.map(d => Number(d.total));
    const colors = ['#3b82f6', '#22c55e', '#f59e0b', '#8b5cf6', '#ec4899'];

    if (values.length > 0) {
        const total = values.reduce((a, b) => a + b, 0);
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
                        labels: { boxWidth: 12, padding: 10 } 
                    },
                    datalabels: {
                        color: '#fff',
                        font: {
                            weight: 'bold',
                            size: 13
                        },
                        formatter: function(value, context) {
                            const percentage = ((value / total) * 100).toFixed(1);
                            return value + '\n' + percentage + '%';
                        },
                        textAlign: 'center',
                        anchor: 'center',
                        align: 'center',
                        offset: 0
                    },
                    tooltip: {
                        enabled: false // nonaktifkan tooltip/popup
                    }
                }
            },
            plugins: [ChartDataLabels]
        });
    } else {
        transportContainer.innerHTML = '<p class="text-center text-gray-400 text-sm mt-6">Belum ada data distribusi untuk periode ini</p>';
    }
});
</script>
@endsection