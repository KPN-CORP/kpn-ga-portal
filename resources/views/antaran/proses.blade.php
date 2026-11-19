@extends('layouts.app_messenger_sidebar')

@section('content')
<style>
  .jne-orange{background:#2563eb}.jne-text{color:#2563eb}

  /* ===== Kartu tugas (HP) ===== */
  .tg-list{display:grid;gap:12px}
  @media (min-width:768px){.tg-list{display:none}}  /* card hanya untuk HP; tabel untuk desktop */
  .tg-card{background:#fff;border:1px solid #e5e7eb;border-left-width:4px;border-radius:16px;padding:14px;box-shadow:0 1px 2px rgba(0,0,0,.04)}
  .tg-card.is-belum{border-left-color:#f59e0b}
  .tg-card.is-antar{border-left-color:#2563eb}
  .tg-card.is-lain{border-left-color:#d1d5db}
  .tg-head{display:flex;justify-content:space-between;align-items:flex-start;gap:8px}
  .tg-head-l{display:flex;gap:10px;min-width:0}
  .tg-shrink{flex:none}.tg-min0{min-width:0}.tg-right{text-align:right}
  .tg-no{font-size:15px;font-weight:600;color:#111827;word-break:break-all}
  .tg-desc{font-size:13px;color:#4b5563;margin-top:2px}
  .tg-meta{font-size:11px;color:#9ca3af;margin-top:2px}
  .tg-thumb,.tg-pdf{width:56px;height:56px;border-radius:12px;border:1px solid #e5e7eb}
  .tg-thumb{object-fit:cover;display:block}
  .tg-pdf{display:flex;align-items:center;justify-content:center;background:#fef2f2;border-color:#fecaca;color:#ef4444;font-size:12px;font-weight:700}

  .tg-route{position:relative;margin:14px 0 12px;padding-left:22px}
  .tg-route:before{content:"";position:absolute;left:5px;top:10px;bottom:14px;border-left:2px dashed #d1d5db}
  .tg-stop{position:relative;padding-bottom:12px}
  .tg-stop:last-child{padding-bottom:0}
  .tg-stop:before{content:"";position:absolute;left:-22px;top:3px;width:12px;height:12px;border-radius:50%;background:#2563eb;box-shadow:0 0 0 3px #fff}
  .tg-stop.tujuan:before{background:#16a34a}
  .tg-lbl{display:block;font-size:10px;font-weight:600;color:#9ca3af;text-transform:uppercase;letter-spacing:.04em}
  .tg-addr{font-size:13px;color:#111827;line-height:1.4;margin-top:1px}
  .tg-map{display:inline-block;font-size:12px;font-weight:500;color:#2563eb;text-decoration:none;padding:4px 0}

  .tg-people{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:12px}
  .tg-person{background:#f9fafb;border-radius:12px;padding:8px 10px;min-width:0}
  .tg-person b{display:block;font-size:13px;font-weight:600;color:#111827;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .tg-call{display:inline-block;margin-top:2px;font-size:12px;font-weight:500;color:#2563eb;text-decoration:none;padding:4px 0}

  .tg-actions{display:flex;flex-direction:column;gap:8px}
  .tg-btn{display:block;width:100%;min-height:46px;border-radius:12px;border:1px solid #d1d5db;background:#fff;color:#111827;font-size:14px;font-weight:600;text-align:center;-webkit-tap-highlight-color:transparent}
  .tg-btn.primary{background:#2563eb;border-color:#2563eb;color:#fff}
  .tg-btn.warn{background:#fffbeb;border-color:#fcd34d;color:#b45309}
  .tg-btn:active{transform:scale(.98)}

  /* ===== Filter (HP saja) ===== */
  .tg-chips{display:flex;gap:8px;overflow-x:auto;margin:0 -16px 12px;padding:0 16px 2px;-webkit-overflow-scrolling:touch;scrollbar-width:none}
  .tg-chips::-webkit-scrollbar{display:none}
  .tg-chip{flex:none;border:1px solid #d1d5db;background:#fff;color:#374151;border-radius:999px;padding:8px 14px;font-size:12px;font-weight:500}
  .tg-chip.on{background:#2563eb;border-color:#2563eb;color:#fff}
  @media (min-width:768px){.tg-chips{display:none}}
</style>

@php
  // "Dokumen Belum Tersedia" tidak dianggap tugas aktif kurir, jadi disembunyikan dari halaman ini.
  // Idealnya difilter di query controller ($transaksi), ini fallback filter di view.
  // Resi yang sedang diantar ditaruh paling atas (urutan lain tetap: terbaru dulu)
  $transaksiTampil = $transaksi->reject(fn($item) => $item->status === 'Dokumen Belum Tersedia')
                               ->sortByDesc(fn($item) => (int) ($item->status === 'Proses Pengiriman'))
                               ->values();
  $jmlBelum = $transaksiTampil->filter(fn($i) => $i->kurir <= 0)->count();
  $jmlAntar = $transaksiTampil->where('status', 'Proses Pengiriman')->count();
@endphp

<div class="max-w-lg md:max-w-full mx-auto px-4 md:px-8 py-5">
  <div class="flex items-center justify-between mb-1">
    <h1 class="text-lg font-semibold">Tugas kurir</h1>
    <a href="{{ route('antaran.rute.harian') }}" class="text-xs jne-text underline">Lihat rute hari ini</a>
  </div>
  <p id="lokasi-status" class="text-xs text-gray-400 mb-4">Lokasi: nonaktif</p>
  <p class="hidden md:block text-xs text-gray-500 mb-3">Belum diambil: <b>{{ $jmlBelum }}</b> &middot; Sedang diantar: <b>{{ $jmlAntar }}</b></p>

  {{-- Error validasi upload (mis. foto gagal/terlalu besar) sebelumnya tidak tampil sama sekali --}}
  @if (session('error'))
    <div class="mb-3 rounded-lg border border-red-200 bg-red-50 text-red-700 text-xs px-3 py-2">{{ session('error') }}</div>
  @endif
  @if (session('success'))
    <div class="mb-3 rounded-lg border border-green-200 bg-green-50 text-green-700 text-xs px-3 py-2">{{ session('success') }}</div>
  @endif
  @if ($errors->any())
    <div class="mb-3 rounded-lg border border-red-200 bg-red-50 text-red-700 text-xs px-3 py-2">
      {{ $errors->first() }}
    </div>
  @endif

  {{-- HP: filter + tampilan card --}}
  @if ($transaksiTampil->isNotEmpty())
    <div class="tg-chips md:hidden">
      <button type="button" class="tg-chip on" onclick="tgFilter(this,'semua')">Semua ({{ $transaksiTampil->count() }})</button>
      <button type="button" class="tg-chip" onclick="tgFilter(this,'belum')">Belum diambil ({{ $jmlBelum }})</button>
      <button type="button" class="tg-chip" onclick="tgFilter(this,'antar')">Sedang diantar ({{ $jmlAntar }})</button>
    </div>
  @endif

  <div id="tg-list" class="tg-list md:hidden">
    @forelse ($transaksiTampil as $item)
      @include('antaran.partials.task-card', ['item' => $item])
    @empty
      <p class="text-sm text-gray-400 text-center py-10">Tidak ada tugas aktif.</p>
    @endforelse
    <p id="tg-kosong" style="display:none" class="text-sm text-gray-400 text-center py-10">Tidak ada tugas di kategori ini.</p>
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
            <th class="py-2 pr-3 font-medium">Dibuat</th>
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
<script>
  function tgFilter(btn, grup) {
    document.querySelectorAll('.tg-chip').forEach(function (c) { c.classList.toggle('on', c === btn); });
    var ada = 0;
    document.querySelectorAll('#tg-list .tg-card').forEach(function (c) {
      var tampil = grup === 'semua' || c.getAttribute('data-grup') === grup;
      c.style.display = tampil ? '' : 'none';
      if (tampil) ada++;
    });
    document.getElementById('tg-kosong').style.display = ada ? 'none' : 'block';
  }
</script>
@include('antaran.partials.foto-modal')
@include('antaran.partials.geo-tracker', ['resiAktif' => $resiAktif])
@endsection