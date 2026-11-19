<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mengambil file...</title>
    <style>
        body { font-family: system-ui, sans-serif; background: #f3f4f6; display: flex; min-height: 100vh; align-items: center; justify-content: center; margin: 0; }
        .box { background: #fff; border-radius: 12px; padding: 32px 40px; text-align: center; box-shadow: 0 4px 16px rgba(0,0,0,.08); max-width: 380px; }
        .spin { width: 40px; height: 40px; border: 4px solid #e5e7eb; border-top-color: #2563eb; border-radius: 50%; margin: 0 auto 16px; animation: s 1s linear infinite; }
        @keyframes s { to { transform: rotate(360deg); } }
        .err { color: #b91c1c; }
    </style>
</head>
<body>
    <div class="box">
        <div class="spin" id="spin"></div>
        <p id="msg">File sedang diambil dari server lokal GA Portal.<br>Mohon tunggu...</p>
    </div>
    <script>
        (function () {
            var statusUrl = @json($statusUrl);
            var started = Date.now();
            function fail(text) {
                document.getElementById('spin').style.display = 'none';
                var m = document.getElementById('msg');
                m.className = 'err';
                m.textContent = text;
            }
            function poll() {
                if (Date.now() - started > 5 * 60 * 1000) { return fail('Masih diproses. Silakan coba lagi nanti.'); }
                fetch(statusUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.json(); })
                    .then(function (s) {
                        if (s.status === 'COMPLETED') { return window.location.reload(); }
                        if (s.status === 'FAILED') { return fail(s.message || 'Gagal mengambil file.'); }
                        setTimeout(poll, 2000);
                    })
                    .catch(function () { setTimeout(poll, 2000); });
            }
            setTimeout(poll, 2000);
        })();
    </script>
</body>
</html>
