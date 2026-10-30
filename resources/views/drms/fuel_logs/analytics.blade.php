@extends('layouts.app_car_sidebar')

@php
    use Carbon\Carbon;
@endphp

@section('content')
<div class="container mx-auto max-w-full px-4 py-6">
    {{-- HEADER --}}
    <div class="flex flex-wrap justify-between items-center mb-6 gap-2">
        <h1 class="text-xl md:text-2xl font-bold">📊 Analisis Konsumsi</h1>
        <a href="{{ route('drms.fuel-logs.index') }}" class="w-full sm:w-auto bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition text-center">
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

        // Langkah hitung deviasi, 1 operasi per langkah (biar gampang diikuti pakai kalkulator)
        $devSteps = function ($std, $eff) {
            $a = round($std - $eff, 2);
            $b = round($a / $std, 3);
            return ['a' => number_format($a, 2), 'b' => number_format($b, 3), 'c' => round($b * 100, 1)];
        };

        // Data untuk popup "Lihat hitungan" per baris riwayat
        $calcPayload = function ($row, $vd) use ($devSteps) {
            $st = $devSteps($vd['standard'], $row['efficiency']);
            return [
                'date'      => $row['current_date']->format('d M Y'),
                'odo_now'   => number_format($row['current_odometer'], 0, ',', '.'),
                'odo_prev'  => number_format($row['previous_odometer'], 0, ',', '.'),
                'distance'  => number_format($row['distance'], 0, ',', '.'),
                'liters'    => number_format($row['liters'], 2, ',', '.'),
                'fuel_unit' => $vd['fuel_unit_label'],
                'unit'      => $vd['unit_label'],
                'eff'       => number_format($row['efficiency'], 2),
                'std'       => $vd['standard'],
                'a' => $st['a'], 'b' => $st['b'], 'c' => $st['c'],
            ];
        };

        // Pisahkan total liter (BBM) dan total kWh (listrik) supaya tidak dijumlah campur 1 angka.
        $totalLiterBbm   = collect($result)->where('fuel_unit_label', 'Liter')->sum('total_liters');
        $totalKwhListrik = collect($result)->where('fuel_unit_label', 'kWh')->sum('total_liters');
    @endphp
    <div class="bg-white rounded-lg shadow p-4 mb-6 border">
        <form method="GET" action="{{ route('drms.fuel-logs.analytics') }}"
              class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">🏢 Business Unit</label>
                @if(auth()->user()->isDrmsSuperAdmin())
                    <select name="business_unit_id" id="bu_filter" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua BU</option>
                        @foreach($businessUnits as $bu)
                            <option value="{{ $bu->id_bisnis_unit }}" {{ (string) $filterBusinessUnitId === (string) $bu->id_bisnis_unit ? 'selected' : '' }}>
                                {{ $bu->nama_bisnis_unit }}
                            </option>
                        @endforeach
                    </select>
                @else
                    {{-- User biasa: terkunci ke BU sendiri --}}
                    <select id="bu_filter" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-600" disabled>
                        @foreach($businessUnits as $bu)
                            <option selected>{{ $bu->nama_bisnis_unit }}</option>
                        @endforeach
                    </select>
                @endif
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
                <label class="block text-xs font-medium text-gray-600 mb-1">📅 Dari Tanggal</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">📅 Sampai Tanggal</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex gap-2 items-end md:col-span-2 lg:col-span-1">
                <button type="submit" class="flex-1 lg:flex-none bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                    🔍 Tampilkan
                </button>
                @if(request()->anyFilled(['business_unit_id', 'vehicle_id', 'date_from', 'date_to']))
                    <a href="{{ route('drms.fuel-logs.analytics') }}" class="flex-1 lg:flex-none text-center bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>

        {{-- Quick filter periode --}}
        <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t">
            <span class="w-full sm:w-auto text-xs text-gray-500 sm:self-center sm:mr-1">Periode cepat:</span>
            <a href="{{ route('drms.fuel-logs.analytics', array_filter(['business_unit_id' => request('business_unit_id'), 'vehicle_id' => request('vehicle_id'), 'date_from' => $thisMonthFrom, 'date_to' => $thisMonthTo])) }}"
               class="px-3 py-1.5 rounded-full text-xs font-medium {{ $isThisMonth ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Bulan Ini ({{ $thisMonthLabel }})
            </a>
            <a href="{{ route('drms.fuel-logs.analytics', array_filter(['business_unit_id' => request('business_unit_id'), 'vehicle_id' => request('vehicle_id'), 'date_from' => $lastMonthFrom, 'date_to' => $lastMonthTo])) }}"
               class="px-3 py-1.5 rounded-full text-xs font-medium {{ $isLastMonth ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Bulan Lalu ({{ $lastMonthLabel }})
            </a>
            @if(request()->filled('date_from') || request()->filled('date_to'))
                <a href="{{ route('drms.fuel-logs.analytics', array_filter(['business_unit_id' => request('business_unit_id'), 'vehicle_id' => request('vehicle_id')])) }}"
                   class="px-3 py-1.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700 hover:bg-gray-200">
                    Semua Periode
                </a>
            @endif
        </div>
    </div>

    {{-- RINGKASAN TOTAL (sesuai filter aktif): HP 2 kolom, tablet 3 kolom, desktop 5 kolom --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 md:gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-3 md:p-4 border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase">Jumlah Pengisian</p>
            <p class="text-xl md:text-2xl font-bold">{{ $summary['count'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-3 md:p-4 border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase">Total Liter (BBM)</p>
            <p class="text-xl md:text-2xl font-bold text-green-600 break-words">{{ number_format($totalLiterBbm, 2, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-3 md:p-4 border-l-4 border-teal-500">
            <p class="text-xs text-gray-500 uppercase">Total kWh (Listrik)</p>
            <p class="text-xl md:text-2xl font-bold text-teal-600 break-words">{{ number_format($totalKwhListrik, 2, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-3 md:p-4 border-l-4 border-purple-500">
            <p class="text-xs text-gray-500 uppercase">Total Biaya</p>
            <p class="text-xl md:text-2xl font-bold text-purple-600 break-words">Rp {{ number_format($summary['total_cost'], 0, ',', '.') }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-3 md:p-4 border-l-4 border-orange-500 col-span-2 md:col-span-1">
            <p class="text-xs text-gray-500 uppercase">Total Jarak (km)</p>
            <p class="text-xl md:text-2xl font-bold text-orange-600 break-words">{{ number_format($summary['total_distance'], 0, ',', '.') }}</p>
        </div>
    </div>

    {{-- RINGKASAN WARNING EFISIENSI --}}
    @if(($summary['warning_yellow_count'] ?? 0) > 0 || ($summary['warning_red_count'] ?? 0) > 0)
    <div class="flex flex-col sm:flex-row sm:flex-wrap gap-2 sm:gap-3 mb-6">
        @if($summary['warning_red_count'] > 0)
        <div class="flex items-start gap-2 bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg text-sm">
            🔴 <span><strong>{{ $summary['warning_red_count'] }}</strong> kendaraan boros signifikan (&gt;10% di bawah standar)</span>
        </div>
        @endif
        @if($summary['warning_yellow_count'] > 0)
        <div class="flex items-start gap-2 bg-yellow-50 border border-yellow-200 text-yellow-700 px-3 py-2 rounded-lg text-sm">
            🟡 <span><strong>{{ $summary['warning_yellow_count'] }}</strong> kendaraan mulai boros (&gt;5% di bawah standar)</span>
        </div>
        @endif
    </div>
    @endif

    {{-- INFO SOP: STANDAR EFISIENSI (BBM vs LISTRIK) --}}
    @php
        $stdBbm = 8;      // km/L  (harus sama dengan $standard di FuelLogController)
        $stdListrik = 6;  // km/kWh
    @endphp
    <div class="bg-white rounded-lg shadow p-4 mb-6 border">
        <h3 class="font-semibold text-gray-700">📘 CP Perjalanan Dinas MAR 2026 — Ketentuan Penggantian BBM</h3>
        <p class="text-xs text-gray-500">Perjalanan Dinas – Transportasi Perjalanan Dinas / <em>Business Travel Transportation</em></p>
        <p class="text-xs text-gray-400 mb-3">Makin besar angka km per liter/kWh, makin irit.</p>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="border rounded-lg p-3 bg-green-50 border-green-200">
                <div class="text-sm font-semibold text-green-800">⛽ Mobil BBM (Bensin)</div>
                <div class="text-2xl font-bold text-green-700">{{ $stdBbm }} <span class="text-sm font-medium">km/L</span></div>
                <p class="text-xs text-gray-600 mt-1"><strong>Poin 4</strong> — 1 liter BBM untuk setiap {{ $stdBbm }} km (1:{{ $stdBbm }})</p>
                <div class="text-xs text-gray-600 mt-2 space-y-0.5">
                    <p>🟢 <strong>{{ number_format($stdBbm * 0.95, 2) }}</strong> km/L ke atas</p>
                    <p>🟡 <strong>{{ number_format($stdBbm * 0.90, 2) }} – {{ number_format($stdBbm * 0.95, 2) }}</strong> km/L</p>
                    <p>🔴 di bawah <strong>{{ number_format($stdBbm * 0.90, 2) }}</strong> km/L</p>
                </div>
            </div>
            <div class="border rounded-lg p-3 bg-teal-50 border-teal-200">
                <div class="text-sm font-semibold text-teal-800">⚡ Mobil Listrik</div>
                <div class="text-2xl font-bold text-teal-700">{{ $stdListrik }} <span class="text-sm font-medium">km/kWh</span></div>
                <p class="text-xs text-gray-600 mt-1"><strong>Poin 5</strong> — 1 kWh untuk setiap {{ $stdListrik }} km (1:{{ $stdListrik }})</p>
                <div class="text-xs text-gray-600 mt-2 space-y-0.5">
                    <p>🟢 <strong>{{ number_format($stdListrik * 0.95, 2) }}</strong> km/kWh ke atas</p>
                    <p>🟡 <strong>{{ number_format($stdListrik * 0.90, 2) }} – {{ number_format($stdListrik * 0.95, 2) }}</strong> km/kWh</p>
                    <p>🔴 di bawah <strong>{{ number_format($stdListrik * 0.90, 2) }}</strong> km/kWh</p>
                </div>
            </div>
        </div>
    </div>

    {{-- RIWAYAT PENGISIAN PER KENDARAAN (SEBELUM vs SEKARANG) + ESTIMASI KE DEPAN --}}
    @if($vehicleDetail)
    <div class="bg-white p-4 rounded-lg shadow border mb-6">
        <h3 class="font-semibold text-gray-700 mb-1">🔍 Riwayat Pengisian — {{ $vehicleDetail['vehicle']->plate_number }}</h3>
        <p class="text-xs text-gray-400 mb-3">Tidak dirangkum jadi rata-rata saja — tiap baris membandingkan pengisian sebelumnya dengan pengisian sekarang.</p>

        {{-- HP & tablet: tampilan kartu --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:hidden">
            @foreach($vehicleDetail['rows'] as $row)
            @php
                $cardBg = match($row['warning_level'] ?? null) {
                    'red' => 'bg-red-50 border-red-200',
                    'yellow' => 'bg-yellow-50 border-yellow-200',
                    default => 'bg-white',
                };
            @endphp
            <div class="border rounded-lg p-3 {{ $cardBg }}">
                <div class="flex justify-between items-start gap-2 mb-2">
                    <div>
                        <div class="font-medium text-sm">{{ $row['current_date']->format('d M Y') }}</div>
                        <div class="text-xs text-gray-400">odo {{ number_format($row['current_odometer'], 0, ',', '.') }} km</div>
                    </div>
                    <div class="shrink-0">
                        @if($row['deviation_percent'] !== null)
                            @if($row['warning_level'] === 'red')
                                <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-semibold">🔴 {{ $row['deviation_percent'] }}%</span>
                            @elseif($row['warning_level'] === 'yellow')
                                <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs font-semibold">🟡 {{ $row['deviation_percent'] }}%</span>
                            @else
                                <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-semibold">🟢 {{ $row['deviation_percent'] }}%</span>
                            @endif
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </div>
                </div>
                <div class="text-xs text-gray-500 mb-2">
                    Sebelumnya:
                    @if($row['previous_date'])
                        {{ $row['previous_date']->format('d M Y') }} (odo {{ number_format($row['previous_odometer'], 0, ',', '.') }} km)
                    @else
                        <span class="italic text-gray-400">Pengisian pertama</span>
                    @endif
                </div>
                <dl class="grid grid-cols-2 gap-x-3 gap-y-1 text-sm">
                    <dt class="text-xs text-gray-500">Jarak</dt>
                    <dd class="text-right">{{ $row['distance'] !== null ? number_format($row['distance'], 0, ',', '.') . ' km' : '-' }}</dd>
                    <dt class="text-xs text-gray-500">{{ $vehicleDetail['fuel_unit_label'] }} Diisi</dt>
                    <dd class="text-right">{{ number_format($row['liters'], 2, ',', '.') }}</dd>
                    <dt class="text-xs text-gray-500">Biaya</dt>
                    <dd class="text-right">Rp {{ number_format($row['cost'], 0, ',', '.') }}</dd>
                    <dt class="text-xs text-gray-500">Parameter</dt>
                    <dd class="text-right">{{ $row['efficiency'] !== null ? number_format($row['efficiency'], 2) . ' ' . $vehicleDetail['unit_label'] : '-' }}</dd>
                </dl>
                @if($row['efficiency'] !== null)
                <div class="mt-2 pt-2 border-t text-right"><button type="button" class="calc-btn mt-1 text-[11px] text-blue-600 hover:underline" data-calc="{{ json_encode($calcPayload($row, $vehicleDetail)) }}">🧮 Lihat hitungan</button></div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- Desktop: tampilan tabel --}}
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pengisian Sebelumnya</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pengisian Sekarang</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Jarak (km)</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $vehicleDetail['fuel_unit_label'] }} Diisi</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Biaya</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Parameter</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Deviasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($vehicleDetail['rows'] as $row)
                    @php
                        $rowBg = match($row['warning_level'] ?? null) {
                            'red' => 'bg-red-50',
                            'yellow' => 'bg-yellow-50',
                            default => '',
                        };
                    @endphp
                    <tr class="border-t {{ $rowBg }}">
                        <td class="px-4 py-2 text-gray-500">
                            @if($row['previous_date'])
                                {{ $row['previous_date']->format('d M Y') }}<br>
                                <span class="text-xs text-gray-400">odo {{ number_format($row['previous_odometer'], 0, ',', '.') }} km</span>
                            @else
                                <span class="text-gray-400 italic">Pengisian pertama</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 font-medium">
                            {{ $row['current_date']->format('d M Y') }}<br>
                            <span class="text-xs text-gray-400 font-normal">odo {{ number_format($row['current_odometer'], 0, ',', '.') }} km</span>
                        </td>
                        <td class="px-4 py-2">
                            @if($row['distance'] !== null)
                                {{ number_format($row['distance'], 0, ',', '.') }}
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">{{ number_format($row['liters'], 2, ',', '.') }}</td>
                        <td class="px-4 py-2 whitespace-nowrap">Rp {{ number_format($row['cost'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2 whitespace-nowrap">
                            @if($row['efficiency'] !== null)
                                {{ number_format($row['efficiency'], 2) }} {{ $vehicleDetail['unit_label'] }}
                            @else
                                <span class="text-gray-400" title="Butuh pengisian sebelumnya untuk hitung jarak">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-2">
                            @if($row['deviation_percent'] !== null)
                                @if($row['warning_level'] === 'red')
                                    <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-semibold">🔴 {{ $row['deviation_percent'] }}%</span>
                                @elseif($row['warning_level'] === 'yellow')
                                    <span class="px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs font-semibold">🟡 {{ $row['deviation_percent'] }}%</span>
                                @else
                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-semibold">🟢 {{ $row['deviation_percent'] }}%</span>
                                @endif
                                <div><button type="button" class="calc-btn mt-1 text-[11px] text-blue-600 hover:underline" data-calc="{{ json_encode($calcPayload($row, $vehicleDetail)) }}">🧮 Lihat hitungan</button></div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- POPUP HITUNGAN --}}
        <div id="calc_modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-black/40 p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-4 text-sm text-gray-700">
                <div class="flex justify-between items-start mb-3">
                    <h4 id="calc_title" class="font-semibold text-gray-800"></h4>
                    <button type="button" id="calc_close" class="text-gray-400 hover:text-gray-600 text-lg leading-none">&times;</button>
                </div>
                <div id="calc_body" class="space-y-3"></div>
                <p class="text-xs text-gray-400 mt-3">Minus = lebih irit dari standar.</p>
                <button type="button" id="calc_close2" class="mt-3 w-full bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 rounded-lg text-sm font-semibold">Tutup</button>
            </div>
        </div>

        {{-- ESTIMASI KE DEPAN --}}
        @if($vehicleDetail['estimate'])
            @php $est = $vehicleDetail['estimate']; @endphp
            <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-3 sm:p-4">
                <h4 class="font-semibold text-blue-800 mb-2">🔮 Estimasi ke Depan (dari pengisian {{ $est['based_on_date']->format('d M Y') }})</h4>
                <p class="text-sm text-blue-900 mb-2">
                    Isi terakhir <strong>{{ number_format($est['based_on_liters'], 2, ',', '.') }} {{ $vehicleDetail['fuel_unit_label'] }}</strong>,
                    dengan rata-rata efisiensi historis <strong>{{ number_format($est['avg_efficiency'], 2) }} {{ $vehicleDetail['unit_label'] }}</strong>,
                    diperkirakan bisa menempuh sekitar <strong>{{ number_format($est['estimated_range_km'], 0, ',', '.') }} km</strong> ke depan.
                </p>
                <p class="text-sm text-blue-900">
                    @if($est['estimated_next_fill_date'])
                        Berdasarkan rata-rata jarak antar pengisian (~{{ $est['avg_days_between_fills'] }} hari), pengisian berikutnya diperkirakan sekitar
                        <strong>{{ $est['estimated_next_fill_date']->format('d M Y') }}</strong>,
                    @else
                        Pengisian berikutnya diperkirakan
                    @endif
                    dengan estimasi volume <strong>{{ number_format($est['estimated_next_liters'], 2, ',', '.') }} {{ $vehicleDetail['fuel_unit_label'] }}</strong>
                    dan estimasi biaya <strong>Rp {{ number_format($est['estimated_next_cost'], 0, ',', '.') }}</strong>
                    (pakai harga per {{ $vehicleDetail['fuel_unit_label'] }} dari pengisian terakhir).
                </p>
                <p class="text-xs text-blue-400 mt-2">*Estimasi kasar dari pola historis, bukan jaminan — kondisi jalan, gaya berkendara, dan beban bisa mengubah hasil aktual.</p>
            </div>
        @else
            <p class="text-xs text-gray-400 mt-3">Belum cukup data (minimal 2 pengisian dengan jarak berbeda) untuk membuat estimasi ke depan.</p>
        @endif
    </div>
    @else
    <p class="text-xs text-gray-400 mb-4">💡 Info efisiensi &amp; estimasi tiap kendaraan sudah langsung tampil di bawah. Pilih satu kendaraan di filter kalau mau lihat riwayat pengisian lengkap satu-satu.</p>
    <p class="text-xs text-gray-500 mb-4">
        📊 Angka di tabel ini adalah <strong>rangkuman dari seluruh pengisian</strong> pada periode yang dipilih:
        Parameter = rata-rata km per liter/kWh dari semua pengisian, Deviasi = selisih rata-rata itu dengan standar.
        Klik nomor plat untuk melihat hitungan tiap pengisian.
    </p>
    @endif

    {{-- PER KENDARAAN — HP & tablet: kartu --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 md:hidden">
        @forelse($result as $data)
        @php
            $cardBg = match($data['warning_level'] ?? null) {
                'red' => 'bg-red-50 border-red-200',
                'yellow' => 'bg-yellow-50 border-yellow-200',
                default => 'bg-white',
            };
            $est = $data['estimate'] ?? null;
        @endphp
        <div class="border rounded-lg shadow-sm p-4 {{ $cardBg }}">
            <div class="flex justify-between items-start gap-2 mb-3">
                <div>
                    <a href="{{ route('drms.fuel-logs.analytics', array_filter(['business_unit_id' => request('business_unit_id'), 'vehicle_id' => $data['vehicle_id'], 'date_from' => request('date_from'), 'date_to' => request('date_to')])) }}"
                       class="text-blue-600 hover:underline font-semibold">
                        {{ $data['plate_number'] }}
                    </a>
                    <div class="text-xs text-gray-400">{{ $data['fuel_type'] ?? 'Bensin' }}</div>
                </div>
                <div class="text-right shrink-0">
                    @if($data['actual_efficiency'] !== null)
                        <div class="text-sm">{{ number_format($data['actual_efficiency'], 2) }} {{ $data['unit_label'] }}</div>
                        <div class="text-xs text-gray-400">standar {{ $data['efficiency_standard'] }}</div>
                        @if($data['warning_level'] === 'red')
                            <span class="inline-block mt-1 px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-semibold">🔴 -{{ $data['deviation_percent'] }}%</span>
                        @elseif($data['warning_level'] === 'yellow')
                            <span class="inline-block mt-1 px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs font-semibold">🟡 -{{ $data['deviation_percent'] }}%</span>
                        @else
                            <span class="inline-block mt-1 px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-semibold">🟢</span>
                        @endif
                    @else
                        <span class="text-gray-400 text-xs">Efisiensi: -</span>
                    @endif
                </div>
            </div>


            <div class="text-sm space-y-2 border-t pt-3">
                <div>
                    <div class="text-xs text-gray-500 uppercase">Isi Terakhir</div>
                    @if($data['last_fill_date'])
                        <div>{{ $data['last_fill_date']->format('d M Y') }}</div>
                        @if($data['last_interval_distance'] !== null && $data['last_interval_efficiency'] !== null)
                            <div class="text-xs text-gray-400">
                                {{ number_format($data['last_interval_distance'], 0, ',', '.') }} km
                                · {{ number_format($data['last_interval_efficiency'], 2) }} {{ $data['unit_label'] }}
                            </div>
                        @else
                            <div class="text-xs text-gray-400 italic">pengisian pertama</div>
                        @endif
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Estimasi Isi Berikutnya</div>
                    @if($est)
                        @if($est['estimated_next_fill_date'])
                            <div>~{{ $est['estimated_next_fill_date']->format('d M Y') }}</div>
                        @else
                            <div class="text-gray-400 italic">tanggal blm bisa diprediksi</div>
                        @endif
                        <div class="text-xs text-gray-400">
                            ~{{ number_format($est['estimated_next_liters'], 1, ',', '.') }} {{ $data['fuel_unit_label'] }}
                            · Rp {{ number_format($est['estimated_next_cost'], 0, ',', '.') }}
                        </div>
                        <div class="text-xs text-gray-400">jarak ~{{ number_format($est['estimated_range_km'], 0, ',', '.') }} km lagi</div>
                    @else
                        <span class="text-gray-400">-</span>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-3 gap-2 mt-3 pt-3 border-t text-center">
                <div>
                    <div class="text-[10px] text-gray-500 uppercase">Total {{ $data['fuel_unit_label'] }}</div>
                    <div class="text-sm font-semibold">{{ number_format($data['total_liters'], 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-[10px] text-gray-500 uppercase">Biaya</div>
                    <div class="text-sm font-semibold break-words">Rp {{ number_format($data['total_cost'], 0, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-[10px] text-gray-500 uppercase">Jml Isi</div>
                    <div class="text-sm font-semibold">{{ $data['count'] }}</div>
                </div>
            </div>
        </div>
        @empty
        <div class="sm:col-span-2 bg-white rounded-lg shadow-sm p-4 text-center text-gray-500 text-sm">
            Belum ada data terverifikasi untuk filter ini.
        </div>
        @endforelse
    </div>

    {{-- PER KENDARAAN — Desktop: tabel --}}
    <div class="hidden md:block bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kendaraan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parameter</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Isi Terakhir</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estimasi Isi Berikutnya</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Liter/kWh</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Biaya</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jumlah Isi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($result as $data)
                @php
                    $rowBg = match($data['warning_level'] ?? null) {
                        'red' => 'bg-red-50',
                        'yellow' => 'bg-yellow-50',
                        default => '',
                    };
                    $est = $data['estimate'] ?? null;
                @endphp
                <tr class="{{ $rowBg }} align-top">
                    <td class="px-6 py-4 font-medium">
                        <a href="{{ route('drms.fuel-logs.analytics', array_filter(['business_unit_id' => request('business_unit_id'), 'vehicle_id' => $data['vehicle_id'], 'date_from' => request('date_from'), 'date_to' => request('date_to')])) }}"
                           class="text-blue-600 hover:underline">
                            {{ $data['plate_number'] }}
                        </a>
                        <div class="text-xs text-gray-400 mt-0.5">{{ $data['fuel_type'] ?? 'Bensin' }}</div>
                    </td>
                    <td class="px-6 py-4">
                        @if($data['actual_efficiency'] !== null)
                            <div>{{ number_format($data['actual_efficiency'], 2) }} {{ $data['unit_label'] }}
                                <span class="text-gray-400 text-xs">(standar {{ $data['efficiency_standard'] }})</span>
                            </div>
                            @if($data['warning_level'] === 'red')
                                <span class="inline-block mt-1 px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-semibold" title="Boros signifikan, lebih dari 10% di bawah standar">
                                    🔴 -{{ $data['deviation_percent'] }}%
                                </span>
                            @elseif($data['warning_level'] === 'yellow')
                                <span class="inline-block mt-1 px-2 py-0.5 bg-yellow-100 text-yellow-700 rounded-full text-xs font-semibold" title="Mulai boros, lebih dari 5% di bawah standar">
                                    🟡 -{{ $data['deviation_percent'] }}%
                                </span>
                            @else
                                <span class="inline-block mt-1 px-2 py-0.5 bg-green-100 text-green-700 rounded-full text-xs font-semibold" title="Sesuai atau lebih baik dari standar">
                                    🟢 
                                </span>
                            @endif
                        @else
                            <span class="text-gray-400" title="Butuh minimal 2 riwayat pengisian untuk menghitung efisiensi">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($data['last_fill_date'])
                            <div>{{ $data['last_fill_date']->format('d M Y') }}</div>
                            @if($data['last_interval_distance'] !== null && $data['last_interval_efficiency'] !== null)
                                <div class="text-xs text-gray-400">
                                    {{ number_format($data['last_interval_distance'], 0, ',', '.') }} km
                                    · {{ number_format($data['last_interval_efficiency'], 2) }} {{ $data['unit_label'] }}
                                </div>
                            @else
                                <div class="text-xs text-gray-400 italic">pengisian pertama</div>
                            @endif
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($est)
                            @if($est['estimated_next_fill_date'])
                                <div>~{{ $est['estimated_next_fill_date']->format('d M Y') }}</div>
                            @else
                                <div class="text-gray-400 italic">tanggal blm bisa diprediksi</div>
                            @endif
                            <div class="text-xs text-gray-400">
                                ~{{ number_format($est['estimated_next_liters'], 1, ',', '.') }} {{ $data['fuel_unit_label'] }}
                                · Rp {{ number_format($est['estimated_next_cost'], 0, ',', '.') }}
                            </div>
                            <div class="text-xs text-gray-400">jarak ~{{ number_format($est['estimated_range_km'], 0, ',', '.') }} km lagi</div>
                        @else
                            <span class="text-gray-400" title="Minimal 2 pengisian dengan jarak berbeda untuk membuat estimasi">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        {{ number_format($data['total_liters'], 2, ',', '.') }} {{ $data['fuel_unit_label'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">Rp {{ number_format($data['total_cost'], 0, ',', '.') }}</td>
                    <td class="px-6 py-4">{{ $data['count'] }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-4 text-center text-gray-500">Belum ada data terverifikasi untuk filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <p class="text-xs text-gray-400 mt-3">
        Catatan: efisiensi &amp; estimasi dihitung dari selisih odometer antar pengisian yang berurutan.
        Standar efisiensi: mobil BBM 8 km/L, mobil listrik 6 km/kWh. Deviasi &gt;5% di bawah standar = warning kuning, &gt;10% = warning merah.
        Klik nomor plat untuk lihat riwayat pengisian lengkap satu-satu.
    </p>
</div>


{{-- Autocomplete pencarian kendaraan untuk filter (tanpa dropdown) --}}
<script>
    const VEHICLES_FILTER_DATA = [
        @foreach($vehicles as $v)
        { id: {{ $v->id }}, bu: {{ (int) $v->business_unit_id }}, label: @json($v->plate_number . ' - ' . $v->type) },
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
                    const id = this.getAttribute('data-id');
                    hiddenInput.value = id;
                    searchInput.value = id ? this.getAttribute('data-label') : '';
                    // Pilih Kendaraan -> Business Unit ikut ter-set otomatis.
                    if (id && buSelect && !buSelect.disabled) {
                        const v = VEHICLES_FILTER_DATA.find(x => String(x.id) === String(id));
                        if (v) buSelect.value = String(v.bu);
                    }
                    hideSuggestions();
                });
            });
        }

        const buSelect = document.getElementById('bu_filter');

        // Cocokkan per kata (plat + merek, urutan bebas); plat juga cocok tanpa spasi.
        function matchesTerm(label, q) {
            const l = label.toLowerCase();
            const compact = l.replace(/\s+/g, '');
            return q.split(/\s+/).filter(Boolean).every(t => l.includes(t) || compact.includes(t));
        }

        function search(term) {
            const q = term.trim().toLowerCase();
            const buVal = (buSelect && !buSelect.disabled) ? buSelect.value : '';
            return VEHICLES_FILTER_DATA.filter(v =>
                (!buVal || String(v.bu) === String(buVal)) && (!q || matchesTerm(v.label, q))
            );
        }

        // Ganti Business Unit -> kosongkan pilihan kendaraan yang bukan milik BU tsb.
        if (buSelect && !buSelect.disabled) {
            buSelect.addEventListener('change', function () {
                const cur = VEHICLES_FILTER_DATA.find(v => String(v.id) === String(hiddenInput.value));
                if (cur && this.value && String(cur.bu) !== String(this.value)) {
                    hiddenInput.value = '';
                    searchInput.value = '';
                }
            });
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('calc_modal');
        if (!modal) return;
        const body = document.getElementById('calc_body');
        const title = document.getElementById('calc_title');

        function open(d) {
            title.textContent = '🧮 Hitungan — ' + d.date;
            body.innerHTML =
                '<div><p class="font-semibold">1. Jarak</p>' +
                '<p>' + d.odo_now + ' − ' + d.odo_prev + ' = <strong>' + d.distance + ' km</strong></p>' +
                '<p class="text-xs text-gray-400">odometer sekarang − odometer sebelumnya</p></div>' +
                '<div><p class="font-semibold">2. Parameter</p>' +
                '<p>' + d.distance + ' ÷ ' + d.liters + ' = <strong>' + d.eff + ' ' + d.unit + '</strong></p>' +
                '<p class="text-xs text-gray-400">jarak ÷ ' + d.fuel_unit + ' diisi</p></div>' +
                '<div><p class="font-semibold">3. Deviasi (standar ' + d.std + ')</p>' +
                '<p>a) ' + d.std + ' − ' + d.eff + ' = ' + d.a + '</p>' +
                '<p>b) ' + d.a + ' ÷ ' + d.std + ' = ' + d.b + '</p>' +
                '<p>c) ' + d.b + ' × 100 = <strong>' + d.c + '%</strong></p></div>';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function close() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.calc-btn');
            if (btn) { open(JSON.parse(btn.getAttribute('data-calc'))); return; }
            if (e.target === modal) close();
        });
        document.getElementById('calc_close').addEventListener('click', close);
        document.getElementById('calc_close2').addEventListener('click', close);
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    });
</script>
@endsection