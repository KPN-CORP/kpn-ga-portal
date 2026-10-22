@extends('layouts.fingrab')

@section('title', 'Dashboard')

@section('content')
<div id="efg">
  <div class="w0">
    @include('fingrab.partials.header', ['title' => 'Dashboard E-FinGrab', 'hitung' => true])

    <div id="ov" style="display:grid;gap:14px">
      <div class="kp" id="kp"></div>
      <div class="p"><h2 id="h9">Tren biaya per bulan</h2><div id="mtn" class="mtn"></div><div class="cv"><canvas id="c9"></canvas></div></div>
      <div class="p mu" id="em" style="text-align:center">Tidak ada transaksi untuk filter ini.</div>
      <div class="g">
        <div class="p"><h2 id="h1"></h2><div class="cv"><canvas id="c1"></canvas></div></div>
        <div class="p"><h2>Komposisi layanan</h2><div class="cv"><canvas id="c2"></canvas></div></div>
        <div class="p"><h2>10 karyawan teratas</h2><div class="cv"><canvas id="c4"></canvas></div></div>
        <div class="p"><h2>Per bisnis unit</h2><div class="cv"><canvas id="c11"></canvas></div></div>
        <div class="p"><h2>Per unit (HCIS)</h2><div class="cv"><canvas id="c5"></canvas></div></div>
        <div class="p"><h2>Per office area (HCIS)</h2><div class="cv"><canvas id="c10"></canvas></div></div>
        <div class="p"><h2>Per grup</h2><div class="cv"><canvas id="c6"></canvas></div></div>
        <div class="p"><h2>Per kota</h2><div class="cv"><canvas id="c7"></canvas></div></div>
      </div>
    </div>
  </div>
  @include('fingrab.partials.panel')
</div>
@endsection

@push('styles')
  @include('fingrab.partials.style')
@endpush

@push('scripts')
  @include('fingrab.partials.data')
  <script>const PAGE = 'ov';</script>
  @include('fingrab.partials.engine')
@endpush