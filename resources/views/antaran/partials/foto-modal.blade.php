{{--
  SATU form upload foto untuk seluruh halaman (dipakai "Ambil & mulai tracking" dan "Selesaikan").
  Sebelumnya tiap resi punya form sendiri (2-4 form per baris) -> HTML membengkak -> memori PHP habis.
  Sekarang tombol di baris hanya membawa data-aksi + data-no; form ini di-isi lewat JS.
  Foto dari Kamera maupun File diseragamkan: JPEG, sisi terpanjang maks 1600px, kualitas 0.8.
--}}
<style>
  .fm-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:50;align-items:flex-end;justify-content:center}
  .fm-sheet{background:#fff;width:100%;max-width:28rem;border-radius:20px 20px 0 0;padding:10px 16px calc(16px + env(safe-area-inset-bottom));max-height:92vh;overflow:auto}
  @media (min-width:768px){
    .fm-overlay{align-items:center;padding:16px}
    .fm-sheet{border-radius:20px;padding:20px}
    .fm-handle{display:none}
  }
  .fm-btn{display:block;width:100%;min-height:48px;border-radius:12px;border:1px solid #d1d5db;background:#fff;color:#111827;font-size:14px;font-weight:600;text-align:center;-webkit-tap-highlight-color:transparent}
  .fm-btn.primary{background:#2563eb;border-color:#2563eb;color:#fff}
  .fm-btn.primary:disabled{opacity:.45}
  .fm-btn.ghost{border-color:transparent;color:#6b7280;font-weight:500}
  .fm-btn:active{transform:scale(.98)}
</style>
<div id="foto-modal" class="fm-overlay" onclick="if(event.target===this) fotoTutup()">
  <div class="fm-sheet">
    <div class="fm-handle" style="width:40px;height:4px;border-radius:2px;background:#d1d5db;margin:0 auto 12px;"></div>
    <p id="foto-judul" style="font-size:16px;font-weight:600;color:#111827;"></p>
    <p id="foto-no" style="font-size:12px;color:#9ca3af;margin-bottom:14px;word-break:break-all;"></p>

    <form id="foto-form" method="POST" enctype="multipart/form-data" style="display:grid;gap:10px;"
          onsubmit="var b=document.getElementById('foto-submit'); b.disabled=true; b.textContent='Mengirim...';">
      @csrf
      <input type="file" id="foto-input" required accept="image/*" style="display:none" onchange="fotoProses(this)">

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
        <button type="button" class="fm-btn" onclick="fotoPilih('kamera')">Kamera</button>
        <button type="button" class="fm-btn" onclick="fotoPilih('file')">Pilih file</button>
      </div>

      <img id="foto-preview" alt="Preview foto" style="display:none;width:100%;max-height:240px;object-fit:contain;border-radius:12px;border:1px solid #e5e7eb;background:#f9fafb;">
      <p id="foto-info" style="font-size:11px;color:#9ca3af;"></p>

      <div id="foto-note-wrap" style="display:none;">
        <input type="text" name="note_penerima" maxlength="500" placeholder="Catatan penerima (opsional)"
               style="width:100%;border:1px solid #d1d5db;border-radius:12px;padding:12px;font-size:14px;">
      </div>

      <button type="submit" id="foto-submit" disabled class="fm-btn primary">Kirim</button>
      <button type="button" class="fm-btn ghost" onclick="fotoTutup()">Batal</button>
    </form>
  </div>
</div>

<script>
  (function () {
    var URL_AMBIL   = @json(route('antaran.antar', '__NO__'));
    var URL_SELESAI = @json(route('antaran.selesaikan', '__NO__'));
    var $ = function (id) { return document.getElementById(id); };

    // Dipanggil tombol di baris: data-aksi="ambil|selesai", data-no="<no_transaksi>"
    window.fotoBuka = function (btn) {
      var ambil = btn.getAttribute('data-aksi') === 'ambil';
      var no = btn.getAttribute('data-no');
      var f = $('foto-form');

      f.reset();
      f.action = (ambil ? URL_AMBIL : URL_SELESAI).replace('__NO__', encodeURIComponent(no));
      $('foto-input').name = ambil ? 'gambar_awal' : 'gambar_akhir';
      $('foto-judul').textContent = ambil ? 'Foto barang diambil' : 'Foto bukti diterima';
      $('foto-no').textContent = no;
      $('foto-note-wrap').style.display = ambil ? 'none' : 'block';
      f.querySelector('[name=note_penerima]').disabled = ambil;

      var sb = $('foto-submit');
      sb.textContent = ambil ? 'Kirim foto & mulai tracking' : 'Upload bukti & selesai';
      sb.disabled = true;

      var img = $('foto-preview');
      img.style.display = 'none'; img.removeAttribute('src');
      $('foto-info').textContent = '';

      $('foto-modal').style.display = 'flex';
      document.body.style.overflow = 'hidden';
    };

    window.fotoTutup = function () {
      $('foto-form').reset();
      $('foto-modal').style.display = 'none';
      document.body.style.overflow = '';
    };

    // Kamera / File: satu input, bedanya hanya atribut capture
    window.fotoPilih = function (mode) {
      var input = $('foto-input');
      if (mode === 'kamera') input.setAttribute('capture', 'environment');
      else input.removeAttribute('capture');
      input.click();
    };

    // Kecilkan + ubah ke JPEG (orientasi EXIF dibetulkan)
    async function kompres(file, maxSide, quality) {
      var bmp = null;
      if (window.createImageBitmap) {
        try { bmp = await createImageBitmap(file, { imageOrientation: 'from-image' }); } catch (e) { bmp = null; }
      }
      if (!bmp) {
        var url = URL.createObjectURL(file);
        bmp = await new Promise(function (res, rej) {
          var i = new Image();
          i.onload = function () { res(i); };
          i.onerror = function () { rej(new Error('gagal baca gambar')); };
          i.src = url;
        });
      }
      var w = bmp.width || bmp.naturalWidth, h = bmp.height || bmp.naturalHeight;
      var s = Math.min(1, maxSide / Math.max(w, h));
      var c = document.createElement('canvas');
      c.width = Math.round(w * s); c.height = Math.round(h * s);
      var ctx = c.getContext('2d');
      ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
      ctx.drawImage(bmp, 0, 0, c.width, c.height);
      return await new Promise(function (res, rej) {
        c.toBlob(function (b) { b ? res(b) : rej(new Error('gagal kompres')); }, 'image/jpeg', quality);
      });
    }

    // Dipanggil saat foto dipilih / diambil
    window.fotoProses = async function (input) {
      var file = input.files[0];
      if (!file) return;
      var img = $('foto-preview'), info = $('foto-info'), btn = $('foto-submit');

      btn.disabled = true;
      info.textContent = 'Memproses foto...';
      try {
        var blob = await kompres(file, 1600, 0.8);
        var baru = new File([blob], 'foto_' + Date.now() + '.jpg', { type: 'image/jpeg' });
        var dipakai = baru;
        try {
          var dt = new DataTransfer();
          dt.items.add(baru);
          input.files = dt.files;          // ganti file di input dengan hasil kompres
        } catch (e) {
          dipakai = file;                  // browser lama: kirim file asli
        }
        img.src = URL.createObjectURL(dipakai);
        img.style.display = 'block';
        info.textContent = 'Ukuran foto: ' + Math.round(dipakai.size / 1024) + ' KB';
        btn.disabled = false;
      } catch (e) {
        input.value = '';
        img.style.display = 'none'; img.removeAttribute('src');
        info.textContent = 'Foto tidak bisa diproses (format tidak didukung?). Coba foto lain.';
      }
    };
  })();
</script>