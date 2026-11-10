@extends('layouts.app-budget-sidebar')
@section('title', 'Form Realisasi')
@section('content')
@php $F = \App\Support\budget_s\BudgetFormat::class; @endphp

<div class="top">
    <h2>Form Realisasi</h2>
    <button type="button" class="btn pr" onclick="formBaru()">Form baru</button>
</div>

<div class="card">
    <p style="margin:0">Buat form untuk jenis invoice yang sering dicatat (misalnya Hotel, Tiket, Deklarasi). Setiap isian form otomatis menjadi
        <b>satu baris realisasi</b> di item budget yang Anda pilih, dengan PPN, materai, dan total dihitung otomatis oleh sistem.</p>
    <ul class="lbl" style="margin:8px 0 0 18px">
        <li>Satu form mengisi satu item budget milik departemen Anda. Item itu <b>terkunci</b> dari input manual selama form aktif.</li>
        <li>Satu invoice hanya untuk satu PT (PT ikut dari item). Invoice gabungan beberapa PT dicatat sebagai isian terpisah.</li>
        <li>Field yang sudah punya isian tidak dihapus, hanya diarsipkan, supaya data lama tetap terbaca.</li>
    </ul>
</div>

@if($admin)
<form class="card fb" method="post" action="{{ route('budgeting.form.pengaturan') }}">
    @csrf
    <label><span class="lbl">Tarif PPN (%)</span><input type="number" step="0.01" min="0" max="100" name="ppn_persen" value="{{ $F::angka($ppnPersen) }}" required></label>
    <label><span class="lbl">Nominal materai (Rp)</span><input type="number" step="any" min="0" name="materai_nominal" value="{{ $F::angka($materai) }}" required></label>
    <div class="fb-act"><button class="btn">Simpan pengaturan</button></div>
    <p class="lbl" style="grid-column:1/-1;margin:0">Berlaku untuk isian baru. Isian yang sudah dicatat menyimpan tarif dan nominal saat itu, jadi angkanya tidak ikut berubah.</p>
</form>
@endif

@if($forms->isEmpty())
    @include('budget_p.partials.kosong', ['pesan' => 'Belum ada form. Tekan "Form baru" untuk membuat yang pertama.'])
@else
<div class="card scroll" style="padding:0">
<table>
    <thead><tr><th>Form</th><th>Item tujuan</th><th>Komponen</th><th class="n">Field</th><th class="n">Isian</th><th>Status</th><th></th></tr></thead>
    <tbody>
    @foreach($forms as $f)
        <tr>
            <td><b>{{ $f->nama }}</b>@if($f->deskripsi)<div class="lbl">{{ $f->deskripsi }}</div>@endif
                <div class="lbl">Dibuat {{ $f->pembuat->name ?? '-' }}</div></td>
            <td>{{ $f->item->nama }}<div class="lbl">{{ $f->item->sub->kategori->nama }} / {{ $f->item->sub->nama }}</div>
                <div class="lbl">PT: {{ $f->item->pt ?: '-' }} | Dept: {{ \App\Support\budget_s\BudgetPeran::namaDept($f->dept_head_user_id) }}</div>
                @if(($manual[$f->item_id] ?? 0) > 0)<div class="lbl">{{ $manual[$f->item_id] }} tagihan manual lama di item ini</div>@endif</td>
            <td>@if($f->pakai_ppn)<span class="bd b-mu">PPN</span> @endif @if($f->pakai_materai)<span class="bd b-mu">Materai</span>@endif
                @unless($f->pakai_ppn || $f->pakai_materai)<span class="lbl">-</span>@endunless</td>
            <td class="n">{{ $f->fields->where('aktif', true)->count() }}</td>
            <td class="n">{{ $f->isian_count }}</td>
            <td>@if($f->aktif)<span class="bd b-ok">Aktif, item terkunci</span>@else<span class="bd b-mu">Diarsipkan</span>@endif</td>
            <td class="n" style="white-space:nowrap">
                @if($f->aktif)<a class="btn sm pr" href="{{ route('budgeting.isian', ['form' => $f->id]) }}">Isi form</a>@endif
                <button type="button" class="btn sm" onclick="formEdit({{ $f->id }})">Ubah</button>
                <form method="post" action="{{ route('budgeting.form.arsip', $f) }}" style="display:inline"
                      onsubmit="return confirm('{{ $f->aktif ? 'Arsipkan form ini? Item tujuan bisa diisi manual lagi dan form tidak bisa diisi.' : 'Aktifkan kembali form ini? Item tujuan akan terkunci dari input manual.' }}')">@csrf
                    <button class="btn sm">{{ $f->aktif ? 'Arsipkan' : 'Aktifkan' }}</button></form>
                @if($f->isian_count === 0)
                    <form method="post" action="{{ route('budgeting.form.hapus', $f) }}" style="display:inline" onsubmit="return confirm('Hapus form ini?')">@csrf @method('DELETE')<button class="btn sm dg">Hapus</button></form>
                @endif
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endif

