@extends('layouts.app-budget-sidebar')
@section('title', 'Realisasi Budget')
@section('content')
@php $F = \App\Support\budget_s\BudgetFormat::class; @endphp

@include('budget_p.partials.header-tahun', ['judul' => 'Realisasi Budget'])

<form method="get" class="card row" style="padding:10px 14px">
    <input type="hidden" name="tahun" value="{{ $tahun }}">
    <label class="row"><span class="lbl">Bulan</span>
        <select name="bulan" onchange="this.form.submit()">
            <option value="">Semua</option>
            @foreach($F::BULAN as $n => $nama)<option value="{{ $n }}" @selected((string) $filter['bulan'] === (string) $n)>{{ $nama }}</option>@endforeach
        </select>
    </label>
    <label class="row"><span class="lbl">Item</span>
        <select name="item_id" onchange="this.form.submit()">
            <option value="">Semua</option>
            @foreach($semuaItem as $i)<option value="{{ $i->id }}" @selected((string) $filter['item_id'] === (string) $i->id)>{{ $i->nama }}</option>@endforeach
        </select>
    </label>
    @if($filter['bulan'] || $filter['item_id'])<a class="btn sm" href="{{ route('budgeting.tagihan.index', ['tahun' => $tahun]) }}">Hapus filter</a>@endif
</form>

<div class="kpi">
    <div class="card"><span class="lbl">Tagihan</span><b>{{ $F::rupiah($ringkas['tagihan']) }}</b></div>
    <div class="card"><span class="lbl">PPN</span><b>{{ $F::rupiah($ringkas['ppn']) }}</b></div>
    <div class="card"><span class="lbl">Biaya materai</span><b>{{ $F::rupiah($ringkas['biaya_materai']) }}</b></div>
    <div class="card"><span class="lbl">Total realisasi</span><b>{{ $F::rupiah($ringkas['total']) }}</b></div>
</div>

<div class="row" style="margin-bottom:12px">
    @if($itemBisaInput->isNotEmpty() && $tahunRow)
        <button type="button" class="btn pr" onclick="tagihanBaru()">Catat realisasi</button>
    @endif
</div>

@if($daftar->isEmpty())
    @include('budget_p.partials.kosong', [
        'pesan' => $itemBisaInput->isEmpty()
            ? 'Belum ada item yang bisa Anda catat realisasinya.'
            : 'Belum ada realisasi untuk filter ini. Tekan "Catat realisasi" untuk menambah.',
    ])
@else
<div class="card scroll" style="padding:0">
<table>
    <tr><th>Bulan</th><th>Item</th><th class="n">Tagihan</th><th class="n">PPN</th><th class="n">Biaya materai</th><th class="n">Total</th><th>Keterangan</th><th>Diinput oleh</th><th></th></tr>
    @foreach($daftar as $t)
        <tr>
            <td>{{ $F::BULAN[$t->bulan] }}</td>
            <td><b>{{ $t->item->nama }}</b><div class="lbl">{{ $t->item->sub->kategori->nama }} / {{ $t->item->sub->nama }}</div></td>
            <td class="n">{{ $F::rupiah($t->tagihan) }}</td>
            <td class="n">{{ $F::rupiah($t->ppn) }}</td>
            <td class="n">{{ $F::rupiah($t->biaya_materai) }}</td>
            <td class="n"><b>{{ $F::rupiah($t->total) }}</b></td>
            <td>{{ $t->keterangan }}</td>
            <td>{{ $t->penginput->name ?? '-' }}</td>
            <td class="n">
                @if($itemBisaInput->contains('id', $t->item_id))
                    <button type="button" class="btn sm" data-tg="{{ json_encode(['id' => $t->id, 'item_id' => $t->item_id, 'bulan' => $t->bulan,
                        'tagihan' => $F::angka($t->tagihan), 'ppn' => $F::angka($t->ppn), 'biaya_materai' => $F::angka($t->biaya_materai), 'keterangan' => $t->keterangan]) }}" onclick="tagihanEdit(this)">Ubah</button>
                    <form method="post" action="{{ route('budgeting.tagihan.hapus', $t) }}" style="display:inline" onsubmit="return confirm('Hapus tagihan ini?')">@csrf @method('DELETE')<button class="btn sm dg">Hapus</button></form>
                @endif
            </td>
        </tr>
    @endforeach
