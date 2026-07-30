@php
  $jenisLabel = $trx->nama_barang === 'dokumen' ? 'Dokumen' : 'Paket';
@endphp

<div class="grid grid-cols-2 gap-3 text-sm">
  <div class="col-span-2">
    <p class="text-gray-400 text-xs">Jenis & deskripsi</p>
    <p class="text-gray-700">{{ $jenisLabel }} — {{ $trx->deskripsi }}</p>
  </div>

  <div>
    <p class="text-gray-400 text-xs">Dari</p>
    <p class="text-gray-700">
      @if ($trx->maps_asal)
        <a href="{{ $trx->maps_asal }}" target="_blank" class="jne-text underline">{{ $trx->alamat_asal }}</a>
      @else
        {{ $trx->alamat_asal }}
      @endif
    </p>
  </div>
  <div>
    <p class="text-gray-400 text-xs">Ke</p>
    <p class="text-gray-700">
      @if ($trx->maps_tujuan)
        <a href="{{ $trx->maps_tujuan }}" target="_blank" class="jne-text underline">{{ $trx->alamat_tujuan }}</a>
      @else
        {{ $trx->alamat_tujuan }}
      @endif
    </p>
  </div>

  <div>
    <p class="text-gray-400 text-xs">Penerima</p>
    <p class="text-gray-700">{{ $trx->penerima }}</p>
    <p class="text-gray-400 text-xs">{{ $trx->no_hp_penerima }}</p>
  </div>

  @if ($pengirim)
  <div>
    <p class="text-gray-400 text-xs">Pengirim</p>
    <p class="text-gray-700">{{ $pengirim->nama_pelanggan }}</p>
    <p class="text-gray-400 text-xs">{{ $pengirim->no_hp_pelanggan }}</p>
  </div>
  @endif

  @if ($kurir)
  <div>
    <p class="text-gray-400 text-xs">Kurir</p>
    <p class="text-gray-700">{{ $kurir->nama_pelanggan }}</p>
    <p class="text-gray-400 text-xs">{{ $kurir->no_hp_pelanggan }}</p>
  </div>
  @endif

  @if ($trx->foto_barang_url)
  <div>
    <p class="text-gray-400 text-xs">Foto/dokumen barang</p>
    <a href="{{ $trx->foto_barang_url }}" target="_blank" class="jne-text underline text-xs">Lihat lampiran</a>
  </div>
  @endif

  @if ($trx->gambar_akhir_url)
  <div>
    <p class="text-gray-400 text-xs">Bukti serah terima</p>
    <a href="{{ $trx->gambar_akhir_url }}" target="_blank" class="jne-text underline text-xs">Lihat bukti foto</a>
  </div>
  @endif

  @if ($trx->note_penerima)
  <div class="col-span-2">
    <p class="text-gray-400 text-xs">Catatan</p>
    <p class="text-gray-700">{{ $trx->note_penerima }}</p>
  </div>
  @endif

  <div class="col-span-2 text-xs text-gray-400 pt-1 border-t">
    Dibuat {{ \Carbon\Carbon::parse($trx->created_at)->translatedFormat('d M Y, H:i') }}
  </div>
</div>