<dialog id="modal-form" aria-labelledby="fm-judul" style="width:min(860px,96vw)">
    <div class="dh"><b id="fm-judul">Form baru</b><button type="button" class="btn sm" onclick="tutup('modal-form')">Tutup</button></div>
    <form class="db" id="fm-form" method="post" action="{{ route('budgeting.form.simpan') }}">
        @csrf
        <input type="hidden" name="id" id="fm-id">
        <div class="f">
            <label><span>Nama form</span><input name="nama" id="fm-nama" required maxlength="100" placeholder="Contoh: Hotel"></label>
            <label><span>Item budget tujuan</span>
                <select name="item_id" id="fm-item" required data-cari>
                    <option value="">- Pilih item -</option>
                    @foreach($dataItem as $i)<option value="{{ $i['id'] }}">{{ $i['label'] }}</option>@endforeach
                </select>
            </label>
            <label><span>Deskripsi (boleh kosong)</span><input name="deskripsi" id="fm-desk" maxlength="255"></label>
        </div>
        <div class="row" style="margin-top:10px;gap:18px">
            <label><input type="hidden" name="pakai_ppn" value="0"><input type="checkbox" name="pakai_ppn" value="1" id="fm-ppn" checked> Form ini memakai PPN</label>
            <label><input type="hidden" name="pakai_materai" value="0"><input type="checkbox" name="pakai_materai" value="1" id="fm-materai"> Form ini memakai materai</label>
        </div>
        <p class="lbl" id="fm-kunci-info" style="margin:6px 0 0"></p>

        <h3 style="margin-top:16px">Field tambahan</h3>
        <p class="lbl" style="margin:-4px 0 8px">Tanggal, nomor dokumen, nilai (DPP), PPN, materai, dan total sudah otomatis ada. Tambahkan field lain di sini (misalnya Nama tamu, Vendor, Rute).</p>
        <div id="fm-fields"></div>
        <div class="row" style="margin-top:8px"><button type="button" class="btn sm" onclick="tambahField()">+ Tambah field</button></div>
        <div class="row" style="justify-content:flex-end;margin-top:14px"><button class="btn pr">Simpan form</button></div>
    </form>
</dialog>
@endsection

