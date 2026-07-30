<form id="selesai-{{ $item->no_transaksi }}" action="{{ route('antaran.selesaikan', $item->no_transaksi) }}"
      method="POST" enctype="multipart/form-data" class="hidden mt-3 space-y-2 border-t pt-3">
  @csrf
  <input type="file" name="gambar_akhir" required accept=".jpg,.jpeg,.png" class="text-xs w-full">
  <input type="text" name="note_penerima" maxlength="500" placeholder="Catatan penerima (opsional)"
         class="w-full border rounded-lg px-2 py-1.5 text-xs">
  <button class="jne-orange w-full text-white rounded-lg py-1.5 text-xs">Upload bukti & selesai</button>
</form>
