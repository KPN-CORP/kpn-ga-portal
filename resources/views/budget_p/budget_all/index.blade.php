@extends('layouts.app-budget-sidebar')
@section('title', 'Budget')
@section('content')
@php
    $F = \App\Support\budget_s\BudgetFormat::class;
    $bisaKelola = in_array($peran, ['admin', 'dept_head'], true);
@endphp

@include('budget_p.partials.header-tahun', ['judul' => 'Budget bulanan'])
@include('budget_p.partials.filter', ['pakaiKategori' => true, 'pakaiBu' => true, 'pakaiQ' => true])

@if($terkunci)
    <div class="msg wn">Tahun {{ $tahun }} dikunci.
        @if($peran === 'admin') Sebagai Admin Anda tetap bisa mengubah budget.@else Budget bulanan tidak bisa diubah sampai Admin membuka kunci.@endif
    </div>
@endif

<div class="row" style="margin-bottom:12px">
    @if($bisaKelola)
        <button type="button" class="btn pr" onclick="itemBaru()">Item baru</button>
        <button type="button" class="btn" onclick="buka('modal-salin')">Salin dari tahun lain</button>
    @endif
    <a class="btn" href="{{ route('budgeting.unduh', array_filter(['tahun' => $tahun, 'bu' => $filter['bu'], 'kategori_id' => $filter['kategori_id']])) }}">Unduh CSV</a>
</div>

@if($items->isEmpty())
    @include('budget_p.partials.kosong', [
        'pesan' => $peran === 'pic' ? 'Belum ada item yang ditunjuk ke Anda.' : 'Belum ada item budget. Mulai dengan menekan "Item baru".',
    ])
@else
<div class="card scroll" style="padding:0">
<table>
    <thead>
        <tr>
            <th>Item</th><th>BU / PT</th><th>PIC</th>
            @foreach(range(1, 12) as $b)<th class="n">{{ $F::BULAN_SINGKAT[$b] }}</th>@endforeach
            <th class="n">Total</th><th></th>
        </tr>
    </thead>
    <tbody>
    @php $subSebelumnya = null; @endphp
    @foreach($items as $i)
        @if($subSebelumnya !== $i->sub_id)
            @php $subSebelumnya = $i->sub_id; @endphp
            <tr><td colspan="17" style="background:var(--bar)"><b>{{ $i->sub->kategori->nama }}</b> / {{ $i->sub->nama }}</td></tr>
        @endif
        @php $nm = $nominal->get($i->id); $boleh = $bolehIsi[$i->id]; @endphp
        <tr>
            <td style="min-width:170px"><b>{{ $i->nama }}</b><div class="lbl">Dept Head: {{ $i->deptHead->name ?? '-' }}</div></td>
            <td style="min-width:150px">{{ $i->bu ?: '-' }}<div class="lbl">{{ $i->pt ?: '-' }}</div></td>
            <td>{{ $i->pic->name ?? '-' }}</td>
            @foreach(range(1, 12) as $b)
                <td class="n"><input type="number" step="any" min="0" name="nominal[{{ $b }}]" form="fb{{ $i->id }}"
                    value="{{ $F::angka($nm?->get($b)) }}" style="width:104px;text-align:right" aria-label="{{ $i->nama }} {{ $F::BULAN[$b] }}" {{ $boleh ? '' : 'disabled' }}></td>
            @endforeach
            <td class="n">{{ $F::rupiah($nm ? $nm->sum() : 0) }}</td>
            <td class="n" style="white-space:nowrap">
                @if($boleh)<button class="btn sm pr" form="fb{{ $i->id }}">Simpan</button>@endif
                @if($bolehUbah[$i->id])
                    <button type="button" class="btn sm" data-item="{{ json_encode([
                        'id' => $i->id, 'sub_id' => $i->sub_id, 'nama' => $i->nama, 'bu' => $i->bu, 'pt' => $i->pt,
                        'dept_head_user_id' => $i->dept_head_user_id, 'pic_user_id' => $i->pic_user_id,
                        'sumber' => $i->sumber, 'keterangan' => $i->keterangan, 'aktif' => $i->aktif]) }}" onclick="itemEdit(this)">Ubah</button>
                    <button class="btn sm dg" form="fh{{ $i->id }}">Hapus</button>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>

@foreach($items as $i)
    <form id="fb{{ $i->id }}" method="post" action="{{ route('budgeting.budget.bulan') }}">@csrf
        <input type="hidden" name="item_id" value="{{ $i->id }}"><input type="hidden" name="tahun" value="{{ $tahun }}"></form>
    @if($bolehUbah[$i->id])
        <form id="fh{{ $i->id }}" method="post" action="{{ route('budgeting.budget.item.hapus', $i) }}"
              onsubmit="return confirm('Hapus item &quot;{{ addslashes($i->nama) }}&quot;? Item yang sudah punya tagihan hanya dinonaktifkan.')">@csrf @method('DELETE')</form>
    @endif
@endforeach
<p class="lbl">Kosongkan kotak bulan untuk menghapus angka bulan itu. Tekan Simpan pada baris item untuk menyimpan 12 bulan sekaligus.</p>
@endif

