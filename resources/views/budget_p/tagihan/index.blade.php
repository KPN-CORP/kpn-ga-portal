@extends('layouts.app-budget-sidebar')
@section('title', 'Realisasi Budget')
@section('content')
@php
    $F = \App\Support\budget_s\BudgetFormat::class;

    // Pilihan filter bertingkat: Sub mengikuti Kategori, Item mengikuti Sub (atau Kategori bila Sub belum dipilih)
    $fKat = (string) ($filter['kategori_id'] ?? '');
    $fSub = (string) ($filter['sub_id'] ?? '');
    $subFilter = $subList->when($fKat !== '', fn ($c) => $c->filter(fn ($x) => (string) $x->kategori_id === $fKat))->values();
    $itemFilter = $semuaItem
        ->when($fKat !== '', fn ($c) => $c->filter(fn ($i) => (string) $i->sub->kategori_id === $fKat))
        ->when($fSub !== '', fn ($c) => $c->filter(fn ($i) => (string) $i->sub_id === $fSub))->values();
    $adaFilter = $filter['bulan'] || $fKat !== '' || $fSub !== '' || $filter['item_id'] || $filter['q'] !== '';

    // Data dropdown bertingkat di form Catat realisasi (Kategori -> Sub -> Item, mengikuti Tahun yang dipilih)
    $subModal = $itemModal->pluck('sub')->filter()->unique('id')->values();
    $katModal = $subModal->pluck('kategori')->filter()->unique('id')->values();
    $dataKat = $katModal->map(fn ($k) => ['id' => $k->id, 'nama' => $k->nama])->values();
    $dataSub = $subModal->map(fn ($x) => ['id' => $x->id, 'kategori_id' => $x->kategori_id, 'nama' => $x->nama])->values();
    $dataItemInput = $itemModal->map(fn ($i) => ['id' => $i->id, 'sub_id' => $i->sub_id, 'nama' => $i->nama])->values();
@endphp

@include('budget_p.partials.header-tahun', ['judul' => 'Realisasi Budget'])

<form method="get" class="card fb" id="tg-filter">
    <input type="hidden" name="tahun" value="{{ $tahun }}">
    <label><span class="lbl">Bulan</span>
        <select name="bulan" data-cari onchange="this.form.submit()">
            <option value="">Semua</option>
            @foreach($F::BULAN as $n => $nama)<option value="{{ $n }}" @selected((string) $filter['bulan'] === (string) $n)>{{ $nama }}</option>@endforeach
        </select>
    </label>
    <label><span class="lbl">Kategori</span>
        <select name="kategori_id" data-cari onchange="this.form.sub_id.value='';this.form.item_id.value='';this.form.submit()">
            <option value="">Semua</option>
            @foreach($kategori as $k)<option value="{{ $k->id }}" @selected($fKat === (string) $k->id)>{{ $k->nama }}</option>@endforeach
        </select>
    </label>
    <label><span class="lbl">Sub</span>
        <select name="sub_id" data-cari onchange="this.form.item_id.value='';this.form.submit()">
            <option value="">Semua</option>
            @foreach($subFilter as $x)<option value="{{ $x->id }}" @selected($fSub === (string) $x->id)>{{ $x->nama }}</option>@endforeach
        </select>
    </label>
    <label><span class="lbl">Item</span>
        <select name="item_id" data-cari onchange="this.form.submit()">
            <option value="">Semua</option>
            @foreach($itemFilter as $i)<option value="{{ $i->id }}" @selected((string) $filter['item_id'] === (string) $i->id)>{{ $i->nama }}</option>@endforeach
        </select>
    </label>
    <label><span class="lbl">Cari</span>
        <input type="search" name="q" value="{{ $filter['q'] }}" placeholder="Item, sub, kategori, keterangan">
    </label>
    <div class="fb-act">
        <button class="btn pr">Cari</button>
        @if($adaFilter)<a class="btn" href="{{ route('budgeting.tagihan.index', ['tahun' => $tahun]) }}">Hapus filter</a>@endif
    </div>
</form>

<div class="kpi">
    <div class="card"><span class="lbl">Tagihan</span><b>{{ $F::rupiah($ringkas['tagihan']) }}</b></div>
    <div class="card"><span class="lbl">PPN</span><b>{{ $F::rupiah($ringkas['ppn']) }}</b></div>
    <div class="card"><span class="lbl">Biaya materai</span><b>{{ $F::rupiah($ringkas['biaya_materai']) }}</b></div>
    <div class="card"><span class="lbl">Total realisasi</span><b>{{ $F::rupiah($ringkas['total']) }}</b></div>
</div>

