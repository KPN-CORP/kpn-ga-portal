@extends('layouts.app-budget-sidebar')
@section('title', 'Dashboard budget')
@section('content')
@php $F = \App\Support\budget_s\BudgetFormat::class; @endphp

@include('budget_p.partials.header-tahun', ['judul' => 'Dashboard budget'])
@include('budget_p.partials.filter', ['pakaiKategori' => true, 'pakaiBu' => true, 'pakaiQ' => false])

@if($kpi['jumlah'] === 0)
    @include('budget_p.partials.kosong', [
        'pesan' => $peran === 'pic'
            ? 'Belum ada item yang ditunjuk ke Anda. Minta Dept Head menunjuk Anda pada item budget.'
            : 'Belum ada item budget untuk filter ini.',
        'tautan' => $peran === 'pic' ? null : route('budgeting.budget.index', ['tahun' => $tahun]),
        'teksTautan' => 'Buka halaman Budget',
    ])
@else
    @include('budget_p.partials.kartu-kpi')

    <div class="row" style="margin-bottom:12px">
        <button type="button" class="btn" data-g="{{ json_encode(array_filter(['tahun' => $tahun, 'bu' => $filter['bu'], 'kategori_id' => $filter['kategori_id']])) }}" onclick="grafik(JSON.parse(this.dataset.g))">Grafik bulanan</button>
        <a class="btn" href="{{ route('budgeting.unduh', array_filter(['tahun' => $tahun, 'bu' => $filter['bu'], 'kategori_id' => $filter['kategori_id']])) }}">Unduh CSV</a>
    </div>

    @foreach($perKategori as $k)
        @php $perSub = $k['items']->groupBy(fn ($i) => $i->sub->id)->sortBy(fn ($g) => $g->first()->sub->urut); @endphp
        <details class="kat lv1 c{{ $loop->index % 5 }}" name="grup-kategori" @if($loop->first) open @endif>
            <summary>
                @include('budget_p.partials.baris-progres', [
                    'tag' => 'kategori', 'nama' => $k['nama'],
                    'sub' => $perSub->count() . ' sub kategori, ' . $k['items']->count() . ' item',
                    'budget' => $k['budget'], 'realisasi' => $k['realisasi'], 'persen' => $k['persen'], 'status' => $k['status'],
                ])
            </summary>
            <div class="isi">
                @foreach($perSub as $grup)
                    @php
                        $sb = (float) $grup->sum('budget');
                        $sr = (float) $grup->sum('realisasi');
                    @endphp
                    <details class="sub" open>
                        <summary>
                            @include('budget_p.partials.baris-progres', [
                                'tag' => 'sub', 'nama' => $grup->first()->sub->nama,
                                'sub' => $grup->count() . ' item',
                                'budget' => $sb, 'realisasi' => $sr,
                                'persen' => $sb > 0 ? round($sr / $sb * 100, 1) : null,
                                'status' => \App\Support\budget_s\BudgetStatus::dari($sb, $sr),
                            ])
                        </summary>
                        <div class="isi-sub">
                            @foreach($grup->sortBy('nama') as $i)
                                <div class="itm">
                                    @include('budget_p.partials.baris-progres', [
                                        'nama' => $i->nama,
                                        'sub' => ($i->bu ?: '-') . ' / ' . ($i->pt ?: '-') . ' - PIC: ' . ($i->pic->name ?? 'belum ditunjuk'),
                                        'budget' => $i->budget, 'realisasi' => $i->realisasi, 'persen' => $i->persen, 'status' => $i->status,
                                        'itemId' => $i->id,
                                    ])
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endforeach
            </div>
        </details>
    @endforeach
@endif

@include('budget_p.partials.modal-grafik')
@include('budget_p.partials.modal-rincian')

@push('scripts')
<script>
/* Akordeon kategori: membuka satu kategori menutup kategori lain (cadangan untuk browser tanpa atribut name pada details) */
document.querySelectorAll('details.kat.lv1').forEach(function (d) {
    d.addEventListener('toggle', function () {
        if (!d.open) return;
        document.querySelectorAll('details.kat.lv1').forEach(function (o) { if (o !== d) o.open = false; });
    });
});
</script>
@endpush
@endsection