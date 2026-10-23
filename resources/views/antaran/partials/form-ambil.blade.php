{{--
  Foto ambil. Kurir bisa pilih "Kamera" atau "Pilih file"; hasilnya diseragamkan (lihat foto-script).
  $uid = 'c' (card HP) atau 't' (tabel desktop). Di tabel, yang di-toggle adalah <tr> pembungkusnya.
--}}
@include('antaran.partials.foto-script')

<form id="ambil-{{ $uid }}-{{ $item->no_transaksi }}"
      action="{{ route('antaran.antar', $item->no_transaksi) }}"
      method="POST" enctype="multipart/form-data"
      class="{{ $uid === 'c' ? 'hidden ' : '' }}mt-3 space-y-2 border-t pt-3"
      onsubmit="var b=this.querySelector('button[type=submit]'); b.disabled=true; b.textContent='Mengirim...';">
  @csrf
  <input type="file" name="gambar_awal" required accept="image/*" class="hidden" onchange="fotoProses(this)">

  <p class="text-xs text-gray-500">Foto barang yang diambil sebagai bukti.</p>
  <div class="flex gap-2">
    <button type="button" class="flex-1 border rounded-lg py-1.5 text-xs" onclick="fotoPilih(this,'kamera')">Kamera</button>
    <button type="button" class="flex-1 border rounded-lg py-1.5 text-xs" onclick="fotoPilih(this,'file')">Pilih file</button>
  </div>

  <img alt="Preview foto ambil" class="hidden w-full max-h-56 object-contain rounded-lg border bg-gray-50">
  <p data-info class="text-[11px] text-gray-400"></p>

  <button type="submit" disabled class="jne-orange w-full text-white rounded-lg py-1.5 text-xs disabled:opacity-50">Kirim foto & mulai tracking</button>
  <button type="button" class="w-full border rounded-lg py-1.5 text-xs text-gray-500" onclick="fotoBatal(this)">Batal</button>
</form>