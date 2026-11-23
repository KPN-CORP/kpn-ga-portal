{{-- Kirim titik GPS kurir tiap 15 detik untuk semua resi "Proses Pengiriman" yang
     sedang dipegang kurir ini (titik-ke-titik, bukan garis lurus asal-tujuan). --}}
<script>
  const resiAktif = @json($resiAktif);
  const statusEl = document.getElementById('lokasi-status');

  function kirimTitik(noTransaksi, lat, lng, akurasi) {
    fetch(`/antaran/lacak/${noTransaksi}/titik`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
      },
      body: JSON.stringify({ latitude: lat, longitude: lng, akurasi }),
    }).catch(() => {});
  }

  if (resiAktif.length && navigator.geolocation) {
    statusEl.textContent = 'Lokasi: aktif, mengirim tiap 15 detik';
    setInterval(() => {
      navigator.geolocation.getCurrentPosition((pos) => {
        resiAktif.forEach((no) => kirimTitik(no, pos.coords.latitude, pos.coords.longitude, pos.coords.accuracy));
      }, () => { statusEl.textContent = 'Lokasi: izin ditolak'; }, { enableHighAccuracy: true });
    }, 15000);
  } else if (!resiAktif.length) {
    statusEl.textContent = 'Lokasi: tidak ada tugas aktif';
  }
</script>
