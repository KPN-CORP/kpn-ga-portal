{{--
    resources/views/help/partials/grafik-proses.blade.php

    Pemakaian:
      @include('help_p.partials.grafik-proses', ['tiket' => $tiket])                    -> kartu lengkap (halaman detail)
      @include('help_p.partials.grafik-proses', ['tiket' => $tiket, 'compact' => true]) -> strip kecil (tabel daftar)

    - Tiket yang pernah diserahkan ke vendor  -> grafik pengerjaan vendor (tanpa merah/hijau)
    - Tiket lain (termasuk HELP_GA_CORP)      -> grafik 7 hari internal, dihitung sejak OPEN
      Merah/hijau hanya muncul setelah CLOSED: 7 hari atau kurang = hijau, lebih dari 7 hari = merah.
--}}
@php
    $compact = $compact ?? false;
    $vendors = $tiket->vendorProses;
@endphp

@if($vendors->isNotEmpty())
    {{-- ===================== GRAFIK PENGERJAAN VENDOR ===================== --}}
    @php
        $vendorItems = $vendors->map(function ($v) {
            return ['v' => $v, 'detik' => $v->durasi_detik];
        });
        $maxDetik    = max(7 * 86400, $vendorItems->max('detik'));   // skala bar: minimal 7 hari
    @endphp

    @if($compact)
        @php
            $lastV = $vendorItems->last();
            $lebar = max(12, min(100, (int) round($lastV['detik'] / $maxDetik * 100)));
        @endphp
        <div class="flex items-center gap-2 text-xs">
            <div class="w-20 h-2.5 rounded-full bg-purple-100 overflow-hidden">
                <div class="h-full bg-purple-500 rounded-full" style="width: {{ $lebar }}%"></div>
            </div>
            <span class="text-purple-700 font-medium whitespace-nowrap">Vendor · {{ \App\Support\help_s\HelpTimeline::formatDurasi($lastV['detik']) }}</span>
        </div>
    @else
        <div class="bg-white rounded-lg border border-gray-200 p-4">
            <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                <i class="fas fa-truck text-purple-500 mr-2"></i> Pengerjaan Vendor
            </h3>
            <div class="space-y-4">
                @foreach($vendorItems as $item)
                    @php
                        $v     = $item['v'];
                        $lebar = max(12, min(100, (int) round($item['detik'] / $maxDetik * 100)));
                    @endphp
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-1 text-sm">
                            <span class="font-medium text-gray-900">{{ $v->nama_vendor }}</span>
                            <span class="text-xs {{ $v->selesai_pada ? 'text-gray-600' : 'text-purple-700 font-medium' }}">
                                {{ $v->selesai_pada ? 'Selesai' : 'Sedang dikerjakan' }}
                            </span>
                        </div>
                        <div class="mt-2 h-6 rounded-full bg-gray-100 overflow-hidden">
                            <div class="h-full bg-purple-500 rounded-full flex items-center px-3 text-xs font-medium text-white"
                                 style="width: {{ $lebar }}%">
                                {{ \App\Support\help_s\HelpTimeline::formatDurasi($item['detik']) }}
                            </div>
                        </div>
                        <div class="mt-1 flex justify-between text-xs text-gray-500">
                            <span>Masuk vendor {{ $v->mulai_pada->format('d M Y H:i') }}</span>
                            <span>{{ $v->selesai_pada ? 'Selesai ' . $v->selesai_pada->format('d M Y H:i') : 'Berjalan' }}</span>
                        </div>
                        @if($v->catatan)
                            <p class="mt-1 text-xs text-gray-600">{{ $v->catatan }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

@else
    {{-- ===================== GRAFIK 7 HARI INTERNAL ===================== --}}
    @php
        $g = \App\Support\help_s\HelpTimeline::build($tiket);

        $warnaState = [
            'OPEN'         => 'bg-gray-300',
            'ON_PROCESS'   => 'bg-blue-500',
            'WAITING'      => 'bg-amber-400',
            'HELP_GA_CORP' => 'bg-teal-500',
            'ON_PROCESS_CORP' => 'bg-indigo-500',
            'DONE'         => 'bg-sky-400',
        ];
        $warnaHasil = ['ok' => 'bg-green-500', 'bad' => 'bg-red-500'];

        $warnaCell = function ($c) use ($g, $warnaState, $warnaHasil) {
            if (!$c['terisi']) {
                return 'bg-gray-100';
            }
            return $g['hasil'] ? $warnaHasil[$g['hasil']] : ($warnaState[$c['state']] ?? 'bg-gray-300');
        };

        $teksHari = $g['hasil'] === 'ok' ? 'text-green-700' : ($g['hasil'] === 'bad' ? 'text-red-700' : 'text-gray-600');
    @endphp

    @if($compact)
        <div class="flex items-center gap-2">
            <div class="flex gap-0.5">
                @foreach($g['cells'] as $i => $c)
                    <span class="w-3.5 h-3 rounded-sm {{ $warnaCell($c) }}"
                          title="Hari ke-{{ $i + 1 }} · {{ $c['tgl'] }}"></span>
                @endforeach
            </div>
            <span class="text-xs font-medium whitespace-nowrap {{ $teksHari }}">
                {{ $g['durasi'] }}
            </span>
        </div>
    @else
        {{-- Kartu detail: Gantt tanggal kalender, dari tanggal OPEN sampai CLOSED / sekarang --}}
        @include('help_p.partials.grafik-kalender', [
            'tikets'      => collect([$tiket]),
            'rentang'     => \App\Support\help_s\HelpTimeline::rentangTiket($tiket),
            'judul'       => $g['ditutup']
                ? 'Proses tiket · Closed dalam ' . $g['durasi'] . ($g['hasil'] === 'bad' ? ' (lebih dari 7 hari)' : '')
                : 'Proses tiket · Berjalan ' . $g['durasi'] . ($g['lewat'] ? ' (lewat 7 hari)' : ''),
            'routeDetail' => 'help.tiket.show',
        ])
    @endif
@endif