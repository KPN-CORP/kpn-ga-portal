{{-- resources/views/help_p/grafik/index.blade.php --}}
@extends('layouts.app-help-sidebar')

@section('title', 'Grafik')

@section('content')
@php
    $namaBulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $periode   = ($bulan ? $namaBulan[$bulan] . ' ' : 'Tahun ') . $tahun;

    $totalAktif  = collect($grafikBU)->sum('aktif');
    $totalClosed = collect($grafikBU)->sum('closed');
    $maks        = max(1, collect($grafikBU)->max(fn ($g) => $g['aktif'] + $g['closed']));
@endphp

<div class="space-y-6 text-sm text-gray-800 font-sans"
     x-data="grafikHelp()"
     data-url="{{ route('help.grafik.daftar') }}"
     data-query='@json(request()->query())'>

    <div>
        <h2 class="text-xl md:text-2xl font-bold text-gray-800">📊 Grafik</h2>
        <p class="text-gray-500 mt-1">Jumlah tiket per Business Unit · periode {{ $periode }}. Klik batang untuk melihat daftar tiketnya di panel kanan.</p>
    </div>

    @include('help_p.partials.grafik-filter', ['aksi' => 'help.grafik', 'tampilStatus' => false])

    {{-- KIRI: grafik · KANAN: panel daftar tiket (muncul saat batang diklik) --}}
    <div class="grid grid-cols-1 gap-6 items-start"
         :class="terbuka ? 'lg:grid-cols-[minmax(0,1fr)_26rem]' : 'lg:grid-cols-1'">

        {{-- ===== KIRI ===== --}}
        <div class="space-y-6 min-w-0">
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <p class="text-xs text-gray-500">Belum close</p>
                    <p class="text-2xl font-semibold text-blue-600 mt-1">{{ $totalAktif }}</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-xl p-4">
                    <p class="text-xs text-gray-500">Sudah closed</p>
                    <p class="text-2xl font-semibold text-green-600 mt-1">{{ $totalClosed }}</p>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4 md:p-6">
                <div class="flex flex-wrap items-center gap-4 text-xs text-gray-600 mb-4">
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-blue-500"></span> Belum close (warna ikut BU)</span>
                    <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded-sm bg-green-500"></span> Sudah closed</span>
                </div>

                @forelse($grafikBU as $g)
                    @php $total = $g['aktif'] + $g['closed']; @endphp
                    <div class="flex items-center gap-3 py-2">
                        <div class="w-28 sm:w-40 shrink-0 text-xs sm:text-sm font-medium text-gray-700 truncate" title="{{ $g['label'] }}">
                            {{ $g['label'] }}
                        </div>
                        <div class="flex-1 bg-gray-100 rounded h-8 overflow-hidden">
                            <div class="flex h-full" style="width: {{ $total > 0 ? max(4, round($total / $maks * 100)) : 0 }}%;">
                                @if($g['aktif'] > 0)
                                    <button type="button"
                                            @click="buka({{ $g['id'] }}, '{{ addslashes($g['label']) }}', 'aktif')"
                                            class="h-full text-white text-xs font-semibold hover:opacity-80 transition"
                                            :class="aktif({{ $g['id'] }}, 'aktif') ? 'ring-2 ring-inset ring-gray-900/60' : ''"
                                            style="width: {{ round($g['aktif'] / $total * 100) }}%; background: {{ $g['warna'] }};"
                                            title="{{ $g['label'] }} · belum close: {{ $g['aktif'] }}">
                                        {{ $g['aktif'] }}
                                    </button>
                                @endif
                                @if($g['closed'] > 0)
                                    <button type="button"
                                            @click="buka({{ $g['id'] }}, '{{ addslashes($g['label']) }}', 'closed')"
                                            class="h-full bg-green-500 text-white text-xs font-semibold hover:opacity-80 transition"
                                            :class="aktif({{ $g['id'] }}, 'closed') ? 'ring-2 ring-inset ring-gray-900/60' : ''"
                                            style="width: {{ round($g['closed'] / $total * 100) }}%;"
                                            title="{{ $g['label'] }} · closed: {{ $g['closed'] }}">
                                        {{ $g['closed'] }}
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="w-10 shrink-0 text-right text-xs text-gray-500">{{ $total }}</div>
                    </div>
                @empty
                    <p class="text-gray-500 text-center py-6">Tidak ada data pada periode ini.</p>
                @endforelse
            </div>
        </div>

        {{-- ===== KANAN: PANEL DAFTAR TIKET ===== --}}
        <aside x-show="terbuka" x-cloak
               class="bg-white border border-gray-200 rounded-xl overflow-hidden flex flex-col lg:sticky lg:top-4 lg:max-h-[calc(100vh-2rem)]">
            <div class="flex items-start justify-between gap-3 px-4 py-3 border-b bg-gray-50">
                <div class="min-w-0">
                    <p class="font-semibold text-gray-800 truncate" x-text="judul"></p>
                    <p class="text-xs text-gray-500" x-show="!memuat && !galat">
                        <span x-text="total"></span> tiket<span x-show="total > data.length"> · menampilkan <span x-text="data.length"></span> terlama</span>
                    </p>
                </div>
                <button type="button" @click="tutup()" class="text-gray-400 hover:text-gray-600 text-xl leading-none" aria-label="Tutup">&times;</button>
            </div>

            <div class="px-4 py-2 border-b" x-show="!memuat && !galat && data.length > 0">
                <input type="search" x-model="cari" @input="hal = 0" placeholder="Cari nomor, judul, atau PIC"
                       class="w-full border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex-1 overflow-y-auto">
                <div x-show="memuat" class="p-6 text-center text-gray-500">Memuat…</div>
                <div x-show="galat" class="p-6 text-center text-red-600" x-text="galat"></div>
                <div x-show="!memuat && !galat && tersaring.length === 0" class="p-6 text-center text-gray-500">Tidak ada tiket.</div>

                <ul class="divide-y" x-show="!memuat && tersaring.length > 0">
                    <template x-for="t in halaman" :key="t.nomor">
                        <li class="px-4 py-3 hover:bg-gray-50">
                            <div class="flex items-start justify-between gap-3">
                                <a :href="t.url" class="text-blue-600 hover:underline font-medium" x-text="t.nomor"></a>
                                <span class="text-xs font-semibold whitespace-nowrap"
                                      :class="t.telat ? 'text-red-600' : 'text-green-600'"
                                      x-text="t.umur"></span>
                            </div>
                            <p class="text-sm text-gray-700 mt-0.5 line-clamp-2" x-text="t.judul"></p>
                            <p class="text-xs text-gray-500 mt-1">
                                <span x-text="t.pic"></span> · <span x-text="t.status"></span> · <span x-text="t.sejak"></span>
                            </p>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="flex items-center justify-between px-4 py-2 border-t bg-gray-50 text-xs text-gray-500"
                 x-show="!memuat && tersaring.length > perHal">
                <button type="button" @click="hal--" :disabled="hal === 0"
                        class="px-2 py-1 rounded border bg-white disabled:opacity-40">‹ Sebelumnya</button>
                <span>Hal <span x-text="hal + 1"></span> / <span x-text="jmlHal"></span></span>
                <button type="button" @click="hal++" :disabled="hal >= jmlHal - 1"
                        class="px-2 py-1 rounded border bg-white disabled:opacity-40">Berikutnya ›</button>
            </div>
        </aside>
    </div>
