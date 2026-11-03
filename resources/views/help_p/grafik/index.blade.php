{{-- resources/views/help_p/grafik/index.blade.php --}}
@extends('layouts.app-help-sidebar')

@section('title', 'Grafik')

@section('content')
@php
    $HT        = \App\Support\help_s\HelpTimeline::class;
    $warnaCorp = $HT::WARNA_CORP;   // coklat: GA Corp / KPN Corporate
    $namaBulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $periode   = \App\Support\help_s\HelpTimeline::labelPeriode($rentang);
    $fd        = fn ($d) => $d === null ? '-' : $HT::formatDurasi((int) $d);

    $warnaSla  = fn ($p) => $p >= 90 ? '#059669' : ($p >= 75 ? '#d97706' : '#dc2626');
@endphp

<div x-data="grafikHelp()" class="text-sm text-slate-800 font-sans">
    <script type="application/json" id="data-rincian">@json($rincian)</script>

    {{-- HEADER --}}
    <div class="flex flex-wrap items-end justify-between gap-2 mb-6">
        <div>
            <h2 class="text-xl md:text-2xl font-bold text-slate-900">Dashboard Help Desk</h2>
            <p class="text-slate-500 mt-0.5">Ringkasan penanganan tiket · {{ $periode }} · waktu WIB. Klik batang untuk melihat daftar tiketnya.</p>
        </div>
        <span class="text-xs text-slate-500 bg-slate-100 rounded-full px-3 py-1" title="Jumlah tiket yang dihitung pada periode dan filter terpilih">{{ number_format($ringkasan['tiket']) }} tiket</span>
    </div>

    <div class="mb-6">
        @include('help_p.partials.grafik-filter', ['aksi' => 'help.grafik', 'tampilStatus' => false])
    </div>

    @if($terpotong)
        <p class="mb-6 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
            Hanya 1.000 tiket terbaru yang dihitung. Persempit periode atau pilih satu BU.
        </p>
    @endif

    {{-- KIRI: dashboard · KANAN: panel tiket (muncul saat batang diklik) --}}
    <div class="grid grid-cols-1 gap-6 items-start"
         :class="panel ? 'lg:grid-cols-[minmax(0,1fr)_27rem]' : 'lg:grid-cols-1'">

        <div class="space-y-6 min-w-0">

            {{-- KPI: KECEPATAN LAYANAN --}}
            <h3 class="text-sm font-semibold text-slate-900">Kecepatan Layanan</h3>
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 !mt-2">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Rata-rata menunggu ditangani</p>
                    <p class="mt-1.5 text-2xl font-semibold text-slate-900 tabular-nums">{{ $fd($ringkasan['tunggu']) }}</p>
                    <p class="mt-1 text-xs text-slate-400">Diajukan sampai mulai ditangani</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Rata-rata lama pengerjaan</p>
                    <p class="mt-1.5 text-2xl font-semibold text-slate-900 tabular-nums">{{ $fd($ringkasan['done']) }}</p>
                    <p class="mt-1 text-xs text-slate-400">Mulai ditangani sampai selesai · {{ number_format($ringkasan['jml_done']) }} tiket</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Dialihkan ke GA Corp</p>
                    <p class="mt-1.5 text-2xl font-semibold tabular-nums" style="color: {{ $warnaCorp }};">{{ $ringkasan['persen_esk'] !== null ? $ringkasan['persen_esk'] . '%' : '-' }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ number_format($ringkasan['eskalasi']) }} dari {{ number_format($ringkasan['tiket']) }} tiket</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Selesai tepat waktu (SLA)</p>
                    <p class="mt-1.5 text-2xl font-semibold tabular-nums" @if($ringkasan['sla'] !== null) style="color: {{ $warnaSla($ringkasan['sla']) }};" @endif>{{ $ringkasan['sla'] !== null ? $ringkasan['sla'] . '%' : '-' }}</p>
                    <p class="mt-1 text-xs text-slate-400">dari {{ number_format($ringkasan['sla_total']) }} tiket selesai</p>
                </div>
            </div>

            {{-- KPI: JUMLAH TIKET --}}
            <h3 class="text-sm font-semibold text-slate-900">Jumlah Tiket</h3>
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 !mt-2">
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Tiket masuk</p>
                    <p class="mt-1.5 text-2xl font-semibold text-blue-600 tabular-nums">{{ number_format($kpiTambah['masuk']) }}</p>
                    <p class="mt-1 text-xs text-slate-400">Diajukan pada {{ $periode }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Tiket selesai</p>
                    <p class="mt-1.5 text-2xl font-semibold text-emerald-600 tabular-nums">{{ number_format($kpiTambah['selesai']) }}</p>
                    <p class="mt-1 text-xs text-slate-400">Selesai pada {{ $periode }}</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Belum ditutup</p>
                    <p class="mt-1.5 text-2xl font-semibold text-amber-600 tabular-nums">{{ number_format($kpiTambah['belum_close']) }}</p>
                    <p class="mt-1 text-xs text-slate-400">Saat ini · semua periode</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Belum ditutup &gt; {{ $kpiTambah['batas'] }} hari</p>
                    <p class="mt-1.5 text-2xl font-semibold tabular-nums {{ $kpiTambah['lewat'] > 0 ? 'text-red-600' : 'text-slate-900' }}">{{ number_format($kpiTambah['lewat']) }}</p>
                    <p class="mt-1 text-xs text-slate-400">Sejak diajukan · idealnya 0</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6" :class="panel ? '' : 'xl:grid-cols-2'">

                {{-- 2. UMUR TIKET YANG BELUM CLOSE --}}
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">Tiket Open & On Proses</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Umur tiket sejak diajukan · semua periode</p>
                    @if($insUmur)
                        <p class="mt-3 text-xs rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-slate-700">{{ $insUmur }}</p>
                    @endif

                    @php
                        $totalUmur = array_sum(array_column($umur, 'n'));
                        $maksUmur  = max(1, max(array_column($umur, 'n')));
                    @endphp
                    <div class="mt-2">
                        @foreach($umur as $u)
                            <button type="button" @click='bukaUmur(@json($u['k']), @json($u['label']))'
                                    class="group w-full text-left rounded-lg px-2 py-2.5 hover:bg-slate-50 transition"
                                    :class="pilih === 'umur:{{ $u['k'] }}' ? 'bg-slate-50 ring-1 ring-slate-300' : ''">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-medium text-slate-800">{{ $u['label'] }}</span>
                                    <span class="inline-flex items-center gap-2">
                                        <span class="text-sm font-semibold tabular-nums" style="color: {{ $u['warna'] }};">{{ number_format($u['n']) }} tiket</span>
                                        <span class="text-xs text-slate-400 tabular-nums w-9 text-right">{{ $totalUmur > 0 ? round($u['n'] / $totalUmur * 100) : 0 }}%</span>
                                        <span class="text-slate-300 group-hover:text-slate-500">›</span>
                                    </span>
                                </div>
                                <div class="mt-1.5 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full" style="width: {{ round($u['n'] / $maksUmur * 100) }}%; background: {{ $u['warna'] }};"></div>
                                </div>
                            </button>
                        @endforeach
                        @if($totalUmur === 0)
                            <p class="py-6 text-center text-slate-400">Tidak ada tiket yang belum ditutup.</p>
                        @endif
                    </div>
                </section>

                {{-- 3. LAMA TIKET SAMPAI CLOSE --}}
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">Tiket Closed</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Lama dari diajukan sampai ditutup · {{ $periode }}</p>
                    @if($insClose)
                        <p class="mt-3 text-xs rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-slate-700">{{ $insClose }}</p>
                    @endif
                    @php
                        $totalUmur = array_sum(array_column($umurClose, 'n'));
                        $maksUmur  = max(1, max(array_column($umurClose, 'n')));
                    @endphp
                    <div class="mt-2">
                        @foreach($umurClose as $u)
                            <button type="button" @click='bukaClose(@json($u['k']), @json($u['label']))'
                                    class="group w-full text-left rounded-lg px-2 py-2.5 hover:bg-slate-50 transition"
                                    :class="pilih === 'close:{{ $u['k'] }}' ? 'bg-slate-50 ring-1 ring-slate-300' : ''">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-medium text-slate-800">{{ $u['label'] }}</span>
                                    <span class="inline-flex items-center gap-2">
                                        <span class="text-sm font-semibold tabular-nums" style="color: {{ $u['warna'] }};">{{ number_format($u['n']) }} tiket</span>
                                        <span class="text-xs text-slate-400 tabular-nums w-9 text-right">{{ $totalUmur > 0 ? round($u['n'] / $totalUmur * 100) : 0 }}%</span>
                                        <span class="text-slate-300 group-hover:text-slate-500">›</span>
                                    </span>
                                </div>
                                <div class="mt-1.5 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full" style="width: {{ round($u['n'] / $maksUmur * 100) }}%; background: {{ $u['warna'] }};"></div>
                                </div>
                            </button>
                        @endforeach
                        @if($totalUmur === 0)
                            <p class="py-6 text-center text-slate-400">Belum ada tiket ditutup pada periode ini.</p>
                        @endif
                    </div>
                </section>
            </div>

            <div class="grid grid-cols-1 gap-6" :class="panel ? '' : 'xl:grid-cols-2'">

                {{-- 1. DIALIHKAN KE GA CORP --}}
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">Tiket Dialihkan ke GA Corp</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Persen tiket tiap unit · {{ $periode }}</p>
                    @if($insEsk)
                        <p class="mt-3 text-xs rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-slate-700">{{ $insEsk }}</p>
                    @endif

                    <div class="mt-3">
                        @forelse($eskalasiBU as $g)
                            <button type="button" @click='bukaBU({{ $g['id'] }}, @json($g['label']))'
                                    class="group w-full text-left rounded-lg px-2 py-2.5 hover:bg-slate-50 transition"
                                    :class="pilih === 'bu:{{ $g['id'] }}' ? 'bg-slate-50 ring-1 ring-slate-300' : ''">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="inline-flex items-center gap-2 min-w-0">
                                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ $g['warna'] }};"></span>
                                        <span class="font-medium text-slate-800 truncate">{{ $g['label'] }}</span>
                                    </span>
                                    <span class="inline-flex items-center gap-2">
                                        <span class="text-sm font-semibold tabular-nums text-slate-900">{{ $g['persen'] }}%</span>
                                        <span class="text-slate-300 group-hover:text-slate-500">›</span>
                                    </span>
                                </div>
                                <div class="mt-1.5 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full" style="width: {{ $g['persen'] }}%; background: {{ $warnaCorp }};"></div>
                                </div>
                                <p class="mt-1 text-xs text-slate-400 tabular-nums">{{ number_format($g['eskalasi']) }} dari {{ number_format($g['tiket']) }} tiket</p>
                            </button>
                        @empty
                            <p class="py-8 text-center text-slate-400">Belum ada tiket pada periode ini.</p>
                        @endforelse
                    </div>
                </section>

                {{-- 1b. SELESAI DI UNIT SENDIRI --}}
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">Tiket Selesai di Unit</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Persen tiket tiap unit yang selesai tanpa GA Corp · {{ $periode }}</p>
                    @if($insUnit)
                        <p class="mt-3 text-xs rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-slate-700">{{ $insUnit }}</p>
                    @endif

                    <div class="mt-3">
                        @forelse($selesaiUnitBU as $g)
                            <button type="button" @click='bukaUnit({{ $g['id'] }}, @json($g['label']))'
                                    class="group w-full text-left rounded-lg px-2 py-2.5 hover:bg-slate-50 transition"
                                    :class="pilih === 'unit:{{ $g['id'] }}' ? 'bg-slate-50 ring-1 ring-slate-300' : ''">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="inline-flex items-center gap-2 min-w-0">
                                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ $g['warna'] }};"></span>
                                        <span class="font-medium text-slate-800 truncate">{{ $g['label'] }}</span>
                                    </span>
                                    <span class="inline-flex items-center gap-2">
                                        <span class="text-sm font-semibold tabular-nums text-slate-900">{{ $g['persen_unit'] }}%</span>
                                        <span class="text-slate-300 group-hover:text-slate-500">›</span>
                                    </span>
                                </div>
                                <div class="mt-1.5 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full" style="width: {{ $g['persen_unit'] }}%; background: #059669;"></div>
                                </div>
                                <p class="mt-1 text-xs text-slate-400 tabular-nums">{{ number_format($g['selesai_unit']) }} dari {{ number_format($g['tiket']) }} tiket</p>
                            </button>
                        @empty
                            <p class="py-8 text-center text-slate-400">Belum ada tiket pada periode ini.</p>
                        @endforelse
                    </div>
                </section>

            </div>

            {{-- 4. KETEPATAN SLA PER KATEGORI --}}
            @php
                $slaUrut  = collect($slaKategori)->sortBy([['persen', 'asc'], ['total', 'desc']])->values()->all();
                $slaTarget = 90;
            @endphp
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Ketepatan SLA per Kategori</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Tiket selesai pada {{ $periode }} · dari diajukan sampai selesai dibanding batas SLA kategori · terburuk di atas</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs text-slate-500">
                        <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm" style="background:#059669"></span>≥ 90%</span>
                        <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm" style="background:#d97706"></span>75 – 89%</span>
                        <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm" style="background:#dc2626"></span>&lt; 75%</span>
                        <span class="inline-flex items-center gap-1"><span class="w-px h-3 bg-slate-500"></span>target {{ $slaTarget }}%</span>
                    </div>
                </div>
                @if($insSla)
                    <p class="mt-3 text-xs rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-slate-700">{{ $insSla }}</p>
                @endif

                <div class="mt-3">
                    @forelse($slaUrut as $k)
                        <button type="button" @click='bukaSla({{ (int) $k['id'] }}, @json($k['nama'], JSON_HEX_APOS))'
                                class="group w-full text-left rounded-lg px-2 py-2.5 hover:bg-slate-50 transition"
                                :class="pilih === 'sla:{{ (int) $k['id'] }}' ? 'bg-slate-50 ring-1 ring-slate-300' : ''">
                            <div class="flex items-center gap-3">
                                <div class="w-36 sm:w-52 shrink-0 min-w-0">
                                    <p class="font-medium text-slate-800 truncate" title="{{ $k['nama'] }}">{{ $k['nama'] }}</p>
                                    <p class="text-xs text-slate-400 truncate">batas {{ $k['sla_jam'] }} jam · rata-rata {{ $fd($k['rata']) }}</p>
                                </div>
                                <div class="flex-1 relative h-3 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full" style="width: {{ $k['persen'] }}%; background: {{ $warnaSla($k['persen']) }};"></div>
                                    <div class="absolute inset-y-0 w-px bg-slate-500/70" style="left: {{ $slaTarget }}%;" title="Target {{ $slaTarget }}%"></div>
                                </div>
                                <div class="w-28 shrink-0 text-right">
                                    <span class="text-sm font-semibold tabular-nums" style="color: {{ $warnaSla($k['persen']) }};">{{ $k['persen'] }}%</span>
                                    <span class="text-slate-300 group-hover:text-slate-500 ml-1">›</span>
                                    <p class="text-xs text-slate-400 tabular-nums">{{ number_format($k['dalam']) }} dari {{ number_format($k['total']) }} tiket</p>
                                </div>
                            </div>
                        </button>
                    @empty
                        <p class="py-8 text-center text-slate-400">Belum ada tiket selesai pada kategori ber-SLA di periode ini.</p>
                    @endforelse
                </div>
            </section>

            {{-- 5. BEBAN KERJA PER ADMIN --}}
            @php
                $maksTiketAdmin = max(1, collect($adminRekap)->max('tiket') ?: 1);
            @endphp
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Beban Kerja per Admin</h3>
                <p class="text-xs text-slate-400 mt-0.5">Tiket yang ditangani tiap admin · {{ $periode }} · klik baris untuk melihat tiketnya</p>

                <div class="mt-3 overflow-x-auto max-h-[28rem] overflow-y-auto border border-slate-200 rounded-lg">
                    <table class="min-w-[640px] w-full text-sm">
                        <thead class="bg-slate-50 text-xs text-slate-500 sticky top-0 z-10">
                            <tr>
                                <th class="text-left font-medium px-3 py-2">Admin</th>
                                <th class="text-left font-medium px-3 py-2 w-56">Tiket ditangani</th>
                                <th class="text-right font-medium px-3 py-2 whitespace-nowrap" title="Dari diajukan sampai mulai ditangani">Rata-rata menunggu</th>
                                <th class="text-right font-medium px-3 py-2 whitespace-nowrap" title="Dari mulai ditangani sampai selesai">Rata-rata pengerjaan</th>
                                <th class="text-right font-medium px-3 py-2 whitespace-nowrap">Diteruskan ke GA Corp</th>
                                <th class="text-right font-medium px-3 py-2 whitespace-nowrap">Masih berjalan</th>
                                <th class="w-6"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($adminRekap as $a)
                                <tr class="group cursor-pointer hover:bg-slate-50 transition"
                                    @click='bukaAdmin(@json($a['nama'], JSON_HEX_APOS))'
                                    :class='pilih === @json("admin:" . $a["nama"], JSON_HEX_APOS) ? "bg-slate-50" : ""'>
                                    <td class="px-3 py-2.5">
                                        <span class="font-medium text-slate-800">{{ $a['nama'] }}</span>
                                        @if($a['corp'])
                                            <span class="ml-1 text-[10px] font-semibold px-1.5 py-0.5 rounded text-white" style="background: {{ $warnaCorp }};">GA Corp</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-2.5">
                                        <div class="flex items-center gap-2">
                                            <div class="flex-1 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                                <div class="h-full rounded-full bg-blue-500" style="width: {{ round($a['tiket'] / $maksTiketAdmin * 100) }}%;"></div>
                                            </div>
                                            <span class="w-8 text-right font-semibold tabular-nums text-slate-900">{{ number_format($a['tiket']) }}</span>
                                        </div>
                                    </td>
                                    <td class="px-3 py-2.5 text-right tabular-nums text-slate-700 whitespace-nowrap">{{ $fd($a['tunggu']) }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums text-slate-700 whitespace-nowrap">
                                        {{ $fd($a['done']) }}
                                        @if($a['jml_done'])<span class="text-xs text-slate-400">({{ $a['jml_done'] }})</span>@endif
                                    </td>
                                    <td class="px-3 py-2.5 text-right tabular-nums whitespace-nowrap {{ $a['jml_over'] > 0 ? 'font-semibold' : 'text-slate-400' }}" @if($a['jml_over'] > 0) style="color: {{ $warnaCorp }};" @endif>{{ number_format($a['jml_over']) }}</td>
                                    <td class="px-3 py-2.5 text-right tabular-nums whitespace-nowrap {{ $a['berjalan'] > 0 ? 'text-amber-600 font-semibold' : 'text-slate-400' }}">{{ number_format($a['berjalan']) }}</td>
                                    <td class="pr-3 text-slate-300 group-hover:text-slate-500">›</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="py-8 text-center text-slate-400">Belum ada tiket yang ditangani pada periode ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-xs text-slate-400">Angka dalam kurung = jumlah tiket yang dihitung untuk rata-rata pengerjaan. Satu tiket bisa dihitung di beberapa admin bila dialihkan.</p>
            </section>
        </div>

        {{-- ===== PANEL KANAN: TIKET DI BALIK BATANG YANG DIKLIK ===== --}}
        <aside x-show="panel" x-cloak
               class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden flex flex-col lg:sticky lg:top-4 lg:max-h-[calc(100vh-2rem)]">
            <template x-if="panel">
                <div class="flex flex-col min-h-0 flex-1">
                    <div class="flex items-start justify-between gap-3 px-4 py-3 border-b bg-slate-50">
                        <div class="min-w-0">
                            <p class="font-semibold text-slate-900 truncate" x-text="panel.judul"></p>
                            <p class="text-xs text-slate-500 mt-0.5" x-text="panel.ringkas"></p>
                        </div>
                        <button type="button" @click="tutup()" class="text-slate-400 hover:text-slate-600 text-xl leading-none" aria-label="Tutup" title="Tutup">&times;</button>
                    </div>

                    <div class="px-4 pt-3 flex flex-wrap gap-2" x-show="panel.chips.length > 1">
                        <template x-for="c in panel.chips" :key="c.k">
                            <button type="button" @click="panel.aktif = c.k; hal = 0"
                                    class="text-xs rounded-full border px-3 py-1 transition"
                                    :class="panel.aktif === c.k ? 'bg-slate-900 text-white border-slate-900' : 'bg-white text-slate-600 border-slate-300 hover:bg-slate-50'">
                                <span x-text="c.l"></span> (<span x-text="panel.sumber.filter(c.f).length"></span>)
                            </button>
                        </template>
                    </div>

                    <div class="px-4 py-3">
                        <input type="search" x-model="cari" @input="hal = 0" placeholder="Cari nomor, judul, atau petugas"
                               class="w-full border border-slate-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="flex-1 overflow-y-auto border-t">
                        <p x-show="tampil.length === 0" class="p-6 text-center text-slate-400">Tidak ada tiket.</p>
                        <ul class="divide-y">
                            <template x-for="t in halaman" :key="t.id">
                                <li class="px-4 py-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <a :href="t.u" class="text-blue-600 hover:underline font-semibold" x-text="t.n"></a>
                                        <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full whitespace-nowrap bg-slate-100 text-slate-600" x-text="t.st.replaceAll('_', ' ')"></span>
                                    </div>
                                    <p class="text-sm text-slate-800 mt-0.5 line-clamp-2" x-text="t.j"></p>
                                    <p class="text-xs text-slate-500 mt-0.5"><span x-text="t.bn"></span></p>

                                    <ol class="mt-2 space-y-1 text-xs text-slate-600 border-l-2 border-slate-200 pl-3">
                                        <li>Diajukan · <span x-text="t.dibuat"></span></li>
                                        <li x-show="t.diambil">Ditangani · <span x-text="t.diambil"></span>
                                            <span x-show="t.admin.length"> oleh <b x-text="t.admin[0]"></b></span>
                                            <span x-show="t.tunggu" class="text-amber-600 font-semibold">(menunggu <span x-text="t.tunggu"></span>)</span>
                                        </li>
                                        <li x-show="t.esk" class="text-purple-700">Dialihkan ke GA Corp · <span x-text="t.over"></span></li>
                                        <li x-show="t.admin.length > 1">Petugas lain: <span x-text="t.admin.slice(1).join(', ')"></span></li>
                                        <li x-show="t.done">Selesai · <span x-text="t.done"></span>
                                            <span x-show="t.proses" class="text-blue-600 font-semibold">(pengerjaan <span x-text="t.proses"></span>)</span>
                                        </li>
                                        <li x-show="!t.done" class="text-slate-400">Belum selesai</li>
                                        <li x-show="t.tutup">Ditutup · <span x-text="t.tutup"></span></li>
                                    </ol>

                                    <p x-show="panel.tipe === 'sla' && t.total" class="mt-2 text-xs font-semibold"
                                       :class="t.dalam ? 'text-emerald-600' : 'text-red-600'">
                                        <span x-text="t.dalam ? 'Tepat waktu' : 'Melewati SLA'"></span>:
                                        selesai dalam <span x-text="t.total"></span> · batas <span x-text="t.sla"></span> jam
                                    </p>
                                </li>
                            </template>
                        </ul>
                    </div>

                    <div class="flex items-center justify-between px-4 py-2 border-t bg-slate-50 text-xs text-slate-500" x-show="tampil.length > perHal">
                        <button type="button" @click="hal--" :disabled="hal === 0" class="px-2 py-1 rounded border bg-white disabled:opacity-40">‹ Sebelumnya</button>
                        <span>Hal <span x-text="hal + 1"></span> / <span x-text="jmlHal"></span></span>
                        <button type="button" @click="hal++" :disabled="hal >= jmlHal - 1" class="px-2 py-1 rounded border bg-white disabled:opacity-40">Berikutnya ›</button>
                    </div>
                </div>
            </template>
        </aside>
    </div>
</div>

<script>
function grafikHelp() {
    return {
        data: JSON.parse(document.getElementById('data-rincian').textContent || '[]'),
        panel: null, pilih: null, cari: '', hal: 0, perHal: 10,

        get tampil() {
            if (!this.panel) return [];
            const c = this.panel.chips.find(x => x.k === this.panel.aktif);
            const q = this.cari.trim().toLowerCase();
            return this.panel.sumber.filter(c.f).filter(t =>
                !q || (t.n + ' ' + t.j + ' ' + t.admin.join(' ')).toLowerCase().includes(q));
        },
        get jmlHal()  { return Math.max(1, Math.ceil(this.tampil.length / this.perHal)); },
        get halaman() { return this.tampil.slice(this.hal * this.perHal, (this.hal + 1) * this.perHal); },

        tutup() { this.panel = null; this.pilih = null; },
        buka(kunci, panel) { this.pilih = kunci; this.panel = panel; this.cari = ''; this.hal = 0; },

        bukaBU(id, nama) {
            const s = this.data.filter(t => t.bu === id);
            const esk = s.filter(t => t.esk).length;
            this.buka('bu:' + id, {
                tipe: 'bu', judul: nama, sumber: s,
                ringkas: esk + ' dari ' + s.length + ' tiket dialihkan ke GA Corp (' + (s.length ? Math.round(esk / s.length * 100) : 0) + '%)',
                chips: [
                    { k: 'esk', l: 'Dialihkan ke GA Corp', f: t => t.esk },
                    { k: 'semua', l: 'Semua tiket', f: () => true },
                ],
                aktif: esk ? 'esk' : 'semua',
            });
        },

        bukaUnit(id, nama) {
            const s = this.data.filter(t => t.bu === id);
            const sel = s.filter(t => t.unit).length;
            this.buka('unit:' + id, {
                tipe: 'unit', judul: nama, sumber: s,
                ringkas: sel + ' dari ' + s.length + ' tiket selesai di unit sendiri (' + (s.length ? Math.round(sel / s.length * 100) : 0) + '%)',
                chips: [
                    { k: 'unit', l: 'Selesai di unit', f: t => t.unit },
                    { k: 'semua', l: 'Semua tiket', f: () => true },
                ],
                aktif: sel ? 'unit' : 'semua',
            });
        },

        bukaSla(id, nama) {
            const s = this.data.filter(t => t.kt === id && t.dalam !== null);
            const dalam = s.filter(t => t.dalam).length;
            const telat = s.length - dalam;
            this.buka('sla:' + id, {
                tipe: 'sla', judul: nama, sumber: s,
                ringkas: dalam + ' dari ' + s.length + ' tiket tepat waktu (' + (s.length ? Math.round(dalam / s.length * 100) : 0) + '%)',
                chips: [
                    { k: 'telat', l: 'Melewati SLA', f: t => !t.dalam },
                    { k: 'tepat', l: 'Tepat waktu', f: t => t.dalam },
                    { k: 'semua', l: 'Semua tiket', f: () => true },
                ],
                aktif: telat ? 'telat' : 'semua',
            });
        },

        bukaAdmin(nama) {
            const s = this.data.filter(t => t.pr && t.admin.includes(nama));
            const esk = s.filter(t => t.esk).length;
            this.buka('admin:' + nama, {
                tipe: 'admin', judul: nama, sumber: s,
                ringkas: s.length + ' tiket ditangani · ' + esk + ' pernah diteruskan ke GA Corp',
                chips: [
                    { k: 'semua', l: 'Semua tiket', f: () => true },
                    { k: 'jalan', l: 'Belum selesai', f: t => !t.done },
                    { k: 'esk', l: 'Dialihkan ke GA Corp', f: t => t.esk },
                ],
                aktif: 'semua',
            });
        },

        bukaUmur(kunci, nama) {
            const s = this.data.filter(t => t.um === kunci);
            this.buka('umur:' + kunci, {
                tipe: 'umur', judul: 'Belum ditutup · ' + nama, sumber: s,
                ringkas: s.length + ' tiket',
                chips: [{ k: 'semua', l: 'Semua tiket', f: () => true }],
                aktif: 'semua',
            });
        },

        bukaClose(kunci, nama) {
            const s = this.data.filter(t => t.uc === kunci);
            this.buka('close:' + kunci, {
                tipe: 'close', judul: 'Ditutup · ' + nama, sumber: s,
                ringkas: s.length + ' tiket',
                chips: [{ k: 'semua', l: 'Semua tiket', f: () => true }],
                aktif: 'semua',
            });
        },
    };
}
</script>
@endsection