<div class="row" style="margin-bottom:12px">
    @if($itemModal->isNotEmpty())
        <button type="button" class="btn pr" onclick="tagihanBaru()">Catat realisasi</button>
    @endif
</div>

@if($daftar->isEmpty())
    @include('budget_p.partials.kosong', [
        'pesan' => $itemModal->isEmpty()
            ? 'Belum ada item yang bisa Anda catat realisasinya.'
            : 'Belum ada realisasi untuk filter ini. Tekan "Catat realisasi" untuk menambah.',
    ])
@else
<div class="card scroll" style="padding:0">
<table>
    <tr><th>Bulan</th><th>Item</th><th class="n">Tagihan</th><th class="n">PPN</th><th class="n">Biaya materai</th><th class="n">Total</th><th>Keterangan</th><th>Diinput oleh</th><th></th></tr>
    @foreach($daftar as $t)
        <tr>
            <td>{{ $F::BULAN[$t->bulan] }}@if(($jumlahPerBulan[$t->item_id . '-' . $t->bulan] ?? 0) > 1)<div><span class="bd b-wn" title="Item ini punya lebih dari satu tagihan di bulan ini">{{ $jumlahPerBulan[$t->item_id . '-' . $t->bulan] }} tagihan</span></div>@endif</td>
            <td><b>{{ $t->item->nama }}</b><div class="lbl">{{ $t->item->sub->kategori->nama }} / {{ $t->item->sub->nama }}</div></td>
            <td class="n">{{ $F::rupiah($t->tagihan) }}</td>
            <td class="n">{{ $F::rupiah($t->ppn) }}</td>
            <td class="n">{{ $F::rupiah($t->biaya_materai) }}</td>
            <td class="n"><b>{{ $F::rupiah($t->total) }}</b></td>
            <td>{{ $t->keterangan }}</td>
            <td>{{ $t->penginput->name ?? '-' }}<div class="lbl">{{ optional($t->created_at)->format('d/m/Y H:i') }}@if($t->updated_at && $t->created_at && $t->updated_at->diffInSeconds($t->created_at) > 60) | diubah {{ $t->updated_at->format('d/m/Y H:i') }}@endif</div></td>
            <td class="n">
                @if($itemBisaInput->contains('id', $t->item_id))
                    <button type="button" class="btn sm" data-tg="{{ json_encode(['id' => $t->id, 'item_id' => $t->item_id, 'tahun' => $t->tahun, 'bulan' => $t->bulan,
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
    <form class="db" id="tg-form" method="post" action="{{ route('budgeting.tagihan.simpan') }}" onsubmit="return konfirmasiSimpan()">
        @csrf
        <input type="hidden" name="id" id="tg-id"><input type="hidden" name="tahun_halaman" value="{{ $tahun }}">
        <div class="f">
            <label><span>Tahun</span>
                <select name="tahun" id="tg-tahun" required data-cari onchange="gantiTahun()">
                    @foreach($daftarTahun as $t)<option value="{{ $t }}" @selected((int) $t === (int) $tahun)>{{ $t }}</option>@endforeach
                </select>
            </label>
            <label><span>Bulan tagihan</span>
                <select name="bulan" id="tg-bulan" required data-cari onchange="cekBulan()">@foreach($F::BULAN as $n => $nama)<option value="{{ $n }}" @selected($n === (int) now()->month)>{{ $nama }}</option>@endforeach</select>
            </label>
            <label><span>Kategori</span>
                <select id="tg-kat" required data-cari onchange="isiSub()"><option value="">- Pilih kategori -</option></select>
            </label>
            <label><span>Sub</span>
                <select id="tg-sub" required disabled data-cari onchange="isiItem()"><option value="">- Pilih kategori dulu -</option></select>
            </label>
            <label><span>Item</span>
                <select name="item_id" id="tg-item" required disabled data-cari onchange="cekBulan()"><option value="">- Pilih sub dulu -</option></select>
            </label>
            <div id="tg-info" style="grid-column:1/-1" hidden></div>
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
function jumlahBaru(){ return (+g('tg-tagihan').value||0)+(+g('tg-ppn').value||0)+(+g('tg-materai').value||0); }
function hitungTotal(){ g('tg-total').textContent = rp(jumlahBaru()); if(tgSiap) renderInfo(); }

const URL_CEK = @json(route('budgeting.tagihan.cek'));
const DATA_KAT = @json($dataKat);
const DATA_SUB = @json($dataSub);
const DATA_ITEM = @json($dataItemInput);
const ITEM_TAHUN = @json($idPerTahun);   // tahun -> id item yang berlaku di tahun itu
const FILTER_ITEM = @json($filter['item_id'] ? (int) $filter['item_id'] : null);
const urut = (a,b) => a.nama.localeCompare(b.nama);

