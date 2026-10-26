@extends('layouts.fingrab')

@section('title', 'Karyawan dan unit')

@section('content')
<div id="efg">
  <div class="w0">
    @include('fingrab.partials.header', ['title' => 'Karyawan dan unit', 'hitung' => false])

    <div id="ep" style="display:grid;gap:14px">
      <div class="p"><h2>Ringkasan per unit. Klik judul kolom untuk mengurutkan.</h2><div class="w" id="dt"></div></div>
      <div class="p"><h2>Ringkasan per karyawan. Klik judul kolom untuk mengurutkan, klik baris untuk melihat transaksi.</h2><div class="w" id="et"></div></div>
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
  <script>const PAGE = 'ep';</script>
  @include('fingrab.partials.engine')
@endpush