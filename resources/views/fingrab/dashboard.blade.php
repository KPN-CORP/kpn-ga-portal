@extends('layouts.fingrab')

@section('title', 'Dashboard')

@section('content')
<div id="efg">
  <div class="w0">
    @include('fingrab.partials.header', ['title' => 'Dashboard E-FinGrab', 'hitung' => true])

    <div id="ov" style="display:grid;gap:14px">
      <div class="kp" id="kp"></div>
      <div class="p mu" id="em" style="text-align:center">Tidak ada data pada kombinasi filter ini. Atur ulang filter.</div>
      <div class="g2">
        <div class="p"><h2 id="h9">Tren pengeluaran bulanan</h2><div id="mtn" class="mtn"></div><div class="cv"><canvas id="c9"></canvas></div></div>
        <div class="p"><h2>Komposisi tipe</h2><div class="cv"><canvas id="c2"></canvas></div></div>
      </div>
      <div class="gh">
        <div class="p"><h2 id="h1"></h2><div class="cv"><canvas id="c1"></canvas></div></div>
        <div class="p"><h2 id="h4">10 karyawan dengan pengeluaran tertinggi</h2><div class="cv"><canvas id="c4"></canvas></div></div>
      </div>
      <div class="g3">
        <div class="p"><h2>Distribusi per bisnis unit</h2><div class="cv"><canvas id="c11"></canvas></div></div>
        <div class="p"><h2>Distribusi per Service</h2><div class="cv"><canvas id="c6"></canvas></div></div>
        <div class="p"><h2>Distribusi per kota</h2><div class="cv"><canvas id="c7"></canvas></div></div>
      </div>
      <div class="gh">
        <div class="p"><h2>Distribusi per unit (HCIS)</h2><div class="cv"><canvas id="c5"></canvas></div></div>
        <div class="p"><h2>Distribusi per office area (HCIS)</h2><div class="cv"><canvas id="c10"></canvas></div></div>
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