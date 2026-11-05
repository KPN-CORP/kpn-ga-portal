{{-- params: $nama, $budget, $realisasi, $persen, $status; opsional: $sub, $itemId, $tahun, $tag (dept|kategori|sub), $grafik (array parameter grafik; kalau kosong dan ada $itemId, grafik per item) --}}
@php
    $F = \App\Support\budget_s\BudgetFormat::class;
    $lebar = min(100, (float) ($persen ?? ($realisasi > 0 ? 100 : 0)));
    $warna = $status['kode'] === 'lebih' ? 'er' : ($status['kode'] === 'waspada' ? 'wn' : '');
@endphp
<div class="pg">
    <div class="nm">@if(!empty($tag))<span class="tag t-{{ $tag }}">{{ ['kategori' => 'Kategori', 'sub' => 'Sub kategori', 'dept' => 'Dept Head'][$tag] ?? $tag }}</span>@endif<b>{{ $nama }}</b>@if(!empty($sub))<div class="lbl">{{ $sub }}</div>@endif</div>
    <div class="bw"><div class="bar" role="img" aria-label="Pemakaian {{ $F::persen($persen) }}"><i class="{{ $warna }}" style="width:{{ $lebar }}%"></i></div></div>
    <div class="ang">
        {{ $F::rupiah($realisasi) }} dari {{ $F::rupiah($budget) }}
        <div><span class="bd {{ $status['kelas'] }}">{{ $F::persen($persen) }}</span></div>
    </div>
    @php $gp = $grafik ?? (!empty($itemId) ? ['tahun' => $tahun, 'item_id' => $itemId] : null); @endphp
    @if(!empty($itemId) || $gp)
        <div class="row">
            @if(!empty($itemId))
                <button type="button" class="btn sm" onclick="event.stopPropagation(); rincian({{ $itemId }}, {{ $tahun }})">Rincian</button>
            @endif
            @if($gp)
                <button type="button" class="btn sm" data-g="{{ json_encode($gp) }}" onclick="event.stopPropagation(); grafik(JSON.parse(this.dataset.g))">Grafik</button>
            @endif
        </div>
    @endif
</div>
