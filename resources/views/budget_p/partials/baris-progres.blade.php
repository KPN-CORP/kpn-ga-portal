{{-- params: $nama, $budget, $realisasi, $persen, $status; opsional: $sub, $itemId, $tahun, $tag (kategori|sub) --}}
@php
    $F = \App\Support\budget_s\BudgetFormat::class;
    $lebar = min(100, (float) ($persen ?? ($realisasi > 0 ? 100 : 0)));
    $warna = $status['kode'] === 'lebih' ? 'er' : ($status['kode'] === 'waspada' ? 'wn' : '');
@endphp
<div class="pg">
    <div class="nm">@if(!empty($tag))<span class="tag {{ $tag === 'kategori' ? 't-kat' : 't-sub' }}">{{ $tag === 'kategori' ? 'Kategori' : 'Sub kategori' }}</span>@endif<b>{{ $nama }}</b>@if(!empty($sub))<div class="lbl">{{ $sub }}</div>@endif</div>
    <div class="bw"><div class="bar" role="img" aria-label="Pemakaian {{ $F::persen($persen) }}"><i class="{{ $warna }}" style="width:{{ $lebar }}%"></i></div></div>
    <div class="ang">
        {{ $F::rupiah($realisasi) }} dari {{ $F::rupiah($budget) }}
        <div><span class="bd {{ $status['kelas'] }}">{{ $F::persen($persen) }}</span></div>
    </div>
    @if(!empty($itemId))
        <div class="row">
            <button type="button" class="btn sm" onclick="rincian({{ $itemId }}, {{ $tahun }})">Rincian</button>
            <button type="button" class="btn sm" data-g="{{ json_encode(['tahun' => $tahun, 'item_id' => $itemId]) }}" onclick="grafik(JSON.parse(this.dataset.g))">Grafik</button>
        </div>
    @endif
</div>