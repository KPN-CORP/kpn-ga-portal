@extends('layouts.app-sidebar')

@section('content')
<style>.jne-orange{background:#f36f21}.jne-text{color:#f36f21}</style>

@php
  // "Dokumen Belum Tersedia" tidak dianggap tugas aktif kurir, jadi disembunyikan dari halaman ini.
  // Idealnya difilter di query controller ($transaksi), ini fallback filter di view.
  $transaksiTampil = $transaksi->reject(fn($item) => $item->status === 'Dokumen Belum Tersedia')->values();
@endphp

<div class="max-w-lg md:max-w-full mx-auto px-4 md:px-8 py-6">
  <div class="flex items-center justify-between mb-1">
    <h1 class="text-lg font-semibold">Tugas kurir</h1>
    <a href="{{ route('antaran.rute.harian') }}" class="text-xs jne-text underline">Lihat rute hari ini</a>
  </div>
  <p id="lokasi-status" class="text-xs text-gray-400 mb-4">Lokasi: nonaktif</p>

  {{-- HP: tampilan card --}}
  <div class="grid grid-cols-1 gap-3 md:hidden">
    @forelse ($transaksiTampil as $item)
      @include('antaran.partials.task-card', ['item' => $item])
    @empty
      <p class="text-sm text-gray-400 text-center py-10">Tidak ada tugas aktif.</p>
    @endforelse
  </div>

  {{-- Desktop: tampilan tabel, full width --}}
  <div class="hidden md:block overflow-x-auto">
    @if ($transaksiTampil->isEmpty())
      <p class="text-sm text-gray-400 text-center py-10">Tidak ada tugas aktif.</p>
    @else
      <table class="w-full text-left border-collapse">
        <thead>
          <tr class="border-b text-xs text-gray-400">
            <th class="py-2 pr-3 font-medium">Lampiran</th>
            <th class="py-2 pr-3 font-medium">No. Transaksi</th>
            <th class="py-2 pr-3 font-medium">Jemput</th>
            <th class="py-2 pr-3 font-medium">Antar</th>
            <th class="py-2 pr-3 font-medium">Pengirim</th>
            <th class="py-2 pr-3 font-medium">Penerima</th>
            <th class="py-2 pr-3 font-medium">Status</th>
            <th class="py-2 pr-3 font-medium">Aksi</th>
          </tr>
        </thead>
        <tbody>
          @foreach ($transaksiTampil as $item)
            @include('antaran.partials.task-table-row', ['item' => $item])
          @endforeach
        </tbody>
      </table>
    @endif
  </div>
</div>

@php
  $resiAktif = $transaksi->where('status', 'Proses Pengiriman')
                          ->where('kurir', $kurir->id_pelanggan)
                          ->pluck('no_transaksi')->values();
@endphp
@include('antaran.partials.geo-tracker', ['resiAktif' => $resiAktif])
@endsection