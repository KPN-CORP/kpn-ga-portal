{{-- Potongan HTML isi popup rincian. params: $item, $tahun, $budget, $real, $bisaUbah --}}
@php
    $F = \App\Support\budget_s\BudgetFormat::class;
    $tb = (float) collect($budget)->sum();
    $tr = (float) collect($real)->sum();
@endphp
<div class="f" style="margin-bottom:12px">
    <div><span class="lbl">Item</span><div><b>{{ $item->nama }}</b></div></div>
    <div><span class="lbl">Kategori / subkategori</span><div>{{ $item->sub->kategori->nama }} / {{ $item->sub->nama }}</div></div>
    <div><span class="lbl">BU</span><div>{{ $item->bu ?: '-' }}</div></div>
    <div><span class="lbl">PT</span><div>{{ $item->pt ?: '-' }}</div></div>
    <div><span class="lbl">Departemen</span><div>{{ \App\Support\budget_s\BudgetPeran::namaDept($item->dept_head_user_id) }}</div></div>
    <div><span class="lbl">PIC</span><div>{{ $item->pic->name ?? 'Belum ditunjuk' }}</div></div>
</div>
@if($item->sumber || $item->keterangan)
    <p class="lbl">{{ $item->sumber ? 'Sumber: ' . $item->sumber . '. ' : '' }}{{ $item->keterangan }}</p>
@endif
<div class="scroll">
<table>
    <tr><th>Bulan {{ $tahun }}</th><th class="n">Budget</th><th class="n">Realisasi</th><th class="n">Sisa</th></tr>
    @foreach(range(1, 12) as $b)
        @php $bb = (float) ($budget[$b] ?? 0); $rr = (float) ($real[$b] ?? 0); @endphp
        <tr>
            <td>{{ $F::BULAN[$b] }}</td>
            <td class="n">{{ $F::rupiah($bb) }}</td>
            <td class="n">{{ $F::rupiah($rr) }}</td>
            <td class="n {{ $bb - $rr < 0 ? 'b-er' : '' }}">{{ $F::rupiah($bb - $rr) }}</td>
        </tr>
    @endforeach
    <tr><th>Total</th><th class="n">{{ $F::rupiah($tb) }}</th><th class="n">{{ $F::rupiah($tr) }}</th><th class="n">{{ $F::rupiah($tb - $tr) }}</th></tr>
</table>
</div>
<div class="row" style="margin-top:12px">
    <a class="btn sm" href="{{ route('budgeting.tagihan.index', ['tahun' => $tahun, 'item_id' => $item->id]) }}">Lihat tagihan item ini</a>
    <a class="btn sm" href="{{ route('budgeting.budget.index', ['tahun' => $tahun, 'q' => $item->nama]) }}">Buka di halaman Budget</a>
</div>