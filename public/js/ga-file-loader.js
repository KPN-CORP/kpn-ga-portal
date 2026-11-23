/*
 * GA Portal - pemuat gambar untuk file yang bisa berada di Local (bukan di Cloud).
 * Pemakaian:  <img src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" data-ga-src="URL_ROUTE_FOTO">
 *             GaFileLoader.load(imgElement, url)
 * Alur: fetch URL -> 200 = tampilkan; 202 = tampilkan "sedang diambil", polling 2 detik, lalu muat ulang otomatis.
 */
(function () {
    var POLL_MS = 2000;
    var MAX_WAIT_MS = 5 * 60 * 1000;

    function placeholder(line1, line2, spinning) {
        var spinner = spinning
            ? '<circle cx="200" cy="42" r="14" fill="none" stroke="#d1d5db" stroke-width="4"/>' +
              '<path d="M200 28 a14 14 0 0 1 14 14" fill="none" stroke="#2563eb" stroke-width="4" stroke-linecap="round">' +
              '<animateTransform attributeName="transform" type="rotate" from="0 200 42" to="360 200 42" dur="1s" repeatCount="indefinite"/></path>'
            : '';
        var svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 150">' +
            '<rect width="400" height="150" fill="#f3f4f6"/>' + spinner +
            '<text x="200" y="88" font-family="sans-serif" font-size="14" fill="#374151" text-anchor="middle">' + line1 + '</text>' +
            '<text x="200" y="110" font-family="sans-serif" font-size="14" fill="#374151" text-anchor="middle">' + line2 + '</text>' +
            '</svg>';
        return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svg);
    }

    function showWaiting(img) {
        img.src = placeholder('File sedang diambil dari server', 'lokal GA Portal. Mohon tunggu...', true);
    }

    function showError(img, text) {
        img.src = placeholder(text, '', false);
    }

    function setBlob(img, blob) {
        if (img._gaObj) { URL.revokeObjectURL(img._gaObj); }
        img._gaObj = URL.createObjectURL(blob);
        img.src = img._gaObj;
    }

    function attempt(img, url, tok, t0) {
        fetch(url, {
            credentials: 'same-origin',
            headers: { 'Accept': 'image/*,application/json;q=0.9', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (res) {
            if (tok !== img._gaTok) { return; }
            if (res.status === 200) {
                return res.blob().then(function (b) { if (tok === img._gaTok) { setBlob(img, b); } });
            }
            if (res.status === 202) {
                return res.json().then(function (j) {
                    if (tok !== img._gaTok) { return; }
                    showWaiting(img);
                    poll(img, url, tok, j.status_url, t0);
                });
            }
            showError(img, 'Foto tidak ditemukan');
        }).catch(function () {
            if (tok === img._gaTok) { showError(img, 'Gagal memuat foto'); }
        });
    }

    function poll(img, url, tok, statusUrl, t0) {
        if (tok !== img._gaTok) { return; }
        if (Date.now() - t0 > MAX_WAIT_MS) { return showError(img, 'Masih diproses, coba lagi nanti'); }
        setTimeout(function () {
            if (tok !== img._gaTok) { return; }
            fetch(statusUrl, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) { return r.json(); }).then(function (s) {
                if (tok !== img._gaTok) { return; }
                if (s.status === 'COMPLETED') { return attempt(img, url, tok, t0); }
                if (s.status === 'FAILED') { return showError(img, 'Gagal mengambil file dari server lokal'); }
                poll(img, url, tok, statusUrl, t0);
            }).catch(function () { poll(img, url, tok, statusUrl, t0); });
        }, POLL_MS);
    }

    function load(img, url) {
        if (!img || !url) { return; }
        if (/^(blob:|data:)/.test(url)) { img.src = url; return; }
        img._gaTok = (img._gaTok || 0) + 1;
        attempt(img, url, img._gaTok, Date.now());
    }

    function init(root) {
        (root || document).querySelectorAll('img[data-ga-src]').forEach(function (img) {
            if (img._gaInit) { return; }
            img._gaInit = true;
            load(img, img.getAttribute('data-ga-src'));
        });
    }

    document.addEventListener('DOMContentLoaded', function () { init(); });
    window.GaFileLoader = { load: load, init: init };
})();
