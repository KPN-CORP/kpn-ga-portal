@extends('layouts.app_car_sidebar')

@section('content')
<div style="position: relative; height: 100vh; width: 100%;">
    <div id="map" style="height: 100%; width: 100%;"></div>

    
</div>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<style>
    .car-item { cursor: pointer; transition: 0.2s; }
    .car-item:hover { background: #f5f5f5; }
    .online { color: green; font-weight: bold; }
    .offline { color: red; font-weight: bold; }
    .btn-reset { width: 100%; padding: 8px; background: #2563eb; color: white; border: none; border-radius: 6px; }
</style>

<script>
    const gpsData = @json($gpsData);
    const map = L.map('map').setView([-6.2, 106.8], 10);
    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    const markers = {};
    const tooltips = {};
    const carIcon = L.icon({
        iconUrl: 'https://cdn-icons-png.flaticon.com/512/3448/3448339.png',
        iconSize: [45,45],
        iconAnchor: [22,22],
        popupAnchor: [0,-20]
    });

    @foreach($gpsData as $plate => $info)
        markers['{{ $plate }}'] = L.marker([{{ $info['lat'] }}, {{ $info['lng'] }}], { icon: carIcon }).addTo(map);
        markers['{{ $plate }}'].bindPopup(`
            <div style="min-width:220px">
                <div style="font-size:16px; font-weight:bold; margin-bottom:8px;">🚗 {{ $plate }}</div>
                <div style="margin-bottom:5px;"><b>Status:</b> <span style="color: {{ strtolower($info['status']) == 'on' ? 'green' : 'red' }}; font-weight:bold;">{{ $info['status'] }}</span></div>
                <div style="font-size:13px; color:#444;">📍 {{ addslashes($info['alamat']) }}</div>
            </div>
        `);
        tooltips['{{ $plate }}'] = L.tooltip({ permanent: true, direction: 'right', offset: [15,0] })
            .setContent(`<div style="background:white; padding:6px 10px; border-radius:10px; box-shadow:0 0 5px rgba(0,0,0,0.2);"><b>{{ $plate }}</b></div>`)
            .setLatLng([{{ $info['lat'] }}, {{ $info['lng'] }}]);
        // tooltip tidak ditambahkan otomatis (akan muncul saat fokus)
    @endforeach

    function showAllCars() {
        for (let plate in markers) {
            if (!map.hasLayer(markers[plate])) markers[plate].addTo(map);
            if (tooltips[plate] && map.hasLayer(tooltips[plate])) map.removeLayer(tooltips[plate]);
        }
        map.setView([-6.2, 106.8], 10);
    }

    function focusCar(plate, lat, lng) {
        for (let p in markers) {
            if (map.hasLayer(markers[p])) map.removeLayer(markers[p]);
            if (tooltips[p] && map.hasLayer(tooltips[p])) map.removeLayer(tooltips[p]);
        }
        if (markers[plate]) {
            markers[plate].addTo(map);
            if (tooltips[plate]) tooltips[plate].addTo(map);
            map.setView([lat, lng], 18);
            markers[plate].openPopup();
        } else {
            alert('Data GPS untuk ' + plate + ' tidak tersedia.');
        }
    }

    @if(isset($focusPlate) && array_key_exists($focusPlate, $gpsData))
        setTimeout(() => {
            const info = gpsData['{{ $focusPlate }}'];
            if (info) focusCar('{{ $focusPlate }}', info.lat, info.lng);
        }, 500);
    @endif
</script>
@endsection