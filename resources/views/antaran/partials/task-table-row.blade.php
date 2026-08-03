@php
  $sudahDiambil = $item->kurir > 0;
  $sedangDiantar = $item->status === 'Proses Pengiriman';
  $isPdf = $item->foto_barang_url && str_ends_with(strtolower($item->foto_barang_url), '.pdf');
@endphp
<tr class="border-b last:border-0 align-top">
  <td class="py-2 pr-3">
    @if ($item->foto_barang_url)
      <a href="{{ $item->foto_barang_url }}" target="_blank">
        @if ($isPdf)
          <span class="w-10 h-10 rounded-lg border bg-red-50 flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="w-5 h-5 text-red-500" fill="currentColor">
              <path d="M6 2h8l6 6v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z" fill="none" stroke="currentColor" stroke-width="1.5"/>
              <path d="M14 2v5a1 1 0 0 0 1 1h5" fill="none" stroke="currentColor" stroke-width="1.5"/>
              <text x="12" y="17" font-size="6" font-weight="700" text-anchor="middle" fill="currentColor" stroke="none">PDF</text>
            </svg>
          </span>
        @else
          <img src="{{ $item->foto_barang_url }}" alt="Foto barang" class="w-10 h-10 rounded-lg object-cover border">
        @endif
      </a>
    @endif
  </td>
  <td class="py-2 pr-3">
    <p class="text-sm font-semibold">{{ $item->no_transaksi }}</p>
    <p class="text-xs text-gray-500">{{ Str::limit($item->deskripsi, 40) }}</p>
  </td>
  <td class="py-2 pr-3 text-xs text-gray-600">
    <a href="{{ $item->maps_asal }}" target="_blank" class="jne-text underline">{{ Str::limit($item->alamat_asal, 35) }}</a>
  </td>
  <td class="py-2 pr-3 text-xs text-gray-600">
    <a href="{{ $item->maps_tujuan }}" target="_blank" class="jne-text underline">{{ Str::limit($item->alamat_tujuan, 35) }}</a>
  </td>
  <td class="py-2 pr-3 text-xs text-gray-600">
    {{ $item->nama_pengirim ?? '-' }}
    @if ($item->no_hp_pengirim)<br><a href="tel:{{ $item->no_hp_pengirim }}" class="jne-text underline">{{ $item->no_hp_pengirim }}</a>@endif
  </td>
  <td class="py-2 pr-3 text-xs text-gray-600">
    {{ $item->penerima }}
    @if ($item->no_hp_penerima)<br><a href="tel:{{ $item->no_hp_penerima }}" class="jne-text underline">{{ $item->no_hp_penerima }}</a>@endif
  </td>
  <td class="py-2 pr-3">
    @include('antaran.partials.status-badge', ['status' => $item->status])
  </td>
  <td class="py-2 pr-3">
    @if (!$sudahDiambil)
      <form action="{{ route('antaran.antar', $item->no_transaksi) }}" method="POST">
        @csrf
        <button class="jne-orange text-white rounded-lg px-3 py-1.5 text-xs whitespace-nowrap">Ambil & mulai tracking</button>
      </form>
    @elseif ($sedangDiantar)
      <div class="flex flex-col gap-1.5">
        <button type="button" onclick="document.getElementById('selesai-tbl-{{ $item->no_transaksi }}').classList.toggle('hidden')"
          class="border rounded-lg px-3 py-1.5 text-xs whitespace-nowrap">Selesaikan</button>
        <form action="{{ route('antaran.kembalikan', $item->no_transaksi) }}" method="POST"
          onsubmit="return confirm('Dokumen belum tersedia dari pengirim?')">
          @csrf
          <button class="w-full border border-amber-300 text-amber-700 rounded-lg px-3 py-1.5 text-xs whitespace-nowrap">Dok. belum ada</button>
        </form>
      </div>
    @endif
  </td>
</tr>
@if ($sudahDiambil && $sedangDiantar)
  <tr id="selesai-tbl-{{ $item->no_transaksi }}" class="hidden border-b last:border-0">
    <td colspan="8" class="pb-3">
      @include('antaran.partials.form-selesaikan', ['item' => $item])
    </td>
  </tr>
@endif