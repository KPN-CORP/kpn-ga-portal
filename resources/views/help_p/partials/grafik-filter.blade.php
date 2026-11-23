{{--
    resources/views/help_p/partials/grafik-filter.blade.php
    Filter bersama halaman Timeline & Grafik.
    Parameter: aksi (nama route halaman), tampilStatus (default true)
    Periode = SATU tombol rentang tanggal: klik tanggal awal, lalu tanggal akhir (otomatis ditampilkan).
    Parameter URL: ?dari=YYYY-MM-DD&sampai=YYYY-MM-DD   (variabel $rentang disediakan controller)
--}}
@php
    $HTf       = \App\Support\help_s\HelpTimeline::class;
    $dariTgl   = $rentang['awal']->format('Y-m-d');
    $sampaiTgl = $rentang['akhir']->format('Y-m-d');
    $adaFilter = request()->anyFilled(['bisnis_unit_id', 'search', 'status', 'dari', 'sampai', 'tahun']);
@endphp
<div class="bg-white rounded-lg shadow p-4 border">
    <form method="GET" action="{{ route($aksi) }}" class="grid grid-cols-1 md:grid-cols-2 {{ ($tampilStatus ?? true) ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }} gap-4 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">🏢 Business Unit</label>
            @if($bisnisUnitTerkunci)
                <select class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-gray-100 text-gray-600" disabled>
                    @forelse($bisnisUnits as $bu)
                        <option selected>{{ $bu->nama_bisnis_unit }}</option>
                    @empty
                        <option selected>-</option>
                    @endforelse
                </select>
            @else
                <select name="bisnis_unit_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                    <option value="">Semua BU</option>
                    @foreach($bisnisUnits as $bu)
                        <option value="{{ $bu->id_bisnis_unit }}" @selected((string) $bisnisUnitId === (string) $bu->id_bisnis_unit)>
                            {{ $bu->nama_bisnis_unit }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>

        {{-- RENTANG TANGGAL: 1 tombol --}}
        <div x-data="rentangTanggal({ dari: '{{ $dariTgl }}', sampai: '{{ $sampaiTgl }}', hariIni: '{{ now()->format('Y-m-d') }}' })"
             @keydown.escape.window="tutup()" @click.outside="tutup()" class="relative">
            <label class="block text-xs font-medium text-gray-600 mb-1">📅 Periode</label>
            <input type="hidden" name="dari" :value="dari">
            <input type="hidden" name="sampai" :value="sampai">

            <button type="button" @click="open ? tutup() : bukaKalender()"
                    class="w-full flex items-center justify-between gap-2 border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white hover:bg-gray-50 focus:ring-2 focus:ring-blue-500">
                <span class="truncate" x-text="label"></span>
                <span class="text-gray-400 text-xs">▾</span>
            </button>

            <div x-show="open" x-cloak x-transition.opacity
                 class="absolute z-40 mt-2 left-0 md:left-auto md:right-0 lg:left-0 lg:right-auto bg-white border border-gray-200 rounded-xl shadow-xl p-3 w-[min(92vw,44rem)]">
                <div class="flex flex-col sm:flex-row gap-3">
                    {{-- pilihan cepat --}}
                    <div class="sm:w-36 shrink-0 flex sm:flex-col flex-wrap gap-1 sm:border-r sm:pr-3">
                        <template x-for="p in preset" :key="p.k">
                            <button type="button" @click="pakaiPreset(p.k)"
                                    class="text-left text-xs px-2.5 py-1.5 rounded-lg bg-gray-100 sm:bg-transparent hover:bg-gray-100 text-gray-700" x-text="p.l"></button>
                        </template>
                    </div>

                    {{-- kalender 2 bulan --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between mb-2">
                            <button type="button" @click="geser(-1)" class="px-2 py-1 rounded bg-gray-100 hover:bg-gray-200 text-xs font-semibold" title="Bulan sebelumnya">◀</button>
                            <p class="text-xs text-gray-500" x-text="petunjuk"></p>
                            <button type="button" @click="geser(1)" class="px-2 py-1 rounded bg-gray-100 hover:bg-gray-200 text-xs font-semibold" title="Bulan berikutnya">▶</button>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <template x-for="m in bulanTampil" :key="m.kunci">
                                <div>
                                    <p class="text-center text-sm font-semibold text-gray-800 mb-1" x-text="m.judul"></p>
                                    <div class="grid grid-cols-7 text-center text-[10px] text-gray-400 mb-1">
                                        <template x-for="h in ['Sen','Sel','Rab','Kam','Jum','Sab','Min']" :key="h"><span x-text="h"></span></template>
                                    </div>
                                    <div class="grid grid-cols-7 text-center">
                                        <template x-for="(c, i) in m.sel" :key="m.kunci + '-' + i">
                                            <div class="py-0.5" :class="c ? kelasSel(c) : ''">
                                                <button type="button" x-show="c" @click="klik(c)" @mouseenter="hover = c"
                                                        class="w-8 h-8 text-xs rounded-full"
                                                        :class="kelasHari(c)" x-text="c ? parseInt(c.slice(8), 10) : ''"></button>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">🔎 Cari Tiket</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Nomor tiket / judul..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
        </div>

        @if($tampilStatus ?? true)
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">📌 Status</label>
            <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                <option value="semua"  @selected(($status ?? 'semua') === 'semua')>Semua</option>
                <option value="aktif"  @selected(($status ?? '') === 'aktif')>Belum close</option>
                <option value="closed" @selected(($status ?? '') === 'closed')>Sudah closed</option>
            </select>
        </div>
        @endif
        <div class="flex gap-2 md:col-span-2 lg:col-span-full">
            <button type="submit" class="flex-1 lg:flex-none bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                🔍 Tampilkan
            </button>
            @if($adaFilter)
                <a href="{{ route($aksi) }}" class="flex-1 lg:flex-none text-center bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-semibold transition">
                    Reset
                </a>
            @endif
        </div>
    </form>
</div>

@once
<script>
// Pemilih rentang tanggal: klik tanggal awal, lalu tanggal akhir -> halaman langsung dimuat ulang.
function rentangTanggal(cfg) {
    const BLN  = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    const BLNS = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    const p2  = n => String(n).padStart(2, '0');
    const fmt = d => d.getFullYear() + '-' + p2(d.getMonth() + 1) + '-' + p2(d.getDate());
    const urai = s => { const [y, m, d] = s.split('-').map(Number); return new Date(y, m - 1, d); };
    const tampil = s => { const d = urai(s); return d.getDate() + ' ' + BLNS[d.getMonth()] + ' ' + d.getFullYear(); };

    return {
        open: false, dari: cfg.dari, sampai: cfg.sampai, mulai: null, hover: null, thn: 0, bln: 0,
        preset: [
            { k: 'hari7',     l: '7 hari terakhir' },
            { k: 'hari30',    l: '30 hari terakhir' },
            { k: 'bulanIni',  l: 'Bulan ini' },
            { k: 'bulanLalu', l: 'Bulan lalu' },
            { k: 'tahunIni',  l: 'Tahun ini' },
            { k: 'tahunLalu', l: 'Tahun lalu' },
        ],

        get label() {
            return this.dari === this.sampai ? tampil(this.dari) : tampil(this.dari) + ' – ' + tampil(this.sampai);
        },
        get petunjuk() { return this.mulai ? 'Pilih tanggal akhir' : 'Pilih tanggal awal'; },

        // rentang yang disorot: saat baru klik awal -> dari tanggal awal sampai tanggal di bawah kursor
        get sorot() {
            if (this.mulai) {
                const h = this.hover || this.mulai;
                return h < this.mulai ? [h, this.mulai] : [this.mulai, h];
            }
            return [this.dari, this.sampai];
        },

        get bulanTampil() {
            const hasil = [];
            for (let i = 0; i < 2; i++) {
                const d = new Date(this.thn, this.bln + i, 1);
                const y = d.getFullYear(), m = d.getMonth();
                const jml = new Date(y, m + 1, 0).getDate();
                const awal = (d.getDay() + 6) % 7;          // minggu mulai Senin
                const sel = Array(awal).fill(null);
                for (let t = 1; t <= jml; t++) { sel.push(y + '-' + p2(m + 1) + '-' + p2(t)); }
                hasil.push({ kunci: y + '-' + m, judul: BLN[m] + ' ' + y, sel });
            }
            return hasil;
        },

        bukaKalender() {
            const d = urai(this.dari);
            this.thn = d.getFullYear(); this.bln = d.getMonth();
            this.mulai = null; this.hover = null; this.open = true;
        },
        tutup() { this.open = false; this.mulai = null; this.hover = null; },
        geser(n) { const d = new Date(this.thn, this.bln + n, 1); this.thn = d.getFullYear(); this.bln = d.getMonth(); },

        klik(t) {
            if (!this.mulai) { this.mulai = t; this.hover = t; return; }
            const a = t < this.mulai ? t : this.mulai, b = t < this.mulai ? this.mulai : t;
            this.terapkan(a, b);
        },
        terapkan(a, b) {
            this.dari = a; this.sampai = b; this.open = false; this.mulai = null;
            this.$nextTick(() => this.$root.closest('form').submit());
        },

        kelasSel(c) {     // latar rentang (pita) di belakang tanggal
            const [a, b] = this.sorot;
            if (c < a || c > b) return '';
            if (a === b) return '';
            if (c === a) return 'bg-blue-100 rounded-l-full';
            if (c === b) return 'bg-blue-100 rounded-r-full';
            return 'bg-blue-100';
        },
        kelasHari(c) {
            const [a, b] = this.sorot;
            if (c === a || c === b) return 'bg-blue-600 text-white font-semibold';
            if (c > a && c < b) return 'text-blue-900 hover:bg-blue-200';
            return (c === cfg.hariIni ? 'ring-1 ring-blue-400 ' : '') + 'text-gray-700 hover:bg-gray-100';
        },

        pakaiPreset(k) {
            const h = urai(cfg.hariIni), y = h.getFullYear(), m = h.getMonth();
            let a, b;
            if (k === 'hari7')       { a = new Date(y, m, h.getDate() - 6);  b = h; }
            else if (k === 'hari30') { a = new Date(y, m, h.getDate() - 29); b = h; }
            else if (k === 'bulanIni')  { a = new Date(y, m, 1);     b = new Date(y, m + 1, 0); }
            else if (k === 'bulanLalu') { a = new Date(y, m - 1, 1); b = new Date(y, m, 0); }
            else if (k === 'tahunIni')  { a = new Date(y, 0, 1);     b = new Date(y, 11, 31); }
            else                        { a = new Date(y - 1, 0, 1); b = new Date(y - 1, 11, 31); }
            this.terapkan(fmt(a), fmt(b));
        },
    };
}
</script>
@endonce