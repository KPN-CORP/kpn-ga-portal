{{-- params: $tahun, $daftarTahun, $dataSalin. Hanya Admin dan Dept Head. Mengambil item beserta kategori dan subkategorinya ke tahun tujuan. --}}
<dialog id="modal-salin" aria-labelledby="salin-judul">
    <div class="dh"><b id="salin-judul">Salin dari tahun lain</b><button type="button" class="btn sm" onclick="tutup('modal-salin')">Tutup</button></div>
    <form class="db" method="post" action="{{ route('budgeting.budget.salin') }}">
        @csrf
        <p class="lbl">Tahun baru kosong sampai Anda mengambil item dari tahun lain. Yang diambil adalah item beserta subkategori dan kategorinya. Kosongkan pilihan untuk mengambil semua item yang Anda kelola. Angka tahun asal tidak berubah.</p>
        <div class="f">
            <label><span>Salin dari tahun</span>
                <select name="dari" id="sl-dari" data-cari onchange="slIsiKat()">@foreach($daftarTahun as $t)<option value="{{ $t }}" @selected($t == $tahun - 1)>{{ $t }}</option>@endforeach</select>
            </label>
            <label><span>Ke tahun</span>
                <select name="ke" data-cari>@foreach($daftarTahun as $t)<option value="{{ $t }}" @selected($t == $tahun)>{{ $t }}</option>@endforeach</select>
            </label>
            <label><span>Kategori</span>
                <select name="kategori_id" id="sl-kat" data-cari onchange="slIsiSub()"><option value="">Semua kategori</option></select>
            </label>
            <label><span>Sub</span>
                <select name="sub_id" id="sl-sub" data-cari onchange="slIsiItem()"><option value="">Semua sub</option></select>
            </label>
            <label><span>Item</span>
                <select name="item_id" id="sl-item" data-cari><option value="">Semua item</option></select>
            </label>
        </div>
        <label style="display:block;margin-top:10px"><input type="hidden" name="angka" value="0"><input type="checkbox" name="angka" value="1" checked> Salin juga angka budget bulanan</label>
        <label style="display:block;margin-top:6px"><input type="hidden" name="timpa" value="0"><input type="checkbox" name="timpa" value="1"> Timpa angka yang sudah terisi di tahun tujuan</label>
        <div class="row" style="justify-content:flex-end;margin-top:12px"><button class="btn pr">Salin</button></div>
    </form>
</dialog>

@push('scripts')
<script>
const SL = @json($dataSalin);
const slUrut = (a,b) => a.nama.localeCompare(b.nama);
const slGet = id => document.getElementById(id);
// Item yang tampil di tahun sumber (Kategori -> Sub -> Item mengikuti tahun yang dipilih)
function slItem(){
  const ok = new Set(((SL.perTahun || {})[slGet('sl-dari').value] || []).map(String));
  return (SL.item || []).filter(i => ok.has(String(i.id)));
}
function slIsi(el, kosong, daftar, nilai){
  el.innerHTML = ''; el.add(new Option(kosong, ''));
  daftar.sort(slUrut).forEach(d => el.add(new Option(d.nama, d.id)));
  if(nilai && daftar.some(d => String(d.id) === String(nilai))) el.value = nilai;
  csSync(el);
}
function slIsiKat(){
  const subIds = new Set(slItem().map(i => String(i.sub_id)));
  const katIds = new Set((SL.sub || []).filter(s => subIds.has(String(s.id))).map(s => String(s.kategori_id)));
  slIsi(slGet('sl-kat'), 'Semua kategori', (SL.kat || []).filter(k => katIds.has(String(k.id))), slGet('sl-kat').value);
  slIsiSub();
}
function slIsiSub(){
  const kat = slGet('sl-kat').value, subIds = new Set(slItem().map(i => String(i.sub_id)));
  slIsi(slGet('sl-sub'), 'Semua sub', (SL.sub || []).filter(s => subIds.has(String(s.id)) && (!kat || String(s.kategori_id) === String(kat))), slGet('sl-sub').value);
  slIsiItem();
}
function slIsiItem(){
  const kat = slGet('sl-kat').value, sub = slGet('sl-sub').value;
  const subKat = new Map((SL.sub || []).map(s => [String(s.id), String(s.kategori_id)]));
  slIsi(slGet('sl-item'), 'Semua item', slItem().filter(i => (!sub || String(i.sub_id) === String(sub)) && (!kat || subKat.get(String(i.sub_id)) === String(kat))), slGet('sl-item').value);
}
slIsiKat();
</script>
@endpush
