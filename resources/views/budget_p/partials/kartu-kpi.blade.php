{{-- params: $kpi --}}
@php $F = \App\Support\budget_s\BudgetFormat::class; @endphp
<div class="kpi">
    <div class="card"><span class="lbl">Total budget</span><b>{{ $F::rupiah($kpi['budget']) }}</b></div>
    <div class="card"><span class="lbl">Realisasi (dari tagihan)</span><b>{{ $F::rupiah($kpi['realisasi']) }}</b></div>
    <div class="card"><span class="lbl">Sisa budget</span><b class="{{ $kpi['sisa'] < 0 ? 'b-er' : '' }}">{{ $F::rupiah($kpi['sisa']) }}</b></div>
    <div class="card"><span class="lbl">Pemakaian</span><b>{{ $F::persen($kpi['persen']) }}</b>
        <span class="bd {{ $kpi['status']['kelas'] }}">{{ $kpi['status']['label'] }}</span>
        <div class="lbl">{{ $kpi['jumlah'] }} item, {{ $kpi['melebihi'] }} melebihi budget</div>
    </div>
</div>
