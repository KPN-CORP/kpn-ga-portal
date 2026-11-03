{{--
    resources/views/help_p/partials/grafik-kalender.blade.php

    Timeline Gantt dengan kolom tanggal kalender (posisi & panjang bar = jam / tanggal asli tiket).

    Parameter:
      tikets      : Collection<HelpTiket>  (eager-load: bisnisUnit, ditugaskanKe, logStatus.pengguna, vendorProses)
      rentang     : HelpTimeline::rentangBulan() / rentangTahun() / rentangTiket()   (wajib)
      judul       : judul kartu
      mode        : 'internal' (default) | 'vendor'
      routeDetail : nama route detail tiket (default 'help.proses.show')
      kosong      : teks bila tidak ada tiket (null = kartu disembunyikan)
      legenda     : true/false (default true)

    Mode internal: satu tiket = judul + baris-baris fase, masing-masing dengan batangnya sendiri.
    Urutan dari ATAS ke BAWAH = terbaru ke terlama:
        Close  →  admin terakhir  →  Over GA Corp (menunggu)  →  admin pertama  →  Pengajuan
    Jadi admin pertama yang mengambil ada di bawah, admin berikutnya di atasnya.

    Warna fase:
      abu = Pengajuan | warna Bisnis Unit = diproses | kuning = Waiting | teal = Over ke GA Corp (menunggu)
      coklat = diproses GA Corp | biru muda = selesai, menunggu close | garis merah = lewat 7 hari
      blok Close: biru ≤ 7 hari, merah > 7 hari | ungu = pengerjaan vendor
--}}
@php
    $HT = \App\Support\help_s\HelpTimeline::class;

    $mode        = $mode ?? 'internal';
    $judul       = $judul ?? ($mode === 'vendor' ? 'Pengerjaan vendor' : 'Timeline tiket');
    $routeDetail = $routeDetail ?? 'help.proses.show';
    $kosong      = $kosong ?? null;
    $legenda     = $legenda ?? true;
    $r           = $rentang;          // rentang terpilih (1 bulan / 1 tahun)
    $r0          = $rentang;
    $bulanPenuh0 = $r0['awal']->day === 1 && $r0['awal']->isSameMonth($r0['akhir']) && $r0['akhir']->isSameDay($r0['awal']->copy()->endOfMonth());
    // Bulan penuh: tampilkan juga bulan sebelum & sesudahnya (abu-abu) supaya bisa digeser dengan scrollbar
    $ext = ($tampilLebar ?? true) && $bulanPenuh0 && ($r0['jenis'] ?? '') === 'hari';
    if ($ext) { $r = \App\Support\help_s\HelpTimeline::rentangTampil($r0); }
    $lk  = $ext ? $r['lebarKiri'] . 'px' : '50%';                       // lebar kolom judul
    $stk = $ext ? 'sticky left-0 z-10 bg-white' : '';                   // kolom judul tetap saat digeser

    $warnaState = [
        'OPEN'         => 'bg-gray-400',
        'WAITING'      => 'bg-amber-400',
        'HELP_GA_CORP' => 'bg-teal-500',
        'DONE'         => 'bg-sky-300',
    ];
    $labelFase = [
        'OPEN' => 'Pengajuan (belum diambil)', 'ON_PROCESS' => 'Diambil / diproses', 'WAITING' => 'Waiting',
        'HELP_GA_CORP' => 'Over ke GA Corp (menunggu)', 'ON_PROCESS_CORP' => 'Diproses GA Corp', 'DONE' => 'Selesai, menunggu close',
    ];
    $chipStatus = [
        'OPEN' => 'bg-yellow-100 text-yellow-800', 'ON_PROCESS' => 'bg-blue-100 text-blue-800',
        'WAITING' => 'bg-orange-100 text-orange-800', 'DONE' => 'bg-green-100 text-green-800',
        'CLOSED' => 'bg-green-100 text-green-800', 'HELP_GA_CORP' => 'bg-slate-100 text-slate-700',
    ];

    // 1 hari = 24 jam penuh; sisanya dalam jam (mis. "2 hari 5 jam")
    $fmt    = fn ($d) => $HT::formatDurasi((int) $d);
    $jamTrx = fn ($ts) => \App\Support\help_s\HelpTimeline::wib($ts, 'd/m H:i');

    // warna fase diproses: ikut warna Business Unit tiket; setelah di-over -> warna Corporation
    $warnaFase = fn ($state, $warnaBU) => match ($state) {
        'ON_PROCESS'      => $warnaBU,
        'ON_PROCESS_CORP' => $HT::WARNA_CORP,
        default           => null,
    };

    // Susun daftar tiket
    $baris = [];
    foreach (collect($tikets ?? []) as $t) {
        if ($mode === 'vendor') {
            foreach ($t->vendorProses as $v) {
                if ($bar = $HT::barVendor($v, $r)) { $baris[] = [$t, $bar, $v]; }
            }
        } elseif ($bar = $HT::barTiket($t, $r)) {
            $baris[] = [$t, $bar, null];
        }
    }

    // nama pelaku (pengaju, yang meng-over, yang menutup): satu query untuk seluruh baris
    $idPelaku = [];
    foreach ($baris as [$t, $bar, $v]) {
        foreach (($bar['baris'] ?? []) as $fr) { if (!empty($fr['oleh'])) { $idPelaku[$fr['oleh']] = true; } }
        if (!empty($bar['tutup']['oleh'])) { $idPelaku[$bar['tutup']['oleh']] = true; }
    }
    $namaPelaku = $idPelaku
        ? \App\Models\Pelanggan::whereIn('id_pelanggan', array_keys($idPelaku))->pluck('nama_pelanggan', 'id_pelanggan')
        : collect();
    $nama = fn ($id) => $id ? ($namaPelaku[$id] ?? null) : null;

    // Navigasi periode (tombol ◀ ▶): 1 bulan penuh = bulan sebelum/sesudah, 1 tahun penuh = tahun sebelum/sesudah,
    // rentang bebas = geser sepanjang rentang itu.
    $navPrevUrl = $navNextUrl = null;
    if (($navigasi ?? true)) {
        $aw = $r0['awal']->copy()->startOfDay(); $ak = $r0['akhir']->copy()->startOfDay();
        $tahunPenuh = $aw->month === 1 && $aw->day === 1 && $ak->month === 12 && $ak->day === 31 && $aw->year === $ak->year;
        if ($bulanPenuh0) {
            $pa = $aw->copy()->subMonthNoOverflow()->startOfMonth(); $pb = $pa->copy()->endOfMonth()->startOfDay();
            $na = $aw->copy()->addMonthNoOverflow()->startOfMonth(); $nb = $na->copy()->endOfMonth()->startOfDay();
            $navLabelMundur = 'Bulan sebelumnya'; $navLabelMaju = 'Bulan berikutnya';
        } elseif ($tahunPenuh) {
            $pa = $aw->copy()->subYear(); $pb = $ak->copy()->subYear();
            $na = $aw->copy()->addYear(); $nb = $ak->copy()->addYear();
            $navLabelMundur = 'Tahun sebelumnya'; $navLabelMaju = 'Tahun berikutnya';
        } else {
            $n  = (int) round(($ak->timestamp - $aw->timestamp) / 86400) + 1;
            $pa = $aw->copy()->subDays($n); $pb = $aw->copy()->subDay();
            $na = $ak->copy()->addDay();    $nb = $ak->copy()->addDays($n);
            $navLabelMundur = 'Periode sebelumnya'; $navLabelMaju = 'Periode berikutnya';
        }
        $url = fn ($x, $y) => request()->fullUrlWithQuery(['dari' => $x->format('Y-m-d'), 'sampai' => $y->format('Y-m-d'), 'tahun' => null, 'bulan' => null]);
        if ($pa->year >= 2000) { $navPrevUrl = $url($pa, $pb); }
        if ($na->year <= now()->year + 1) { $navNextUrl = $url($na, $nb); }
    }
    $bisaGeser = $navPrevUrl || $navNextUrl;

    $gridCols = collect($r['kolom'])->map(fn ($k) => 'minmax(0,' . $k['hari'] . 'fr)')->implode(' ');
