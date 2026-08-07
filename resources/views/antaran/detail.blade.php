@extends('layouts.app-sidebar')

@section('content')
<style>.jne-orange{background:#2563eb}.jne-text{color:#2563eb}.jne-dot{background:#2563eb}</style>

<div class="max-w-7xl mx-auto px-4 lg:px-8 py-5">
  <div class="jne-orange rounded-t-xl px-5 py-3.5 text-white flex items-start justify-between gap-3">
    <div>
      <p class="text-xs opacity-90">No. resi</p>
      <h1 class="text-base font-semibold tracking-wide">{{ $trx->no_transaksi }}</h1>
      <p class="text-xs opacity-90 mt-0.5">{{ $trx->status }}</p>
    </div>

    <a href="{{ route('messenger.print', $trx->no_transaksi) }}"
       target="_blank"
       class="inline-flex items-center shrink-0 px-3 py-2 bg-white border border-gray-300 rounded-lg font-medium text-xs text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
      <i class="fas fa-print mr-1.5 text-xs"></i>
      <span class="hidden sm:inline">Print</span> PDF
    </a>
  </div>

  <div class="bg-white border border-t-0 rounded-b-xl px-5 py-4">

    {{-- Baris atas: peta & detail berdampingan, biar lebar layar terisi --}}
    <div class="grid lg:grid-cols-2 gap-5">
      @include('antaran.partials.map', ['trx' => $trx, 'titikLokasi' => $titikLokasi])
      @include('antaran.partials.detail-info', ['trx' => $trx, 'pengirim' => $pengirim, 'kurir' => $kurir])
    </div>

    {{-- Baris bawah: riwayat full-width, karena isinya list panjang ke bawah --}}
    <div class="mt-5">
      <p class="text-sm font-medium text-gray-700 mb-2">Riwayat</p>
      @include('antaran.partials.timeline', ['waktu' => $trx->waktu])
    </div>

    <div class="flex gap-2 pt-4 mt-4 border-t">
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
@endsection