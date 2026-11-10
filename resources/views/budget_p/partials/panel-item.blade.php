{{-- Potongan HTML isi popup rincian. params: $item, $tahun, $budget, $real, $bisaUbah, $rincian (tagihan per bulan) --}}
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
        @php $bb = (float) ($budget[$b] ?? 0); $rr = (float) ($real[$b] ?? 0); $baris = $rincian[$b] ?? collect(); @endphp
        <tr @if($baris->isNotEmpty()) style="cursor:pointer" title="Klik untuk melihat rincian invoice bulan ini" onclick="var d=document.getElementById('rb-{{ $item->id }}-{{ $b }}');d.hidden=!d.hidden;this.querySelector('.pnl').textContent=d.hidden?'\u25B8':'\u25BE'" @endif>
            <td>@if($baris->isNotEmpty())<span class="pnl" style="color:var(--mu)">&#9656;</span> @endif{{ $F::BULAN[$b] }}@if($baris->count() > 1) <span class="bd b-wn">{{ $baris->count() }} invoice</span>@endif</td>
            <td class="n">{{ $F::rupiah($bb) }}</td>
            <td class="n">{{ $F::rupiah($rr) }}</td>
            <td class="n {{ $bb - $rr < 0 ? 'b-er' : '' }}">{{ $F::rupiah($bb - $rr) }}</td>
        </tr>
        @if($baris->isNotEmpty())
        <tr id="rb-{{ $item->id }}-{{ $b }}" hidden>
            <td colspan="4" style="background:var(--bg);padding:8px 10px">
                <table>
                    <tr><th>Keterangan</th><th class="n">Tagihan</th><th class="n">PPN</th><th class="n">Materai</th><th class="n">Total</th><th>Diinput oleh</th></tr>
                    @foreach($baris as $t)
                        <tr>
                            <td>{{ $t->keterangan ?: '-' }}@if($t->isian_id) <span class="bd b-mu">dari form</span>@endif</td>
                            <td class="n">{{ $F::rupiah($t->tagihan) }}</td>
                            <td class="n">{{ $F::rupiah($t->ppn) }}</td>
                            <td class="n">{{ $F::rupiah($t->biaya_materai) }}</td>
                            <td class="n"><b>{{ $F::rupiah($t->total) }}</b></td>
                            <td>{{ $t->penginput->name ?? '-' }}<div class="lbl">{{ optional($t->created_at)->format('d/m/Y H:i') }}</div></td>
                        </tr>
                    @endforeach
                    <tr><th>Total {{ $F::BULAN[$b] }}</th><th class="n">{{ $F::rupiah($baris->sum('tagihan')) }}</th><th class="n">{{ $F::rupiah($baris->sum('ppn')) }}</th><th class="n">{{ $F::rupiah($baris->sum('biaya_materai')) }}</th><th class="n">{{ $F::rupiah($baris->sum('total')) }}</th><th></th></tr>
                </table>
            </td>
        </tr>
        @endif
    @endforeach
    <tr><th>Total</th><th class="n">{{ $F::rupiah($tb) }}</th><th class="n">{{ $F::rupiah($tr) }}</th><th class="n">{{ $F::rupiah($tb - $tr) }}</th></tr>
</table>
</div>
<div class="row" style="margin-top:12px">
    <a class="btn sm" href="{{ route('budgeting.tagihan.index', ['tahun' => $tahun, 'item_id' => $item->id]) }}">Lihat tagihan item ini</a>
    <a class="btn sm" href="{{ route('budgeting.budget.index', ['tahun' => $tahun, 'q' => $item->nama]) }}">Buka di halaman Budget</a>
</div>