@endphp

@if(empty($baris) && !$kosong)
@else
{{-- contain: inline-size = lebar kartu TIDAK ikut lebar isi grafik (3 bulan), jadi yang bergeser hanya isi kartu ini, bukan halaman --}}
<div class="bg-white rounded-xl border border-gray-200 p-4 w-full min-w-0 max-w-full overflow-hidden" style="contain: inline-size;">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <h3 class="font-semibold text-gray-800 flex items-center">
            <i class="fas {{ $mode === 'vendor' ? 'fa-truck text-purple-500' : 'fa-calendar-week text-blue-500' }} mr-2"></i>{{ $judul }}
        </h3>
        <span class="text-xs text-gray-500 flex items-center gap-2">
            @if($bisaGeser)
                @if($navPrevUrl)
                    <a href="{{ $navPrevUrl }}" class="px-2 py-1 rounded bg-gray-100 text-gray-700 hover:bg-gray-200 font-semibold" title="{{ $navLabelMundur }}">◀</a>
                @else
                    <span class="px-2 py-1 rounded bg-gray-50 text-gray-300">◀</span>
                @endif
            @endif
            <span>
                {{ $r0['awal']->copy()->locale('id')->isoFormat('D MMM Y') }} – {{ $r0['akhir']->copy()->locale('id')->isoFormat('D MMM Y') }}
                · {{ count($baris) }} {{ $mode === 'vendor' ? 'pengerjaan' : 'tiket' }}
            </span>
            @if($bisaGeser)
                @if($navNextUrl)
                    <a href="{{ $navNextUrl }}" class="px-2 py-1 rounded bg-gray-100 text-gray-700 hover:bg-gray-200 font-semibold" title="{{ $navLabelMaju }}">▶</a>
                @else
                    <span class="px-2 py-1 rounded bg-gray-50 text-gray-300">▶</span>
                @endif
            @endif
        </span>
    </div>

    @if(empty($baris))
        <p class="text-sm text-gray-500 py-4 text-center">{{ $kosong }}</p>
    @else
    <div class="w-full max-w-full min-w-0 overflow-x-auto {{ $ext ? 'overflow-y-auto cursor-grab' : '' }}"
         @if($ext) style="max-height: 75vh;" data-gantt-geser data-scroll-hari="{{ max(0, $r['idxAwal'] - 3) }}" data-lebar-hari="{{ $r['lebarKolom'] }}" title="Tarik atau pakai scrollbar di bawah untuk geser kiri / kanan (bulan sebelum & sesudah)" @endif>
        <div style="min-width: {{ $ext ? $r['minWidth'] . 'px' : '720px' }};">

            {{-- Header: nama bulan (hanya mode geser) + tanggal; tetap di atas saat digulung ke bawah --}}
            <div class="{{ $ext ? 'sticky top-0 z-20 bg-white' : '' }}">
                @if($ext)
                    <div class="flex">
                        <div class="shrink-0 {{ $stk }} z-30" style="width: {{ $lk }};"></div>
                        <div class="flex-1 grid" style="grid-template-columns: {{ $gridCols }};">
                            @foreach($r['grup'] as $g)
                                <div class="text-center text-xs py-1 border-l border-gray-300 overflow-hidden whitespace-nowrap {{ $g['aktif'] ? 'bg-yellow-100 text-gray-900 font-semibold' : 'bg-gray-200 text-gray-500' }}"
                                     style="grid-column: span {{ $g['span'] }};">{{ $g['label'] }}</div>
                            @endforeach
                        </div>
                    </div>
                @endif
                <div class="flex">
                    <div class="shrink-0 {{ $stk }} z-30" style="width: {{ $lk }};"></div>
                    <div class="flex-1 grid border-b border-gray-200" style="grid-template-columns: {{ $gridCols }};">
                        @foreach($r['kolom'] as $k)
                            @php
                                $nonAktif = $ext && !$k['aktif'];
                            @endphp
                            <div class="text-center py-1 border-l border-gray-200 overflow-hidden {{ $k['ini'] ? 'bg-blue-100 text-blue-800 font-semibold' : ($nonAktif ? 'bg-gray-200 text-gray-500' : ($k['libur'] ? 'bg-gray-50 text-gray-400' : ($ext ? 'bg-white text-gray-700' : 'text-gray-500'))) }}"
                                 title="{{ $k['title'] }}">
                                <div class="{{ $r['jenis'] === 'hari' ? 'text-[10px]' : 'text-xs' }} leading-tight">{{ $k['label'] }}</div>
                                @if($k['sub'] !== '')<div class="text-[10px] leading-tight">{{ $k['sub'] }}</div>@endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="relative">
                {{-- Garis kolom: SATU overlay untuk seluruh baris --}}
                <div class="absolute inset-y-0 right-0 grid pointer-events-none" style="left: {{ $lk }}; grid-template-columns: {{ $gridCols }};">
                    @foreach($r['kolom'] as $k)
                        <div class="border-l border-gray-100 {{ $k['ini'] ? 'bg-blue-50' : (($ext && !$k['aktif']) ? 'bg-gray-100' : ($k['libur'] ? 'bg-gray-50' : '')) }}"></div>
                    @endforeach
                    @if($r['sekarang'] !== null)
                        <div class="absolute inset-y-0 w-px bg-blue-400" style="left: {{ $r['sekarang'] }}%;"></div>
                    @endif
                </div>

                @foreach($baris as [$t, $bar, $v])
                    @php
                        $bu      = $t->bisnisUnit;
                        $warnaBU = $HT::infoBisnisUnit(optional($bu)->nama_bisnis_unit)['warna'];
                        $pic     = optional($t->ditugaskanKe)->nama_pelanggan;
                    @endphp

                    @if($mode === 'vendor')
                        {{-- ====== VENDOR: satu batang per pengerjaan ====== --}}
                        @php
                            $teks  = ($bar['kiriPotong'] ? '← ' : '') . $bar['durasi'] . ($bar['kananPotong'] ? ' →' : '');
                            $dalam = $bar['lebar'] >= 7;
                            $tip   = \App\Support\help_s\HelpTimeline::wib($bar['mulai']) . ' → ' . ($bar['berjalan'] ? 'sekarang' : \App\Support\help_s\HelpTimeline::wib($bar['selesai']));
                        @endphp
                        <div class="relative flex border-b border-gray-100">
                            <div style="width: {{ $lk }};" class="shrink-0 {{ $stk }} pr-3 py-2.5">
                                <a href="{{ route($routeDetail, $t) }}" class="block text-sm font-medium text-gray-900 hover:underline break-words"
                                   title="{{ $t->nomor_tiket }} {{ $t->judul }}">{{ $t->nomor_tiket }} {{ $t->judul }}</a>
                                <div class="mt-0.5 flex items-center gap-1.5 text-xs text-gray-500">
                                    <span class="inline-block w-2 h-2 rounded-full shrink-0" style="background: {{ $warnaBU }}" title="{{ optional($bu)->nama_bisnis_unit }}"></span>
                                    <span class="truncate">{{ \Illuminate\Support\Str::limit($v->nama_vendor, 18) }}</span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $bar['berjalan'] ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-700' }}">{{ $bar['berjalan'] ? 'DIKERJAKAN' : 'SELESAI' }}</span>
                                </div>
                            </div>
                            <div class="flex-1 relative min-h-[48px]">
                                <div class="absolute top-1/2 transform -translate-y-1/2 h-7 rounded-full overflow-hidden flex shadow-sm bg-purple-500"
                                     style="left: {{ $bar['kiri'] }}%; width: {{ $bar['lebar'] }}%; min-width: 6px;" title="{{ $tip }}">
                                    @if($dalam)
                                        <span class="absolute inset-0 flex items-center justify-center text-xs font-semibold text-white whitespace-nowrap" style="text-shadow: 0 1px 2px rgba(0,0,0,.45);">{{ $teks }}</span>
                                    @endif
                                </div>
                                @unless($dalam)
                                    <span class="absolute top-1/2 transform -translate-y-1/2 text-xs font-medium text-gray-700 whitespace-nowrap"
                                          style="{{ ($bar['kiri'] + $bar['lebar']) > 80 ? 'right: calc(' . (100 - $bar['kiri']) . '% + 6px);' : 'left: calc(' . ($bar['kiri'] + $bar['lebar']) . '% + 6px);' }}">{{ $teks }}</span>
                                @endunless
                            </div>
                        </div>
                    @else
                        {{-- ====== INTERNAL: judul + baris fase (terbaru di atas) ====== --}}
                        @php
                            $t7         = $bar['mulai']->timestamp + $HT::BATAS_HARI * 86400;
                            $fr         = array_reverse($bar['baris'] ?? []);          // atas = terbaru
                            $bad        = ($bar['hasil'] ?? null) === 'bad';
                            $warnaClose = $bad ? '#dc2626' : '#2f5597';
                            $nmTutup    = $nama($bar['tutup']['oleh'] ?? null);
                        @endphp
                        <div class="relative border-b border-gray-200 py-1">

                            {{-- Judul tiket --}}
                            <div class="flex">
                                <div style="width: {{ $lk }};" class="shrink-0 {{ $stk }} pr-3 pt-1.5 pb-0.5">
                                    <a href="{{ route($routeDetail, $t) }}" class="block text-sm font-medium text-blue-700 underline hover:text-blue-900 break-words"
                                       title="{{ $t->nomor_tiket }} {{ $t->judul }}">{{ $t->nomor_tiket }} {{ $t->judul }}</a>
                                    <div class="mt-0.5 flex flex-wrap items-center gap-x-1.5 gap-y-0.5 text-xs text-gray-600">
                                        <span class="inline-block w-2 h-2 rounded-full shrink-0" style="background: {{ $warnaBU }}" title="{{ optional($bu)->nama_bisnis_unit }}"></span>
                                        @if($pic)<span class="truncate">{{ \Illuminate\Support\Str::limit($pic, 22) }}</span>@endif
                                        <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $chipStatus[$t->status] ?? 'bg-gray-100 text-gray-700' }}">{{ str_replace('_', ' ', $t->status) }}</span>
                                        <span class="text-[10px] text-gray-500">total {{ $bar['durasi'] }}</span>
                                        @if($bar['lewat'])<span class="text-[10px] text-red-600 font-medium">· lewat {{ $bar['lebih'] }}</span>@endif
                                    </div>
                                </div>
                                <div class="flex-1"></div>
                            </div>

                            {{-- Baris Close (paling atas) --}}
                            @if(!empty($bar['tutup']))
                                @php
                                    $posC  = $HT::posisi((int) $bar['tutup']['mulai'], (int) $bar['tutup']['mulai'], $r);
                                    $kananC = $posC && ($posC['kiri'] + $posC['lebar']) > 94;
                                @endphp
                                <div class="flex">
                                    <div style="width: {{ $lk }};" class="shrink-0 {{ $stk }} pr-3 py-0.5 text-[11px] leading-snug text-gray-500">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full mr-0.5" style="background: {{ $warnaClose }}"></span>
                                        <span class="font-medium text-gray-600">Close</span>
                                        <span class="text-gray-400">· @if($nmTutup)oleh {{ $nmTutup }} · @endif{{ $jamTrx($bar['tutup']['mulai']) }}</span>
                                    </div>
                                    <div class="flex-1 relative min-h-[26px]">
                                        @if($posC)
                                            <div class="absolute top-1/2 transform -translate-y-1/2 h-5 rounded flex items-center justify-center text-white text-[10px] shadow-sm"
                                                 style="{{ $kananC ? 'right: ' . round(100 - $posC['kiri'] - $posC['lebar'], 4) . '%;' : 'left: ' . $posC['kiri'] . '%;' }} width: {{ $posC['lebar'] }}%; min-width: 28px; background: {{ $warnaClose }};"
                                                 title="{{ $bad ? 'Closed lebih dari 7 hari' : 'Closed ≤ 7 hari' }} · {{ $jamTrx($bar['tutup']['mulai']) }}">
                                                <i class="fas {{ $bad ? 'fa-xmark' : 'fa-check' }}"></i>
                                            </div>
                                            {{-- jam close tampil langsung di grafik --}}
                                            <span class="absolute top-1/2 transform -translate-y-1/2 text-[11px] font-medium whitespace-nowrap"
                                                  style="color: {{ $warnaClose }}; {{ $kananC ? 'right: calc(' . round(100 - $posC['kiri'] - $posC['lebar'], 4) . '% + 34px);' : 'left: calc(' . $posC['kiri'] . '% + 34px);' }}">total {{ $bar['durasi'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Baris fase: admin terakhir ... admin pertama, Pengajuan paling bawah --}}
                            @foreach($fr as $row)
                                @php
                                    $pos     = $HT::posisi($row['a'], $row['b'], $r);
                                    $jenis   = $row['jenis'];
                                    $kanan   = $pos && ($pos['kiri'] + $pos['lebar']) > 88;
                                    $teksBar = !$pos ? '' : ($pos['kiriPotong'] ? '← ' : '') . $fmt($row['detik']) . ($pos['kananPotong'] ? ' →' : '');
                                    $dalam   = $pos && $pos['lebar'] >= 8;
                                    $hatch   = (in_array($jenis, ['proses', 'over'], true) && $row['b'] > $t7)
                                                ? $HT::posisi(max($row['a'], $t7), $row['b'], $r) : null;
                                    $judulRow = ['ajuan' => 'Pengajuan', 'over' => 'Over GA Corp', 'alih' => 'Dialihkan', 'proses' => $row['nama'] ?? 'Proses'][$jenis];
                                    $tipRow   = $judulRow . ' · ' . $jamTrx($row['a']) . ($row['b'] > $row['a'] ? ' → ' . $jamTrx($row['b']) : '');
                                    // Dialihkan: lama admin tujuan memegang tugas (dialihkan -> diambil / sekarang)
                                    $posPegang = null;
                                    if ($jenis === 'alih') {
                                        $durPegang  = $row['pegang'] > 0 ? $fmt($row['pegang']) : 'langsung diambil';
                                        $textPegang = ($row['pegang'] > 0 && !$row['diambil'] ? 'belum diambil ' : '') . $durPegang;
                                        $tipRow    .= ' · ' . $textPegang . ($row['diambil'] ? ' sebelum diambil ' . $jamTrx($row['sampai']) : '');
                                        $posPegang  = $row['pegang'] > 0 ? $HT::posisi($row['a'], $row['sampai'], $r) : null;
                                    }
                                    $dotRow   = $jenis === 'alih' ? '#9333ea'
                                              : ($jenis === 'ajuan' ? '#9ca3af'
                                              : ($jenis === 'over' ? '#14b8a6'
                                              : ($warnaFase($row['bagian'][0]['state'] ?? 'ON_PROCESS', $warnaBU) ?? $warnaBU)));
                                @endphp
                                <div class="flex">
                                    <div style="width: {{ $lk }};" class="shrink-0 {{ $stk }} pr-3 py-0.5 text-[11px] leading-snug text-gray-500">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full mr-0.5" style="background: {{ $dotRow }}"></span>
                                        @if($jenis === 'proses')
                                            <span class="font-semibold text-gray-700">{{ $row['nama'] }}</span>
                                            <span class="text-gray-600">· Proses {{ $fmt($row['detik']) }}</span>
                                            <span class="text-gray-400">· {{ !empty($row['dari_alih']) ? 'menerima pengalihan' : 'diambil' }} {{ $jamTrx($row['a']) }}
                                                @if($row['waiting'] > 0)· waiting {{ $fmt($row['waiting']) }}@endif
                                                @if($row['selesai'])· selesai {{ $jamTrx($row['selesai']) }}@endif
                                            </span>
                                            @if(!empty($row['alih_ke']))
                                                <span class="text-purple-600 font-medium">· dialihkan ke {{ $row['alih_ke'] }}</span>
                                            @endif
                                        @elseif($jenis === 'alih')
                                            <span class="font-semibold text-purple-700">Dialihkan</span>
                                            <span class="text-gray-600">· {{ $row['nama'] ?? '?' }} → {{ $row['ke'] }}</span>
                                            <span class="text-gray-400">· @if($nama($row['oleh']))oleh {{ $nama($row['oleh']) }} · @endif{{ $jamTrx($row['a']) }}@if(!empty($row['alasan'])) · alasan: {{ \Illuminate\Support\Str::limit($row['alasan'], 60) }}@endif</span>
                                        @elseif($jenis === 'over')
                                            <span class="font-medium text-gray-600">Over GA Corp</span> · menunggu {{ $fmt($row['detik']) }}
                                            <span class="text-gray-400">· @if($nama($row['oleh']))di-over oleh {{ $nama($row['oleh']) }} · @endif{{ $jamTrx($row['a']) }}</span>
                                        @else
                                            <span class="font-medium text-gray-600">Pengajuan</span> {{ $fmt($row['detik']) }}
                                            <span class="text-gray-400">· @if($nama($row['oleh']))diajukan {{ $nama($row['oleh']) }} · @endif{{ $jamTrx($row['a']) }}</span>
                                        @endif
                                    </div>

                                    <div class="flex-1 relative min-h-[26px]">
                                        @if($jenis === 'alih')
                                            @if($pos)
                                                {{-- bar lama dipegang: dari dialihkan sampai diambil --}}
                                                @if($posPegang)
                                                    <div class="absolute top-1/2 transform -translate-y-1/2 h-4 rounded bg-purple-200 border border-purple-400"
                                                         style="left: {{ $posPegang['kiri'] }}%; width: {{ $posPegang['lebar'] }}%; min-width: 6px;"
                                                         title="{{ $tipRow }}">
                                                    </div>
                                                @endif
                                                <div class="absolute top-1/2 w-3 h-3 bg-purple-600 border border-white shadow-sm"
                                                     style="left: calc({{ $pos['kiri'] }}% - 6px); transform: translateY(-50%) rotate(45deg);"
                                                     title="{{ $tipRow }}"></div>
                                                {{-- jam dialihkan + lama dipegang langsung di grafik --}}
                                                @php
                                                    $ujung   = $posPegang ? ($posPegang['kiri'] + $posPegang['lebar']) : $pos['kiri'];
                                                    $teksAlh = $textPegang;
                                                @endphp
                                                <span class="absolute top-1/2 transform -translate-y-1/2 text-[11px] font-medium text-purple-700 whitespace-nowrap"
                                                      style="{{ $ujung > 60 ? 'right: calc(' . (100 - $pos['kiri']) . '% + 12px);' : 'left: calc(' . $ujung . '% + 10px);' }}">{{ $teksAlh }}</span>
                                            @endif
                                        @elseif($pos)
                                            <div class="absolute top-1/2 transform -translate-y-1/2 h-5 rounded overflow-hidden flex shadow-sm"
                                                 style="{{ $kanan ? 'right: ' . round(100 - $pos['kiri'] - $pos['lebar'], 4) . '%;' : 'left: ' . $pos['kiri'] . '%;' }} width: {{ $pos['lebar'] }}%; min-width: 8px;"
                                                 title="{{ $tipRow }}">
                                                @foreach($row['bagian'] as $f)
                                                    @php
                                                        $ov = max(0, min($f['akhir'], $pos['vb']) - max($f['mulai'], $pos['va']));
                                                        $wf = $warnaFase($f['state'], $warnaBU);
                                                    @endphp
                                                    @if($ov > 0)
                                                        <div class="{{ $wf ? '' : ($warnaState[$f['state']] ?? 'bg-gray-400') }}"
                                                             style="width: {{ round($ov / max(1, $pos['vb'] - $pos['va']) * 100, 2) }}%;{{ $wf ? ' background: ' . $wf : '' }}"
                                                             title="{{ $labelFase[$f['state']] ?? $f['state'] }} · {{ $fmt($f['akhir'] - $f['mulai']) }} · {{ $jamTrx($f['mulai']) }}"></div>
                                                    @endif
                                                @endforeach
                                                @if($dalam)
                                                    <span class="absolute inset-0 flex items-center justify-center text-[11px] font-semibold text-white whitespace-nowrap" style="text-shadow: 0 1px 2px rgba(0,0,0,.45);">{{ $teksBar }}</span>
                                                @endif
                                            </div>

                                            @if($hatch)
                                                <div class="absolute top-1/2 transform -translate-y-1/2 h-5 pointer-events-none" title="Lewat batas 7 hari"
                                                     style="left: {{ $hatch['kiri'] }}%; width: {{ $hatch['lebar'] }}%; min-width: 4px; background: repeating-linear-gradient(45deg, rgba(220,38,38,.55), rgba(220,38,38,.55) 6px, rgba(153,27,27,.65) 6px, rgba(153,27,27,.65) 12px);"></div>
                                            @endif

                                            @if(!$dalam && $teksBar !== '')
                                                <span class="absolute top-1/2 transform -translate-y-1/2 text-[11px] font-medium text-gray-700 whitespace-nowrap"
                                                      style="{{ ($pos['kiri'] + $pos['lebar']) > 80 ? 'right: calc(' . (100 - $pos['kiri']) . '% + 6px);' : 'left: calc(' . ($pos['kiri'] + $pos['lebar']) . '% + 6px);' }}">{{ $teksBar }}</span>
                                            @endif
                                        @elseif($row['b'] <= $r['awal']->timestamp)
                                            {{-- fase ini selesai SEBELUM periode yang ditampilkan (mis. pengajuan di bulan lalu):
                                                 tetap diberi penanda di tepi kiri supaya grafik pengajuan terlihat --}}
                                            <div class="absolute left-0 top-1/2 transform -translate-y-1/2 h-5 w-2 rounded-r"
                                                 style="background: {{ $dotRow }}" title="{{ $tipRow }}"></div>
                                            <span class="absolute top-1/2 transform -translate-y-1/2 text-[11px] font-medium text-gray-700 whitespace-nowrap"
                                                  style="left: 14px;" title="{{ $tipRow }}">← {{ $fmt($row['detik']) }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    @if($legenda)
        @php
            $buAda = collect($baris)->map(fn ($b) => $b[0]->bisnisUnit)->filter()->unique(fn ($b) => $b->id_bisnis_unit)->values();
        @endphp
        <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-600">
            @if($mode === 'vendor')
                <span class="inline-flex items-center"><span class="w-2.5 h-2.5 rounded-sm bg-purple-500 mr-1.5"></span>Dikerjakan vendor</span>
            @else
                <span class="inline-flex items-center"><span class="w-2.5 h-2.5 rounded-sm bg-gray-400 mr-1.5"></span>Pengajuan</span>
                <span class="inline-flex items-center">Diproses:
                    @foreach($buAda as $b)
                        <span class="inline-flex items-center ml-2"><span class="w-2.5 h-2.5 rounded-sm mr-1" style="background: {{ $HT::infoBisnisUnit($b->nama_bisnis_unit)['warna'] }}"></span>{{ $b->nama_bisnis_unit }}</span>
                    @endforeach
                </span>
                <span class="inline-flex items-center"><span class="w-2.5 h-2.5 rounded-sm bg-amber-400 mr-1.5"></span>Waiting</span>
                <span class="inline-flex items-center"><span class="w-2.5 h-2.5 bg-purple-600 mr-1.5" style="transform: rotate(45deg)"></span>Dialihkan ke admin lain</span>
                <span class="inline-flex items-center"><span class="w-4 h-2.5 rounded-sm bg-purple-200 border border-purple-400 mr-1.5"></span>Dialihkan → diambil</span>
                <span class="inline-flex items-center"><span class="w-2.5 h-2.5 rounded-sm bg-teal-500 mr-1.5"></span>Over GA Corp (menunggu)</span>
                <span class="inline-flex items-center"><span class="w-2.5 h-2.5 rounded-sm mr-1.5" style="background: {{ $HT::WARNA_CORP }}"></span>Diproses GA Corp</span>
                <span class="inline-flex items-center"><span class="w-2.5 h-2.5 rounded-sm bg-sky-300 mr-1.5"></span>Selesai, menunggu close</span>
                <span class="inline-flex items-center"><span class="w-2.5 h-2.5 rounded-sm mr-1.5" style="background: repeating-linear-gradient(45deg,#dc2626,#dc2626 3px,#991b1b 3px,#991b1b 6px)"></span>Lewat 7 hari</span>
                <span class="inline-flex items-center"><span class="w-3 h-3 rounded-sm mr-1.5 flex items-center justify-center text-white text-[8px]" style="background:#2f5597"><i class="fas fa-check"></i></span>Close ≤ 7 hari</span>
                <span class="inline-flex items-center"><span class="w-3 h-3 rounded-sm bg-red-600 mr-1.5 flex items-center justify-center text-white text-[8px]"><i class="fas fa-xmark"></i></span>Close &gt; 7 hari</span>
                <span class="inline-flex items-center text-gray-500">Tiap admin punya baris sendiri: admin pertama di bawah, berikutnya di atasnya</span>
            @endif
            <span class="inline-flex items-center"><span class="w-px h-3 bg-blue-400 mr-1.5"></span>Sekarang</span>
        </div>
    @endif
    @endif
</div>
@endif

@once
<script>
// Grafik bulan: scrollbar di bawah untuk geser kiri/kanan; bisa juga ditarik dengan mouse.
(function () {
    function posisiAwal() {
        document.querySelectorAll('[data-gantt-geser][data-scroll-hari]').forEach(function (c) {
            if (c.dataset.sudah) return;
            c.dataset.sudah = '1';
            c.scrollLeft = (parseInt(c.dataset.scrollHari, 10) || 0) * (parseInt(c.dataset.lebarHari, 10) || 30);
        });
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', posisiAwal); } else { posisiAwal(); }
    window.addEventListener('load', posisiAwal);

    var st = null;
    document.addEventListener('mousedown', function (e) {
        if (e.button !== 0) return;
        var c = e.target.closest('[data-gantt-geser]');
        // abaikan klik di scrollbar (tepi kanan / bawah) dan elemen interaktif
        if (!c || e.target.closest('a, button, input, select')) return;
        var r = c.getBoundingClientRect();
        if (e.clientX > r.left + c.clientWidth || e.clientY > r.top + c.clientHeight) return;
        st = { c: c, x: e.clientX, left: c.scrollLeft, dx: 0 };
        c.classList.remove('cursor-grab'); c.classList.add('cursor-grabbing', 'select-none');
    });
    document.addEventListener('mousemove', function (e) {
        if (!st) return;
        st.dx = e.clientX - st.x;
        st.c.scrollLeft = st.left - st.dx;
        window.__ganttDx = st.dx;
        e.preventDefault();
    });
    function selesai() {
        if (!st) return;
        st.c.classList.remove('cursor-grabbing', 'select-none'); st.c.classList.add('cursor-grab');
        st = null;
    }
    document.addEventListener('mouseup', selesai);
    document.addEventListener('mouseleave', selesai);
    // cegah klik link tidak sengaja setelah menarik
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-gantt-geser]') && Math.abs(window.__ganttDx || 0) > 5) { e.preventDefault(); }
        window.__ganttDx = 0;
    }, true);
})();
</script>
@endonce