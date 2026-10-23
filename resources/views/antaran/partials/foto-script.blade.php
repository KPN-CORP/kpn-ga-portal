{{--
  Script bersama untuk semua upload foto kurir (foto ambil & foto selesai).
  Apa pun sumbernya (kamera atau file), hasilnya SERAGAM: JPEG, sisi terpanjang maks 1600px, kualitas 0.8.
  Di-include dari form-ambil & form-selesaikan; @once memastikan hanya dimuat sekali per halaman.
--}}
@once
<script>
  // Kecilkan + ubah ke JPEG (orientasi EXIF dibetulkan)
  window.fotoKompres = async function (file, maxSide, quality) {
    let bmp = null;
    if (window.createImageBitmap) {
      try { bmp = await createImageBitmap(file, { imageOrientation: 'from-image' }); } catch (e) { bmp = null; }
    }
    if (!bmp) {
      const url = URL.createObjectURL(file);
      bmp = await new Promise(function (res, rej) {
        const i = new Image();
        i.onload = function () { res(i); };
        i.onerror = function () { rej(new Error('gagal baca gambar')); };
        i.src = url;
      });
    }
    const w = bmp.width || bmp.naturalWidth, h = bmp.height || bmp.naturalHeight;
    const s = Math.min(1, maxSide / Math.max(w, h));
    const c = document.createElement('canvas');
    c.width = Math.round(w * s); c.height = Math.round(h * s);
    const ctx = c.getContext('2d');
    ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, c.width, c.height);
    ctx.drawImage(bmp, 0, 0, c.width, c.height);
    return await new Promise(function (res, rej) {
      c.toBlob(function (b) { b ? res(b) : rej(new Error('gagal kompres')); }, 'image/jpeg', quality);
    });
  };

  // Tombol "Kamera" / "Pilih file": satu input file, bedanya hanya atribut capture
  window.fotoPilih = function (btn, mode) {
    const input = btn.closest('form').querySelector('input[type=file]');
    if (mode === 'kamera') input.setAttribute('capture', 'environment');
    else input.removeAttribute('capture');
    input.click();
  };

  // Dipanggil saat foto dipilih/diambil
  window.fotoProses = async function (input) {
    const file = input.files[0];
    if (!file) return;
    const form = input.form;
    const img = form.querySelector('img');
    const btn = form.querySelector('button[type=submit]');
    const info = form.querySelector('[data-info]');

    btn.disabled = true;
    info.textContent = 'Memproses foto...';

    try {
      const blob = await window.fotoKompres(file, 1600, 0.8);
      const baru = new File([blob], 'foto_' + Date.now() + '.jpg', { type: 'image/jpeg' });
      let dipakai = baru;
      try {
        const dt = new DataTransfer();
        dt.items.add(baru);
        input.files = dt.files;          // ganti file di input dengan hasil kompres
      } catch (e) {
        dipakai = file;                  // browser lama: kirim file asli
      }
      img.src = URL.createObjectURL(dipakai);
      img.classList.remove('hidden');
      info.textContent = 'Ukuran foto: ' + Math.round(dipakai.size / 1024) + ' KB';
      btn.disabled = false;
    } catch (e) {
      input.value = '';
      img.classList.add('hidden');
      img.removeAttribute('src');
      info.textContent = 'Foto tidak bisa diproses (format tidak didukung?). Coba foto lain.';
    }
  };

  // Tombol Batal: kosongkan form lalu sembunyikan
  window.fotoBatal = function (btn) {
    const f = btn.closest('form'), t = f.closest('tr');
    f.reset();
    const img = f.querySelector('img');
    img.classList.add('hidden'); img.removeAttribute('src');
    f.querySelector('[data-info]').textContent = '';
    f.querySelector('button[type=submit]').disabled = true;
    (t || f).classList.add('hidden');
  };
</script>
@endonce
