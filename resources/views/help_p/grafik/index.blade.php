{{-- resources/views/help_p/grafik/index.blade.php --}}
@extends('layouts.app-help-sidebar')

@section('title', 'Grafik')

@section('content')
@php
    $HT        = \App\Support\help_s\HelpTimeline::class;
    $warnaCorp = $HT::WARNA_CORP;   // coklat: GA Corp / KPN Corporate
    $namaBulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $periode   = ($bulan ? $namaBulan[$bulan] . ' ' : 'Tahun ') . $tahun;
    $fd        = fn ($d) => $d === null ? '-' : $HT::formatDurasi((int) $d);

    // buang nilai kosong dari query halaman ini supaya tidak terkirim sebagai teks "null"
    $query = array_filter(request()->query(), fn ($v) => $v !== null && $v !== '');

    $maksAdmin = max(1, collect($admin)->max(fn ($a) => array_sum($a['per_bu']) ?: $a['total']));
    $warnaSla  = fn ($p) => $p >= 90 ? '#059669' : ($p >= 75 ? '#d97706' : '#dc2626');
@endphp

<div class="space-y-6 text-sm text-slate-800 font-sans"
     x-data="grafikHelp()"
     data-url="{{ route('help.grafik.daftar') }}"
     data-query='@json((object) $query)'>

    {{-- HEADER --}}
    <div class="flex flex-wrap items-end justify-between gap-2">
        <div>
            <h2 class="text-xl md:text-2xl font-bold text-slate-900">Dashboard Help Desk</h2>
            <p class="text-slate-500 mt-0.5">Ringkasan kinerja penanganan tiket · periode {{ $periode }}</p>
        </div>
        <span class="text-xs text-slate-500 bg-slate-100 rounded-full px-3 py-1">{{ number_format($ringkasan['tiket']) }} tiket dianalisis</span>
    </div>

    @include('help_p.partials.grafik-filter', ['aksi' => 'help.grafik', 'tampilStatus' => false])

    @if($terpotong)
        <p class="text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
            Data dibatasi 1000 tiket terbaru. Persempit periode atau Business Unit agar semua tiket ikut dihitung.
        </p>
    @endif

    {{-- KPI --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Rata-rata menunggu diambil</p>
            <p class="mt-1.5 text-2xl font-semibold text-slate-900 tabular-nums">{{ $fd($ringkasan['tunggu']) }}</p>
            <p class="mt-1 text-xs text-slate-400">tiket dibuat → diambil admin</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Rata-rata waktu pengerjaan</p>
            <p class="mt-1.5 text-2xl font-semibold text-slate-900 tabular-nums">{{ $fd($ringkasan['done']) }}</p>
            <p class="mt-1 text-xs text-slate-400">diambil → Done · {{ number_format($ringkasan['jml_done']) }} tiket</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Diteruskan ke GA Corp</p>
            <p class="mt-1.5 text-2xl font-semibold tabular-nums" style="color: {{ $warnaCorp }};">
                {{ $ringkasan['persen_esk'] !== null ? $ringkasan['persen_esk'] . '%' : '-' }}
            </p>
            <p class="mt-1 text-xs text-slate-400">{{ number_format($ringkasan['eskalasi']) }} dari {{ number_format($ringkasan['tiket']) }} tiket</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-slate-500">Ketepatan SLA</p>
            <p class="mt-1.5 text-2xl font-semibold tabular-nums" @if($ringkasan['sla'] !== null) style="color: {{ $warnaSla($ringkasan['sla']) }};" @endif>
                {{ $ringkasan['sla'] !== null ? $ringkasan['sla'] . '%' : '-' }}
            </p>
            <p class="mt-1 text-xs text-slate-400">selesai dalam batas SLA · {{ number_format($ringkasan['sla_total']) }} tiket</p>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        {{-- 1. KETERGANTUNGAN PADA GA CORP PER BU --}}
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Ketergantungan pada GA Corp</h3>
            <p class="text-xs text-slate-500 mt-0.5 mb-4">Persentase tiket tiap Business Unit yang diteruskan ke GA Corp untuk dibantu.</p>

            @forelse($eskalasiBU as $g)
                <div class="py-2.5">
                    <div class="flex items-center justify-between gap-3">
                        <span class="inline-flex items-center gap-2 min-w-0">
                            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ $g['warna'] }};"></span>
                            <span class="font-medium text-slate-800 truncate">{{ $g['label'] }}</span>
                        </span>
                        <span class="text-sm font-semibold tabular-nums text-slate-900">{{ $g['persen'] }}%</span>
                    </div>
                    <div class="mt-1.5 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full" style="width: {{ $g['persen'] }}%; background: {{ $warnaCorp }};"></div>
                    </div>
                    <p class="mt-1 text-xs text-slate-400 tabular-nums">{{ number_format($g['eskalasi']) }} dari {{ number_format($g['tiket']) }} tiket</p>
                </div>
            @empty
                <p class="py-8 text-center text-slate-400">Belum ada data pada periode ini.</p>
            @endforelse
        </section>

        {{-- 2. KETEPATAN SLA PER KATEGORI --}}
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Ketepatan SLA per kategori</h3>
            <p class="text-xs text-slate-500 mt-0.5 mb-1">Persentase tiket yang selesai (Done) dalam batas SLA kategorinya, dihitung sejak tiket dibuat.</p>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500 mb-3">
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#059669"></span> ≥ 90%</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#d97706"></span> 75–89%</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#dc2626"></span> &lt; 75%</span>
            </div>

            @forelse($slaKategori as $k)
                <div class="py-2.5">
                    <div class="flex items-center justify-between gap-3">
                        <span class="font-medium text-slate-800 truncate">{{ $k['nama'] }}
                            <span class="font-normal text-xs text-slate-400">· SLA {{ $k['sla_jam'] }} jam</span></span>
                        <span class="text-sm font-semibold tabular-nums" style="color: {{ $warnaSla($k['persen']) }};">{{ $k['persen'] }}%</span>
                    </div>
                    <div class="mt-1.5 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full" style="width: {{ $k['persen'] }}%; background: {{ $warnaSla($k['persen']) }};"></div>
                    </div>
                    <p class="mt-1 text-xs text-slate-400 tabular-nums">
                        {{ number_format($k['dalam']) }} dari {{ number_format($k['total']) }} tiket tepat waktu · rata-rata {{ $fd($k['rata']) }}
                    </p>
                </div>
            @empty
                <p class="py-8 text-center text-slate-400">Belum ada tiket yang selesai pada periode ini.</p>
            @endforelse
        </section>
    </div>

    {{-- 3. BEBAN KERJA PER ADMIN --}}
    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Beban kerja per admin</h3>
                <p class="text-xs text-slate-500 mt-0.5">Jumlah tiket yang ditangani tiap admin, dibagi menurut Business Unit. Klik nama admin untuk melihat rincian tiketnya.</p>
            </div>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-600">
                @foreach($grafikBU as $g)
                    <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background: {{ $g['warna'] }};"></span>{{ $g['label'] }}</span>
                @endforeach
            </div>
        </div>

        {{-- judul kolom --}}
        <div class="hidden md:grid grid-cols-[14rem_minmax(0,1fr)_5rem_8rem_8rem] gap-4 mt-5 px-2 pb-2 border-b border-slate-200 text-xs font-medium text-slate-500">
            <span>Admin</span><span>Tiket per Business Unit</span><span class="text-right">Total</span><span class="text-right">Menunggu diambil</span><span class="text-right">Waktu pengerjaan</span>
        </div>

        @forelse($admin as $a)
            @php $key = json_encode($a['nama'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP); @endphp
            <div class="border-b border-slate-100 last:border-0">
                <button type="button" @click='toggle({!! $key !!})'
                        class="w-full text-left grid grid-cols-1 md:grid-cols-[14rem_minmax(0,1fr)_5rem_8rem_8rem] gap-x-4 gap-y-1 items-center px-2 py-3 hover:bg-slate-50 rounded-lg transition">
                    <span class="flex items-center gap-2 min-w-0">
                        <span class="font-medium text-slate-900 truncate">{{ $a['nama'] }}</span>
                        @if($a['corp'])
                            <span class="shrink-0 text-[10px] font-semibold px-1.5 py-0.5 rounded text-white" style="background: {{ $warnaCorp }};">KPN Corporate</span>
                        @endif
                    </span>

                    <span class="flex h-5 rounded overflow-hidden bg-slate-100">
                        <span class="flex h-full" style="width: {{ round((array_sum($a['per_bu']) ?: $a['total']) / $maksAdmin * 100) }}%;">
                            @foreach($grafikBU as $g)
                                @php $n = $a['per_bu'][$g['id']] ?? 0; @endphp
                                @if($n > 0)
                                    <span class="h-full" style="width: {{ round($n / max(1, array_sum($a['per_bu'])) * 100) }}%; background: {{ $g['warna'] }};"
                                          title="{{ $g['label'] }}: {{ $n }} tiket"></span>
                                @endif
                            @endforeach
                        </span>
                    </span>

                    <span class="md:text-right font-semibold tabular-nums">{{ number_format($a['total']) }} <span class="md:hidden font-normal text-xs text-slate-400">tiket</span></span>
                    <span class="md:text-right tabular-nums text-slate-700"><span class="md:hidden text-xs text-slate-400">Menunggu diambil · </span>{{ $fd($a['tunggu']) }}</span>
                    <span class="md:text-right tabular-nums text-slate-700"><span class="md:hidden text-xs text-slate-400">Pengerjaan · </span>{{ $fd($a['done']) }}</span>
                </button>

                {{-- rincian tiket admin --}}
                <div x-show='buka === {!! $key !!}' x-cloak class="mx-2 mb-3 rounded-lg border border-slate-200 bg-slate-50">
                    <div x-show='memuat && !cache[{!! $key !!}]' class="p-4 text-slate-500">Memuat…</div>
                    <div x-show="galat" class="p-4 text-red-600" x-text="galat"></div>

                    <template x-if='cache[{!! $key !!}]'>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-xs">
                                <thead class="text-slate-500">
                                    <tr class="border-b border-slate-200">
                                        <th class="px-3 py-2 text-left font-medium">Tiket</th>
                                        <th class="px-3 py-2 text-left font-medium">Business Unit</th>
                                        <th class="px-3 py-2 text-left font-medium">Dibuat</th>
                                        <th class="px-3 py-2 text-left font-medium">Diambil</th>
                                        <th class="px-3 py-2 text-left font-medium">Akhir</th>
                                        <th class="px-3 py-2 text-right font-medium">Dikerjakan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    <template x-for="(d, i) in cache[{!! $key !!}].daftar" :key="d.nomor + '-' + i">
                                        <tr class="hover:bg-white">
                                            <td class="px-3 py-2">
                                                <a :href="d.url" class="text-blue-600 hover:underline font-medium" x-text="d.nomor"></a>
                                                <div class="text-slate-500 truncate max-w-[16rem]" x-text="d.judul"></div>
                                            </td>
                                            <td class="px-3 py-2 text-slate-700" x-text="d.bu"></td>
                                            <td class="px-3 py-2 text-slate-700 whitespace-nowrap" x-text="d.laporan"></td>
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                <span x-text="d.diambil"></span>
                                                <span x-show="d.tunggu" class="text-amber-600"> (+<span x-text="d.tunggu"></span>)</span>
                                            </td>
                                            <td class="px-3 py-2 whitespace-nowrap">
                                                <span class="inline-block rounded-full px-2 py-0.5 font-semibold"
                                                      :class="{
                                                        'bg-green-100 text-green-700': d.kode === 'DONE',
                                                        'bg-purple-100 text-purple-700': d.kode === 'OVER',
                                                        'bg-sky-100 text-sky-700': d.kode === 'ALIH',
                                                        'bg-amber-100 text-amber-700': d.kode === 'BERJALAN',
                                                        'bg-slate-200 text-slate-600': d.kode === 'CLOSED'
                                                      }" x-text="d.hasil"></span>
                                                <span class="text-slate-500" x-text="d.akhir"></span>
                                            </td>
                                            <td class="px-3 py-2 text-right font-semibold tabular-nums text-slate-800" x-text="d.proses"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                </div>
            </div>
        @empty
            <p class="py-10 text-center text-slate-400">Belum ada admin yang mengambil tiket pada periode ini.</p>
        @endforelse

        @if($adminLain > 0)
            <p class="mt-3 text-xs text-slate-400">+ {{ $adminLain }} admin lain dengan jumlah tiket lebih sedikit tidak ditampilkan.</p>
        @endif
    </section>

    {{-- CARA MEMBACA --}}
    <details class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-xs text-slate-600">
        <summary class="cursor-pointer font-medium text-slate-700">Cara membaca angka di halaman ini</summary>
        <ul class="mt-2 space-y-1 list-disc pl-5">
            <li><b>Menunggu diambil</b>: sejak tiket dibuat sampai diambil admin.</li>
            <li><b>Waktu pengerjaan</b>: sejak diambil admin sampai status Done (bukan Closed). 1 hari = 24 jam; sisanya dihitung dalam jam.</li>
            <li><b>Diteruskan ke GA Corp</b>: tiket yang pernah diminta bantuannya ke GA Corp.</li>
            <li><b>Ketepatan SLA</b>: tiket yang selesai (Done) dalam batas jam SLA kategorinya, dihitung sejak tiket dibuat.</li>
            <li><b>KPN Corporate</b> pada nama admin: admin dari BU KPN Corporate / GA Corp.</li>
        </ul>
    </details>
</div>

<script>
function grafikHelp() {
    return {
        buka: null, memuat: false, galat: '', cache: {},

        async toggle(nama) {
            if (this.buka === nama) { this.buka = null; return; }
            this.buka = nama; this.galat = '';
            if (this.cache[nama]) return;

            const el = this.$root;
            const params = new URLSearchParams();
            // hanya kirim parameter yang berisi (jangan kirim null/kosong sebagai teks "null")
            const q = JSON.parse(el.dataset.query || '{}');
            Object.keys(q).forEach(k => {
                if (q[k] !== null && q[k] !== '') params.set(k, q[k]);
            });
            params.set('admin', nama);

            this.memuat = true;
            try {
                const res = await fetch(el.dataset.url + '?' + params.toString(), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                this.cache = { ...this.cache, [nama]: await res.json() };
            } catch (e) {
                this.galat = 'Gagal memuat rincian tiket (' + e.message + ').';
            } finally {
                this.memuat = false;
            }
        }
    };
}
</script>
@endsection