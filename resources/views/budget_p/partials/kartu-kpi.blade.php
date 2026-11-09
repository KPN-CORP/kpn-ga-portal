{{-- params: $kpi --}}
@php $F = \App\Support\budget_s\BudgetFormat::class; @endphp
<div class="kpi">
    <div class="card"><span class="lbl">Total budget</span><b>{{ $F::rupiah($kpi['budget']) }}</b></div>
    <div class="card"><span class="lbl">Realisasi (dari tagihan)</span><b>{{ $F::rupiah($kpi['realisasi']) }}</b></div>
    <div class="card"><span class="lbl">Sisa budget</span><b class="{{ $kpi['sisa'] < 0 ? 'b-er' : '' }}">{{ $F::rupiah($kpi['sisa']) }}</b></div>
    <div class="card"><span class="lbl">Pemakaian</span><b class="{{ $kpi['status']['kelas'] }}">{{ $F::persen($kpi['persen']) }}</b>
        <div class="lbl">{{ $kpi['jumlah'] }} item,
            @if($kpi['melebihi'] > 0 && !empty($melebihi))
                <a href="#" role="button" onclick="buka('modal-melebihi'); return false" title="Klik untuk melihat item yang melebihi budget" style="color:var(--er);font-weight:600;text-decoration:underline">{{ $kpi['melebihi'] }} melebihi budget</a>
            @else
                {{ $kpi['melebihi'] }} melebihi budget
            @endif
        </div>
    </div>
</div>
