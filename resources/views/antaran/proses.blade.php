@extends('layouts.app-sidebar')

@section('content')
<style>.jne-orange{background:#f36f21}.jne-text{color:#f36f21}</style>

<div class="max-w-lg md:max-w-3xl lg:max-w-5xl mx-auto px-4 py-6">
  <div class="flex items-center justify-between mb-1">
    <h1 class="text-lg font-semibold">Tugas kurir</h1>
    <a href="{{ route('antaran.rute.harian') }}" class="text-xs jne-text underline">Lihat rute hari ini</a>
  </div>
  <p id="lokasi-status" class="text-xs text-gray-400 mb-4">Lokasi: nonaktif</p>

  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
    @forelse ($transaksi as $item)
      @include('antaran.partials.task-card', ['item' => $item])
    @empty
      <p class="text-sm text-gray-400 text-center py-10 col-span-full">Tidak ada tugas aktif.</p>
    @endforelse
  </div>
</div>

@php
  $resiAktif = $transaksi->where('status', 'Proses Pengiriman')
                          ->where('kurir', $kurir->id_pelanggan)
                          ->pluck('no_transaksi')->values();
@endphp
@include('antaran.partials.geo-tracker', ['resiAktif' => $resiAktif])
@endsection
