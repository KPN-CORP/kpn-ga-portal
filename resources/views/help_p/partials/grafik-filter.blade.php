{{--
    resources/views/help/partials/grafik-filter.blade.php
    Filter bersama halaman Timeline & Grafik.
    Parameter: aksi (nama route halaman), tampilStatus (default true)
--}}
@php
    $namaBulan = [1=>'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
@endphp
@php
    $hariIni      = now();
    $bulanLalu    = $hariIni->copy()->subMonthNoOverflow();
    $tahunLalu    = $hariIni->year - 1;
    $filterLain   = array_filter([
        'bisnis_unit_id' => $bisnisUnitId,
        'search'         => $search !== '' ? $search : null,
        'status'         => ($status ?? 'semua') !== 'semua' ? $status : null,
    ]);

    $cepat = [
        ['label' => 'Bulan Ini (' . $namaBulan[$hariIni->month] . ' ' . $hariIni->year . ')', 'tahun' => $hariIni->year, 'bulan' => $hariIni->month],
        ['label' => 'Bulan Lalu (' . $namaBulan[$bulanLalu->month] . ' ' . $bulanLalu->year . ')', 'tahun' => $bulanLalu->year, 'bulan' => $bulanLalu->month],
        ['label' => 'Tahun Ini (' . $hariIni->year . ')', 'tahun' => $hariIni->year, 'bulan' => ''],
        ['label' => 'Tahun Lalu (' . $tahunLalu . ')', 'tahun' => $tahunLalu, 'bulan' => ''],
    ];
    $adaFilter = request()->anyFilled(['bisnis_unit_id', 'search', 'status']) || request()->has('tahun');
@endphp
<div class="bg-white rounded-lg shadow p-4 border">
    <form method="GET" action="{{ route($aksi) }}" class="grid grid-cols-1 md:grid-cols-2 {{ ($tampilStatus ?? true) ? 'lg:grid-cols-5' : 'lg:grid-cols-4' }} gap-4">
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
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">🔎 Cari Tiket</label>
            <input type="text" name="search" value="{{ $search }}" placeholder="Nomor tiket / judul..."
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">📅 Tahun</label>
            <select name="tahun" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                @foreach($daftarTahun as $th)
                    <option value="{{ $th }}" @selected($th == $tahun)>{{ $th }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">🗓️ Bulan</label>
            <select name="bulan" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                <option value="">Semua bulan</option>
                @foreach($namaBulan as $no => $nm)
                    <option value="{{ $no }}" @selected($no == $bulan)>{{ $nm }}</option>
                @endforeach
            </select>
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

    {{-- Periode cepat --}}
    <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t">
        <span class="w-full sm:w-auto text-xs text-gray-500 sm:self-center sm:mr-1">Periode cepat:</span>
        @foreach($cepat as $p)
            @php $aktifChip = (int) $tahun === (int) $p['tahun'] && (string) $bulan === (string) $p['bulan']; @endphp
            <a href="{{ route($aksi, $filterLain + ['tahun' => $p['tahun'], 'bulan' => $p['bulan']]) }}"
               class="px-3 py-1.5 rounded-full text-xs font-medium {{ $aktifChip ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                {{ $p['label'] }}
            </a>
        @endforeach
    </div>
</div>