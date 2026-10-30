{{--
    resources/views/help/partials/grafik-kalender.blade.php

    Timeline Gantt dengan kolom tanggal kalender (posisi bar = tanggal asli tiket).

    Parameter:
      tikets      : Collection<HelpTiket>  (eager-load: bisnisUnit, ditugaskanKe, logStatus, vendorProses)
      rentang     : HelpTimeline::rentangBulan() / rentangTahun() / rentangTiket()   (wajib)
      judul       : judul kartu
      mode        : 'internal' (default) | 'vendor'
      routeDetail : nama route detail tiket (default 'help.proses.show')
      kosong      : teks bila tidak ada tiket (null = kartu disembunyikan)
      legenda     : true/false (default true)

    Warna fase:
      abu = Pengajuan | WARNA BISNIS UNIT = Diambil/diproses | kuning = Waiting
      teal = Over ke GA Corp | coklat = Diproses GA Corp | biru muda = Selesai, menunggu close
      garis merah = lewat 7 hari | lingkaran ujung bar (closed): hijau ≤ 7 hari, merah > 7 hari
      ungu = pengerjaan vendor
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
        'HELP_GA_CORP' => 'Over ke GA Corp (unit lain)', 'ON_PROCESS_CORP' => 'Diproses GA Corp', 'DONE' => 'Selesai, menunggu close',
    ];
    $pendekFase = [
        'OPEN' => 'Pengajuan', 'ON_PROCESS' => 'Diproses', 'WAITING' => 'Waiting',
        'HELP_GA_CORP' => 'Over GA Corp', 'ON_PROCESS_CORP' => 'Proses GA Corp', 'DONE' => 'Selesai',
    ];
    $chipStatus = [
        'OPEN' => 'bg-yellow-100 text-yellow-800', 'ON_PROCESS' => 'bg-blue-100 text-blue-800',
        'WAITING' => 'bg-orange-100 text-orange-800', 'DONE' => 'bg-green-100 text-green-800',
        'CLOSED' => 'bg-green-100 text-green-800', 'HELP_GA_CORP' => 'bg-slate-100 text-slate-700',
    ];
    // 1 hari = 24 jam penuh; sisanya dalam jam (mis. "2 hari 5 jam")
    $fmt = fn ($d) => $HT::formatDurasi((int) $d);

    // warna fase "diambil": ikut warna Business Unit tiket; setelah di-over -> warna Corporation
    $warnaFase = fn ($state, $warnaBU) => match ($state) {
        'ON_PROCESS'      => $warnaBU,
        'ON_PROCESS_CORP' => $HT::WARNA_CORP,
        default           => null,
    };

    // Susun baris
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

    // nama pelaku tiap fase (satu query untuk seluruh baris)
    $idPelaku = [];
    foreach ($baris as [$t, $bar, $v]) {
        foreach (($bar['fase'] ?? []) as $f) { if (!empty($f['oleh'])) { $idPelaku[$f['oleh']] = true; } }
        if (!empty($bar['tutup']['oleh'])) { $idPelaku[$bar['tutup']['oleh']] = true; }
    }
    $namaPelaku = $idPelaku
        ? \App\Models\Pelanggan::whereIn('id_pelanggan', array_keys($idPelaku))->pluck('nama_pelanggan', 'id_pelanggan')
        : collect();
    $verbFase = [
        'OPEN' => 'diajukan', 'ON_PROCESS' => 'diambil', 'WAITING' => 'oleh', 'HELP_GA_CORP' => 'di-over oleh',
        'ON_PROCESS_CORP' => 'diambil', 'DONE' => 'oleh',
    ];
    $jamTrx = fn ($ts) => \Carbon\Carbon::createFromTimestamp($ts)->format('d/m H:i');

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
                {{-- Garis kolom: SATU overlay untuk seluruh baris (bukan per baris) --}}
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
                        $teks    = ($bar['kiriPotong'] ? '← ' : '') . $bar['durasi'] . ($bar['kananPotong'] ? ' →' : '');
                        $dalam   = $bar['lebar'] >= 7;
                        $tip     = $bar['mulai']->format('d/m/Y H:i') . ' → '
                                 . ($mode === 'vendor' ? ($bar['berjalan'] ? 'sekarang' : $bar['selesai']->format('d/m/Y H:i'))
                                                       : ($bar['ditutup'] ? $bar['selesai']->format('d/m/Y H:i') : 'sekarang'))
                                 . ($mode !== 'vendor' && $bar['lewat'] ? ' · lewat ' . $bar['lebih'] . ' dari batas 7 hari' : '');
                    @endphp
                    <div class="relative flex border-b border-gray-100">
                        <div class="w-1/2 shrink-0 pr-3 py-2.5">
                            <a href="{{ route($routeDetail, $t) }}" class="block text-sm font-medium text-gray-900 hover:underline truncate"
                               title="{{ $t->nomor_tiket }} {{ $t->judul }}">{{ $t->nomor_tiket }} {{ \Illuminate\Support\Str::limit($t->judul, 60) }}</a>
                            <div class="mt-0.5 flex items-center gap-1.5 text-xs text-gray-500">
                                <span class="inline-block w-2 h-2 rounded-full shrink-0" style="background: {{ $warnaBU }}" title="{{ optional($bu)->nama_bisnis_unit }}"></span>
                                @if($mode === 'vendor')
                                    <span class="truncate">{{ \Illuminate\Support\Str::limit($v->nama_vendor, 18) }}</span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $bar['berjalan'] ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-700' }}">{{ $bar['berjalan'] ? 'DIKERJAKAN' : 'SELESAI' }}</span>
                                @else
                                    @if($pic)<span class="truncate">{{ \Illuminate\Support\Str::limit($pic, 14) }}</span>@endif
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold {{ $chipStatus[$t->status] ?? 'bg-gray-100 text-gray-700' }}">{{ str_replace('_', ' ', $t->status) }}</span>
                                @endif
                            </div>
                            @if($mode !== 'vendor' && !empty($bar['fase']))
                                @php
                                    $fl        = collect($bar['fase']);
                                    $pengajuan = $fl->firstWhere('state', 'OPEN');
                                    $proses    = $fl->reject(fn ($x) => $x['state'] === 'OPEN')->values();
                                    $ambil     = $proses->first(fn ($x) => in_array($x['state'], ['ON_PROCESS', 'WAITING', 'DONE'], true));
                                    $over      = $proses->firstWhere('state', 'HELP_GA_CORP');
                                    $ambilCorp = $proses->firstWhere('state', 'ON_PROCESS_CORP');
                                    $waiting   = $proses->where('state', 'WAITING')->sum('detik');
                                    $nama      = fn ($x) => $namaPelaku[$x['oleh'] ?? 0] ?? null;
                                    $rincian   = $fl->map(fn ($x) => ($labelFase[$x['state']] ?? $x['state']) . ' ' . $fmt($x['detik'])
                                                    . ($nama($x) ? ' · ' . $nama($x) : '') . ' · ' . $jamTrx($x['mulai']))->implode("\n");
                                    $nmTutup   = !empty($bar['tutup']) ? ($namaPelaku[$bar['tutup']['oleh'] ?? 0] ?? null) : null;
                                @endphp
                                <div class="mt-1 space-y-0.5 text-[11px] leading-snug text-gray-500" title="{{ $rincian }}">
                                    {{-- CLOSE (paling atas) --}}
                                    @if(!empty($bar['tutup']))
                                        <div>
                                            <span class="inline-block w-1.5 h-1.5 rounded-full mr-0.5 {{ $bar['hasil'] === 'ok' ? 'bg-green-600' : 'bg-red-600' }}"></span>
                                            <span class="font-medium text-gray-600">Close</span>
                                            <span class="text-gray-400">· @if($nmTutup)oleh {{ $nmTutup }} · @endif{{ $jamTrx($bar['tutup']['mulai']) }}</span>
                                        </div>
                                    @endif
                                    {{-- PROSES --}}
                                    @if($proses->isNotEmpty())
                                        <div>
                                            <span class="inline-block w-1.5 h-1.5 rounded-full mr-0.5" style="background: {{ $warnaBU }}"></span>
                                            <span class="font-medium text-gray-600">Proses</span> {{ $fmt($proses->sum('detik')) }}
                                            <span class="text-gray-400">
                                                @if($ambil)· diambil @if($nama($ambil)){{ $nama($ambil) }} @endif{{ $jamTrx($ambil['mulai']) }}@endif
                                                @if($over)· over GA Corp @if($nama($over))({{ $nama($over) }}) @endif{{ $jamTrx($over['mulai']) }}@endif
                                                @if($ambilCorp)· diambil GA Corp @if($nama($ambilCorp)){{ $nama($ambilCorp) }} @endif{{ $jamTrx($ambilCorp['mulai']) }}@endif
                                                @if($waiting > 0)· waiting {{ $fmt($waiting) }}@endif
                                            </span>
                                        </div>
                                    @endif
                                    {{-- PENGAJUAN (paling bawah) --}}
                                    @if($pengajuan)
                                        <div>
                                            <span class="inline-block w-1.5 h-1.5 rounded-full mr-0.5 bg-gray-400"></span>
                                            <span class="font-medium text-gray-600">Pengajuan</span> {{ $fmt($pengajuan['detik']) }}
                                            <span class="text-gray-400">· @if($nama($pengajuan))diajukan {{ $nama($pengajuan) }} · @endif{{ $jamTrx($pengajuan['mulai']) }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        <div class="flex-1 relative min-h-[48px]">
                            <div class="absolute top-1/2 transform -translate-y-1/2 h-7 rounded-full overflow-hidden flex shadow-sm"
                                 style="left: {{ $bar['kiri'] }}%; width: {{ $bar['lebar'] }}%; min-width: 6px;" title="{{ $tip }}">
                                @if($mode === 'vendor')
                                    <div class="w-full bg-purple-500"></div>
                                @else
                                    @forelse($bar['bagian'] as $p)
                                        @php $wf = $warnaFase($p['state'], $warnaBU); @endphp
                                        <div class="{{ $wf ? '' : ($warnaState[$p['state']] ?? 'bg-gray-400') }}"
                                             style="width: {{ $p['persen'] }}%;{{ $wf ? ' background: ' . $wf : '' }}"
                                             title="{{ $labelFase[$p['state']] ?? $p['state'] }} · {{ $fmt($p['detik']) }}"></div>
                                    @empty
                                        <div class="w-full bg-gray-400"></div>
                                    @endforelse
                                    @if($bar['telat'])
                                        <div class="absolute inset-y-0" title="Lewat batas 7 hari"
                                             style="left: {{ $bar['telat']['kiri'] }}%; width: {{ $bar['telat']['lebar'] }}%; background: repeating-linear-gradient(45deg, rgba(220,38,38,.55), rgba(220,38,38,.55) 6px, rgba(153,27,27,.65) 6px, rgba(153,27,27,.65) 12px);"></div>
                                    @endif
                                @endif
                                @if($dalam)
                                    <span class="absolute inset-0 flex items-center justify-center text-xs font-semibold text-white whitespace-nowrap" style="text-shadow: 0 1px 2px rgba(0,0,0,.45);">{{ $teks }}</span>
                                @endif
                            </div>

                            @if($mode !== 'vendor' && $bar['hasil'] && !$bar['kananPotong'])
                                <span class="absolute top-1/2 transform -translate-y-1/2 -translate-x-1/2 w-6 h-6 rounded-full flex items-center justify-center text-white text-[11px] shadow ring-2 ring-white {{ $bar['hasil'] === 'ok' ? 'bg-green-600' : 'bg-red-600' }}"
                                      style="left: calc({{ $bar['kiri'] + $bar['lebar'] }}% - 6px);"
                                      title="{{ $bar['hasil'] === 'ok' ? 'Closed ≤ 7 hari' : 'Closed lebih dari 7 hari' }}"><i class="fas {{ $bar['hasil'] === 'ok' ? 'fa-check' : 'fa-xmark' }}"></i></span>
                            @endif

                            @unless($dalam)
                                <span class="absolute top-1/2 transform -translate-y-1/2 text-xs font-medium text-gray-700 whitespace-nowrap"
                                      style="{{ ($bar['kiri'] + $bar['lebar']) > 80 ? 'right: calc(' . (100 - $bar['kiri']) . '% + 6px);' : 'left: calc(' . ($bar['kiri'] + $bar['lebar']) . '% + 6px);' }}">{{ $teks }}</span>
                            @endunless
                        </div>
                    </div>
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
                <span class="inline-flex items-center">Diambil / diproses:
                    @foreach($buAda as $b)
                        <span class="inline-flex items-center ml-2"><span class="w-2.5 h-2.5 rounded-sm mr-1" style="background: {{ $HT::infoBisnisUnit($b->nama_bisnis_unit)['warna'] }}"></span>{{ $b->nama_bisnis_unit }}</span>
                    @endforeach
                </span>
                <span class="inline-flex items-center"><span class="w-2.5 h-2.5 rounded-sm bg-amber-400 mr-1.5"></span>Waiting</span>
                <span class="inline-flex items-center"><span class="w-2.5 h-2.5 rounded-sm bg-teal-500 mr-1.5"></span>Over ke GA Corp</span>
                <span class="inline-flex items-center"><span class="w-2.5 h-2.5 rounded-sm mr-1.5" style="background: {{ $HT::WARNA_CORP }}"></span>Diproses GA Corp</span>
                <span class="inline-flex items-center"><span class="w-2.5 h-2.5 rounded-sm bg-sky-300 mr-1.5"></span>Selesai, menunggu close</span>
                <span class="inline-flex items-center"><span class="w-2.5 h-2.5 rounded-sm mr-1.5" style="background: repeating-linear-gradient(45deg,#dc2626,#dc2626 3px,#991b1b 3px,#991b1b 6px)"></span>Lewat 7 hari</span>
                <span class="inline-flex items-center"><span class="w-4 h-4 rounded-full bg-green-600 text-white text-[9px] flex items-center justify-center mr-1.5"><i class="fas fa-check"></i></span>Closed ≤ 7 hari</span>
                <span class="inline-flex items-center"><span class="w-4 h-4 rounded-full bg-red-600 text-white text-[9px] flex items-center justify-center mr-1.5"><i class="fas fa-xmark"></i></span>Closed &gt; 7 hari</span>
            @endif
            <span class="inline-flex items-center"><span class="w-px h-3 bg-blue-400 mr-1.5"></span>Sekarang</span>
        </div>
    @endif
    @endif
</div>
@endif