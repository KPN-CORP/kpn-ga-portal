@extends('layouts.app_car_sidebar')

@section('content')
@php
    $now = now();
    $routeName = 'drms.drivers.schedule';

    // ---------- Warna & label satu jadwal ----------
    $calMeta = function ($req) use ($now) {
        $start = \Carbon\Carbon::parse($req->usage_date->format('Y-m-d') . ' ' . $req->start_time);
        $endDate = ($req->trip_type === 'round_trip' && $req->return_date) ? $req->return_date : $req->usage_date;
        $end = $req->end_time ? \Carbon\Carbon::parse($endDate->format('Y-m-d') . ' ' . $req->end_time) : null;
        if ($req->status == 'completed') {
            return ['bg-gray-100 text-gray-700 border-gray-300', 'Selesai', $now->lessThan($start)];
        }
        if ($req->status == 'approved_admin') {
            if ($now->lessThan($start)) return ['bg-blue-100 text-blue-700 border-blue-300', 'Terjadwal', false];
            if ($end && $now->between($start, $end)) return ['bg-green-100 text-green-700 border-green-300', 'Dalam Perjalanan', false];
            return ['bg-yellow-100 text-yellow-700 border-yellow-300', 'Belum diselesaikan', false];
        }
        return ['bg-gray-100 text-gray-700 border-gray-300', ucfirst(str_replace('_', ' ', $req->status)), false];
    };

    // ---------- Jadwal per driver per tanggal (trip pulang-pergi muncul di tiap hari rentangnya) ----------
    $driverDay = []; // [driver_id][Y-m-d] => daftar jadwal
    foreach ($allRequests as $req) {
        $rStart = $req->usage_date->copy()->startOfDay();
        $isRound = $req->trip_type === 'round_trip' && $req->return_date;
        $rEnd = $isRound ? $req->return_date->copy()->startOfDay() : $rStart->copy();
        if ($rEnd->lt($rStart)) $rEnd = $rStart->copy();
        $spanDays = (int) $rStart->diffInDays($rEnd) + 1;

        [$color, $label, $tooEarly] = $calMeta($req);
        $startTime = \Carbon\Carbon::parse($req->usage_date->format('Y-m-d') . ' ' . $req->start_time)->format('H:i');
        $endTime = $req->end_time ? \Carbon\Carbon::parse($req->end_time)->format('H:i') : null;

        $cur = $rStart->lt($monthStart) ? $monthStart->copy()->startOfDay() : $rStart->copy();
        $last = $rEnd->gt($monthEnd) ? $monthEnd->copy()->startOfDay() : $rEnd->copy();
        while ($cur->lte($last)) {
            $driverDay[$req->driver_id][$cur->format('Y-m-d')][] = [
                'sort'       => $startTime,
                'no'         => $req->request_no,
                'driver'     => optional($req->driver)->name ?? '-',
                'phone'      => optional($req->driver)->phone,
                'start'      => $startTime,
                'end'        => $endTime,
                'dest'       => $req->destination,
                'requester'  => optional($req->requester)->name ?? '-',
                'label'      => $label,
                'color'      => $color,
                'pp'         => (bool) $isRound,
                'range'      => $isRound ? ($rStart->format('d M') . ' - ' . $rEnd->format('d M')) : null,
                'day_no'     => (int) $rStart->diffInDays($cur) + 1,
                'span'       => $spanDays,
                'too_early'  => (bool) $tooEarly,
                'date_title' => $cur->translatedFormat('l, d F Y'),
            ];
            $cur->addDay();
        }
    }
    foreach ($driverDay as $did => $byDay) {
        foreach ($byDay as $k => $list) {
            usort($list, fn ($a, $b) => strcmp($a['sort'], $b['sort']));
            $driverDay[$did][$k] = $list;
        }
    }

    // ---------- Minggu (Senin - Minggu) ----------
    $firstDow = $monthStart->dayOfWeekIso - 1; // 0 = Senin
    $cells = array_merge(array_fill(0, $firstDow, null), $daysInMonth);
    while (count($cells) % 7 !== 0) { $cells[] = null; }
    $weeks = array_chunk($cells, 7);
    $dayNames = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

    $todayWeekIdx = null;
    $weekLabels = [];
    foreach ($weeks as $wi => $wk) {
        $real = array_values(array_filter($wk));
        $weekLabels[$wi] = $real[0]->translatedFormat('d M') . ' - ' . end($real)->translatedFormat('d M Y');
        foreach ($real as $d) {
            if ($d->isToday()) { $todayWeekIdx = $wi; }
        }
    }

    // Minggu yang dibuka pertama kali: ?week=first|last (loncat bulan), minggu hari ini, atau minggu pertama
    $reqWeek = request('week');
    if ($reqWeek === 'last') {
        $startWeekIdx = count($weeks) - 1;
    } elseif ($reqWeek === 'first') {
        $startWeekIdx = 0;
    } else {
        $startWeekIdx = $todayWeekIdx ?? 0;
    }

    // ---------- URL navigasi ----------
    $prevMonth = $monthStart->copy()->subMonthNoOverflow()->format('Y-m');
    $nextMonth = $monthStart->copy()->addMonthNoOverflow()->format('Y-m');
    $baseQuery = request()->except(['week']);
    $urlPrevMonth = route($routeName, array_merge($baseQuery, ['month' => $prevMonth]));
    $urlNextMonth = route($routeName, array_merge($baseQuery, ['month' => $nextMonth]));
    $urlPrevMonthLastWeek = route($routeName, array_merge($baseQuery, ['month' => $prevMonth, 'week' => 'last']));
    $urlNextMonthFirstWeek = route($routeName, array_merge($baseQuery, ['month' => $nextMonth, 'week' => 'first']));
    $urlToday = route($routeName, array_merge(request()->except(['week', 'month']), ['month' => now()->format('Y-m')]));

    $activeFilterCount = collect(['business_unit_id', 'driver_id', 'search'])->filter(fn ($f) => request()->filled($f))->count()
        + (request('status', 'all') !== 'all' ? 1 : 0);
