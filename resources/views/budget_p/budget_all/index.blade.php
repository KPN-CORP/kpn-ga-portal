@extends('layouts.app-budget-sidebar')
@section('title', 'Rencana Budget')
@section('content')
@php
    $F = \App\Support\budget_s\BudgetFormat::class;
    $P = \App\Support\budget_s\BudgetPeran::class;
    $bisaKelola = in_array($peran, ['admin', 'dept_head', 'pic'], true);
    $kunciBerlaku = $terkunci && $peran !== 'admin'; // tahun dikunci menutup perubahan budget bulanan bagi Dept Head dan PIC
    $tahunBaruDefault = (int) max($daftarTahun) + 1;
    $unduhParam = array_filter(['tahun' => $tahun, 'bu' => $filter['bu'], 'pt' => $filter['pt'], 'kategori_id' => $filter['kategori_id'], 'sub_id' => $filter['sub_id'], 'q' => $filter['q']], fn ($v) => $v !== null && $v !== '');
@endphp

@push('styles')
<style>
    .bud tr.kotor td{background:#fff8e1}
    .bud tr.nonaktif td{background:#f3f4f6;color:#6b7280}
    .bud .thn{display:inline-block;font-size:11px;padding:1px 6px;border-radius:999px;background:var(--bar);color:var(--mu);margin:2px 3px 0 0}
    .bud .thn.ini{background:#dbeafe;color:#1d4ed8;font-weight:600}
    .bud .thn.off{text-decoration:line-through}
    #toast-b{position:fixed;right:16px;bottom:16px;z-index:90;max-width:360px;display:none;padding:10px 14px;border-radius:8px;color:#fff;font-size:13px;box-shadow:0 6px 20px rgba(0,0,0,.2)}
    #toast-b.ok{background:#15803d}#toast-b.er{background:#dc2626}
    .bud .hal{display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin:10px 0}
    .bud .hal a,.bud .hal span{padding:4px 10px;border:1px solid var(--ln);border-radius:6px;background:#fff;color:inherit;text-decoration:none;font-size:13px}
    .bud .hal .on{background:var(--ac);color:var(--ac-t);border-color:var(--ac)}
    .bud .hal .off{opacity:.45}
</style>
@endpush

@include('budget_p.partials.header-tahun', ['judul' => 'Rencana Budget'])
@include('budget_p.partials.filter', ['pakaiKategori' => true, 'pakaiSub' => true, 'subFilterList' => $subFilterList, 'pakaiBu' => true, 'pakaiPt' => true, 'pakaiQ' => true, 'pakaiNonaktif' => true])

@if($terkunci)
    <div class="msg wn">Budget bulanan tahun {{ $tahun }} dikunci.
        @if($peran === 'admin') Sebagai Admin Anda tetap bisa mengubahnya.@else Angka budget bulanan tidak bisa diubah sampai Admin membuka kunci.@endif
        Pencatatan realisasi tidak ikut terkunci.
    </div>
@endif

<div class="row" style="margin-bottom:12px">
    @if($bisaKelola)
        <button type="button" class="btn pr" onclick="itemBaru()">Item baru</button>
    @endif
    @if($bisaBuatTahun)
        <button type="button" class="btn" onclick="buka('modal-tahun')">Tahun baru</button>
    @endif
    @if($bisaSalin)
        <button type="button" class="btn" onclick="buka('modal-salin')">Salin dari tahun lain</button>
    @endif
    <a class="btn" href="{{ route('budgeting.unduh', $unduhParam) }}" title="Unduhan mengikuti tahun dan semua filter di atas">Unduh Excel{{ $adaFilter ? ' (sesuai filter)' : ' (semua item)' }}</a>
    <button type="button" class="btn pr" id="btn-simpan-semua" style="display:none" onclick="simpanSemua()"></button>
</div>

@if($items->isEmpty())
    @include('budget_p.partials.kosong', [
        'pesan' => $adaFilter
            ? 'Tidak ada item yang cocok dengan filter ini.'
            : 'Belum ada item aktif di tahun ' . $tahun . '.' . ($bisaBuatTahun ? ' Pakai "Item baru", "Salin dari tahun lain", atau "Tahun baru" untuk mengisinya.' : ' Minta Dept Head atau Admin menyiapkan tahun ini.'),
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
        @php
            $nm = $nominal->get($i->id); $boleh = $bolehIsi[$i->id];
            $aktifIni = $i->masihBerlaku($tahun);
            $tahunAktif = $i->tahunTerdaftar->where('aktif', true)->pluck('tahun')->map(fn ($v) => (int) $v)->sort()->values();
            $tahunOff = $i->tahunTerdaftar->where('aktif', false)->pluck('tahun')->map(fn ($v) => (int) $v)->sort()->values();
        @endphp
        <tr id="tr{{ $i->id }}" class="{{ $aktifIni ? '' : 'nonaktif' }}">
            <td class="beku" style="min-width:200px;max-width:260px"><b>{{ $i->nama }}</b><div class="lbl">Departemen: {{ $P::namaDept($i->dept_head_user_id) }}</div>
                <div>
                    @foreach($tahunAktif as $t)<span class="thn {{ $t === (int) $tahun ? 'ini' : '' }}">{{ $t }}</span>@endforeach
                    @foreach($tahunOff as $t)<span class="thn off {{ $t === (int) $tahun ? 'ini' : '' }}" title="Nonaktif di {{ $t }}">{{ $t }}</span>@endforeach
                </div>
                @unless($aktifIni)
                    <div class="lbl"><b>Nonaktif di {{ $tahun }}.</b>@if(!empty($itemAdaData[$i->id])) Data {{ $tahun }} tetap tersimpan, tetapi tidak bisa diubah.@else Tidak dipakai di {{ $tahun }}.@endif</div>
                @endunless
                @if($i->item_asal_id)<div class="lbl">Pengganti item lama (riwayat tahun sebelumnya ada di item lama)</div>@endif</td>
            <td style="min-width:150px">{{ $i->bu ?: '-' }}<div class="lbl">{{ $i->pt ?: '-' }}</div></td>
            <td>{{ $i->pic->name ?? '-' }}</td>
            @foreach(range(1, 12) as $b)
                <td class="n"><input type="number" step="any" min="0" name="nominal[{{ $b }}]" form="fb{{ $i->id }}" data-item="{{ $i->id }}" data-bulan="{{ $b }}"
                    value="{{ $F::angka($nm?->get($b)) }}" style="width:104px;text-align:right" aria-label="{{ $i->nama }} {{ $F::BULAN[$b] }}" {{ $boleh ? '' : 'disabled' }}></td>
            @endforeach
            <td class="n" id="tot{{ $i->id }}">{{ $F::rupiah($nm ? $nm->sum() : 0) }}</td>
            <td class="n" style="white-space:nowrap">
                @if($boleh)<button type="button" class="btn sm pr" onclick="simpanBaris({{ $i->id }})">Simpan</button>@endif
                @if($bolehUbah[$i->id])
                    <button type="button" class="btn sm" data-item="{{ json_encode([
                        'id' => $i->id, 'sub_id' => $i->sub_id, 'nama' => $i->nama, 'bu' => $i->bu, 'pt' => $i->pt,
                        'dept_head_user_id' => $i->dept_head_user_id, 'pic_user_id' => $i->pic_user_id,
                        'sumber' => $i->sumber, 'keterangan' => $i->keterangan]) }}" onclick="itemEdit(this)">Ubah</button>
                    @if($kunciBerlaku)
                        <button type="button" class="btn sm dg" disabled title="Budget bulanan {{ $tahun }} dikunci. Minta Admin membuka kunci dulu.">{{ $aktifIni ? 'Nonaktifkan' : 'Aktifkan' }} di {{ $tahun }}</button>
                    @elseif($aktifIni)
                        <button type="button" class="btn sm dg" onclick="ubahStatus({{ $i->id }}, 0, @js($i->nama))">Nonaktifkan di {{ $tahun }}</button>
                    @else
                        <button type="button" class="btn sm" onclick="ubahStatus({{ $i->id }}, 1, @js($i->nama))">Aktifkan di {{ $tahun }}</button>
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
@endforeach

<form id="f-status" method="post" action="">@csrf
    <input type="hidden" name="tahun" value="{{ $tahun }}"><input type="hidden" name="aktif" id="st-aktif" value="0"></form>

@php $hal = $paginator->currentPage(); $akhir = $paginator->lastPage(); @endphp
<div class="lbl" style="margin-bottom:4px">Menampilkan {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} dari {{ $paginator->total() }} item</div>
@if($akhir > 1)
    <div class="hal">
        @if($hal > 1)<a href="{{ $paginator->url($hal - 1) }}">&laquo; Sebelumnya</a>@else<span class="off">&laquo; Sebelumnya</span>@endif
        @foreach(range(max(1, $hal - 2), min($akhir, $hal + 2)) as $n)
            @if($n === $hal)<span class="on">{{ $n }}</span>@else<a href="{{ $paginator->url($n) }}">{{ $n }}</a>@endif
        @endforeach
        @if($hal < $akhir)<a href="{{ $paginator->url($hal + 1) }}">Berikutnya &raquo;</a>@else<span class="off">Berikutnya &raquo;</span>@endif
    </div>
@endif
<p class="lbl">Ubah angka lalu tekan Simpan pada barisnya. Baris yang belum disimpan ditandai kuning dan tidak hilang saat Anda menyimpan baris lain. Kosongkan kotak bulan untuk menghapus angka bulan itu.
    Lencana tahun di bawah nama item = tahun tempat item dipakai (dicoret = nonaktif di tahun itu).</p>
@endif

@if($bisaSalin)
    @include('budget_p.partials.modal-salin')
@endif

@if($bisaBuatTahun)
    <dialog id="modal-tahun" aria-labelledby="thn-judul">
        <div class="dh"><b id="thn-judul">Tahun baru</b><button type="button" class="btn sm" onclick="tutup('modal-tahun')">Tutup</button></div>
        <form class="db" method="post" action="{{ route('budgeting.budget.tahun') }}">
            @csrf
            <p class="lbl">Buat tahun anggaran, lalu pasang item dari tahun sebelumnya sekaligus. Hanya item yang aktif di tahun asal yang ikut. Setelah itu, nonaktifkan item yang tidak dipakai langsung dari daftar.</p>
            <div class="f">
                <label><span>Tahun yang dibuat</span><input type="number" name="tahun" value="{{ $tahunBaruDefault }}" min="2000" max="2100" required></label>
                <label><span>Isi dari tahun</span>
                    <select name="dari"><option value="">Kosong (isi nanti)</option>
                        @foreach($daftarTahun as $t)<option value="{{ $t }}" @selected((int) $t === (int) $tahun)>{{ $t }}</option>@endforeach
                    </select>
                </label>
            </div>
            <label style="display:block;margin-top:10px"><input type="hidden" name="angka" value="0"><input type="checkbox" name="angka" value="1" checked> Salin juga angka budget bulanan</label>
            <div class="row" style="justify-content:flex-end;margin-top:12px"><button class="btn pr">Buat tahun</button></div>
        </form>
    </dialog>
@endif

@if($bisaKelola)
    <dialog id="modal-item" aria-labelledby="item-judul">
        <div class="dh"><b id="item-judul">Item baru</b><button type="button" class="btn sm" onclick="tutup('modal-item')">Tutup</button></div>
        <form class="db" id="it-form" method="post" action="{{ route('budgeting.budget.item.simpan') }}">
            @csrf
            <input type="hidden" name="id" id="it-id">
            <input type="hidden" name="tahun" id="it-tmulai" value="{{ $tahun }}">
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
            <p class="lbl" id="it-baru-info" style="margin-top:8px">Item baru langsung aktif di tahun {{ $tahun }}.</p>
            <fieldset id="it-mode" style="display:none;margin-top:10px;border:1px solid var(--bar);padding:8px 10px">
                <legend class="lbl">Perubahan berlaku untuk</legend>
                <label style="display:block"><input type="radio" name="mode" value="semua" checked> Semua tahun <span class="lbl">(perbaikan biasa)</span></label>
                <label style="display:block"><input type="radio" name="mode" value="mulai"> Mulai {{ $tahun }} <span class="lbl">(tahun sebelumnya tetap memakai data lama)</span></label>
                <input type="hidden" name="tahun_berlaku" id="it-tberlaku" value="{{ $tahun }}">
            </fieldset>
            <p class="lbl">Status aktif diatur per tahun lewat tombol "Nonaktifkan di {{ $tahun }}" pada baris item. PIC yang dipilih adalah satu-satunya PIC yang bisa mengisi item ini; PIC lain di departemen yang sama hanya bisa melihat.</p>
            <div class="row" style="justify-content:flex-end"><button class="btn pr">Simpan item</button></div>
        </form>
    </dialog>
@endif
<div id="toast-b" role="status"></div>
@endsection

@push('scripts')
<script>
/* ---- Simpan per baris tanpa muat ulang: baris lain yang belum disimpan tidak hilang ---- */
const TAHUN = @json($tahun), URL_STATUS = @json(url('budgeting/budget/item'));
const kotor = new Set();
const fmtRp = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');
function toast(pesan, ok){
  const t = document.getElementById('toast-b'); t.textContent = pesan; t.className = ok ? 'ok' : 'er'; t.style.display = 'block';
  clearTimeout(t._h); t._h = setTimeout(() => t.style.display = 'none', ok ? 3000 : 7000);
}
function segarkanTombol(){
  const b = document.getElementById('btn-simpan-semua');
  b.style.display = kotor.size ? '' : 'none';
  b.textContent = 'Simpan semua perubahan (' + kotor.size + ' baris)';
}
function totalBaris(id){
  let t = 0; document.querySelectorAll('input[data-item="' + id + '"][data-bulan]').forEach(i => t += parseFloat(i.value) || 0); return t;
}
document.addEventListener('input', e => {
  const el = e.target; if (!el.matches || !el.matches('input[data-bulan]')) return;
  const id = el.dataset.item; kotor.add(id);
  document.getElementById('tr' + id).classList.add('kotor');
  document.getElementById('tot' + id).textContent = fmtRp(totalBaris(id));
  segarkanTombol();
});
document.addEventListener('submit', e => {
  if (e.target.id && e.target.id.startsWith('fb')) { e.preventDefault(); simpanBaris(e.target.id.slice(2)); }
});
window.addEventListener('beforeunload', e => { if (kotor.size) { e.preventDefault(); e.returnValue = ''; } });

async function simpanBaris(id, diam){
  id = String(id);
  const f = document.getElementById('fb' + id), tr = document.getElementById('tr' + id);
  try {
    const r = await fetch(f.action, { method: 'POST', body: new FormData(f), headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    let d = {}; try { d = await r.json(); } catch (_) {}
    if (r.status === 419) { toast('Sesi habis. Muat ulang halaman setelah menyalin angka yang belum tersimpan.', false); return false; }
    if (!r.ok) {
      const pesan = d.pesan || (d.errors ? Object.values(d.errors).flat().join(' ') : d.message) || 'Gagal menyimpan.';
      toast(pesan, false); return false;
    }
    kotor.delete(id); tr.classList.remove('kotor');
    document.getElementById('tot' + id).textContent = fmtRp(d.total ?? totalBaris(id));
    segarkanTombol(); if (!diam) toast(d.pesan || 'Tersimpan.', true);
    return true;
  } catch (_) { toast('Koneksi bermasalah. Perubahan belum tersimpan.', false); return false; }
}
async function simpanSemua(){
  const daftar = [...kotor]; let ok = 0;
  for (const id of daftar) { if (await simpanBaris(id, true)) ok++; else break; }
  if (ok === daftar.length) toast(ok + ' baris tersimpan.', true);
}
function ubahStatus(id, aktif, nama){
  const pesan = aktif
    ? 'Aktifkan "' + nama + '" lagi di tahun ' + TAHUN + '?'
    : 'Nonaktifkan "' + nama + '" di tahun ' + TAHUN + ' saja?\nTahun lain tidak berubah dan data ' + TAHUN + ' tidak dihapus.';
  if (kotor.size && !confirm('Ada ' + kotor.size + ' baris yang belum disimpan dan akan hilang bila lanjut. Lanjutkan?')) return;
  if (!confirm(pesan)) return;
  const f = document.getElementById('f-status'); f.action = URL_STATUS + '/' + id + '/status';
  document.getElementById('st-aktif').value = aktif; kotor.clear(); f.submit();
}
</script>
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
  if (PERAN === 'pic') { $e('it-pic').value = String(pilih || MY_ID); $e('it-pic').disabled = true; }
}
function tampilMode(edit){
  $e('it-mode').style.display = edit ? 'block' : 'none';
  $e('it-baru-info').style.display = edit ? 'none' : 'block';
  $e('it-tmulai').disabled = edit;
  $e('it-tberlaku').disabled = !edit;
  document.querySelectorAll('#it-mode input[name=mode]').forEach(x => x.disabled = !edit);
}
async function itemBaru(){
  $e('it-form').reset(); $e('it-id').value = ''; tampilMode(false); $e('item-judul').textContent = 'Item baru';
  await muatBu(); $e('it-pt').innerHTML = '<option value="">Pilih BU dulu</option>'; isiPic(); buka('modal-item');
}
async function itemEdit(btn){
  const d = JSON.parse(btn.dataset.item);
  $e('it-form').reset(); $e('item-judul').textContent = 'Ubah item'; tampilMode(true);
  $e('it-id').value = d.id; $e('it-sub').value = d.sub_id; $e('it-nama').value = d.nama;
  $e('it-sumber').value = d.sumber || ''; $e('it-ket').value = d.keterangan || '';
  if ($e('it-head')) $e('it-head').value = d.dept_head_user_id;
  await muatBu(); $e('it-bu').value = d.bu || ''; await muatPt(d.pt); isiPic(d.pic_user_id); buka('modal-item');
}
</script>
@endif
@endpush