@push('scripts')
<script>
const DATA_FORM = @json($dataForm);
const TIPE = {teks:'Teks', angka:'Angka', tanggal:'Tanggal', pilihan:'Pilihan'};
let fmNo = 0, fmAdaIsian = false;
const fe = id => document.getElementById(id);
const ea = s => esc(s).replace(/"/g, '&quot;');

function barisField(f){
  f = f || {id:'', label:'', tipe:'teks', pilihan:'', wajib:false};
  const n = fmNo++, kunciTipe = fmAdaIsian && f.id;
  const opsiTipe = Object.keys(TIPE).map(k => `<option value="${k}" ${f.tipe===k?'selected':''}>${TIPE[k]}</option>`).join('');
  const d = document.createElement('div');
  d.className = 'card'; d.style.cssText = 'padding:10px;margin-bottom:8px'; d.dataset.baris = n;
  d.innerHTML =
    `<input type="hidden" name="fields[${n}][id]" value="${ea(f.id||'')}">`
    + `<div class="f">`
    + `<label><span>Nama field</span><input name="fields[${n}][label]" value="${ea(f.label)}" required maxlength="60"></label>`
    + `<label><span>Tipe</span><select ${kunciTipe?'disabled':''} onchange="aturTipe(${n}, this.value)" id="tp-${n}">${opsiTipe}</select>`
    + (kunciTipe ? `<input type="hidden" name="fields[${n}][tipe]" value="${ea(f.tipe)}">` : `<input type="hidden" name="fields[${n}][tipe]" id="tpv-${n}" value="${ea(f.tipe)}">`)
    + `</label>`
    + `<label style="align-self:end"><input type="hidden" name="fields[${n}][wajib]" value="0"><input type="checkbox" name="fields[${n}][wajib]" value="1" ${f.wajib?'checked':''}> Wajib diisi</label>`
    + `</div>`
    + `<label id="op-${n}" style="display:${f.tipe==='pilihan'?'block':'none'};margin-top:8px"><span class="lbl">Opsi pilihan (satu per baris, minimal 2)</span><textarea rows="3" name="fields[${n}][pilihan]">${esc(f.pilihan||'')}</textarea></label>`
    + `<div class="row" style="justify-content:flex-end;margin-top:6px"><button type="button" class="btn sm dg" onclick="hapusField(this)">${fmAdaIsian && f.id ? 'Arsipkan field' : 'Hapus field'}</button></div>`;
  fe('fm-fields').appendChild(d);
}
function aturTipe(n, v){ const h = fe('tpv-'+n); if(h) h.value = v; fe('op-'+n).style.display = v==='pilihan' ? 'block' : 'none'; }
function tambahField(){ barisField(); }
function hapusField(btn){
  const baris = btn.closest('[data-baris]');
  if(fmAdaIsian && baris.querySelector('input[name$="[id]"]').value && !confirm('Field ini sudah punya isian. Field diarsipkan (disembunyikan dari form), nilai lama tetap tersimpan. Lanjutkan?')) return;
  baris.remove();
}
function formBaru(){
  fmAdaIsian = false; fmNo = 0;
  fe('fm-form').reset(); fe('fm-id').value = ''; fe('fm-judul').textContent = 'Form baru'; fe('fm-fields').innerHTML = '';
  fe('fm-item').disabled = false; csSync(fe('fm-item')); fe('fm-kunci-info').textContent = 'Setelah disimpan, item tujuan terkunci dari input manual.';
  buka('modal-form');
}
function formEdit(id){
  const d = DATA_FORM[id]; if(!d) return;
  fmAdaIsian = !!d.ada_isian; fmNo = 0;
  fe('fm-form').reset(); fe('fm-id').value = d.id; fe('fm-judul').textContent = 'Ubah form';
  fe('fm-nama').value = d.nama; fe('fm-desk').value = d.deskripsi || '';
  fe('fm-item').value = d.item_id; csSync(fe('fm-item'));
  fe('fm-ppn').checked = !!d.pakai_ppn; fe('fm-materai').checked = !!d.pakai_materai;
  fe('fm-kunci-info').textContent = fmAdaIsian ? 'Form ini sudah punya isian: item tujuan dan tipe field lama tidak bisa diganti. Field yang dibuang akan diarsipkan.' : '';
  fe('fm-fields').innerHTML = ''; (d.fields || []).forEach(barisField);
  buka('modal-form');
}
</script>
@endpush
