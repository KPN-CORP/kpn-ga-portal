<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $filename }}</title>
    <style>
        * { box-sizing: border-box; }
        html, body { margin: 0; height: 100%; background: #0f172a; font-family: system-ui, sans-serif; }
        .bar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 10;
            display: flex; flex-wrap: wrap; gap: 6px; align-items: center; justify-content: center;
            padding: 8px; background: rgba(15,23,42,.92); backdrop-filter: blur(4px);
        }
        .bar button, .bar a {
            min-width: 40px; height: 40px; padding: 0 12px; border: 0; border-radius: 8px;
            background: #1e293b; color: #f8fafc; font-size: 14px; cursor: pointer;
            display: inline-flex; align-items: center; justify-content: center; text-decoration: none;
        }
        .bar button:hover, .bar a:hover { background: #334155; }
        .info { color: #94a3b8; font-size: 13px; min-width: 70px; text-align: center; }
        #stage { position: fixed; inset: 56px 0 0 0; overflow: auto; }
        #wrap { position: relative; margin: 0 auto; }
        #img {
            position: absolute; top: 50%; left: 50%;
            transform-origin: center center; max-width: none; user-select: none;
            transition: transform .2s ease;
        }
    </style>
</head>
<body>
    <div class="bar">
        <button type="button" id="rotL" title="Putar kiri (L)">⟲ Kiri</button>
        <button type="button" id="rotR" title="Putar kanan (R)">Kanan ⟳</button>
        <button type="button" id="zOut" title="Perkecil (-)">−</button>
        <span class="info" id="info">100%</span>
        <button type="button" id="zIn" title="Perbesar (+)">+</button>
        <button type="button" id="reset" title="Reset (0)">Reset</button>
        @if($rotateUrl)
            <button type="button" id="save" title="Simpan rotasi ke file">💾 Simpan rotasi</button>
        @endif
        <a href="{{ $src }}" download="{{ $filename }}" title="Download">⬇ Unduh</a>
    </div>

    <div id="stage">
        <div id="wrap"><img id="img" src="{{ $src }}" alt="{{ $filename }}" draggable="false"></div>
    </div>

    <script>
        (function () {
            var img = document.getElementById('img');
            var stage = document.getElementById('stage');
            var wrap = document.getElementById('wrap');
            var info = document.getElementById('info');
            var deg = 0, zoom = 1;

            function render() {
                var nw = img.naturalWidth, nh = img.naturalHeight;
                if (!nw) return;
                var swap = Math.abs(deg % 180) === 90;
                var ew = swap ? nh : nw, eh = swap ? nw : nh;
                var fit = Math.min(stage.clientWidth / ew, stage.clientHeight / eh, 1);
                var scale = fit * zoom;

                img.style.width = nw + 'px';
                img.style.height = nh + 'px';
                img.style.transform = 'translate(-50%,-50%) rotate(' + deg + 'deg) scale(' + scale + ')';

                // ukuran wrapper = ukuran gambar setelah rotasi & zoom, agar bisa di-scroll
                wrap.style.width = Math.max(ew * scale, stage.clientWidth) + 'px';
                wrap.style.height = Math.max(eh * scale, stage.clientHeight) + 'px';
                info.textContent = Math.round(zoom * 100) + '% · ' + (((deg % 360) + 360) % 360) + '°';
            }

            function rotate(d) { deg += d; render(); }
            function setZoom(z) { zoom = Math.min(5, Math.max(0.25, z)); render(); }

            document.getElementById('rotL').onclick = function () { rotate(-90); };
            document.getElementById('rotR').onclick = function () { rotate(90); };
            document.getElementById('zIn').onclick = function () { setZoom(zoom + 0.25); };
            document.getElementById('zOut').onclick = function () { setZoom(zoom - 0.25); };
            document.getElementById('reset').onclick = function () { deg = 0; zoom = 1; render(); };

            document.addEventListener('keydown', function (e) {
                if (e.key === 'r' || e.key === 'R') rotate(90);
                else if (e.key === 'l' || e.key === 'L') rotate(-90);
                else if (e.key === '+' || e.key === '=') setZoom(zoom + 0.25);
                else if (e.key === '-') setZoom(zoom - 0.25);
                else if (e.key === '0') { deg = 0; zoom = 1; render(); }
            });

            stage.addEventListener('wheel', function (e) {
                if (!e.ctrlKey) return;
                e.preventDefault();
                setZoom(zoom + (e.deltaY < 0 ? 0.25 : -0.25));
            }, { passive: false });


            var saveBtn = document.getElementById('save');
            if (saveBtn) {
                saveBtn.onclick = function () {
                    var d = ((deg % 360) + 360) % 360;
                    if (d === 0) { alert('Belum ada rotasi untuk disimpan.'); return; }
                    if (!confirm('Simpan rotasi ' + d + '° secara permanen ke file ini?')) return;
                    saveBtn.disabled = true;
                    fetch(@json($rotateUrl), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ degrees: d })
                    }).then(function (r) {
                        return r.json().then(function (j) { if (!r.ok) throw new Error(j.message || 'Gagal'); });
                    }).then(function () {
                        location.reload();
                    }).catch(function (e) {
                        alert('Gagal menyimpan: ' + e.message);
                        saveBtn.disabled = false;
                    });
                };
            }

            img.addEventListener('load', render);
            window.addEventListener('resize', render);
            if (img.complete) render();
        })();
    </script>
</body>
</html>
