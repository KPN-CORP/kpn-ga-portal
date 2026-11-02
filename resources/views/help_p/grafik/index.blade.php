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

    $maksAdmin = max(1, collect($admin)->max(fn ($a) => array_sum($a['per_bu']) ?: $a['total']));
    $warnaSla  = fn ($p) => $p >= 90 ? '#059669' : ($p >= 75 ? '#d97706' : '#dc2626');
@endphp

<div x-data="grafikHelp()" class="text-sm text-slate-800 font-sans">
    <script type="application/json" id="data-rincian">@json($rincian)</script>

    {{-- HEADER --}}
    <div class="flex flex-wrap items-end justify-between gap-2 mb-6">
        <div>
            <h2 class="text-xl md:text-2xl font-bold text-slate-900">Dashboard Help Desk</h2>
            <p class="text-slate-500 mt-0.5">Ringkasan penanganan tiket · periode {{ $periode }}. <b class="text-slate-700">Klik batang mana pun</b> untuk melihat tiket di baliknya.</p>
        </div>
        <span class="text-xs text-slate-500 bg-slate-100 rounded-full px-3 py-1">{{ number_format($ringkasan['tiket']) }} tiket dianalisis</span>
    </div>

    <div class="mb-6">
        @include('help_p.partials.grafik-filter', ['aksi' => 'help.grafik', 'tampilStatus' => false])
    </div>

    @if($terpotong)
        <p class="mb-6 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
            Data dibatasi 1000 tiket terbaru. Persempit periode atau Business Unit agar semua tiket ikut dihitung.
        </p>
    @endif

    {{-- KIRI: dashboard · KANAN: panel tiket (muncul saat batang diklik) --}}
    <div class="grid grid-cols-1 gap-6 items-start"
         :class="panel ? 'lg:grid-cols-[minmax(0,1fr)_27rem]' : 'lg:grid-cols-1'">

        <div class="space-y-6 min-w-0">

            {{-- KPI --}}
            <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
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
                    <p class="mt-1.5 text-2xl font-semibold tabular-nums" style="color: {{ $warnaCorp }};">{{ $ringkasan['persen_esk'] !== null ? $ringkasan['persen_esk'] . '%' : '-' }}</p>
                    <p class="mt-1 text-xs text-slate-400">{{ number_format($ringkasan['eskalasi']) }} dari {{ number_format($ringkasan['tiket']) }} tiket</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Selesai tepat waktu (SLA)</p>
                    <p class="mt-1.5 text-2xl font-semibold tabular-nums" @if($ringkasan['sla'] !== null) style="color: {{ $warnaSla($ringkasan['sla']) }};" @endif>{{ $ringkasan['sla'] !== null ? $ringkasan['sla'] . '%' : '-' }}</p>
                    <p class="mt-1 text-xs text-slate-400">dari {{ number_format($ringkasan['sla_total']) }} tiket yang selesai</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-6" :class="panel ? '' : 'xl:grid-cols-2'">

                {{-- 1. KETERGANTUNGAN PADA GA CORP --}}
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">BU mana yang paling sering minta bantuan GA Corp?</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Persen tiket tiap Business Unit yang diteruskan ke GA Corp.</p>
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
                                <p class="mt-1 text-xs text-slate-400 tabular-nums">{{ number_format($g['eskalasi']) }} dari {{ number_format($g['tiket']) }} tiket diteruskan</p>
                            </button>
                        @empty
                            <p class="py-8 text-center text-slate-400">Belum ada data pada periode ini.</p>
                        @endforelse
                    </div>
                </section>

                {{-- 2. SLA PER KATEGORI --}}
                <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">Apakah tiket selesai tepat waktu?</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Persen tiket per kategori yang selesai (Done) dalam batas jam SLA, dihitung sejak tiket dibuat.</p>
                    @if($insSla)
                        <p class="mt-3 text-xs rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-slate-700">{{ $insSla }}</p>
                    @endif
                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#059669"></span> ≥ 90%</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#d97706"></span> 75–89%</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full" style="background:#dc2626"></span> &lt; 75%</span>
                    </div>

                    <div class="mt-2">
                        @forelse($slaKategori as $k)
                            <button type="button" @click='bukaSLA({{ $k['id'] }}, @json($k['nama']), {{ $k['sla_jam'] }})'
                                    class="group w-full text-left rounded-lg px-2 py-2.5 hover:bg-slate-50 transition"
                                    :class="pilih === 'sla:{{ $k['id'] }}' ? 'bg-slate-50 ring-1 ring-slate-300' : ''">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-medium text-slate-800 truncate">{{ $k['nama'] }} <span class="font-normal text-xs text-slate-400">· SLA {{ $k['sla_jam'] }} jam</span></span>
                                    <span class="inline-flex items-center gap-2">
                                        <span class="text-sm font-semibold tabular-nums" style="color: {{ $warnaSla($k['persen']) }};">{{ $k['persen'] }}%</span>
                                        <span class="text-slate-300 group-hover:text-slate-500">›</span>
                                    </span>
                                </div>
                                <div class="mt-1.5 h-2.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div class="h-full rounded-full" style="width: {{ $k['persen'] }}%; background: {{ $warnaSla($k['persen']) }};"></div>
                                </div>
                                <p class="mt-1 text-xs text-slate-400 tabular-nums">{{ number_format($k['dalam']) }} dari {{ number_format($k['total']) }} tiket tepat waktu · rata-rata selesai {{ $fd($k['rata']) }}</p>
                            </button>
                        @empty
                            <p class="py-8 text-center text-slate-400">Belum ada tiket yang selesai pada periode ini.</p>
                        @endforelse
                    </div>
                </section>
            </div>

            {{-- 3. BEBAN KERJA PER ADMIN --}}
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Siapa yang menangani tiket?</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Jumlah tiket yang diambil tiap admin, dibagi menurut Business Unit.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-600">
                        @foreach($grafikBU as $g)
                            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm" style="background: {{ $g['warna'] }};"></span>{{ $g['label'] }}</span>
                        @endforeach
                    </div>
                </div>
                @if($insAdmin)
                    <p class="mt-3 text-xs rounded-lg bg-slate-50 border border-slate-200 px-3 py-2 text-slate-700">{{ $insAdmin }}</p>
                @endif

                <div class="hidden md:grid grid-cols-[13rem_minmax(0,1fr)_4rem_7rem_7rem] gap-4 mt-4 px-2 pb-2 border-b border-slate-200 text-xs font-medium text-slate-500">
                    <span>Admin</span><span>Tiket per Business Unit</span><span class="text-right">Total</span><span class="text-right">Menunggu diambil</span><span class="text-right">Pengerjaan</span>
                </div>

                @forelse($admin as $a)
                    <button type="button" @click='bukaAdmin(@json($a['nama']))'
                            class="group w-full text-left grid grid-cols-1 md:grid-cols-[13rem_minmax(0,1fr)_4rem_7rem_7rem] gap-x-4 gap-y-1 items-center px-2 py-2.5 hover:bg-slate-50 rounded-lg transition border-b border-slate-100 last:border-0"
                            :class='pilih === "adm:" + @json($a['nama']) ? "bg-slate-50 ring-1 ring-slate-300" : ""'>
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
                                        <span class="h-full" style="width: {{ round($n / max(1, array_sum($a['per_bu'])) * 100) }}%; background: {{ $g['warna'] }};" title="{{ $g['label'] }}: {{ $n }} tiket"></span>
                                    @endif
                                @endforeach
                            </span>
                        </span>
                        <span class="md:text-right font-semibold tabular-nums">{{ number_format($a['total']) }} <span class="md:hidden font-normal text-xs text-slate-400">tiket</span></span>
                        <span class="md:text-right tabular-nums text-slate-700"><span class="md:hidden text-xs text-slate-400">Menunggu diambil · </span>{{ $fd($a['tunggu']) }}</span>
                        <span class="md:text-right tabular-nums text-slate-700"><span class="md:hidden text-xs text-slate-400">Pengerjaan · </span>{{ $fd($a['done']) }}</span>
                    </button>
                @empty
                    <p class="py-10 text-center text-slate-400">Belum ada admin yang mengambil tiket pada periode ini.</p>
                @endforelse

                @if($adminLain > 0)
                    <p class="mt-3 text-xs text-slate-400">+ {{ $adminLain }} admin lain dengan jumlah tiket lebih sedikit tidak ditampilkan.</p>
                @endif
            </section>

            <details class="rounded-xl border border-slate-200 bg-white px-5 py-3 text-xs text-slate-600">
                <summary class="cursor-pointer font-medium text-slate-700">Cara membaca angka di halaman ini</summary>
                <ul class="mt-2 space-y-1 list-disc pl-5">
                    <li><b>Menunggu diambil</b>: sejak tiket dibuat sampai diambil admin.</li>
                    <li><b>Waktu pengerjaan</b>: sejak diambil admin sampai status Done (bukan Closed). 1 hari = 24 jam; sisanya dalam jam.</li>
                    <li><b>Diteruskan ke GA Corp</b>: tiket yang pernah diminta bantuannya ke GA Corp.</li>
                    <li><b>Selesai tepat waktu</b>: tiket Done dalam batas jam SLA kategorinya, dihitung sejak tiket dibuat.</li>
                    <li><b>KPN Corporate</b> di nama admin: admin dari BU KPN Corporate / GA Corp.</li>
                </ul>
            </details>
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
                        <button type="button" @click="tutup()" class="text-slate-400 hover:text-slate-600 text-xl leading-none" aria-label="Tutup">&times;</button>
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
                        <input type="search" x-model="cari" @input="hal = 0" placeholder="Cari nomor, judul, atau nama admin"
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
                                    <p class="text-xs text-slate-500 mt-0.5"><span x-text="t.bn"></span> · <span x-text="t.kn"></span></p>

                                    <ol class="mt-2 space-y-1 text-xs text-slate-600 border-l-2 border-slate-200 pl-3">
                                        <li>Dibuat · <span x-text="t.dibuat"></span></li>
                                        <li x-show="t.diambil">Diambil · <span x-text="t.diambil"></span>
                                            <span x-show="t.admin.length"> oleh <b x-text="t.admin[0]"></b></span>
                                            <span x-show="t.tunggu" class="text-amber-600 font-semibold">(menunggu <span x-text="t.tunggu"></span>)</span>
                                        </li>
                                        <li x-show="t.esk" class="text-purple-700">Diteruskan ke GA Corp · <span x-text="t.over"></span></li>
                                        <li x-show="t.admin.length > 1">Admin lain: <span x-text="t.admin.slice(1).join(', ')"></span></li>
                                        <li x-show="t.done">Done · <span x-text="t.done"></span>
                                            <span x-show="t.proses" class="text-blue-600 font-semibold">(dikerjakan <span x-text="t.proses"></span>)</span>
                                        </li>
                                        <li x-show="!t.done" class="text-slate-400">Belum Done</li>
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
                ringkas: esk + ' dari ' + s.length + ' tiket diteruskan ke GA Corp (' + (s.length ? Math.round(esk / s.length * 100) : 0) + '%)',
                chips: [
                    { k: 'esk', l: 'Diteruskan ke GA Corp', f: t => t.esk },
                    { k: 'semua', l: 'Semua tiket', f: () => true },
                ],
                aktif: esk ? 'esk' : 'semua',
            });
        },

        bukaSLA(id, nama, sla) {
            const s = this.data.filter(t => t.kat === id && t.dalam !== null);
            const dalam = s.filter(t => t.dalam).length;
            this.buka('sla:' + id, {
                tipe: 'sla', judul: nama + ' · SLA ' + sla + ' jam', sumber: s,
                ringkas: dalam + ' dari ' + s.length + ' tiket selesai tepat waktu (' + (s.length ? Math.round(dalam / s.length * 100) : 0) + '%)',
                chips: [
                    { k: 'lewat', l: 'Melewati SLA', f: t => t.dalam === false },
                    { k: 'tepat', l: 'Tepat waktu', f: t => t.dalam === true },
                    { k: 'semua', l: 'Semua', f: () => true },
                ],
                aktif: dalam < s.length ? 'lewat' : 'semua',
            });
        },

        bukaAdmin(nama) {
            const s = this.data.filter(t => t.admin.includes(nama));
            this.buka('adm:' + nama, {
                tipe: 'admin', judul: nama, sumber: s,
                ringkas: s.length + ' tiket ditangani',
                chips: [{ k: 'semua', l: 'Semua tiket', f: () => true }],
                aktif: 'semua',
            });
        },
    };
}
</script>
@endsection