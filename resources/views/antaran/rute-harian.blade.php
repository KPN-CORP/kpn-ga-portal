@extends('layouts.app-sidebar')

@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<style>
  .jne-orange{background:#f36f21}.jne-text{color:#f36f21}
  #peta-harian{height:60vh;min-height:320px;border-radius:12px}
</style>

<div class="max-w-lg lg:max-w-4xl mx-auto px-4 py-6">
  <div class="jne-orange rounded-t-xl px-5 py-4 text-white">
    <h1 class="text-lg font-semibold">Rute pengantaran hari ini</h1>
    <p class="text-sm opacity-90">{{ $kurir->nama_pelanggan ?? '-' }} &middot; {{ \Carbon\Carbon::parse($tanggal)->translatedFormat('d M Y') }}</p>
  </div>

  <div class="bg-white border border-t-0 rounded-b-xl px-5 py-5 space-y-5">

    @if ($titik->isEmpty())
      <div class="text-sm text-gray-400 text-center py-16 border rounded-lg">
        Belum ada titik lokasi tercatat untuk tanggal ini.
      </div>
    @else
      <div id="peta-harian"></div>

      <div class="grid grid-cols-2 gap-3 text-sm">
        <div>
          <p class="text-gray-400 text-xs">Mulai (jemput pertama)</p>
          <p class="text-gray-700">{{ \Carbon\Carbon::parse($titik->first()->created_at)->format('H:i') }}</p>
        </div>
        <div>
          <p class="text-gray-400 text-xs">Terakhir tercatat</p>
          <p class="text-gray-700">{{ \Carbon\Carbon::parse($titik->last()->created_at)->format('H:i') }}</p>
        </div>
      </div>

      <div>
        <p class="text-sm font-medium text-gray-700 mb-2">Resi yang dilalui hari ini ({{ $resiHariIni->count() }})</p>
        <div class="space-y-2">
          @foreach ($resiHariIni as $r)
            <a href="{{ route('antaran.detail', $r->no_transaksi) }}"
               class="flex justify-between items-center border rounded-lg px-3 py-2 hover:border-orange-300">
              <div>
                <p class="text-xs font-medium">{{ $r->no_transaksi }}</p>
                <p class="text-[11px] text-gray-400">{{ Str::limit($r->alamat_asal, 25) }} &rarr; {{ Str::limit($r->alamat_tujuan, 25) }}</p>
              </div>
              @include('antaran.partials.status-badge', ['status' => $r->status])
            </a>
          @endforeach
        </div>
      </div>
    @endif

    <form method="GET" class="flex gap-2 pt-2 border-t">
      <input type="date" name="tanggal" value="{{ $tanggal }}"
        class="border rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
    </form>
  </div>
</div>

@if ($titik->isNotEmpty())
<script>
  const titik = @json($titik->map(fn($t) => [(float)$t->latitude, (float)$t->longitude]));
  const map = L.map('peta-harian');
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);

  const jalur = L.polyline(titik, { color: '#f36f21', weight: 4 }).addTo(map);
  L.circleMarker(titik[0], { radius: 7, color: '#22c55e', fillColor:'#22c55e', fillOpacity:1 })
    .addTo(map).bindPopup('Jemput pertama hari ini');
  L.circleMarker(titik[titik.length - 1], { radius: 7, color: '#f36f21', fillColor:'#f36f21', fillOpacity:1 })
    .addTo(map).bindPopup('Titik terakhir tercatat');
  map.fitBounds(jalur.getBounds(), { padding: [24, 24] });
</script>
@endif
@endsection
