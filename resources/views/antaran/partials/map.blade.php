@if (in_array($trx->status, ['Proses Pengiriman','Terkirim']) && count($titikLokasi))
  <div>
    <p class="text-sm font-medium text-gray-700 mb-2">Rute pengantaran</p>
    <div id="peta" style="height:280px;border-radius:12px"></div>
    <p class="text-xs text-gray-400 mt-1">Diperbarui otomatis tiap kurir mengirim titik baru.</p>
  </div>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
  <script>
    const titik = @json($titikLokasi->map(fn($t) => [(float)$t->latitude, (float)$t->longitude]));
    const map = L.map('peta');
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap' }).addTo(map);

    let jalur;
    if (titik.length) {
      jalur = L.polyline(titik, { color: '#2563eb', weight: 4 }).addTo(map);
      L.circleMarker(titik[0], { radius: 6, color: '#22c55e', fillColor:'#22c55e', fillOpacity:1 }).addTo(map).bindPopup('Titik awal');
      L.circleMarker(titik.at(-1), { radius: 7, color: '#2563eb', fillColor:'#2563eb', fillOpacity:1 }).addTo(map).bindPopup('Posisi terakhir kurir');
      map.fitBounds(jalur.getBounds(), { padding: [24, 24] });
    }

    @if ($trx->status === 'Proses Pengiriman')
    setInterval(async () => {
      const res = await fetch("{{ route('antaran.lokasi.json', $trx->no_transaksi) }}");
      const data = await res.json();
      if (data.titik.length) {
        const pts = data.titik.map(t => [parseFloat(t.latitude), parseFloat(t.longitude)]);
        jalur.setLatLngs(pts);
        map.panTo(pts.at(-1));
      }
      if (data.status !== 'Proses Pengiriman') location.reload();
    }, 15000);
    @endif
  </script>
@elseif ($trx->status === 'Proses Pengiriman')
  <div class="text-sm text-gray-400 text-center py-6 border rounded-lg">
    Menunggu kurir mengirim titik lokasi pertama...
  </div>
@endif