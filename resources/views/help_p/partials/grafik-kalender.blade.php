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
    $r           = $rentang;

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

    $gridCols = collect($r['kolom'])->map(fn ($k) => 'minmax(0,' . $k['hari'] . 'fr)')->implode(' ');
@endphp

@if(empty($baris) && !$kosong)
@else
<div class="bg-white rounded-xl border border-gray-200 p-4">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
        <h3 class="font-semibold text-gray-800 flex items-center">
            <i class="fas {{ $mode === 'vendor' ? 'fa-truck text-purple-500' : 'fa-calendar-week text-blue-500' }} mr-2"></i>{{ $judul }}
        </h3>
        <span class="text-xs text-gray-500">
            {{ $r['awal']->copy()->locale('id')->isoFormat('D MMM Y') }} – {{ $r['akhir']->copy()->locale('id')->isoFormat('D MMM Y') }}
            · {{ count($baris) }} {{ $mode === 'vendor' ? 'pengerjaan' : 'tiket' }}
        </span>
    </div>

    @if(empty($baris))
        <p class="text-sm text-gray-500 py-4 text-center">{{ $kosong }}</p>
    @else
    <div class="overflow-x-auto">
        <div class="min-w-[720px]">

            {{-- Header tanggal --}}
            <div class="flex">
                <div class="w-1/2 shrink-0"></div>
                <div class="flex-1 grid border-b border-gray-200" style="grid-template-columns: {{ $gridCols }};">
                    @foreach($r['kolom'] as $k)
                        <div class="text-center py-1 border-l border-gray-200 overflow-hidden {{ $k['ini'] ? 'bg-blue-100 text-blue-800 font-semibold' : ($k['libur'] ? 'bg-gray-50 text-gray-400' : 'text-gray-500') }}"
                             title="{{ $k['title'] }}">
                            <div class="{{ $r['jenis'] === 'hari' ? 'text-[10px]' : 'text-xs' }} leading-tight">{{ $k['label'] }}</div>
                            @if($k['sub'] !== '')<div class="text-[10px] leading-tight">{{ $k['sub'] }}</div>@endif
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="relative">
                {{-- Garis kolom: SATU overlay untuk seluruh baris --}}
                <div class="absolute inset-y-0 right-0 left-1/2 grid pointer-events-none" style="grid-template-columns: {{ $gridCols }};">
                    @foreach($r['kolom'] as $k)
                        <div class="border-l border-gray-100 {{ $k['ini'] ? 'bg-blue-50' : ($k['libur'] ? 'bg-gray-50' : '') }}"></div>
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
                            <div class="w-1/2 shrink-0 pr-3 py-2.5">
                                <a href="{{ route($routeDetail, $t) }}" class="block text-sm font-medium text-gray-900 hover:underline truncate"
                                   title="{{ $t->nomor_tiket }} {{ $t->judul }}">{{ $t->nomor_tiket }} {{ \Illuminate\Support\Str::limit($t->judul, 60) }}</a>
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
                                <div class="w-1/2 shrink-0 pr-3 pt-1.5 pb-0.5">
                                    <a href="{{ route($routeDetail, $t) }}" class="block text-sm font-medium text-blue-700 underline hover:text-blue-900 truncate"
                                       title="{{ $t->nomor_tiket }} {{ $t->judul }}">{{ $t->nomor_tiket }} {{ \Illuminate\Support\Str::limit($t->judul, 60) }}</a>
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
                                    <div class="w-1/2 shrink-0 pr-3 py-0.5 text-[11px] leading-snug text-gray-500">
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
                                    $judulRow = ['ajuan' => 'Pengajuan', 'over' => 'Over GA Corp', 'proses' => $row['nama'] ?? 'Proses'][$jenis];
                                    $tipRow   = $judulRow . ' · ' . $jamTrx($row['a']) . ($row['b'] > $row['a'] ? ' → ' . $jamTrx($row['b']) : '');
                                    $dotRow   = $jenis === 'ajuan' ? '#9ca3af'
                                              : ($jenis === 'over' ? '#14b8a6'
                                              : ($warnaFase($row['bagian'][0]['state'] ?? 'ON_PROCESS', $warnaBU) ?? $warnaBU));
                                @endphp
                                <div class="flex">
                                    <div class="w-1/2 shrink-0 pr-3 py-0.5 text-[11px] leading-snug text-gray-500">
                                        <span class="inline-block w-1.5 h-1.5 rounded-full mr-0.5" style="background: {{ $dotRow }}"></span>
                                        @if($jenis === 'proses')
                                            <span class="font-semibold text-gray-700">{{ $row['nama'] }}</span>
                                            <span class="text-gray-600">· Proses {{ $fmt($row['detik']) }}</span>
                                            <span class="text-gray-400">· diambil {{ $jamTrx($row['a']) }}
                                                @if($row['waiting'] > 0)· waiting {{ $fmt($row['waiting']) }}@endif
                                                @if($row['selesai'])· selesai {{ $jamTrx($row['selesai']) }}@endif
                                            </span>
                                        @elseif($jenis === 'over')
                                            <span class="font-medium text-gray-600">Over GA Corp</span> · menunggu {{ $fmt($row['detik']) }}
                                            <span class="text-gray-400">· @if($nama($row['oleh']))di-over oleh {{ $nama($row['oleh']) }} · @endif{{ $jamTrx($row['a']) }}</span>
                                        @else
                                            <span class="font-medium text-gray-600">Pengajuan</span> {{ $fmt($row['detik']) }}
                                            <span class="text-gray-400">· @if($nama($row['oleh']))diajukan {{ $nama($row['oleh']) }} · @endif{{ $jamTrx($row['a']) }}</span>
                                        @endif
                                    </div>

                                    <div class="flex-1 relative min-h-[26px]">
                                        @if($pos)
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