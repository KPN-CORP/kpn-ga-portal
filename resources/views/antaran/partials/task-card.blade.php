@php
  $sudahDiambil = $item->kurir > 0;
  $sedangDiantar = $item->status === 'Proses Pengiriman';
  $isPdf = $item->foto_barang_url && str_ends_with(strtolower($item->foto_barang_url), '.pdf');
@endphp
<div class="border rounded-xl px-4 py-3">
  <div class="flex gap-3 mb-2">
    @if ($item->foto_barang_url)
      <a href="{{ $item->foto_barang_url }}" target="_blank" class="shrink-0">
        @if ($isPdf)
          <span class="w-14 h-14 rounded-lg border bg-red-50 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-7 h-7 text-red-500" fill="currentColor">
              <path d="M6 2a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6H6z" opacity=".15"/>
              <path d="M14 2v5a1 1 0 0 0 1 1h5" fill="none" stroke="currentColor" stroke-width="1.5"/>
              <path d="M6 2h8l6 6v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z" fill="none" stroke="currentColor" stroke-width="1.5"/>
              <text x="12" y="17" font-size="7" font-weight="700" text-anchor="middle" fill="currentColor" stroke="none">PDF</text>
            </svg>
          </span>
        @else
          <img src="{{ $item->foto_barang_url }}" alt="Foto barang"
               class="w-14 h-14 rounded-lg object-cover border">
        @endif
      </a>
    @endif
    <div class="flex-1 min-w-0">
      <div class="flex justify-between items-start">
        <p class="text-sm font-semibold">{{ $item->no_transaksi }}</p>
        <div class="flex flex-col items-end">
          @include('antaran.partials.status-badge', ['status' => $item->status, 'kurir' => $item->nama_kurir ?? null])
        </div>
      </div>
      <p class="text-xs text-gray-500">{{ Str::limit($item->deskripsi, 40) }}</p>
      <p class="text-[11px] text-gray-400 mt-0.5">Dibuat {{ \Carbon\Carbon::parse($item->created_at)->translatedFormat('d M Y, H:i') }}</p>
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