@extends('layouts.app-budget-sidebar')
@section('title', 'Dashboard budget')
@section('content')
@php $F = \App\Support\budget_s\BudgetFormat::class; @endphp

@include('budget_p.partials.header-tahun', ['judul' => 'Dashboard budget'])
@include('budget_p.partials.filter', ['pakaiKategori' => true, 'pakaiBu' => true, 'pakaiPt' => true, 'pakaiQ' => false])

@if($kpi['jumlah'] === 0)
    @include('budget_p.partials.kosong', [
        'pesan' => 'Belum ada item budget untuk filter ini.',
        'tautan' => route('budgeting.budget.index', ['tahun' => $tahun]),
        'teksTautan' => 'Buka halaman Budget',
    ])
@else
    @include('budget_p.partials.kartu-kpi')

    <div class="row" style="margin-bottom:12px">
        <button type="button" class="btn" data-g="{{ json_encode(array_filter(['tahun' => $tahun, 'bu' => $filter['bu'], 'pt' => $filter['pt'], 'kategori_id' => $filter['kategori_id']]) + ['pie' => 1]) }}" onclick="grafik(JSON.parse(this.dataset.g))">Grafik semua</button>
        <a class="btn" href="{{ route('budgeting.unduh', array_filter(['tahun' => $tahun, 'bu' => $filter['bu'], 'pt' => $filter['pt'], 'kategori_id' => $filter['kategori_id']])) }}">Unduh Excel</a>
    </div>

    @if($perDept)
        @foreach($perDept as $dp)
            <details class="dept" name="grup-dept">
                <summary>
                    @include('budget_p.partials.baris-progres', [
                        'tag' => 'dept', 'nama' => $dp['nama'],
                        'sub' => 'Dept: ' . $dp['orang'] . ', ' . $dp['jumlah'] . ' item' . ($dp['bu'] ? ', BU: ' . $dp['bu'] : ''),
                        'budget' => $dp['budget'], 'realisasi' => $dp['realisasi'], 'persen' => $dp['persen'], 'status' => $dp['status'],
                        'grafik' => array_filter(['tahun' => $tahun, 'bu' => $filter['bu'], 'pt' => $filter['pt'], 'kategori_id' => $filter['kategori_id']]) + ['dept' => $dp['id']],
                    ])
                </summary>
                <div class="isi-dept">
                    @include('budget_p.partials.blok-kategori', ['daftarKategori' => $dp['perKategori'], 'ns' => 'd' . $loop->index, 'deptId' => $dp['id']])
                </div>
            </details>
        @endforeach
    @else
        @include('budget_p.partials.blok-kategori', ['daftarKategori' => $perKategori, 'ns' => 'tunggal'])
    @endif
@endif

@include('budget_p.partials.modal-grafik')
@include('budget_p.partials.modal-melebihi')
@include('budget_p.partials.modal-rincian')

@push('scripts')
<script>
/* Akordeon: membuka satu panel menutup panel lain dalam grup yang sama (atribut name) */
document.querySelectorAll('details.kat.lv1, details.dept').forEach(function (d) {
    d.addEventListener('toggle', function () {
        if (!d.open) return;
        var n = d.getAttribute('name');
        document.querySelectorAll('details[name="' + n + '"]').forEach(function (o) { if (o !== d) o.open = false; });
    });
});
</script>
@endpush
@endsection