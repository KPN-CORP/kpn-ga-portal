@php
  $sudahDiambil = $item->kurir > 0;
  $sedangDiantar = $item->status === 'Proses Pengiriman';
@endphp
<div class="border rounded-xl px-4 py-3">
  <div class="flex gap-3 mb-2">
    @if ($item->foto_barang_url)
      <a href="{{ $item->foto_barang_url }}" target="_blank" class="shrink-0">
        <img src="{{ $item->foto_barang_url }}" alt="Foto barang"
             class="w-14 h-14 rounded-lg object-cover border">
      </a>
    @endif
    <div class="flex-1 min-w-0">
      <div class="flex justify-between items-start">
        <p class="text-sm font-semibold">{{ $item->no_transaksi }}</p>
        @include('antaran.partials.status-badge', ['status' => $item->status])
      </div>
      <p class="text-xs text-gray-500">{{ Str::limit($item->deskripsi, 40) }}</p>
    </div>
  </div>

  <div class="text-xs space-y-1 mb-3">
    <p class="text-gray-400">Jemput:
      <a href="{{ $item->maps_asal }}" target="_blank" class="jne-text underline">{{ Str::limit($item->alamat_asal, 40) }}</a>
    </p>
    <p class="text-gray-400">Antar:
      <a href="{{ $item->maps_tujuan }}" target="_blank" class="jne-text underline">{{ Str::limit($item->alamat_tujuan, 40) }}</a>
    </p>
    <p class="text-gray-400">Pengirim: <span class="text-gray-600">{{ $item->nama_pengirim ?? '-' }}</span>
      @if ($item->no_hp_pengirim) &middot; <a href="tel:{{ $item->no_hp_pengirim }}" class="jne-text underline">{{ $item->no_hp_pengirim }}</a> @endif
    </p>
    <p class="text-gray-400">Penerima: <span class="text-gray-600">{{ $item->penerima }}</span>
      @if ($item->no_hp_penerima) &middot; <a href="tel:{{ $item->no_hp_penerima }}" class="jne-text underline">{{ $item->no_hp_penerima }}</a> @endif
    </p>
  </div>

  <div class="flex gap-2">
    @if (!$sudahDiambil)
      <form action="{{ route('antaran.antar', $item->no_transaksi) }}" method="POST" class="flex-1">
        @csrf
        <button class="jne-orange w-full text-white rounded-lg py-1.5 text-xs">Ambil & mulai tracking</button>
      </form>
    @elseif ($sedangDiantar)
      <button type="button" onclick="document.getElementById('selesai-{{ $item->no_transaksi }}').classList.toggle('hidden')"
        class="flex-1 border rounded-lg py-1.5 text-xs">Selesaikan</button>
      <form action="{{ route('antaran.kembalikan', $item->no_transaksi) }}" method="POST" class="flex-1"
        onsubmit="return confirm('Dokumen belum tersedia dari pengirim?')">
        @csrf
        <button class="w-full border border-amber-300 text-amber-700 rounded-lg py-1.5 text-xs">Dok. belum ada</button>
      </form>
    @endif
  </div>

  @if ($sudahDiambil && $sedangDiantar)
    @include('antaran.partials.form-selesaikan', ['item' => $item])
  @endif
</div>
