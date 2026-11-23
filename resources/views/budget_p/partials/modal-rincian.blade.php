{{-- Popup detail item. Isinya dimuat dari route budgeting.kategori.item (partials/panel-item). --}}
<dialog id="modal-rincian" aria-labelledby="rincian-judul">
    <div class="dh"><b id="rincian-judul">Rincian item</b><button type="button" class="btn sm" onclick="tutup('modal-rincian')">Tutup</button></div>
    <div class="db" id="rincian-isi"></div>
</dialog>
<script>
async function rincian(id, tahun, bulan){
  const box = document.getElementById('rincian-isi');
  box.textContent = 'Memuat...'; buka('modal-rincian');
  const r = await fetch(@json(url('budgeting/kategori/item')) + '/' + id + '?tahun=' + tahun, {headers:{'X-Requested-With':'XMLHttpRequest'}});
  box.innerHTML = r.ok ? await r.text() : 'Rincian gagal dimuat.';
  // Dari klik batang grafik: buka rincian invoice bulan itu dan gulir ke barisnya
  if (r.ok && bulan) {
    const d = document.getElementById('rb-' + id + '-' + bulan), tr = d ? d.previousElementSibling : null;
    if (d) { d.hidden = false; const p = tr && tr.querySelector('.pnl'); if (p) p.textContent = '\u25BE'; }
    if (tr) tr.scrollIntoView({block: 'center'});
  }
}
</script>