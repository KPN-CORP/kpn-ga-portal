@extends('layouts.app-budget-sidebar')
@section('title', 'Isi Form Realisasi')
@section('content')
@php
    $F = \App\Support\budget_s\BudgetFormat::class;
    $fieldAktif = $form ? $form->fields->where('aktif', true)->values() : collect();
@endphp

@include('budget_p.partials.header-tahun', ['judul' => 'Isi Form Realisasi'])

@if($semuaForm->isEmpty())
    @include('budget_p.partials.kosong', ['pesan' => 'Belum ada form untuk departemen Anda.', 'tautan' => route('budgeting.form'), 'teksTautan' => 'Buat form'])
@else
<form method="get" class="card fb">
    <input type="hidden" name="tahun" value="{{ $tahun }}">
    <label><span class="lbl">Form</span>
        <select name="form" data-cari onchange="this.form.submit()">
            @foreach($semuaForm as $f)<option value="{{ $f->id }}" @selected($form && $form->id === $f->id)>{{ $f->nama }}{{ $f->aktif ? '' : ' (diarsipkan)' }}</option>@endforeach
        </select>
    </label>
    <label><span class="lbl">Bulan</span>
        <select name="bulan" data-cari onchange="this.form.submit()">
            <option value="">Semua</option>
            @foreach($F::BULAN as $n => $nama)<option value="{{ $n }}" @selected((string) $filter['bulan'] === (string) $n)>{{ $nama }}</option>@endforeach
        </select>
    </label>
    <label><span class="lbl">Nomor dokumen</span><input type="search" name="q" value="{{ $filter['q'] }}" placeholder="Cari nomor dokumen"></label>
    <div class="fb-act"><button class="btn pr">Cari</button>
        <a class="btn" href="{{ route('budgeting.form') }}">Kelola form</a></div>
</form>

@if($form)
<div class="card">
    <b>{{ $form->nama }}</b> @if($form->deskripsi)<span class="lbl">- {{ $form->deskripsi }}</span>@endif
    <div class="lbl">Masuk ke item <b>{{ $form->item->nama }}</b> ({{ $form->item->sub->kategori->nama }} / {{ $form->item->sub->nama }}), PT {{ $form->item->pt ?: '-' }}.
        Bulan realisasi mengikuti tanggal invoice.@unless($form->aktif) <b style="color:var(--wn)">Form ini diarsipkan, tidak bisa diisi.</b>@endunless</div>
</div>

<div class="kpi">
    <div class="card"><span class="lbl">Nilai (DPP)</span><b>{{ $F::rupiah($ringkas['dpp']) }}</b></div>
    <div class="card"><span class="lbl">PPN</span><b>{{ $F::rupiah($ringkas['ppn']) }}</b></div>
    <div class="card"><span class="lbl">Materai</span><b>{{ $F::rupiah($ringkas['materai']) }}</b></div>
    <div class="card"><span class="lbl">Total realisasi</span><b>{{ $F::rupiah($ringkas['total']) }}</b></div>
</div>

<div class="row" style="margin-bottom:12px">
    @if($bisaIsi)<button type="button" class="btn pr" onclick="isianBaru()">Catat invoice</button>@endif
</div>

@if($daftar->isEmpty())
    @include('budget_p.partials.kosong', ['pesan' => 'Belum ada isian untuk filter ini.'])