// Item yang tersedia untuk Tahun yang dipilih di form
function itemTersedia(){
  const ok = new Set((ITEM_TAHUN[g('tg-tahun').value] || []).map(String));
  return DATA_ITEM.filter(i => ok.has(String(i.id)));
}
function isiKat(){
  const el = g('tg-kat'), tersedia = itemTersedia();
  const subIds = new Set(tersedia.map(i => String(i.sub_id)));
  const katIds = new Set(DATA_SUB.filter(s => subIds.has(String(s.id))).map(s => String(s.kategori_id)));
  el.innerHTML = ''; el.add(new Option('- Pilih kategori -',''));
  DATA_KAT.filter(k => katIds.has(String(k.id))).sort(urut).forEach(k => el.add(new Option(k.nama, k.id)));
  csSync(el); isiSub();
}
function isiSub(pilihSubId){
  const kat = g('tg-kat').value, el = g('tg-sub');
  el.innerHTML = '';
  if(!kat){ el.disabled = true; el.add(new Option('- Pilih kategori dulu -','')); csSync(el); isiItem(); return; }
  const subIds = new Set(itemTersedia().map(i => String(i.sub_id)));
  const daftar = DATA_SUB.filter(s => String(s.kategori_id) === String(kat) && subIds.has(String(s.id))).sort(urut);
  el.disabled = false;
  if(daftar.length !== 1) el.add(new Option('- Pilih sub -',''));
  daftar.forEach(s => el.add(new Option(s.nama, s.id)));
  if(pilihSubId) el.value = pilihSubId; else if(daftar.length === 1) el.value = daftar[0].id;
  csSync(el); isiItem();
}
function isiItem(pilihItemId){
  const sub = g('tg-sub').value, el = g('tg-item');
  el.innerHTML = '';
  if(!sub){ el.disabled = true; el.add(new Option('- Pilih sub dulu -','')); csSync(el); cekBulan(); return; }
  const daftar = itemTersedia().filter(i => String(i.sub_id) === String(sub)).sort(urut);
  el.disabled = false;
  if(daftar.length !== 1) el.add(new Option('- Pilih item -',''));
  daftar.forEach(i => el.add(new Option(i.nama, i.id)));
  if(pilihItemId) el.value = pilihItemId; else if(daftar.length === 1) el.value = daftar[0].id;
  csSync(el); cekBulan();
}
// Pilih item sekaligus mengisi Kategori dan Sub-nya (untuk ubah data, filter aktif, atau ganti Tahun)
function setItem(itemId){
  isiKat();
  const it = itemTersedia().find(i => String(i.id) === String(itemId));
  const sb = it && DATA_SUB.find(s => String(s.id) === String(it.sub_id));
  if(!sb) return;
  g('tg-kat').value = sb.kategori_id; csSync(g('tg-kat'));
  isiSub(sb.id); isiItem(it.id);
}
// Ganti Tahun: item yang berlaku bisa berbeda, pilihan dipertahankan bila masih ada
function gantiTahun(){ setItem(g('tg-item').value); }