@if($bisaKelola)
    @include('budget_p.partials.modal-salin')

    <dialog id="modal-item" aria-labelledby="item-judul">
        <div class="dh"><b id="item-judul">Item baru</b><button type="button" class="btn sm" onclick="tutup('modal-item')">Tutup</button></div>
        <form class="db" id="it-form" method="post" action="{{ route('budgeting.budget.item.simpan') }}">
            @csrf
            <input type="hidden" name="id" id="it-id">
            <div class="f">
                <label><span>Subkategori</span>
                    <select name="sub_id" id="it-sub" required>
                        @foreach($subList->groupBy(fn ($s) => $s->kategori->nama) as $namaKat => $subs)
                            <optgroup label="{{ $namaKat }}">@foreach($subs as $s)<option value="{{ $s->id }}">{{ $s->nama }}</option>@endforeach</optgroup>
                        @endforeach
                    </select>
                </label>
                <label><span>Nama item</span><input name="nama" id="it-nama" required maxlength="255"></label>
                <label><span>BU (dari HCIS)</span><select name="bu" id="it-bu" required onchange="muatPt()"></select></label>
                <label><span>PT (dari HCIS)</span><select name="pt" id="it-pt" required></select></label>
                @if($peran === 'admin')
                    <label><span>Dept Head pemilik</span>
                        <select name="dept_head_user_id" id="it-head" required onchange="isiPic()">
                            <option value="">Pilih Dept Head</option>
                            @foreach($kepala as $h)<option value="{{ $h['id'] }}">{{ $h['nama'] }}</option>@endforeach
                        </select>
                    </label>
                @endif
                <label><span>PIC (boleh dikosongkan)</span><select name="pic_user_id" id="it-pic"></select></label>
                <label><span>Sumber</span><input name="sumber" id="it-sumber" maxlength="100"></label>
            </div>
            <label style="display:block;margin-top:10px"><span class="lbl">Keterangan</span><textarea name="keterangan" id="it-ket" rows="2" maxlength="1000"></textarea></label>
            <label style="display:block;margin-top:8px"><input type="hidden" name="aktif" value="0"><input type="checkbox" name="aktif" value="1" id="it-aktif" checked> Item aktif</label>
            <p class="lbl">PIC yang bisa dipilih hanya bawahan Dept Head pemilik item. PIC mengisi budget bulanan dan tagihan pada item ini.</p>
            <div class="row" style="justify-content:flex-end"><button class="btn pr">Simpan item</button></div>
        </form>
    </dialog>
@endif
@endsection

@push('scripts')
@if($bisaKelola)
<script>
const URL_BU = @json(route('budgeting.lookup.bu')), URL_PT = @json(route('budgeting.lookup.pt'));
const PIC_MAP = @json($picMap), PERAN = @json($peran), MY_ID = @json(auth()->id());
const $e = id => document.getElementById(id), JH = {headers:{Accept:'application/json'}};
let buSiap = false;

async function muatBu(){
  if (buSiap) return;
  const d = await (await fetch(URL_BU, JH)).json();
  $e('it-bu').innerHTML = '<option value="">Pilih BU</option>' + d.map(x => `<option>${esc(x)}</option>`).join('');
  buSiap = true;
}
async function muatPt(pilih){
  const bu = $e('it-bu').value, s = $e('it-pt');
  if (!bu){ s.innerHTML = '<option value="">Pilih BU dulu</option>'; return; }
  const d = await (await fetch(URL_PT + '?bu=' + encodeURIComponent(bu), JH)).json();
  s.innerHTML = '<option value="">Pilih PT</option>' + d.map(x => `<option>${esc(x)}</option>`).join('');
  if (pilih) s.value = pilih;
}
function isiPic(pilih){
  const head = PERAN === 'admin' ? $e('it-head').value : MY_ID;
  const d = PIC_MAP[head] || [];
  $e('it-pic').innerHTML = '<option value="">(belum ditunjuk)</option>' + d.map(p => `<option value="${p.id}">${esc(p.nama)}</option>`).join('');
  if (pilih) $e('it-pic').value = String(pilih);
}
async function itemBaru(){
  $e('it-form').reset(); $e('it-id').value = ''; $e('item-judul').textContent = 'Item baru';
  await muatBu(); $e('it-pt').innerHTML = '<option value="">Pilih BU dulu</option>'; isiPic(); buka('modal-item');
}
async function itemEdit(btn){
  const d = JSON.parse(btn.dataset.item);
  $e('it-form').reset(); $e('item-judul').textContent = 'Ubah item';
  $e('it-id').value = d.id; $e('it-sub').value = d.sub_id; $e('it-nama').value = d.nama;
  $e('it-sumber').value = d.sumber || ''; $e('it-ket').value = d.keterangan || ''; $e('it-aktif').checked = !!d.aktif;
  if (PERAN === 'admin') $e('it-head').value = d.dept_head_user_id;
  await muatBu(); $e('it-bu').value = d.bu || ''; await muatPt(d.pt); isiPic(d.pic_user_id); buka('modal-item');
}
</script>
@endif
@endpush