@endphp

<div class="container mx-auto w-full max-w-full min-w-0 px-4 py-6"
     x-data="driverSchedule({
         data: @js($driverDay),
         week: {{ $startWeekIdx }},
         todayWeek: @js($todayWeekIdx),
         weekCount: {{ count($weeks) }},
         weekLabels: @js($weekLabels),
         urlToday: @js($urlToday),
         urlPrevMonthLastWeek: @js($urlPrevMonthLastWeek),
         urlNextMonthFirstWeek: @js($urlNextMonthFirstWeek)
     })"
     @keydown.escape.window="close()"
     x-effect="document.body.classList.toggle('overflow-hidden', open)">

    <h1 class="text-xl sm:text-2xl font-bold mb-4">📅 Jadwal Driver</h1>

    {{-- ============ FILTER + NAVIGASI (satu baris, kontainer bisa digeser kiri-kanan) ============ --}}
    <div class="bg-white rounded-lg shadow-sm border mb-4">
        <form method="GET" id="scheduleFilterForm" action="{{ route($routeName) }}"
              class="flex flex-wrap items-end gap-3 p-4">
            <input type="hidden" name="month" value="{{ $month }}">

            <div class="w-full sm:w-auto">
                <label for="filter_bu" class="block text-xs font-medium text-gray-600 mb-1">🏢 Business Unit</label>
                @if(auth()->user()->isDrmsSuperAdmin())
                    <select name="business_unit_id" id="filter_bu" class="w-full sm:w-auto border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">Semua BU</option>
                        @foreach($businessUnits as $bu)
                            <option value="{{ $bu->id_bisnis_unit }}" {{ request('business_unit_id') == $bu->id_bisnis_unit ? 'selected' : '' }}>
                                {{ $bu->nama_bisnis_unit }}
                            </option>
                        @endforeach
                    </select>
                @else
                    {{-- Admin biasa: terkunci ke BU sendiri --}}
                    <select id="filter_bu" class="w-full sm:w-auto border rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-600" disabled>
                        <option selected>{{ $user->drmsProfile->businessUnit->nama_bisnis_unit ?? '-' }}</option>
                    </select>
                @endif
            </div>

            <div class="w-full sm:w-auto">
                <label for="filter_driver" class="block text-xs font-medium text-gray-600 mb-1">👤 Driver</label>
                <select name="driver_id" id="filter_driver" class="w-full sm:w-44 border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua Driver</option>
                    @foreach($driverOptions as $opt)
                        <option value="{{ $opt->id }}" data-bu="{{ $opt->business_unit_id }}" {{ (string) ($driverIdFilter ?? '') === (string) $opt->id ? 'selected' : '' }}>
                            {{ $opt->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-full sm:w-auto">
                <label for="filter_status" class="block text-xs font-medium text-gray-600 mb-1">📌 Status</label>
                <select name="status" id="filter_status" class="w-full sm:w-auto border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="all" {{ ($statusFilter ?? 'all') == 'all' ? 'selected' : '' }}>Semua</option>
                    <option value="scheduled" {{ ($statusFilter ?? '') == 'scheduled' ? 'selected' : '' }}>⏳ Terjadwal</option>
                    <option value="on_trip" {{ ($statusFilter ?? '') == 'on_trip' ? 'selected' : '' }}>🚗 Dalam Perjalanan</option>
                    <option value="completed" {{ ($statusFilter ?? '') == 'completed' ? 'selected' : '' }}>✅ Selesai</option>
                </select>
            </div>

            <div class="w-full sm:w-auto">
                <label for="filter_search" class="block text-xs font-medium text-gray-600 mb-1">🔍 Cari driver</label>
                <input type="text" name="search" id="filter_search" value="{{ $searchDriver ?? '' }}"
                       placeholder="Nama, lalu Enter"
                       class="w-full sm:w-40 border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            @if($activeFilterCount > 0)
                <a href="{{ route($routeName, ['month' => $month]) }}"
                   class="shrink-0 bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-2 rounded-lg text-sm font-semibold transition">
                    Reset
                </a>
            @endif

            {{-- Navigasi: Hari ini | bulan | minggu --}}
            <div class="flex flex-wrap items-center gap-2 w-full lg:w-auto lg:ml-auto">
                <button type="button" @click="goToday()"
                        class="px-3 py-2 bg-white border rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Hari ini
                </button>

                <div class="inline-flex items-center bg-white border rounded-lg divide-x overflow-hidden">
                    <a href="{{ $urlPrevMonth }}" class="px-3 py-2 text-sm hover:bg-gray-50" title="Bulan sebelumnya" aria-label="Bulan sebelumnya">‹</a>
                    <span class="px-3 py-2 text-sm font-semibold text-gray-700 min-w-[120px] text-center whitespace-nowrap">{{ $monthStart->translatedFormat('F Y') }}</span>
                    <a href="{{ $urlNextMonth }}" class="px-3 py-2 text-sm hover:bg-gray-50" title="Bulan berikutnya" aria-label="Bulan berikutnya">›</a>
                </div>

                <div class="inline-flex items-center bg-white border rounded-lg divide-x overflow-hidden">
                    <button type="button" @click="prevWeek()" class="px-3 py-2 text-sm hover:bg-gray-50"
                            :title="week === 0 ? 'Ke bulan sebelumnya' : 'Minggu sebelumnya'" aria-label="Minggu sebelumnya">‹</button>
                    <span class="px-3 py-2 text-sm font-semibold text-gray-700 min-w-[150px] text-center whitespace-nowrap" x-text="weekLabels[week]"></span>
                    <button type="button" @click="nextWeek()" class="px-3 py-2 text-sm hover:bg-gray-50"
                            :title="week === weekCount - 1 ? 'Ke bulan berikutnya' : 'Minggu berikutnya'" aria-label="Minggu berikutnya">›</button>
                </div>
            </div>
        </form>
    </div>

    <script>
    (function () {
        var form = document.getElementById('scheduleFilterForm');
        var bu = document.getElementById('filter_bu');
        var driver = document.getElementById('filter_driver');
        var status = document.getElementById('filter_status');
        var canCascade = bu && !bu.disabled;

        // Pilihan Driver menyesuaikan Business Unit (superadmin)
        function apply() {
            if (!canCascade) return;
            var selectedBu = bu.value;
            Array.prototype.forEach.call(driver.options, function (opt) {
                if (!opt.value) return;
                var match = !selectedBu || String(opt.dataset.bu) === String(selectedBu);
                opt.hidden = !match;
                opt.disabled = !match;
                if (!match && opt.selected) driver.value = '';
            });
        }

        if (canCascade) {
            bu.addEventListener('change', function () { apply(); form.submit(); });
        }
        driver.addEventListener('change', function () {
            var opt = driver.options[driver.selectedIndex];
            if (canCascade && opt && opt.value && opt.dataset.bu && bu.value !== opt.dataset.bu) {
                bu.value = opt.dataset.bu;
            }
            apply();
            form.submit();
        });
        status.addEventListener('change', function () { form.submit(); });
        apply();
    })();
    </script>

    @if($drivers->isNotEmpty())
        {{-- ============ LEGENDA ============ --}}
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-600 mb-3">
            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Terjadwal</span>
            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-green-500"></span> Dalam perjalanan</span>
            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-yellow-500"></span> Belum diselesaikan</span>
            <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span> Selesai</span>
            <span class="text-gray-400">· PP = pulang-pergi, ↳ = lanjutan hari sebelumnya</span>
        </div>

        {{-- ============ JADWAL PER DRIVER (mingguan) ============ --}}
        @foreach($weeks as $wi => $wk)
            <div x-show="week === {{ $wi }}" x-cloak>
                {{-- Desktop / tablet: tabel driver x hari --}}
                <div class="bg-white rounded-lg shadow-sm border overflow-auto" style="width:0; min-width:100%; max-height: calc(100vh - 220px);">
                    <table class="w-full border-collapse" style="table-layout: fixed; min-width: 1000px;">
                        <colgroup>
                            <col style="width:170px">
                            @for($c = 0; $c < 7; $c++)<col>@endfor
                        </colgroup>
                        <thead>
                            <tr class="bg-gray-50 text-xs text-gray-500">
                                <th class="sticky left-0 top-0 z-30 bg-gray-50 px-3 py-2 text-left font-semibold border-r border-gray-200 shadow-[0_2px_0_#e5e7eb]">Driver</th>
                                @foreach($wk as $i => $d)
                                    <th class="sticky top-0 z-20 py-2 text-center border-l border-gray-200 shadow-[0_2px_0_#e5e7eb] {{ $d && $d->isToday() ? 'bg-blue-50 text-blue-700' : 'bg-gray-50' }} {{ $i >= 5 ? 'text-red-400' : '' }}">
                                        <div class="font-semibold">{{ $dayNames[$i] }}</div>
                                        @if($d)
                                            <div class="text-sm font-bold">{{ $d->format('j') }}</div>
                                        @else
                                            <div class="text-gray-300">-</div>
                                        @endif
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($drivers as $driver)
                                @php
                                    $weekTotal = 0;
                                    foreach ($wk as $d) {
                                        if ($d) { $weekTotal += count($driverDay[$driver->id][$d->format('Y-m-d')] ?? []); }
                                    }
                                @endphp
                                <tr class="border-b border-gray-200">
                                    <td class="sticky left-0 z-10 px-3 py-2 align-top border-r border-gray-200 bg-gray-50">
                                        <div class="font-semibold text-gray-800 break-words">{{ $driver->name }}</div>
                                        <div class="text-xs text-gray-400">{{ $driver->phone ?? '-' }}</div>
                                        <div class="mt-1 flex flex-wrap items-center gap-1">
                                            <span class="inline-block px-2 py-0.5 rounded-full text-[10px]
                                                @if($driver->status == 'available') bg-green-100 text-green-800
                                                @elseif($driver->status == 'on_trip') bg-yellow-100 text-yellow-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ ucfirst(str_replace('_', ' ', $driver->status)) }}
                                            </span>
                                            <span class="text-[10px] text-gray-400">{{ $weekTotal }} jadwal minggu ini</span>
                                        </div>
                                    </td>
                                    @foreach($wk as $i => $d)
                                        @if($d === null)
                                            <td class="border-l border-gray-100 bg-gray-50/60"></td>
                                        @else
                                            @php
                                                $dKey = $d->format('Y-m-d');
                                                $dayTrips = $driverDay[$driver->id][$dKey] ?? [];
                                            @endphp
                                            <td class="border-l border-gray-200 align-top p-1 min-w-0 {{ $d->isToday() ? 'bg-blue-50/40' : ($i >= 5 ? 'bg-gray-50' : '') }}">
                                                @foreach($dayTrips as $t)
                                                    <div role="button" tabindex="0"
                                                         @click="openDay('{{ $dKey }}', {{ $driver->id }})"
                                                         @keydown.enter="openDay('{{ $dKey }}', {{ $driver->id }})"
                                                         class="rounded border px-1.5 py-1 mb-1 text-[11px] leading-tight break-words cursor-pointer hover:brightness-95 transition {{ $t['color'] }}"
                                                         title="{{ $t['label'] }} · {{ $t['dest'] }} · {{ $t['requester'] }}">
                                                        <div class="font-semibold">
                                                            @if($t['day_no'] > 1)↳ @endif{{ $t['start'] }}{{ $t['end'] ? '-' . $t['end'] : '' }}
                                                            @if($t['pp'])<span class="font-normal">(PP)</span>@endif
                                                        </div>
                                                        <div>{{ $t['dest'] }}</div>
                                                        <div class="text-gray-500">{{ $t['requester'] }}</div>
                                                    </div>
                                                @endforeach
                                            </td>
                                        @endif
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        @if($allRequests->count() === 0)
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center text-yellow-700 mt-4">
                <div class="text-3xl mb-2">📭</div>
                <p>Tidak ada jadwal pada bulan ini.</p>
                <p class="text-sm mt-1">Pilih bulan lain atau ubah filter.</p>
            </div>
        @endif

        {{-- ============ DETAIL JADWAL (modal di desktop, bottom-sheet di HP) ============ --}}
        <div x-show="open" x-cloak class="fixed inset-0 z-[100] flex items-end md:items-center justify-center" style="display:none;"
             role="dialog" aria-modal="true">
            <div class="absolute inset-0 bg-black/40" @click="close()"></div>
            <div class="relative bg-white w-full md:max-w-lg max-h-[85vh] overflow-y-auto rounded-t-xl md:rounded-xl shadow-xl">
                <div class="sticky top-0 bg-white border-b px-4 py-3 flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-gray-800" x-text="title"></h3>
                        <p class="text-xs text-gray-500" x-text="driverName + ' · ' + trips.length + ' jadwal'"></p>
                    </div>
                    <button type="button" @click="close()" aria-label="Tutup"
                            class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
                </div>
                <div class="p-4 space-y-2">
                    <template x-for="(t, idx) in trips" :key="idx">
                        <div class="rounded border px-3 py-2 text-xs break-words" :class="t.color">
                            <div class="flex items-start justify-between gap-2">
                                <span class="font-semibold text-sm">
                                    <span x-text="t.start + (t.end ? ' - ' + t.end : '')"></span>
                                    <span x-show="t.pp" class="font-normal">(PP)</span>
                                </span>
                                <span class="shrink-0 font-semibold" x-text="t.label"></span>
                            </div>
                            <div class="mt-1 font-semibold" x-text="'🚗 ' + t.driver + (t.phone ? ' · ' + t.phone : '')"></div>
                            <div x-text="'📍 ' + t.dest"></div>
                            <div class="text-gray-600" x-text="'👤 ' + t.requester"></div>
                            <div class="text-gray-500" x-show="t.range" x-text="'🔁 ' + t.range + ' (hari ke-' + t.day_no + ' dari ' + t.span + ')'"></div>
                            <div class="text-gray-400" x-text="t.no"></div>
                            <div class="text-red-600" x-show="t.too_early">⚠️ Diselesaikan terlalu awal</div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center text-yellow-700">
            <div class="text-3xl mb-2">🚗</div>
            <p>Tidak ada driver ditemukan.</p>
            @if(auth()->user()->isDrmsSuperAdmin())
                <p class="text-sm mt-1">Pastikan filter Business Unit tidak membatasi.</p>
            @endif
        </div>
    @endif
