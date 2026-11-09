@extends('layouts.app-budget-sidebar')
@section('title', 'Budget')
@section('content')
@php
    $F = \App\Support\budget_s\BudgetFormat::class;
    $bisaKelola = in_array($peran, ['admin', 'dept_head', 'pic'], true);
@endphp

@include('budget_p.partials.header-tahun', ['judul' => 'Budget bulanan'])
@include('budget_p.partials.filter', ['pakaiKategori' => true, 'pakaiSub' => true, 'subFilterList' => $subFilterList, 'pakaiBu' => true, 'pakaiPt' => true, 'pakaiQ' => true])

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
    <a class="btn" href="{{ route('budgeting.unduh', array_filter(['tahun' => $tahun, 'bu' => $filter['bu'], 'pt' => $filter['pt'], 'kategori_id' => $filter['kategori_id']])) }}">Unduh Excel</a>
</div>

@if(!$adaFilter)
    @include('budget_p.partials.kosong', [
        'pesan' => 'Pilih Kategori, Sub, BU, atau PT, atau ketik nama item di kolom Cari untuk menampilkan item budget.',
    ])
@elseif($items->isEmpty())
    @include('budget_p.partials.kosong', [
        'pesan' => 'Tidak ada item yang cocok dengan filter ini.',
    ])
@else
<div class="card scroll tbl-beku" style="padding:0">
<table>
    <thead>
        <tr>
            <th class="beku">Item</th><th>BU / PT</th><th>PIC</th>
            @foreach(range(1, 12) as $b)<th class="n">{{ $F::BULAN_SINGKAT[$b] }}</th>@endforeach
            <th class="n">Total</th><th></th>
        </tr>
    </thead>
    <tbody>
    @php $subSebelumnya = null; @endphp
    @foreach($items as $i)
        @if($subSebelumnya !== $i->sub_id)
            @php $subSebelumnya = $i->sub_id; @endphp
            <tr><td colspan="17" style="background:var(--bar)"><span class="grp-label"><b>{{ $i->sub->kategori->nama }}</b> / {{ $i->sub->nama }}</span></td></tr>
        @endif
        @php $nm = $nominal->get($i->id); $boleh = $bolehIsi[$i->id]; @endphp
        <tr>
            <td class="beku" style="min-width:200px;max-width:240px"><b>{{ $i->nama }}</b><div class="lbl">Dept: {{ \App\Support\budget_s\BudgetPeran::namaDept($i->dept_head_user_id) }}</div>
                @if($i->tahun_mulai || $i->tahun_akhir)<div class="lbl">Berlaku {{ $i->tahun_mulai ?: 'awal' }} - {{ $i->tahun_akhir ?: 'sekarang' }}</div>@endif
                @unless($i->masihBerlaku($tahun))<div class="lbl">Sudah tidak berlaku di {{ $tahun }}, data tahun ini tetap tersimpan.</div>@endunless</td>
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
                    @if(!empty($itemTerkunci[$i->id]))
                        <button type="button" class="btn sm dg" disabled title="Item punya data di tahun yang dikunci. Minta Admin membuka kunci dulu.">Nonaktifkan</button>
                    @else
                        <button class="btn sm dg" form="fh{{ $i->id }}">Nonaktifkan</button>
                    @endif
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
              onsubmit="return confirm('Nonaktifkan item &quot;{{ addslashes($i->nama) }}&quot;? Item tidak dihapus, hanya disembunyikan dari daftar.')">@csrf @method('DELETE')</form>
    @endif
@endforeach
<div class="lbl" style="margin-bottom:8px">Menampilkan {{ $paginator->total() }} item</div>
<p class="lbl">Kosongkan kotak bulan untuk menghapus angka bulan itu. Tekan Simpan pada baris item untuk menyimpan 12 bulan sekaligus.</p>
@endif