@else
<div class="card scroll tbl-beku" style="padding:0">
<table>
    <thead><tr>
        <th>Tanggal</th><th>No. dokumen</th>
        @foreach($kolom as $k)<th>{{ $k->label }}@unless($k->aktif) <span class="lbl">(arsip)</span>@endunless</th>@endforeach
        <th class="n">Nilai (DPP)</th><th class="n">PPN</th><th class="n">Materai</th><th class="n">Total</th><th>Diinput oleh</th><th></th>
    </tr></thead>
    <tbody>
    @foreach($daftar as $i)
        <tr>
            <td>{{ $i->tanggal->format('d/m/Y') }}</td>
            <td>{{ $i->no_dokumen ?: '-' }}</td>
            @foreach($kolom as $k)
                @php $v = ($i->data ?? [])[$k->id] ?? null; @endphp
                <td @if($k->tipe === 'angka') class="n" @endif>{{ $v === null ? '-' : ($k->tipe === 'tanggal' ? \Illuminate\Support\Carbon::parse($v)->format('d/m/Y') : $v) }}</td>
            @endforeach
            <td class="n">{{ $F::rupiah($i->dpp) }}</td>
            <td class="n">{{ $F::rupiah($i->ppn) }}</td>
            <td class="n">{{ $F::rupiah($i->materai) }}</td>
            <td class="n"><b>{{ $F::rupiah($i->total) }}</b></td>
            <td>{{ $i->penginput->name ?? '-' }}<div class="lbl">{{ optional($i->created_at)->format('d/m/Y H:i') }}</div></td>
            <td class="n" style="white-space:nowrap">
                @if($bisaIsi)
                    <button type="button" class="btn sm" data-isi="{{ json_encode([
                        'id' => $i->id, 'tanggal' => $i->tanggal->format('Y-m-d'), 'no_dokumen' => $i->no_dokumen, 'dpp' => $F::angka($i->dpp),
                        'kena_ppn' => (float) $i->ppn > 0, 'kena_materai' => (float) $i->materai > 0, 'ppn_persen' => $i->ppn_persen, 'materai' => (float) $i->materai,
                        'data' => (object) ($i->data ?? [])]) }}" onclick="isianEdit(this)">Ubah</button>
                @endif
                @if($form->aktif || $bisaIsi)
                    <form method="post" action="{{ route('budgeting.isian.hapus', $i) }}" style="display:inline" onsubmit="return confirm('Hapus isian ini? Realisasinya juga dikurangi dari budget.')">@csrf @method('DELETE')<button class="btn sm dg">Hapus</button></form>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
<div class="row" style="margin-top:8px;justify-content:space-between">
    <span class="lbl">Menampilkan {{ $daftar->firstItem() }}-{{ $daftar->lastItem() }} dari {{ $daftar->total() }} isian</span>
    <span class="row">
        @if($daftar->previousPageUrl())<a class="btn sm" href="{{ $daftar->previousPageUrl() }}">Sebelumnya</a>@endif
        <span class="lbl">Halaman {{ $daftar->currentPage() }} dari {{ $daftar->lastPage() }}</span>
        @if($daftar->nextPageUrl())<a class="btn sm" href="{{ $daftar->nextPageUrl() }}">Berikutnya</a>@endif
    </span>
</div>
@endif

@if($bisaIsi)
<dialog id="modal-isi" aria-labelledby="is-judul">
    <div class="dh"><b id="is-judul">Catat invoice</b><button type="button" class="btn sm" onclick="tutup('modal-isi')">Tutup</button></div>
    <form class="db" id="is-form" method="post" action="{{ route('budgeting.isian.simpan') }}">
        @csrf
        <input type="hidden" name="form_id" value="{{ $form->id }}"><input type="hidden" name="id" id="is-id">
        <div class="f">
            <label><span>Tanggal invoice</span><input type="date" name="tanggal" id="is-tanggal" required></label>
            <label><span>Nomor dokumen / invoice</span><input name="no_dokumen" id="is-no" maxlength="100"></label>
            @foreach($fieldAktif as $f)
                <label><span>{{ $f->label }}{{ $f->wajib ? ' *' : '' }}</span>
                    @if($f->tipe === 'angka')<input type="number" step="any" name="f[{{ $f->id }}]" id="f-{{ $f->id }}" {{ $f->wajib ? 'required' : '' }}>
                    @elseif($f->tipe === 'tanggal')<input type="date" name="f[{{ $f->id }}]" id="f-{{ $f->id }}" {{ $f->wajib ? 'required' : '' }}>
                    @elseif($f->tipe === 'pilihan')<select name="f[{{ $f->id }}]" id="f-{{ $f->id }}" {{ $f->wajib ? 'required' : '' }}><option value="">- Pilih -</option>@foreach($f->opsi() as $o)<option>{{ $o }}</option>@endforeach</select>
                    @else<input name="f[{{ $f->id }}]" id="f-{{ $f->id }}" maxlength="255" {{ $f->wajib ? 'required' : '' }}>
                    @endif
                </label>
            @endforeach
            <label><span>Nilai / DPP (Rp)</span><input type="number" step="any" min="0" name="dpp" id="is-dpp" required oninput="hitungIsi()"></label>
        </div>
        <div class="row" style="margin-top:10px;gap:18px">
            @if($form->pakai_ppn)<label><input type="checkbox" name="kena_ppn" value="1" id="is-ppn" checked onchange="hitungIsi()"> Kena PPN</label>@endif
            @if($form->pakai_materai)<label><input type="checkbox" name="kena_materai" value="1" id="is-materai" onchange="hitungIsi()"> Pakai materai</label>@endif
        </div>
        <div class="card" style="margin:12px 0 0;background:var(--bg)">
            <div class="row" style="justify-content:space-between"><span class="lbl">Nilai (DPP)</span><span id="v-dpp">Rp 0</span></div>
            <div class="row" style="justify-content:space-between"><span class="lbl" id="l-ppn">PPN</span><span id="v-ppn">Rp 0</span></div>
            <div class="row" style="justify-content:space-between"><span class="lbl">Materai</span><span id="v-materai">Rp 0</span></div>
            <div class="row" style="justify-content:space-between;border-top:1px solid var(--ln);margin-top:4px;padding-top:4px"><b>Total yang dicatat ke budget</b><b id="v-total">Rp 0</b></div>
            <p class="lbl" style="margin:6px 0 0">Ini hanya perkiraan di layar. Angka resmi dihitung ulang oleh sistem saat disimpan, memakai tarif PPN {{ $F::angka($ppnPersen) }}% dan materai {{ $F::rupiah($materai) }}.</p>
        </div>
        <p class="lbl">Satu invoice hanya untuk satu PT. Bila ada tagihan gabungan beberapa PT, catat sebagai invoice terpisah.</p>
        <div class="row" style="justify-content:flex-end"><button class="btn pr">Simpan</button></div>
    </form>
