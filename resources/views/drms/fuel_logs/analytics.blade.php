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

        // BARU: pisahkan total liter (BBM) dan total kWh (listrik) dari $result,
        // supaya kartu ringkasan tidak menjumlahkan 2 satuan yang beda jadi 1 angka.
        // Dihitung dari fuel_unit_label per kendaraan yang sudah ada di $result.
        $totalLiterBbm   = collect($result)->where('fuel_unit_label', 'Liter')->sum('total_liters');
        $totalKwhListrik = collect($result)->where('fuel_unit_label', 'kWh')->sum('total_liters');
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
    {{-- BARU: "Total Liter/kWh" dipecah jadi 2 kartu terpisah — BBM (Liter) dan Listrik (kWh) — supaya tidak dijumlah campur 1 angka. --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-blue-500">
            <p class="text-xs text-gray-500 uppercase">Jumlah Pengisian</p>
            <p class="text-2xl font-bold">{{ $summary['count'] }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-green-500">
            <p class="text-xs text-gray-500 uppercase">Total Liter (BBM)</p>
            <p class="text-2xl font-bold text-green-600">{{ number_format($totalLiterBbm, 2, ',', '.') }}</p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow-sm border-l-4 border-teal-500">
            <p class="text-xs text-gray-500 uppercase">Total kWh (Listrik)</p>
            <p class="text-2xl font-bold text-teal-600">{{ number_format($totalKwhListrik, 2, ',', '.') }}</p>
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

    {{-- RINGKASAN WARNING EFISIENSI --}}
    @if(($summary['warning_yellow_count'] ?? 0) > 0 || ($summary['warning_red_count'] ?? 0) > 0)
    <div class="flex flex-wrap gap-3 mb-6">
        @if($summary['warning_red_count'] > 0)
        <div class="flex items-center gap-2 bg-red-50 border border-red-200 text-red-700 px-3 py-2 rounded-lg text-sm">
            🔴 <span><strong>{{ $summary['warning_red_count'] }}</strong> kendaraan boros signifikan (&gt;10% di bawah standar)</span>
        </div>
        @endif
        @if($summary['warning_yellow_count'] > 0)
        <div class="flex items-center gap-2 bg-yellow-50 border border-yellow-200 text-yellow-700 px-3 py-2 rounded-lg text-sm">
            🟡 <span><strong>{{ $summary['warning_yellow_count'] }}</strong> kendaraan mulai boros (&gt;5% di bawah standar)</span>
        </div>
        @endif
    </div>
    @endif

    {{-- RIWAYAT PENGISIAN PER KENDARAAN (SEBELUM vs SEKARANG) + ESTIMASI KE DEPAN --}}
    @if($vehicleDetail)
    <div class="bg-white p-4 rounded-lg shadow-sm border mb-6">
        <h3 class="font-semibold text-gray-700 mb-1">🔍 Riwayat Pengisian — {{ $vehicleDetail['vehicle']->plate_number }}</h3>
        <p class="text-xs text-gray-400 mb-3">Tidak dirangkum jadi rata-rata saja — tiap baris membandingkan pengisian sebelumnya dengan pengisian sekarang.</p>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pengisian Sebelumnya</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pengisian Sekarang</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Jarak (km)</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $vehicleDetail['fuel_unit_label'] }} Diisi</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Biaya</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Efisiensi</th>
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
                        <td class="px-4 py-2">Rp {{ number_format($row['cost'], 0, ',', '.') }}</td>
                        <td class="px-4 py-2">
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
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- ESTIMASI KE DEPAN --}}
        @if($vehicleDetail['estimate'])
            @php $est = $vehicleDetail['estimate']; @endphp
            <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-4">
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
    <p class="text-xs text-gray-400 mb-4">💡 Info efisiensi &amp; estimasi tiap kendaraan sudah langsung tampil di tabel di bawah. Pilih satu kendaraan di filter kalau mau lihat riwayat pengisian lengkap satu-satu.</p>
    @endif

    {{-- TABEL PER KENDARAAN — sudah termasuk info "isi terakhir" & "estimasi berikutnya" langsung, tanpa perlu klik --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kendaraan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Efisiensi vs Standar</th>
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
                        <a href="{{ route('drms.fuel-logs.analytics', array_filter(['vehicle_id' => $data['vehicle_id'], 'date_from' => request('date_from'), 'date_to' => request('date_to')])) }}"
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
                                    🟢 Aman
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
                    <td class="px-6 py-4">
                        {{ number_format($data['total_liters'], 2, ',', '.') }} {{ $data['fuel_unit_label'] }}
                    </td>
                    <td class="px-6 py-4">Rp {{ number_format($data['total_cost'], 0, ',', '.') }}</td>
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