</table>
</div>
<div class="row">
    @if($daftar->previousPageUrl())<a class="btn sm" href="{{ $daftar->previousPageUrl() }}">Sebelumnya</a>@endif
    <span class="lbl">Halaman {{ $daftar->currentPage() }} dari {{ $daftar->lastPage() }}</span>
    @if($daftar->nextPageUrl())<a class="btn sm" href="{{ $daftar->nextPageUrl() }}">Berikutnya</a>@endif
</div>
@endif

<dialog id="modal-tagihan" aria-labelledby="tg-judul">
    <div class="dh"><b id="tg-judul">Catat realisasi</b><button type="button" class="btn sm" onclick="tutup('modal-tagihan')">Tutup</button></div>
    <form class="db" id="tg-form" method="post" action="{{ route('budgeting.tagihan.simpan') }}">
        @csrf
        <input type="hidden" name="id" id="tg-id"><input type="hidden" name="tahun" value="{{ $tahun }}">
        <div class="f">
            <label><span>Item</span>
                <select name="item_id" id="tg-item" required>
                    @foreach($itemBisaInput as $i)<option value="{{ $i->id }}" @selected((string) $filter['item_id'] === (string) $i->id)>{{ $i->nama }} ({{ $i->sub->nama }})</option>@endforeach
                </select>
            </label>
            <label><span>Bulan tagihan</span>
                <select name="bulan" id="tg-bulan" required>@foreach($F::BULAN as $n => $nama)<option value="{{ $n }}" @selected($n === (int) now()->month)>{{ $nama }}</option>@endforeach</select>
            </label>
            <label><span>Tagihan (Rp)</span><input type="number" step="any" min="0" name="tagihan" id="tg-tagihan" required oninput="hitungTotal()"></label>
            <label><span>PPN (Rp)</span><input type="number" step="any" min="0" name="ppn" id="tg-ppn" oninput="hitungTotal()"></label>
            <label><span>Biaya materai (Rp)</span><input type="number" step="any" min="0" name="biaya_materai" id="tg-materai" oninput="hitungTotal()"></label>
            <div><span class="lbl">Total (tagihan + PPN + biaya materai)</span><div><b id="tg-total">Rp 0</b></div></div>
        </div>
        <label style="display:block;margin-top:10px"><span class="lbl">Keterangan</span><textarea name="keterangan" id="tg-ket" rows="2" maxlength="500"></textarea></label>
        <div class="row" style="justify-content:flex-end;margin-top:12px"><button class="btn pr">Simpan tagihan</button></div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
const g = id => document.getElementById(id);
function hitungTotal(){ g('tg-total').textContent = rp((+g('tg-tagihan').value||0)+(+g('tg-ppn').value||0)+(+g('tg-materai').value||0)); }
function tagihanBaru(){ g('tg-form').reset(); g('tg-id').value=''; g('tg-judul').textContent='Catat realisasi'; hitungTotal(); buka('modal-tagihan'); }
function tagihanEdit(btn){
  const d = JSON.parse(btn.dataset.tg); g('tg-form').reset(); g('tg-judul').textContent='Ubah realisasi';
  g('tg-id').value=d.id; g('tg-item').value=d.item_id; g('tg-bulan').value=d.bulan;
  g('tg-tagihan').value=d.tagihan; g('tg-ppn').value=d.ppn; g('tg-materai').value=d.biaya_materai; g('tg-ket').value=d.keterangan||'';
  hitungTotal(); buka('modal-tagihan');
}
</script>
@endpush