@extends('layouts.app_car_sidebar')

@section('content')
<div class="container mx-auto max-w-full px-4 py-6">
    {{-- HEADER --}}
    <div class="flex flex-wrap justify-between items-center mb-6 gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-gray-800">📊 Dashboard Operasional</h1>
            <p class="text-sm text-gray-500">
                Ringkasan biaya, efisiensi, dan aktivitas kendaraan
                @if($isSuperAdmin ?? false)
                    <span class="ml-2 px-2 py-0.5 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold">🔓 Superadmin - Semua BU</span>
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                <a href="{{ route('drms.admin.operational.export', array_merge(request()->query(), ['period' => $period, 'month' => $month, 'year' => $year])) }}" 
                   class="flex-1 sm:flex-none justify-center bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export CSV
                </a>
            <a href="{{ route('drms.admin.monitoring.logs') }}" class="flex-1 sm:flex-none justify-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                📋 Log Driver
            </a>
        </div>
    </div>

    {{-- FILTER: urutan Business Unit → Kendaraan → Driver → Bulan → Tahun --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <form method="GET" id="dashboardFilterForm" action="{{ route('drms.admin.operational.dashboard') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:flex lg:flex-wrap gap-3 lg:items-end">
            <div class="w-full lg:w-auto">
                <label class="block text-xs font-medium text-gray-600">🏢 Business Unit</label>
                @if($isSuperAdmin ?? false)
                    <select name="business_unit_id" id="filter_bu" class="w-full lg:w-auto border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua BU</option>
                        @foreach($businessUnits as $bu)
                            <option value="{{ $bu->id_bisnis_unit }}" {{ (string) $filterBusinessUnitId === (string) $bu->id_bisnis_unit ? 'selected' : '' }}>
                                {{ $bu->nama_bisnis_unit }}
                            </option>
                        @endforeach
                    </select>
                @else
                    {{-- Admin biasa: terkunci ke BU sendiri (server mengabaikan input lain) --}}
                    <select id="filter_bu" class="w-full lg:w-auto border rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-600" disabled>
                        @foreach($businessUnits as $bu)
                            <option selected>{{ $bu->nama_bisnis_unit }}</option>
                        @endforeach
                    </select>
                @endif
            </div>
            <div class="w-full lg:w-auto">
                <label class="block text-xs font-medium text-gray-600">🚗 Kendaraan</label>
                <select name="vehicle_id" id="filter_vehicle" class="w-full lg:w-auto border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Kendaraan</option>
                    @foreach($vehicles as $v)
                        <option value="{{ $v->id }}" data-bu="{{ $v->business_unit_id }}" {{ $filterVehicleId == $v->id ? 'selected' : '' }}>
                            {{ $v->plate_number }} - {{ $v->type }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-full lg:w-auto">
                <label class="block text-xs font-medium text-gray-600">👤 Driver</label>
                <select name="driver_id" id="filter_driver" class="w-full lg:w-auto border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Driver</option>
                    @foreach($drivers as $d)
                        <option value="{{ $d->id }}" data-bu="{{ $d->business_unit_id }}" {{ $filterDriverId == $d->id ? 'selected' : '' }}>
                            {{ $d->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="w-full lg:w-auto">
                <label class="block text-xs font-medium text-gray-600">Periode</label>
                <select name="period" id="filter_period" class="w-full lg:w-auto border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="all" {{ ($period ?? 'year') === 'all' ? 'selected' : '' }}>Semua</option>
                    <option value="year" {{ ($period ?? 'year') === 'year' ? 'selected' : '' }}>Tahun</option>
                    <option value="month" {{ ($period ?? 'year') === 'month' ? 'selected' : '' }}>Bulan</option>
                </select>
            </div>
            <div id="wrap_month" class="w-full lg:w-auto">
                <label class="block text-xs font-medium text-gray-600">Bulan</label>
                <select name="month" class="w-full lg:w-auto border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    @foreach($months as $key => $label)
                        <option value="{{ $key }}" {{ ($month ?? now()->month) == $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div id="wrap_year" class="w-full lg:w-auto">
                <label class="block text-xs font-medium text-gray-600">Tahun</label>
                <select name="year" class="w-full lg:w-auto border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                <button type="submit" class="flex-1 lg:flex-none bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    Tampilkan
                </button>
                <a href="{{ route('drms.admin.operational.dashboard') }}" class="flex-1 lg:flex-none text-center bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg text-sm font-semibold transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Filter periode: Semua / Tahun / Bulan -> tampilkan hanya pilihan yang relevan --}}
    <script>
    (function () {
        var p = document.getElementById('filter_period');
        var wm = document.getElementById('wrap_month');
        var wy = document.getElementById('wrap_year');
        if (!p || !wm || !wy) return;
        function sync() {
            var v = p.value;
            wm.style.display = (v === 'month') ? '' : 'none';
            wy.style.display = (v === 'all') ? 'none' : '';
            wm.querySelector('select').disabled = (v !== 'month');
            wy.querySelector('select').disabled = (v === 'all');
        }
        p.addEventListener('change', sync);
        sync();
    })();
    </script>

    {{-- Cascade filter: pilihan Kendaraan & Driver menyesuaikan Business Unit yang dipilih (superadmin) --}}
    <script>
    (function () {
        var bu = document.getElementById('filter_bu');
        var vehicle = document.getElementById('filter_vehicle');
        var driver = document.getElementById('filter_driver');
        if (!bu || bu.disabled) return;

        function applyBu(select) {
            var selectedBu = bu.value;
            Array.prototype.forEach.call(select.options, function (opt) {
                if (!opt.value) return; // opsi "Semua ..."
                var match = !selectedBu || String(opt.dataset.bu) === String(selectedBu);
                opt.hidden = !match;
                opt.disabled = !match;
                if (!match && opt.selected) select.value = '';
            });
        }
        function applyAll() { applyBu(vehicle); applyBu(driver); }

        // Sebaliknya: kalau user pilih Kendaraan atau Driver duluan,
        // Business Unit ikut ter-set otomatis sesuai BU kendaraan/driver tsb.
        function syncBuFrom(select) {
            var opt = select.options[select.selectedIndex];
            if (!opt || !opt.value) return; // opsi "Semua ..." -> tidak mengubah BU
            var optBu = opt.dataset.bu;
            if (optBu && bu.value !== optBu) {
                bu.value = optBu;
            }
        }

        bu.addEventListener('change', applyAll);
        vehicle.addEventListener('change', function () {
            syncBuFrom(vehicle);
            applyAll();
        });
        driver.addEventListener('change', function () {
            syncBuFrom(driver);
            applyAll();
        });

        applyAll();
    })();
    </script>

    {{-- STATISTIK UTAMA --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 md:gap-4 mb-6">
        <div class="bg-white p-3 md:p-4 rounded-lg shadow-sm border-l-4 border-blue-500 col-span-2 md:col-span-1">
            <p class="text-xs text-gray-500 uppercase">Total Biaya</p>
            <p class="text-lg md:text-xl font-bold break-words text-blue-600">Rp {{ number_format($stats['total_operational_cost'] ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white p-3 md:p-4 rounded-lg shadow-sm border-l-4 border-yellow-500">
            <p class="text-xs text-gray-500 uppercase">BBM/Charge</p>
            <p class="text-lg md:text-xl font-bold break-words text-yellow-600">Rp {{ number_format($stats['total_fuel_cost'] ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white p-3 md:p-4 rounded-lg shadow-sm border-l-4 border-orange-500">
            <p class="text-xs text-gray-500 uppercase">Service Rutin</p>
            <p class="text-lg md:text-xl font-bold break-words text-orange-600">Rp {{ number_format($stats['total_service_cost'] ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white p-3 md:p-4 rounded-lg shadow-sm border-l-4 border-red-500">
            <p class="text-xs text-gray-500 uppercase">Perbaikan</p>
            <p class="text-lg md:text-xl font-bold break-words text-red-600">Rp {{ number_format($stats['total_repair_cost'] ?? 0, 0, ',', '.') }}</p>
        </div>
        <div class="bg-white p-3 md:p-4 rounded-lg shadow-sm border-l-4 {{ ($stats['pending_verification'] ?? 0) > 0 ? 'border-red-500' : 'border-green-500' }}">
            <p class="text-xs text-gray-500 uppercase">Menunggu Verifikasi</p>
            <p class="text-lg md:text-xl font-bold break-words {{ ($stats['pending_verification'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600' }}">
                {{ $stats['pending_verification'] ?? 0 }}
            </p>
        </div>
    </div>

    {{-- STATISTIK TAMBAHAN --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:gap-4 mb-6">
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
        <h3 class="font-semibold text-gray-700 mb-3">📈 Biaya Operasional {{ ($period ?? 'year') === 'all' ? 'per Tahun (Semua Periode)' : (($period ?? 'year') === 'year' ? 'per Bulan (Tahun ' . $year . ')' : 'per Hari (' . $periodLabel . ')') }}</h3>
        {{-- zoom:1.25 membatalkan efek "html { zoom:0.8 }" di layout khusus untuk kotak chart ini
             (0.8 * 1.25 = 1.0), supaya Chart.js membaca posisi mouse dengan benar.
             height dikecilkan jadi 200px (250*0.8) supaya ukuran tampilannya di layar tetap
             sama persis seperti sebelumnya, cuma perhitungan mouse-nya yang dibetulkan. --}}
        <div class="relative" style="height: 200px; zoom: 1.25;">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>

    {{-- GRAFIK PER KENDARAAN: BBM/EV, Service Rutin, Perbaikan (dipisah) --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <h4 class="font-semibold text-gray-700 mb-2">⛽ Grafik Biaya BBM/EV Charging Per Kendaraan ({{ $periodLabel }})</h4>
        <p class="text-xs text-gray-400 mb-2">Hanya biaya pengisian BBM/EV Charging yang sudah terverifikasi. Service Rutin dan Perbaikan ada di grafik terpisah di bawah.</p>

        @if(count($vehicleStats) > 0)
        <div class="flex border-b mb-3 flex-wrap">
            <button class="tab-btn active px-4 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600" data-tab="cost">💰 Biaya</button>
            <button class="tab-btn px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700" data-tab="distance">📏 Jarak</button>
        </div>

        <div class="relative" style="height: 200px; zoom: 1.25;">
            <canvas id="perVehicleChart"></canvas>
        </div>
        <p class="text-sm text-gray-500 mt-2 text-center" id="chartTotalLabel"></p>
        @else
        <p class="text-center text-gray-400 text-sm py-4">Tidak ada data untuk filter yang dipilih</p>
        @endif
    </div>

    @if(count($vehicleStats) > 0)
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- SERVICE RUTIN --}}
        <div class="bg-white p-4 rounded-lg shadow-sm border">
            <h4 class="font-semibold text-gray-700 mb-2">🔧 Grafik Biaya Service Rutin Per Kendaraan ({{ $periodLabel }})</h4>
            <div class="relative" style="height: 200px; zoom: 1.25;">
                <canvas id="serviceVehicleChart"></canvas>
            </div>
            <p class="text-sm text-gray-500 mt-2 text-center" id="serviceTotalLabel"></p>
        </div>

        {{-- PERBAIKAN --}}
        <div class="bg-white p-4 rounded-lg shadow-sm border">
            <h4 class="font-semibold text-gray-700 mb-2">🛠️ Grafik Biaya Perbaikan Per Kendaraan ({{ $periodLabel }})</h4>
            <div class="relative" style="height: 200px; zoom: 1.25;">
                <canvas id="repairVehicleChart"></canvas>
            </div>
            <p class="text-sm text-gray-500 mt-2 text-center" id="repairTotalLabel"></p>
        </div>
    </div>
    @endif

    {{-- TABEL RINGKASAN PER KENDARAAN --}}
    @if(count($vehicleStats) > 0)
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <h3 class="font-semibold text-gray-700 mb-3">📋 Rincian per Kendaraan ({{ $periodLabel }})</h3>
        <p class="text-xs text-gray-400 mb-2">Kolom Jarak &amp; Liter/kWh dihitung dari Log Pengisian BBM/EV Charging (selisih odometer antar pengisian), sama seperti di <a href="{{ route('drms.fuel-logs.analytics') }}" class="underline">Analisis Konsumsi</a>.</p>
        <div class="hidden md:block overflow-x-auto">
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

        {{-- HP: kartu per kendaraan --}}
        <div class="md:hidden space-y-3">
            @foreach($vehicleStats as $v)
            <div class="border rounded-lg p-3 text-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-semibold">{{ $v['plate_number'] }}</span>
                    <span class="font-bold text-blue-600">Rp {{ number_format($v['total_cost'], 0, ',', '.') }}</span>
                </div>
                <dl class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-xs text-gray-600">
                    <dt>BBM/Charge</dt><dd class="text-right">{{ number_format($v['fuel_cost'], 0, ',', '.') }}</dd>
                    <dt>Service Rutin</dt><dd class="text-right">{{ number_format($v['service_cost'], 0, ',', '.') }}</dd>
                    <dt>Perbaikan</dt><dd class="text-right">{{ number_format($v['repair_cost'] ?? 0, 0, ',', '.') }}</dd>
                    <dt>Jarak</dt><dd class="text-right">{{ number_format($v['distance'], 0, ',', '.') }} km</dd>
                    <dt>Liter/kWh</dt><dd class="text-right">{{ number_format($v['fuel_liters'], 2, ',', '.') }}</dd>
                </dl>
            </div>
            @endforeach
            <div class="border-2 border-gray-300 bg-gray-100 rounded-lg p-3 text-sm">
                <div class="flex items-center justify-between gap-2">
                    <span class="font-semibold">TOTAL</span>
                    <span class="font-bold text-blue-600">Rp {{ number_format($totals['total_operational_cost'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <dl class="mt-2 grid grid-cols-2 gap-x-3 gap-y-1 text-xs text-gray-600">
                    <dt>BBM/Charge</dt><dd class="text-right">{{ number_format($totals['total_fuel_cost'] ?? 0, 0, ',', '.') }}</dd>
                    <dt>Service Rutin</dt><dd class="text-right">{{ number_format($totals['total_service_cost'] ?? 0, 0, ',', '.') }}</dd>
                    <dt>Perbaikan</dt><dd class="text-right">{{ number_format($totals['total_repair_cost'] ?? 0, 0, ',', '.') }}</dd>
                    <dt>Jarak</dt><dd class="text-right">{{ number_format($totals['total_distance'] ?? 0, 0, ',', '.') }} km</dd>
                    <dt>Liter/kWh</dt><dd class="text-right">{{ number_format($totals['total_fuel_liters'] ?? 0, 2, ',', '.') }}</dd>
                </dl>
            </div>
        </div>
    </div>
    @endif

    {{-- GRAFIK DISTRIBUSI TRANSPORTASI --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <h3 class="font-semibold text-gray-700 mb-3">🚗 Distribusi Transportasi ({{ $periodLabel }})</h3>
        <div class="relative" style="height: 250px; max-width: 400px; margin: 0 auto;">
            <canvas id="transportChart"></canvas>
        </div>
        @if($transportDistribution->isEmpty())
            <p class="text-center text-gray-400 text-sm mt-2">Belum ada data distribusi untuk periode ini</p>
        @endif
    </div>

    {{-- TABEL LOG TERBARU --}}
    <div class="bg-white p-4 rounded-lg shadow-sm border">
        <div class="flex flex-wrap justify-between items-center gap-2 mb-3">
            <h3 class="font-semibold text-gray-700">📋 Log Terbaru <span class="text-xs font-normal text-gray-400">(5 log terakhir, tidak terpengaruh filter periode)</span></h3>
            <a href="{{ route('drms.admin.monitoring.logs') }}" class="text-blue-600 hover:underline text-sm">Lihat semua →</a>
        </div>
        <div class="hidden md:block overflow-x-auto">
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

        {{-- HP: kartu per log --}}
        <div class="md:hidden divide-y">
            @forelse($recentLogs ?? [] as $log)
            <div class="py-3 space-y-1 text-sm break-words min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <span class="font-medium">{{ $log->request->request_no ?? '-' }}</span>
                    @if($log->is_verified)
                        <span class="shrink-0 px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs">✅ Diverifikasi</span>
                    @elseif($log->is_submitted)
                        <span class="shrink-0 px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs">⏳ Pending</span>
                    @elseif($log->needsRevision())
                        <span class="shrink-0 px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs">🔄 Revisi</span>
                    @else
                        <span class="shrink-0 px-2 py-0.5 bg-gray-100 text-gray-700 rounded-full text-xs">📝 Draft</span>
                    @endif
                </div>
                <div class="text-xs text-gray-600">👤 {{ $log->request->driver->name ?? '-' }}</div>
                <div class="flex items-center justify-between text-xs text-gray-600">
                    <span>📏 {{ $log->distance ?? '-' }} km</span>
                    <span>{{ $log->verified_at ? $log->verified_at->format('d M Y') : '-' }}</span>
                </div>
            </div>
            @empty
            <p class="py-4 text-center text-gray-400 text-sm">Belum ada log</p>
            @endforelse
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
    // Format angka Indonesia: desimal hanya tampil jika memang ada (maks 2 digit)
    const fmtNum = (v) => Number(v).toLocaleString('id-ID', { maximumFractionDigits: 2 });
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
    const currentPeriod = @json(($period ?? 'year') === 'all' ? 'all' : (($period ?? 'year') === 'year' ? (string) $year : sprintf('%04d-%02d', $year, $month)));

    // Business Unit yang sedang dipilih (superadmin); ikut dibawa ke halaman tujuan.
    const currentBuFilter = @json($filterBusinessUnitId ?? null);

    function goToDrilldown(baseUrl, params) {
        if (currentBuFilter && !params.business_unit_id) {
            params = Object.assign({ business_unit_id: currentBuFilter }, params);
        }
        const url = new URL(baseUrl, window.location.origin);
        Object.entries(params).forEach(([key, value]) => {
            if (value !== null && value !== undefined && value !== '') {
                url.searchParams.set(key, value);
            }
        });
        window.location.href = url.toString();
    }

    // ========== CHART BIAYA PER BULAN ==========
    // Label sumbu X: tanggal (mode Bulan) cukup angka harinya; bulan/tahun apa adanya.
    const monthlyLabel = (m) => (m && m.length === 10) ? String(parseInt(m.slice(8), 10)) : m;
    const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
    new Chart(ctxMonthly, {
        type: 'bar',
        data: {
            labels: monthlyData.map(d => monthlyLabel(d.month)),
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
                datalabels: { display: false }, // matikan datalabels di grafik ini
                tooltip: { callbacks: { title: function(items) { return items.length ? monthlyData[items[0].dataIndex].month : ''; } } }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { callback: function(value) { return 'Rp ' + fmtNum(value); } }
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
                const rawLabel = monthlyData[el.index]?.month; // 'YYYY' | 'YYYY-MM' | 'YYYY-MM-DD'
                const monthValue = (rawLabel && rawLabel.length === 10) ? rawLabel.slice(0, 7) : rawLabel;
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

    // ========== CHART PER KENDARAAN (BBM/EV, Service Rutin, Perbaikan) ==========
    // Tiga grafik terpisah, masing-masing hanya membaca satu jenis biaya dari vehicleStats:
    //   BBM/EV  -> fuel_cost   (tab Jarak -> distance)
    //   Service -> service_cost
    //   Perbaikan -> repair_cost
    if (vehicleStats.length > 0) {
        const vehicleCharts = {};

        const fmtRp = (val) => 'Rp ' + fmtNum(val);
        const fmtKm = (val) => fmtNum(val) + ' km';

        // hideZero: untuk Service/Perbaikan, kendaraan tanpa biaya tidak ditampilkan
        function rowsFor(key, hideZero) {
            return vehicleStats
                .map(v => ({ id: v.id, plate: v.plate_number, value: Number(v[key]) || 0 }))
                .filter(r => !hideZero || r.value > 0)
                .sort((a, b) => b.value - a.value);
        }

        function sumRows(rows) {
            return rows.reduce((acc, r) => acc + r.value, 0);
        }

        function renderVehicleChart(canvasId, rows, o) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            if (vehicleCharts[canvasId]) vehicleCharts[canvasId].destroy();

            vehicleCharts[canvasId] = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: rows.map(r => r.plate),
                    datasets: [{
                        label: o.label,
                        data: rows.map(r => r.value),
                        backgroundColor: o.color,
                        borderColor: o.border,
                        borderWidth: 1,
                        hoverBackgroundColor: o.border,
                        hoverBorderColor: o.border,
                        hoverBorderWidth: 2,
                        maxBarThickness: 48
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        datalabels: { display: false },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            displayColors: false,
                            callbacks: {
                                title: function(items) { return items.length ? items[0].label : ''; },
                                label: function(item) { return o.format(item.raw); }
                            }
                        }
                    },
                    scales: {
                        x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 } },
                        y: {
                            beginAtZero: true,
                            ticks: { callback: function(value) { return o.format(value); } }
                        }
                    },
                    // Klik batang -> halaman sumber data yang sesuai, difilter ke kendaraan + periode
                    onClick: (evt, elements) => {
                        if (!elements.length) return;
                        const row = rows[elements[0].index];
                        if (!row || !row.id) return;
                        goToDrilldown(o.url, { vehicle_id: row.id, month: currentPeriod });
                    },
                    onHover: (evt, elements) => {
                        evt.native.target.style.cursor = elements.length ? 'pointer' : 'default';
                    }
                }
            });

            const totalEl = document.getElementById(o.totalId);
            if (totalEl) totalEl.textContent = 'Total: ' + o.format(sumRows(rows));

            // Fix tooltip/hover salah bar bila lebar container berubah setelah chart digambar
            if (!canvas.dataset.observed) {
                canvas.dataset.observed = '1';
                if (window.ResizeObserver) {
                    new ResizeObserver(() => {
                        if (vehicleCharts[canvasId]) vehicleCharts[canvasId].resize();
                    }).observe(canvas.parentElement);
                }
                window.addEventListener('load', function() {
                    if (vehicleCharts[canvasId]) vehicleCharts[canvasId].resize();
                });
            }
        }

        // Kalau tidak ada biaya sama sekali pada periode ini, tampilkan pesan, bukan grafik kosong
        function renderOrEmpty(canvasId, rows, o, emptyText) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;
            if (rows.length === 0) {
                canvas.parentElement.innerHTML = '<p class="text-center text-gray-400 text-sm py-8">' + emptyText + '</p>';
                const totalEl = document.getElementById(o.totalId);
                if (totalEl) totalEl.textContent = '';
                return;
            }
            renderVehicleChart(canvasId, rows, o);
        }

        // ---- BBM / EV Charging (tab Biaya & Jarak) ----
        const fuelTabs = {
            cost: {
                rows: rowsFor('fuel_cost', false),
                label: 'Biaya BBM/EV (Rp)',
                color: 'rgba(59,130,246,0.7)', border: 'rgba(59,130,246,1)',
                format: fmtRp
            },
            distance: {
                rows: rowsFor('distance', false),
                label: 'Jarak (km)',
                color: 'rgba(16,185,129,0.7)', border: 'rgba(16,185,129,1)',
                format: fmtKm
            }
        };

        function renderFuelTab(tab) {
            const t = fuelTabs[tab];
            renderVehicleChart('perVehicleChart', t.rows, {
                label: t.label, color: t.color, border: t.border, format: t.format,
                url: drilldownBaseUrl.fuel, totalId: 'chartTotalLabel'
            });
        }

        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.tab-btn').forEach(b => {
                    b.classList.remove('active', 'text-blue-600', 'border-b-2', 'border-blue-600');
                    b.classList.add('text-gray-500');
                });
                this.classList.add('active', 'text-blue-600', 'border-b-2', 'border-blue-600');
                this.classList.remove('text-gray-500');
                renderFuelTab(this.dataset.tab);
            });
        });
        renderFuelTab('cost');

        // ---- Service Rutin ----
        renderOrEmpty('serviceVehicleChart', rowsFor('service_cost', true), {
            label: 'Biaya Service Rutin (Rp)',
            color: 'rgba(249,115,22,0.7)', border: 'rgba(249,115,22,1)',
            format: fmtRp, url: drilldownBaseUrl.service, totalId: 'serviceTotalLabel'
        }, 'Belum ada biaya service rutin pada periode ini');

        // ---- Perbaikan ----
        renderOrEmpty('repairVehicleChart', rowsFor('repair_cost', true), {
            label: 'Biaya Perbaikan (Rp)',
            color: 'rgba(239,68,68,0.7)', border: 'rgba(239,68,68,1)',
            format: fmtRp, url: drilldownBaseUrl.repair, totalId: 'repairTotalLabel'
        }, 'Belum ada biaya perbaikan pada periode ini');
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