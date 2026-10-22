@extends('layouts.fingrab')

@section('title', 'Kecocokan data')

@section('content')
<div id="efg">
  <div class="w0">
    @include('fingrab.partials.header', ['title' => 'Kecocokan data', 'hitung' => false])

    <div id="mt" style="display:grid;gap:14px"></div>

    @isset($M)
      @include('fingrab.partials.match-report')
    @endisset
  </div>
  @include('fingrab.partials.panel')
</div>
@endsection

@push('styles')
  @include('fingrab.partials.style')
@endpush

@push('scripts')
  @include('fingrab.partials.data')
  <script>const PAGE = 'mt';</script>
  @include('fingrab.partials.engine')
@endpush