// ---- Panel "Sudah tercatat": tagihan yang sudah ada di item + bulan + tahun yang dipilih ----
let tgRows = [], tgBudget = 0, tgSiap = false, tgSeq = 0, tgKunci = '';
function cekBulan(){
  const box = g('tg-info'), item = g('tg-item').value, tahun = g('tg-tahun').value, bulan = g('tg-bulan').value;
  const kunci = item + '|' + tahun + '|' + bulan;
  if(!item || !tahun || !bulan){ tgSiap = false; tgRows = []; tgKunci = ''; box.hidden = true; box.innerHTML = ''; return; }
  if(kunci === tgKunci && tgSiap){ renderInfo(); return; }
  tgKunci = kunci; tgSiap = false; box.hidden = false;
  box.innerHTML = '<span class="lbl">Memeriksa data bulan ini...</span>';
  const seq = ++tgSeq;
  fetch(URL_CEK + '?item_id=' + item + '&tahun=' + tahun + '&bulan=' + bulan, {headers:{'Accept':'application/json'}})
    .then(r => r.ok ? r.json() : Promise.reject())
    .then(d => { if(seq !== tgSeq) return; tgRows = d.rows || []; tgBudget = +d.budget_bulan || 0; tgSiap = true; renderInfo(); })
    .catch(() => { if(seq === tgSeq){ tgKunci = ''; box.innerHTML = '<span class="lbl">Data bulan ini tidak bisa diperiksa.</span>'; } });
}
function renderInfo(){
  const box = g('tg-info'); if(!tgSiap) return;
  const editId = g('tg-id').value, baru = jumlahBaru();
  const total = tgRows.reduce((a,r) => a + r.total, 0);
  const lain = tgRows.filter(r => String(r.id) !== String(editId)).reduce((a,r) => a + r.total, 0);
  const jadi = lain + baru;
  let h = '';
  if(!tgRows.length){
    h += '<div style="padding:8px 10px;border:1px solid var(--ok);color:var(--ok);border-radius:6px">Belum ada realisasi di bulan ini.</div>';
  } else {
    h += '<div style="padding:8px 10px;border:1px solid var(--wn);border-radius:6px"><b style="color:var(--wn)">Sudah ada ' + tgRows.length + ' tagihan di bulan ini</b> <span class="lbl">(total ' + rp(total) + ')</span><div style="margin-top:6px">';
    tgRows.forEach(r => {
      const sedang = String(r.id) === String(editId);
      h += '<div class="row" style="gap:8px;padding:3px 0;border-top:1px solid var(--ln)"><b>' + rp(r.total) + '</b>'
        + '<span class="lbl" style="flex:1">' + esc(r.keterangan || '-') + ' | ' + esc(r.oleh) + ' | ' + esc(r.dibuat) + (r.diubah ? ' | diubah ' + esc(r.diubah) : '') + '</span>'
        + (sedang ? '<span class="bd b-wn">sedang diubah</span>' : '<button type="button" class="btn sm" onclick="muatBaris(' + r.id + ')">Ubah</button>') + '</div>';
    });
    h += '</div>';
    if(editId) h += '<div style="margin-top:6px"><button type="button" class="btn sm" onclick="barisBaru()">Tambah tagihan baru</button></div>';
    h += '</div>';
  }
  if(tgBudget > 0){
    const pct = Math.round(jadi / tgBudget * 100), lebih = jadi > tgBudget;
    h += '<div class="lbl" style="margin-top:6px">Budget bulan ini ' + rp(tgBudget) + ' | realisasi sekarang ' + rp(total)
      + (baru > 0 || editId ? ' | <b style="color:' + (lebih ? 'var(--er)' : 'var(--ok)') + '">setelah disimpan ' + rp(jadi) + ' (' + pct + '%)</b>' : ' | sisa ' + rp(tgBudget - total)) + '</div>';
  } else {
    h += '<div class="lbl" style="margin-top:6px">Budget bulan ini belum diisi.</div>';
  }
  box.innerHTML = h;
}
function muatBaris(id){
  const r = tgRows.find(x => String(x.id) === String(id)); if(!r) return;
  g('tg-id').value = r.id; g('tg-judul').textContent = 'Ubah realisasi';
  g('tg-tagihan').value = r.tagihan; g('tg-ppn').value = r.ppn; g('tg-materai').value = r.biaya_materai; g('tg-ket').value = r.keterangan || '';
  hitungTotal(); renderInfo();
}
function barisBaru(){
  g('tg-id').value = ''; g('tg-judul').textContent = 'Catat realisasi';
  ['tg-tagihan','tg-ppn','tg-materai','tg-ket'].forEach(i => g(i).value = '');
  hitungTotal(); renderInfo();
}
// Simpan tagihan baru dengan total yang sama persis dengan yang sudah ada: minta konfirmasi dulu
function konfirmasiSimpan(){
  if(g('tg-id').value || !tgSiap) return true;
  const sama = tgRows.find(r => Math.abs(r.total - jumlahBaru()) < 0.5);
  return sama ? confirm('Di bulan ini sudah ada tagihan dengan total sama (' + rp(sama.total) + '). Tetap simpan sebagai tagihan baru?') : true;
}

function tagihanBaru(){
  tgKunci=''; g('tg-form').reset(); g('tg-id').value=''; g('tg-judul').textContent='Catat realisasi';
  ['tg-tahun','tg-bulan'].forEach(i => csSync(g(i)));
  setItem(FILTER_ITEM); hitungTotal(); buka('modal-tagihan');
}
function tagihanEdit(btn){
  tgKunci=''; const d = JSON.parse(btn.dataset.tg); g('tg-form').reset(); g('tg-judul').textContent='Ubah realisasi';
  g('tg-id').value=d.id; g('tg-tahun').value=d.tahun; g('tg-bulan').value=d.bulan;
  ['tg-tahun','tg-bulan'].forEach(i => csSync(g(i)));
  setItem(d.item_id);
  g('tg-tagihan').value=d.tagihan; g('tg-ppn').value=d.ppn; g('tg-materai').value=d.biaya_materai; g('tg-ket').value=d.keterangan||'';
  hitungTotal(); buka('modal-tagihan');
}
</script>
@endpush