</div>

<script>
function grafikHelp() {
    return {
        terbuka: false, memuat: false, galat: '', judul: '', total: 0, data: [],
        cari: '', hal: 0, perHal: 10, pilih: { bu: null, mode: null },

        get tersaring() {
            const q = this.cari.trim().toLowerCase();
            if (!q) return this.data;
            return this.data.filter(t =>
                (t.nomor + ' ' + t.judul + ' ' + t.pic).toLowerCase().includes(q));
        },
        get jmlHal()  { return Math.max(1, Math.ceil(this.tersaring.length / this.perHal)); },
        get halaman() { return this.tersaring.slice(this.hal * this.perHal, (this.hal + 1) * this.perHal); },

        aktif(bu, mode) { return this.terbuka && this.pilih.bu === bu && this.pilih.mode === mode; },
        tutup() { this.terbuka = false; this.pilih = { bu: null, mode: null }; },

        async buka(bu, label, mode) {
            const el = this.$root;
            const params = new URLSearchParams(JSON.parse(el.dataset.query || '{}'));
            params.set('bu', bu);
            params.set('mode', mode);
            this.pilih = { bu, mode };
            this.terbuka = true; this.memuat = true; this.galat = '';
            this.data = []; this.total = 0; this.cari = ''; this.hal = 0;
            this.judul = label + ' · ' + (mode === 'closed' ? 'Sudah closed' : 'Belum close');
            try {
                const res = await fetch(el.dataset.url + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const json = await res.json();
                this.total = json.total; this.data = json.data;
            } catch (e) {
                this.galat = 'Gagal memuat daftar tiket (' + e.message + ').';
            } finally {
                this.memuat = false;
            }
        }
    };
}
</script>
@endsection