@php
  $sudahDiambil = $item->kurir > 0;
  $sedangDiantar = $item->status === 'Proses Pengiriman';
  $isPdf = $item->foto_barang_url && str_ends_with(strtolower($item->foto_barang_url), '.pdf');
  $grup = !$sudahDiambil ? 'belum' : ($sedangDiantar ? 'antar' : 'lain');
@endphp
<div class="tg-card is-{{ $grup }}" data-grup="{{ $grup }}">
  <div class="tg-head">
    <div class="tg-head-l">
      @if ($item->foto_barang_url)
        <a href="{{ $item->foto_barang_url }}" target="_blank" class="tg-shrink">
          @if ($isPdf)
            <span class="tg-pdf">PDF</span>
          @else
            <img src="{{ $item->foto_barang_url }}" alt="Foto barang" class="tg-thumb" loading="lazy" decoding="async">
          @endif
        </a>
      @endif
      <div class="tg-min0">
        <p class="tg-no">{{ $item->no_transaksi }}</p>
        <p class="tg-desc">{{ Str::limit($item->deskripsi, 40) }}</p>
        <p class="tg-meta">Dibuat {{ \Carbon\Carbon::parse($item->created_at)->translatedFormat('d M Y, H:i') }}</p>
      </div>
    </div>
    <div class="tg-shrink tg-right">
      @include('antaran.partials.status-badge', ['status' => $item->status, 'kurir' => $item->nama_kurir ?? null])
    </div>
  </div>

  <div class="tg-route">
    <div class="tg-stop">
      <p class="tg-lbl">Jemput</p>
      <p class="tg-addr">{{ Str::limit($item->alamat_asal, 70) }}</p>
      @if ($item->maps_asal)<a href="{{ $item->maps_asal }}" target="_blank" class="tg-map">Buka peta &rarr;</a>@endif
    </div>
    <div class="tg-stop tujuan">
      <p class="tg-lbl">Antar</p>
      <p class="tg-addr">{{ Str::limit($item->alamat_tujuan, 70) }}</p>
      @if ($item->maps_tujuan)<a href="{{ $item->maps_tujuan }}" target="_blank" class="tg-map">Buka peta &rarr;</a>@endif
    </div>
  </div>

  <div class="tg-people">
    <div class="tg-person">
      <span class="tg-lbl">Pengirim</span>
      <b>{{ $item->nama_pengirim ?? '-' }}</b>
      @if ($item->no_hp_pengirim)<a href="tel:{{ $item->no_hp_pengirim }}" class="tg-call">{{ $item->no_hp_pengirim }}</a>@endif
    </div>
    <div class="tg-person">
      <span class="tg-lbl">Penerima</span>
      <b>{{ $item->penerima }}</b>
      @if ($item->no_hp_penerima)<a href="tel:{{ $item->no_hp_penerima }}" class="tg-call">{{ $item->no_hp_penerima }}</a>@endif
    </div>
  </div>

  @if (!$sudahDiambil)
    <div class="tg-actions">
      <button type="button" data-aksi="ambil" data-no="{{ $item->no_transaksi }}" onclick="fotoBuka(this)"
        class="tg-btn primary">Ambil & mulai tracking</button>
      <form action="{{ route('antaran.kembalikan', $item->no_transaksi) }}" method="POST"
        onsubmit="return confirm('Dokumen belum tersedia dari pengirim?')">
        @csrf
        <button class="tg-btn warn">Dok. belum ada</button>
      </form>
    </div>
  @elseif ($sedangDiantar)
    <div class="tg-actions">
      <button type="button" data-aksi="selesai" data-no="{{ $item->no_transaksi }}" onclick="fotoBuka(this)"
        class="tg-btn primary">Selesaikan</button>
    </div>
  @endif
</div>