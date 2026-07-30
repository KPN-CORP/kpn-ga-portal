@extends('layouts.app-sidebar')

@section('content')
<style>.jne-orange{background:#f36f21}.jne-text{color:#f36f21}.jne-dot{background:#f36f21}</style>

<div class="max-w-lg lg:max-w-4xl mx-auto px-4 py-6">
  <div class="jne-orange rounded-t-xl px-5 py-4 text-white">
    <p class="text-xs opacity-90">No. resi</p>
    <h1 class="text-lg font-semibold tracking-wide">{{ $trx->no_transaksi }}</h1>
    <p class="text-sm opacity-90 mt-1">{{ $trx->status }}</p>
  </div>

  <div class="bg-white border border-t-0 rounded-b-xl px-5 py-5 lg:grid lg:grid-cols-[1fr_340px] lg:gap-8 lg:items-start">

    {{-- Kolom kiri: peta + riwayat (jadi lebih dominan di layar lebar) --}}
    <div class="space-y-6">
      @include('antaran.partials.map', ['trx' => $trx, 'titikLokasi' => $titikLokasi])

      <div>
        <p class="text-sm font-medium text-gray-700 mb-3">Riwayat</p>
        @include('antaran.partials.timeline', ['waktu' => $trx->waktu])
      </div>
    </div>

    {{-- Kolom kanan: info lengkap + aksi (di HP/tablet turun ke bawah) --}}
    <div class="space-y-6 mt-6 lg:mt-0">
      @include('antaran.partials.detail-info', ['trx' => $trx, 'pengirim' => $pengirim, 'kurir' => $kurir])

      <div class="flex gap-2 pt-2">
        @if (in_array($trx->status, ['Belum Terkirim','Pengiriman Dibuat']))
          <form action="{{ route('antaran.cancel', $trx->no_transaksi) }}" method="POST" class="flex-1"
                onsubmit="return confirm('Batalkan kiriman ini?')">
            @csrf
            <button class="w-full border border-red-300 text-red-600 rounded-lg py-2 text-sm">Batalkan</button>
          </form>
        @endif
        @if ($trx->status === 'Dokumen Belum Tersedia')
          <form action="{{ route('antaran.kirimUlang', $trx->no_transaksi) }}" method="POST" class="flex-1">
            @csrf
            <button class="jne-orange w-full text-white rounded-lg py-2 text-sm">Kirim ulang</button>
          </form>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