</div>

<style>[x-cloak] { display: none !important; }</style>
<script>
function driverSchedule(cfg) {
    return {
        data: cfg.data || {},               // [driver_id][Y-m-d] => daftar jadwal
        week: cfg.week || 0,
        todayWeek: cfg.todayWeek,           // null jika bulan yang dilihat bukan bulan ini
        weekCount: cfg.weekCount || 1,
        weekLabels: cfg.weekLabels || [],
        open: false,
        date: null,
        driverId: null,

        get trips() {
            var byDay = this.data[String(this.driverId)] || {};
            return byDay[this.date] || [];
        },
        get title() { return this.trips.length ? this.trips[0].date_title : ''; },
        get driverName() { return this.trips.length ? this.trips[0].driver : ''; },

        // Di ujung bulan, lanjut ke bulan sebelumnya/berikutnya
        prevWeek() {
            if (this.week > 0) this.week--;
            else window.location.href = cfg.urlPrevMonthLastWeek;
        },
        nextWeek() {
            if (this.week < this.weekCount - 1) this.week++;
            else window.location.href = cfg.urlNextMonthFirstWeek;
        },
        goToday() {
            if (this.todayWeek !== null && this.todayWeek !== undefined) this.week = this.todayWeek;
            else window.location.href = cfg.urlToday;
        },
        openDay(d, driverId) {
            this.date = d;
            this.driverId = driverId;
            this.open = true;
        },
        close() { this.open = false; }
    };
}
</script>
@endsection