</dialog>
@endif
@endif
@endif
@endsection

@push('scripts')
@if(!empty($bisaIsi))
<script>
const PPN_PERSEN = @json((float) $ppnPersen), MATERAI = @json((float) $materai), FIELD_IDS = @json($fieldAktif->pluck('id'));
const ie = id => document.getElementById(id);
let isiLama = null;   // saat ubah: tarif dan materai tersimpan dipakai kembali (sama seperti server)
function hitungIsi(){
  const dpp = +ie('is-dpp').value || 0;
  const kenaPpn = ie('is-ppn') && ie('is-ppn').checked, kenaMat = ie('is-materai') && ie('is-materai').checked;
  const persen = kenaPpn ? ((isiLama && +isiLama.ppn_persen > 0) ? +isiLama.ppn_persen : PPN_PERSEN) : 0;
  const ppn = Math.round(dpp * persen / 100);
  const mat = kenaMat ? ((isiLama && +isiLama.materai > 0) ? +isiLama.materai : MATERAI) : 0;
  ie('v-dpp').textContent = rp(dpp); ie('v-ppn').textContent = rp(ppn); ie('v-materai').textContent = rp(mat);
  ie('l-ppn').textContent = persen > 0 ? 'PPN ' + persen + '%' : 'PPN';
  ie('v-total').textContent = rp(dpp + ppn + mat);
}
function isianBaru(){
  isiLama = null; ie('is-form').reset(); ie('is-id').value = ''; ie('is-judul').textContent = 'Catat invoice';
  ie('is-tanggal').value = new Date().toISOString().slice(0, 10);
  hitungIsi(); buka('modal-isi');
}
function isianEdit(btn){
  const d = JSON.parse(btn.dataset.isi); isiLama = d;
  ie('is-form').reset(); ie('is-judul').textContent = 'Ubah invoice'; ie('is-id').value = d.id;
  ie('is-tanggal').value = d.tanggal; ie('is-no').value = d.no_dokumen || ''; ie('is-dpp').value = d.dpp;
  FIELD_IDS.forEach(id => { const e = ie('f-' + id); if(e) e.value = (d.data && d.data[id] != null) ? d.data[id] : ''; });
  if(ie('is-ppn')) ie('is-ppn').checked = !!d.kena_ppn;
  if(ie('is-materai')) ie('is-materai').checked = !!d.kena_materai;
  hitungIsi(); buka('modal-isi');
}
</script>
@endif
@endpush
