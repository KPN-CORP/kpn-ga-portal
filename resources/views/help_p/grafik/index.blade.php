{{-- resources/views/help_p/grafik/index.blade.php --}}
@extends('layouts.app-help-sidebar')

@section('title', 'Grafik')

@section('content')
@php
    $HT = \App\Support\help_s\HelpTimeline::class;
    $warnaCorp = $HT::WARNA_CORP;   // coklat: bagian yang dikerjakan GA Corp
    $namaBulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $periode   = ($bulan ? $namaBulan[$bulan] . ' ' : 'Tahun ') . $tahun;
    $fd        = fn ($d) => $d === null ? '-' : $HT::formatDurasi((int) $d);

    // buang nilai kosong dari query halaman ini supaya tidak terkirim sebagai teks "null"
    $query = array_filter(request()->query(), fn ($v) => $v !== null && $v !== '');
@endphp

<div class="space-y-6 text-sm text-gray-800 font-sans"
     x-data="grafikHelp()"
     data-url="{{ route('help.grafik.daftar') }}"
     data-query='@json((object) $query)'>

    <div>
        <h2 class="text-xl md:text-2xl font-bold text-gray-800">📊 Grafik</h2>
        <p class="text-gray-500 mt-1">Berapa lama tiket dikerjakan di tiap Business Unit · periode {{ $periode }}. Klik satu Business Unit untuk melihat admin yang mengerjakan.</p>
    </div>

    @include('help_p.partials.grafik-filter', ['aksi' => 'help.grafik', 'tampilStatus' => false])

    @if($terpotong)
        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
            Data dibatasi 1000 tiket terbaru. Persempit periode atau Business Unit agar semua tiket ikut dihitung.
        </p>
    @endif

    <div class="grid grid-cols-1 gap-6 items-start"
         :class="terbuka ? 'lg:grid-cols-[minmax(0,1fr)_28rem]' : 'lg:grid-cols-1'">

        {{-- ===== KIRI ===== --}}
        <div class="space-y-6 min-w-0">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <p class="text-xs font-semibold text-gray-600">Rata-rata menunggu diambil</p>
                    <p class="text-2xl font-bold text-amber-600 mt-1">{{ $fd($ringkasan['tunggu']) }}</p>
                    <p class="text-xs text-gray-500 mt-1">sejak tiket dibuat sampai diambil admin</p>
                </div>
                <div class="bg-white border-2 border-blue-200 rounded-xl p-4">
                    <p class="text-xs font-semibold text-gray-600">Rata-rata waktu pengerjaan</p>
                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ $fd($ringkasan['done']) }}</p>
                    <p class="text-xs text-gray-500 mt-1">sejak diambil admin sampai selesai · {{ $ringkasan['jml_done'] }} tiket</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <p class="text-xs font-semibold text-gray-600">Diteruskan ke GA Corp</p>
                    <p class="text-2xl font-bold text-purple-600 mt-1">{{ $ringkasan['jml_over'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">kali minta bantuan GA Corp</p>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4 md:p-6">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-gray-600 mb-3">
                    <span>Batang = 100% tiket tiap BU, dibagi menurut siapa yang mengerjakan</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-gray-400"></span> Dikerjakan BU</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm" style="background: {{ $warnaCorp }};"></span> Dikerjakan GA Corp</span>
                </div>

                @forelse($grafikBU as $g)
                    @php
                        $nBU   = (int) $g['tiket_bu'];
                        $nCorp = (int) $g['tiket_corp'];
                        $nSemua = $nBU + $nCorp;
                        $pCorp = $nSemua > 0 ? (int) round($nCorp / $nSemua * 100) : 0;
                        $pBU   = 100 - $pCorp;
                    @endphp
                    <button type="button"
                            @click="buka({{ $g['id'] }}, '{{ addslashes($g['label']) }}', '{{ $g['warna'] }}')"
                            class="w-full text-left rounded-lg px-2 py-3 hover:bg-gray-50 transition"
                            :class="pilih === {{ $g['id'] }} ? 'bg-blue-50 ring-1 ring-blue-200' : ''">
                        <div class="flex items-center justify-between gap-3">
                            <span class="font-medium text-gray-800 truncate">{{ $g['label'] }}</span>
                            <span class="text-xs text-gray-500 whitespace-nowrap">{{ $nSemua }} tiket · {{ $g['jml_admin'] }} admin</span>
                        </div>

                        @if($nSemua > 0)
                            <div class="mt-2 flex h-7 rounded overflow-hidden bg-gray-100 text-xs font-semibold text-white">
                                @if($pBU > 0)
                                    <div class="flex items-center justify-center" style="width: {{ $pBU }}%; background: {{ $g['warna'] }};"
                                         title="Dikerjakan BU: {{ $nBU }} tiket">{{ $pBU }}%</div>
                                @endif
                                @if($pCorp > 0)
                                    <div class="flex items-center justify-center" style="width: {{ $pCorp }}%; background: {{ $warnaCorp }};"
                                         title="Dikerjakan GA Corp: {{ $nCorp }} tiket">{{ $pCorp }}%</div>
                                @endif
                            </div>
                        @else
                            <p class="mt-2 text-xs text-gray-400">Belum ada tiket yang diambil pada periode ini.</p>
                        @endif
                    </button>
                @empty
                    <p class="text-gray-500 text-center py-6">Tidak ada data pada periode ini.</p>
                @endforelse
            </div>
        </div>

        {{-- ===== KANAN: ADMIN PER BUSINESS UNIT ===== --}}
        <aside x-show="terbuka" x-cloak
               class="bg-white border border-gray-200 rounded-xl overflow-hidden flex flex-col lg:sticky lg:top-4 lg:max-h-[calc(100vh-2rem)]">
            <div class="flex items-start justify-between gap-3 px-4 py-3 border-b bg-gray-50">
                <div class="min-w-0">
                    <p class="font-semibold text-gray-800 truncate" x-text="judul"></p>
                    <p class="text-xs text-gray-500" x-show="!memuat && !galat">
                        <span x-text="admin.length"></span> admin · <span x-text="tiket"></span> tiket
                    </p>
                </div>
                <button type="button" @click="tutup()" class="text-gray-400 hover:text-gray-600 text-xl leading-none" aria-label="Tutup">&times;</button>
            </div>

            <div class="flex-1 overflow-y-auto">
                <div x-show="memuat" class="p-6 text-center text-gray-500">Memuat…</div>
                <div x-show="galat" class="p-6 text-center text-red-600" x-text="galat"></div>
                <div x-show="!memuat && !galat && admin.length === 0" class="p-6 text-center text-gray-500">Belum ada admin yang mengambil tiket.</div>

                <template x-for="a in admin" :key="a.nama">
                    <div class="border-b">
                        <button type="button" @click="buka_admin = (buka_admin === a.nama ? null : a.nama)"
                                class="w-full text-left px-4 py-3 hover:bg-gray-50">
                            <div class="flex items-center justify-between gap-3">
                                <span class="inline-flex items-center gap-2 min-w-0">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="'background:' + (a.corp ? warnaCorp : warnaBU)"></span>
                                    <span class="font-semibold text-gray-800 truncate" x-text="a.nama"></span>
                                    <span x-show="a.corp" class="text-[10px] font-semibold px-1.5 py-0.5 rounded text-white shrink-0" :style="'background:' + warnaCorp">GA Corp</span>
                                </span>
                                <span class="text-xs text-gray-500 whitespace-nowrap"><span x-text="a.tiket"></span> tiket <span x-text="buka_admin === a.nama ? '▲' : '▼'"></span></span>
                            </div>
                            <div class="mt-1.5 grid grid-cols-3 gap-2 text-xs">
                                <div><p class="text-gray-400">Pengerjaan<span x-show="a.jml_done > 0"> (<span x-text="a.jml_done"></span>×)</span></p><p class="font-semibold text-blue-600" x-text="a.done || '-'"></p></div>
                                <div><p class="text-gray-400">Menunggu diambil</p><p class="font-semibold text-amber-600" x-text="a.tunggu || '-'"></p></div>
                                <div><p class="text-gray-400">Ke GA Corp<span x-show="a.jml_over > 0"> (<span x-text="a.jml_over"></span>×)</span></p><p class="font-semibold text-purple-600" x-text="a.over || '-'"></p></div>
                            </div>
                        </button>

                        <ul class="bg-gray-50 divide-y" x-show="buka_admin === a.nama">
                            <template x-for="(d, i) in a.daftar" :key="d.nomor + i">
                                <li class="px-4 py-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <a :href="d.url" class="text-blue-600 hover:underline font-medium" x-text="d.nomor"></a>
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full whitespace-nowrap"
                                              :class="{
                                                'bg-green-100 text-green-700': d.kode === 'DONE',
                                                'bg-purple-100 text-purple-700': d.kode === 'OVER',
                                                'bg-sky-100 text-sky-700': d.kode === 'ALIH',
                                                'bg-amber-100 text-amber-700': d.kode === 'BERJALAN',
                                                'bg-gray-200 text-gray-600': d.kode === 'CLOSED'
                                              }" x-text="d.hasil"></span>
                                    </div>
                                    <p class="text-sm text-gray-700 mt-0.5 line-clamp-2" x-text="d.judul"></p>
                                    <ol class="mt-2 space-y-0.5 text-xs text-gray-600">
                                        <li>Dibuat · <span x-text="d.laporan"></span></li>
                                        <li>Diambil · <span x-text="d.diambil"></span><span x-show="d.tunggu" class="text-amber-600 font-semibold"> (menunggu <span x-text="d.tunggu"></span>)</span></li>
                                        <li><span x-text="d.hasil"></span> · <span x-text="d.akhir"></span> <span class="text-blue-600 font-semibold">(dikerjakan <span x-text="d.proses"></span>)</span></li>
                                    </ol>
                                </li>
                            </template>
                        </ul>
                    </div>
                </template>
            </div>
        </aside>
    </div>
</div>

<script>
function grafikHelp() {
    return {
        terbuka: false, memuat: false, galat: '', judul: '', pilih: null,
        warnaBU: '#3b82f6', warnaCorp: '{{ $warnaCorp }}',
        tiket: 0, admin: [], buka_admin: null,

        tutup() { this.terbuka = false; this.pilih = null; },

        async buka(bu, label, warna) {
            const el = this.$root;
            const params = new URLSearchParams();
            // hanya kirim parameter yang berisi (jangan kirim null/kosong sebagai teks "null")
            const q = JSON.parse(el.dataset.query || '{}');
            Object.keys(q).forEach(k => {
                if (q[k] !== null && q[k] !== '') params.set(k, q[k]);
            });
            params.set('bu', bu);

            this.pilih = bu; this.terbuka = true; this.memuat = true; this.galat = '';
            this.admin = []; this.tiket = 0; this.buka_admin = null;
            this.judul = label; this.warnaBU = warna || '#3b82f6';
            try {
                const res = await fetch(el.dataset.url + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const json = await res.json();
                this.tiket = json.tiket; this.admin = json.admin;
            } catch (e) {
                this.galat = 'Gagal memuat data admin (' + e.message + ').';
            } finally {
                this.memuat = false;
            }
        }
    };
}
</script>
@endsection