@if($bisaKelola)
    @include('budget_p.partials.modal-salin')

    <dialog id="modal-item" aria-labelledby="item-judul">
        <div class="dh"><b id="item-judul">Item baru</b><button type="button" class="btn sm" onclick="tutup('modal-item')">Tutup</button></div>
        <form class="db" id="it-form" method="post" action="{{ route('budgeting.budget.item.simpan') }}">
            @csrf
            <input type="hidden" name="id" id="it-id">
            <input type="hidden" name="tahun_mulai" id="it-tmulai" value="{{ $tahun }}">
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
                @if($peran === 'admin' || $kepala->count() > 1)
                    <label><span>Departemen pemilik</span>
                        <select name="dept_head_user_id" id="it-head" required onchange="isiPic()">
                            <option value="">Pilih departemen</option>
                            @foreach($kepala as $h)<option value="{{ $h['id'] }}">{{ $h['nama'] }}</option>@endforeach
                        </select>
                    </label>
                @endif
                <label><span>PIC (boleh dikosongkan)</span><select name="pic_user_id" id="it-pic"></select></label>
                <label><span>Sumber</span><input name="sumber" id="it-sumber" maxlength="100"></label>
            </div>
            <label style="display:block;margin-top:10px"><span class="lbl">Keterangan</span><textarea name="keterangan" id="it-ket" rows="2" maxlength="1000"></textarea></label>
            <label style="display:block;margin-top:8px"><input type="hidden" name="aktif" value="0"><input type="checkbox" name="aktif" value="1" id="it-aktif" checked> Item aktif</label>
            <fieldset id="it-mode" style="display:none;margin-top:10px;border:1px solid var(--bar);padding:8px 10px">
                <legend class="lbl">Perubahan ini berlaku untuk</legend>
                <label style="display:block"><input type="radio" name="mode" value="semua" checked onchange="aturMode()"> Semua tahun (hanya memperbaiki, nama lama di tahun-tahun sebelumnya ikut berubah)</label>
                <label style="display:block"><input type="radio" name="mode" value="mulai" onchange="aturMode()"> Mulai tahun
                    <select name="tahun_berlaku" id="it-tberlaku" disabled>@foreach($daftarTahun as $t)<option value="{{ $t }}" @selected((int) $t === (int) $tahun)>{{ $t }}</option>@endforeach</select>
                    ke depan (item lama tetap dengan nama lama sampai tahun sebelumnya; angka dan tagihan tahun itu dan sesudahnya ikut pindah ke item baru)</label>
            </fieldset>
            <p class="lbl">PIC yang dipilih adalah penanggung jawab item ini. Semua PIC dari departemen yang sama tetap bisa mengakses item ini.</p>
            <div class="row" style="justify-content:flex-end"><button class="btn pr">Simpan item</button></div>
        </form>
    </dialog>
@endif
@endsection

@push('scripts')
@if($bisaKelola)
<script>
const URL_BU = @json(route('budgeting.lookup.bu')), URL_PT = @json(route('budgeting.lookup.pt'));
const PIC_MAP = @json($picMap), PERAN = @json($peran), MY_ID = @json(auth()->id()), HEAD_TETAP = @json($headTetap);
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
  const head = $e('it-head') ? $e('it-head').value : HEAD_TETAP;
  const d = PIC_MAP[head] || [];
  $e('it-pic').innerHTML = '<option value="">(belum ditunjuk)</option>' + d.map(p => `<option value="${p.id}">${esc(p.nama)}</option>`).join('');
  if (pilih) $e('it-pic').value = String(pilih);
}
function aturMode(){
  const mulai = document.querySelector('#it-mode input[name=mode]:checked').value === 'mulai';
  $e('it-tberlaku').disabled = !mulai;
}
function tampilMode(edit){
  $e('it-mode').style.display = edit ? 'block' : 'none';
  $e('it-tmulai').disabled = edit;
  document.querySelectorAll('#it-mode input[name=mode]').forEach(x => x.disabled = !edit);
  aturMode(); if (!edit) $e('it-tberlaku').disabled = true;
}
async function itemBaru(){
  $e('it-form').reset(); $e('it-id').value = ''; tampilMode(false); $e('item-judul').textContent = 'Item baru';
  await muatBu(); $e('it-pt').innerHTML = '<option value="">Pilih BU dulu</option>'; isiPic(); buka('modal-item');
}
async function itemEdit(btn){
  const d = JSON.parse(btn.dataset.item);
  $e('it-form').reset(); $e('item-judul').textContent = 'Ubah item'; tampilMode(true);
  $e('it-id').value = d.id; $e('it-sub').value = d.sub_id; $e('it-nama').value = d.nama;
  $e('it-sumber').value = d.sumber || ''; $e('it-ket').value = d.keterangan || ''; $e('it-aktif').checked = !!d.aktif;
  if ($e('it-head')) $e('it-head').value = d.dept_head_user_id;
  await muatBu(); $e('it-bu').value = d.bu || ''; await muatPt(d.pt); isiPic(d.pic_user_id); buka('modal-item');
}
</script>
@endif
@endpush