{{--
    Sidebar enhancer (minimize + polish)  — v3
    ------------------------------------------
    PERUBAHAN v3 (cari tanda [BARU v3] / [UBAH v3] / [HAPUS v3] di bawah):
      1. [UBAH v3]  Default MINIMIZE untuk semua user & semua ukuran layar (sebelumnya default terbuka di desktop).
                    Key localStorage diganti ke 'gaSidebarV2' supaya pilihan lama ('0' = terbuka) tidak menahan
                    user tetap terbuka. Setelah user klik tombol / Ctrl+B, pilihannya tetap diingat.
      2. [UBAH v3]  Margin konten kini diatur lewat CSS di <head> (bukan lewat JS saat DOMContentLoaded),
                    jadi konten TIDAK lagi "bergeser" dari 16rem ke 4.5rem setiap pindah halaman.
      3. [BARU v3]  Class html.sb-ready: animasi width / margin / tombol hanya aktif SETELAH halaman selesai
                    dirender, jadi animasi hanya muncul saat user klik tombol minimize, bukan saat pindah menu.
      4. [HAPUS v3] Blok JS yang menambahkan class .sb-content (tidak diperlukan lagi).
      5. [BARU v3.1] STANDAR LEBAR HALAMAN: konten tidak lagi "mengambang di tengah dengan kiri-kanan kosong".
                    Pembungkus halaman yang langsung di bawah <main> diseragamkan (lihat blok "STANDAR LEBAR HALAMAN"):
                      - .container, max-w-5xl/6xl/7xl/screen-*        -> selebar area konten (100%)
                      - max-w-4xl (detail/tabel sedang)                -> var(--pg-detail-w) = 72rem
                      - max-w-lg/xl/2xl/3xl (form & detail ringkas)    -> var(--pg-form-w)   = 56rem
                    Halaman mobile-first yang sengaja responsif (mis. "max-w-lg md:max-w-full" di /antaran)
                    TIDAK disentuh. Ubah angka di :root kalau mau lebih sempit / lebih lebar.
      6. [UBAH v3.2] Tooltip saat minimize kini membaca atribut data-sb-tip lebih dulu (baru title / teks link).
                    Dipakai hsrm-app: teks menu tetap "HOME", tooltip minimize "GA Portal".
      7. [UBAH v3.3] DROPDOWN halus saat minimize <-> buka: isi dropdown tidak lagi di-display:none (yang membuat grup
                    aktif/kotak navy "meloncat"), tapi dilipat lewat max-height + opacity sehingga tumbuh/menyusut
                    dengan transisi bawaan layout. Chevron, kotak navy & warna menu juga tidak lagi beranimasi
                    saat halaman baru dimuat (sebelum .sb-ready).
      8. [UBAH v3.4] Animasi dropdown memakai TINGGI ASLI isinya (diukur lewat JS), bukan max-height 600px bawaan layout.
                    Dengan 600px, isi grup yang hanya ~150px membuat animasi terjadi di ruang kosong lalu "mengejutkan"
                    di akhir. Sekarang kotak navy tumbuh/menyusut mulus mengikuti tinggi sebenarnya.
    Di-include SEKALI di <head> tiap layout sidebar:
        @include('layouts.partials.sidebar-enhance')                         <- layout md: sidebar tetap tampil >= 769px
        @include('layouts.partials.sidebar-enhance', ['bp' => 1024])         <- layout lg: drawer sampai 1023px

    - Fitur minimize aktif selama sidebar tampil permanen (>= $bp, default 769px = tablet & desktop).
      Di bawah itu tetap drawer bawaan layout (tidak berubah).
    - Tombol bulat di tepi sidebar, atau Ctrl/Cmd + B. Pilihan diingat (localStorage).
      Tablet (<1100px) yang belum pernah memilih -> default minimize.
    - Saat minimize: ikon grup dropdown membuka panel mengambang berisi sub-menu (hover / klik / tap),
      begitu juga profil + Keluar. Sidebar TIDAK melebar sendiri.
    - Tidak mengubah markup/menu yang ada. Hapus baris @include untuk mematikan fitur.
--}}
<script>
    (function () {
        var r = document.documentElement;
        window.gaSbKey = 'gaSidebarV2';                                   // [BARU v3] key baru -> semua user mulai dari minimize
        // [UBAH v3] DEFAULT MINIMIZE: hanya terbuka kalau user pernah memilih terbuka ('0')
        try {
            if (localStorage.getItem(window.gaSbKey) !== '0') r.classList.add('sb-collapsed');
        } catch (e) { r.classList.add('sb-collapsed'); }
        if (window.matchMedia('(min-width: {{ (int) ($bp ?? 769) }}px)').matches) r.classList.add('sb-on');
    })();
</script>
<style>
    :root { --sb-w: 16rem; }
    html.sb-collapsed { --sb-w: 4.5rem; }

    /* ============ STANDAR LEBAR HALAMAN [BARU v3.1] ============
       Semua layout memakai <main> tanpa batas lebar; yang membatasi adalah pembungkus di tiap halaman
       (.container = maks 1536px, max-w-2xl = 672px, dst) lalu di-center -> kiri/kanan kosong di layar lebar
       (apalagi html{zoom:.8} + sidebar minimize membuat area konten sangat lebar).
       Hanya anak LANGSUNG <main> yang diatur -> modal / kartu / elemen di dalam halaman tidak terpengaruh.
       Elemen dengan varian responsif (md:max-w-*, lg:max-w-*) dikecualikan karena sudah diatur halamannya sendiri. */
    :root { --pg-form-w: 56rem; --pg-detail-w: 72rem; }

    /* 1) halaman lebar: daftar, dashboard, tabel -> penuh */
    main > .container:not([class*=":max-w-"]),
    main > .max-w-5xl:not([class*=":max-w-"]),
    main > .max-w-6xl:not([class*=":max-w-"]),
    main > .max-w-7xl:not([class*=":max-w-"]),
    main > .max-w-screen-lg:not([class*=":max-w-"]),
    main > .max-w-screen-xl:not([class*=":max-w-"]),
    main > .max-w-screen-2xl:not([class*=":max-w-"]) { max-width: 100% !important; }

    /* 2) detail sedang */
    main > .max-w-4xl:not([class*=":max-w-"]) { max-width: var(--pg-detail-w) !important; }

    /* 3) form & detail ringkas (urutan terakhir: menang atas .container yang digabung dengan max-w-lg/2xl) */
    main > .max-w-lg:not([class*=":max-w-"]),
    main > .max-w-xl:not([class*=":max-w-"]),
    main > .max-w-2xl:not([class*=":max-w-"]),
    main > .max-w-3xl:not([class*=":max-w-"]) { max-width: var(--pg-form-w) !important; }

    /* ============ POLISH (semua ukuran layar) ============ */
    .sidebar { background-image: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%); }
    .sidebar .brand-badge { box-shadow: 0 4px 14px rgba(var(--accent) / .18); }
    .sidebar .sidebar-link,
    .sidebar .dropdown-toggle { border-radius: .75rem; }
    .sidebar .sidebar-link i,
    .sidebar .dropdown-toggle i { transition: color .2s ease, transform .2s cubic-bezier(.34, 1.56, .64, 1); }
    .sidebar .sidebar-link:hover i:first-child,
    .sidebar .dropdown-toggle:hover i:first-child { transform: scale(1.12); color: rgb(var(--accent-dark)); }
    .sidebar .sidebar-link.active { box-shadow: inset 0 0 0 1px rgba(var(--accent) / .14), 0 4px 14px rgba(var(--accent) / .10); }
    .sidebar .sidebar-link.active::before { box-shadow: 0 0 10px rgba(var(--accent) / .55); }
    .sidebar nav { scrollbar-width: thin; scrollbar-color: transparent transparent; }
    .sidebar nav:hover { scrollbar-color: rgba(148, 163, 184, .45) transparent; }
    .sidebar .nav-label { display: flex; align-items: center; gap: .6rem; }
    .sidebar .nav-label::after { content: ""; flex: 1; height: 1px; background: linear-gradient(90deg, rgba(148, 163, 184, .35), transparent); }

    /* ============ KOMPONEN MINIMIZE (hanya saat sidebar tampil permanen: html.sb-on) ============ */
    .sb-toggle, .sb-tip, .sb-fly { display: none; }

    /* [UBAH v3] lebar sidebar & margin konten ditetapkan langsung dari <head> (sebelum halaman tampil).
       Selector [class*="ml-64"] = wrapper konten utama (md:ml-64 / lg:ml-64) -> tidak perlu lagi JS .sb-content */
    html.sb-on .sidebar { width: var(--sb-w) !important; }
    html.sb-on [class*="ml-64"] { margin-left: var(--sb-w) !important; }

    /* [BARU v3] selama halaman baru dimuat (belum .sb-ready) semua transisi dimatikan -> tidak ada gerakan saat pindah menu */
    html:not(.sb-ready) .sidebar,
    html:not(.sb-ready) .sidebar *,                                         /* [BARU v3.3] chevron, kotak navy, warna menu, dropdown */
    html:not(.sb-ready) [class*="ml-64"] { transition: none !important; }

    /* [BARU v3.3] sebelum Alpine memberi class open/closed, dropdown jangan memakan tempat (hindari menu di bawahnya bergeser) */
    .sidebar .dropdown-content:not(.open):not(.closed) { max-height: 0; }
    /* animasi hanya aktif setelah .sb-ready (yaitu saat user klik tombol minimize / Ctrl+B) */
    html.sb-ready.sb-on .sidebar { transition: width .3s cubic-bezier(.4, 0, .2, 1), transform .3s cubic-bezier(.4, 0, .2, 1); }
    html.sb-ready.sb-on [class*="ml-64"] { transition: margin-left .3s cubic-bezier(.4, 0, .2, 1) !important; }

    html.sb-on .sb-toggle {
        display: flex; align-items: center; justify-content: center;
        position: fixed; top: 1.6rem; left: calc(var(--sb-w) - .9rem); z-index: 61;
        width: 1.8rem; height: 1.8rem; border-radius: 999px; padding: 0;
        background: #fff; color: rgb(100 116 139); cursor: pointer;
        border: 1px solid rgba(226, 232, 240, .9);
        box-shadow: 0 2px 8px rgba(15, 23, 42, .12);
        opacity: 0;                                                        /* [UBAH v3] muncul halus setelah .sb-ready, tidak "pop" */
    }
    html.sb-ready.sb-on .sb-toggle {                                       /* [BARU v3] transisi tombol hanya setelah .sb-ready */
        opacity: 1;
        transition: left .3s cubic-bezier(.4, 0, .2, 1), opacity .2s ease, transform .2s ease, color .2s ease, box-shadow .2s ease;
    }
    html.sb-on .sb-toggle:hover { color: rgb(var(--accent-dark)); box-shadow: 0 4px 14px rgba(var(--accent) / .30); transform: scale(1.1); }
    html.sb-on .sb-toggle:focus-visible { outline: 2px solid rgb(var(--accent)); outline-offset: 2px; }
    .sb-toggle svg { width: .95rem; height: .95rem; }
    html.sb-ready .sb-toggle svg { transition: transform .3s ease; }       /* [UBAH v3] */
    html.sb-collapsed .sb-toggle svg { transform: rotate(180deg); }

    /* tooltip label */
    html.sb-on .sb-tip {
        display: block; position: fixed; z-index: 100; pointer-events: none;
        padding: .4rem .7rem; border-radius: .5rem; white-space: nowrap;
        font: 500 .8rem/1.2 'Inter', sans-serif; color: #fff;
        background: #0f172a; box-shadow: 0 6px 18px rgba(15, 23, 42, .28);
        opacity: 0; transition: opacity .15s ease, transform .15s ease;
    }
    html.sb-on .sb-tip.show { opacity: 1; }

    /* panel mengambang (sub-menu grup + profil) */
    html.sb-on .sb-fly {
        display: block; position: fixed; z-index: 100; min-width: 13.5rem; max-width: 18rem; overflow-y: auto;
        border-radius: .9rem; visibility: hidden; pointer-events: none;
        opacity: 0; transform: translateX(-6px) scale(.98); transform-origin: left top;
        transition: opacity .16s ease, transform .16s ease, visibility 0s linear .16s;
    }
    html.sb-on .sb-fly.show { visibility: visible; pointer-events: auto; opacity: 1; transform: none; transition-delay: 0s; }
    .sb-fly-dark {
        padding: .35rem .35rem .45rem;
        background: linear-gradient(180deg, #1e293b, #0f172a);   /* slate-800 -> slate-900 (tetap; --navy di layout budget berupa hex) */
        box-shadow: 0 14px 34px rgba(15, 23, 42, .38);
    }
    .sb-fly-light {
        padding: .25rem 0; background: #fff;
        border: 1px solid rgba(226, 232, 240, .9); box-shadow: 0 14px 34px rgba(15, 23, 42, .20);
    }
    .sb-fly-title {
        padding: .6rem .75rem .35rem; font: 600 .68rem/1 'Inter', sans-serif;
        letter-spacing: .08em; text-transform: uppercase; color: rgba(255, 255, 255, .5);
    }
    .sb-fly .dropdown-child { margin: 0; padding-left: 0; list-style: none; }
    .sb-fly .dropdown-child .sidebar-link { padding: .55rem .85rem !important; border-radius: .6rem; }
    .sb-fly .sidebar-link:hover { transform: none; }
    .sb-fly .profile-menu { position: static; }

    /* ---- mode minimize (rail ikon) ---- */
    html.sb-on.sb-collapsed .sidebar > div:first-child { justify-content: center; padding-left: 0; padding-right: 0; }
    html.sb-on.sb-collapsed .sidebar h1,
    html.sb-on.sb-collapsed .sidebar h1 + p { display: none; }
    html.sb-on.sb-collapsed .sidebar .nav-label { visibility: hidden; height: .75rem; margin: 0; padding: 0; }
    html.sb-on.sb-collapsed .sidebar nav { padding-left: .75rem; padding-right: .75rem; overflow-x: hidden; }

    html.sb-on.sb-collapsed .sidebar nav span:not([class*="badge-count"]),
    html.sb-on.sb-collapsed .sidebar nav .fa-chevron-down { display: none !important; }
    html.sb-on.sb-collapsed .sidebar nav i[class*="mr-"] { margin-right: 0 !important; }
    html.sb-on.sb-collapsed .sidebar nav .sidebar-link,
    html.sb-on.sb-collapsed .sidebar nav .dropdown-toggle { justify-content: center; padding-left: 0; padding-right: 0; }
    html.sb-on.sb-collapsed .sidebar nav .dropdown-toggle > div { justify-content: center; }
    html.sb-on.sb-collapsed .sidebar nav .sidebar-link:hover { transform: none; }
    /* [UBAH v3.3] dulu: display:none (loncat saat dibuka lagi). Sekarang dilipat: tinggi 0 + transparan + tidak bisa diklik/fokus */
    html.sb-on.sb-collapsed .sidebar nav .dropdown-content { max-height: 0 !important; opacity: 0 !important; visibility: hidden; pointer-events: none; }
    /* menutup: visibility baru "hidden" setelah animasi lipat selesai; membuka: langsung visible lalu tinggi tumbuh halus */
    html.sb-ready.sb-on.sb-collapsed .sidebar nav .dropdown-content { transition: max-height .3s ease, opacity .25s ease, visibility 0s linear .3s; }
    html.sb-on.sb-collapsed .sidebar nav .sidebar-link.active { background: rgba(var(--accent) / .12); }
    html.sb-on.sb-collapsed .sidebar nav .sidebar-link.active::before { top: 10px; bottom: 10px; }
    html.sb-on.sb-collapsed .sidebar nav .dropdown-toggle.sb-fly-open { background-color: rgba(var(--accent) / .12); }

    /* badge jadi titik kecil di pojok ikon */
    html.sb-on.sb-collapsed .sidebar nav [class*="badge-count"] {
        position: absolute; top: .45rem; right: 1.05rem; margin: 0 !important;
        width: .6rem; height: .6rem; min-width: 0; padding: 0 !important;
        font-size: 0 !important; border-radius: 999px; box-shadow: 0 0 0 2px #fff;
    }

    /* profil: tinggal avatar */
    html.sb-on.sb-collapsed .sidebar > div:last-child { padding-left: .5rem; padding-right: .5rem; }
    html.sb-on.sb-collapsed .sidebar > div:last-child button { justify-content: center; }
    html.sb-on.sb-collapsed .sidebar > div:last-child h3,
    html.sb-on.sb-collapsed .sidebar > div:last-child p,
    html.sb-on.sb-collapsed .sidebar > div:last-child .fa-ellipsis-vertical { display: none; }
    html.sb-on.sb-collapsed .sidebar > div:last-child button > div:not(.avatar-ring) { display: none; }
    html.sb-on.sb-collapsed .sidebar .profile-menu { display: none !important; }

    @media (prefers-reduced-motion: reduce) {
        html .sidebar, html [class*="ml-64"], .sb-toggle, .sb-toggle svg, .sb-tip, .sb-fly { transition: none !important; }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var KEY = window.gaSbKey || 'gaSidebarV2';                         // [UBAH v3] key baru
        var BP = {{ (int) ($bp ?? 769) }};
        var root = document.documentElement;
        var sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        var mq = window.matchMedia('(min-width: ' + BP + 'px)');
        var hoverOK = window.matchMedia('(hover: hover)').matches;
        var isOn = function () { return mq.matches; };
        var isCollapsed = function () { return root.classList.contains('sb-collapsed'); };
        var zoom = function () { return parseFloat(getComputedStyle(root).zoom) || 1; };   // html { zoom: .8 } -> koordinat layar != px CSS

        // [HAPUS v3] blok .sb-content dihapus: margin konten sekarang diatur CSS di <head> (lihat html.sb-on [class*="ml-64"])

        // [BARU v3] aktifkan animasi hanya setelah 2 frame pertama selesai dirender
        requestAnimationFrame(function () {
            requestAnimationFrame(function () { root.classList.add('sb-ready'); });
        });

        /* ---------- tombol minimize ---------- */
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'sb-toggle';
        btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6"/></svg>';
        if (!sidebar.id) sidebar.id = 'sidebar';
        btn.setAttribute('aria-controls', sidebar.id);
        document.body.appendChild(btn);

        function sync() {
            var c = isCollapsed();
            btn.setAttribute('aria-expanded', c ? 'false' : 'true');
            btn.setAttribute('aria-label', c ? 'Buka sidebar' : 'Minimize sidebar');
            btn.title = (c ? 'Buka sidebar' : 'Minimize sidebar') + ' (Ctrl+B)';
        }
        // [UBAH v3.4] animasi dropdown berdasarkan tinggi asli (bukan max-height:600px layout)
        var ddTimer = null;
        function setCollapsed(v) {
            var dds = Array.prototype.slice.call(sidebar.querySelectorAll('.dropdown-content.open'));
            var animate = dds.length && isOn() && root.classList.contains('sb-ready');
            clearTimeout(ddTimer);
            if (animate) {
                dds.forEach(function (el) {
                    if (v) {                                   // minimize: titik awal = tinggi sekarang (bukan 600px)
                        el.style.transition = 'none';
                        el.style.maxHeight = el.offsetHeight + 'px';
                    } else {                                   // buka lagi: target = tinggi isi sebenarnya
                        el.style.maxHeight = el.scrollHeight + 'px';
                    }
                });
                void sidebar.offsetHeight;                     // paksa reflow agar nilai awal terbaca browser
                dds.forEach(function (el) { el.style.transition = ''; });
                void sidebar.offsetHeight;
            }
            root.classList.toggle('sb-collapsed', v);
            try { localStorage.setItem(KEY, v ? '1' : '0'); } catch (e) {}
            closeFly(); hideTip(); sync();
            if (animate) ddTimer = setTimeout(function () {    // bersihkan setelah animasi selesai
                dds.forEach(function (el) { el.style.maxHeight = ''; el.style.transition = ''; });
            }, 380);
        }
        btn.addEventListener('click', function () { setCollapsed(!isCollapsed()); });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeFly(); return; }
            if (!(e.ctrlKey || e.metaKey) || e.key.toLowerCase() !== 'b' || !isOn()) return;
            var t = e.target, tag = t && t.tagName;
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || (t && t.isContentEditable)) return;
            e.preventDefault(); setCollapsed(!isCollapsed());
        });
        sync();

        // lewat/di bawah breakpoint -> kembali ke perilaku drawer bawaan
        var onChange = function (e) { root.classList.toggle('sb-on', e.matches); closeFly(); hideTip(); };
        if (mq.addEventListener) mq.addEventListener('change', onChange); else mq.addListener(onChange);

        /* ---------- tooltip label (link biasa) ---------- */
        var tip = document.createElement('div');
        tip.className = 'sb-tip';
        tip.setAttribute('role', 'tooltip');
        document.body.appendChild(tip);
        var tipHost = null, tipTitle = null;

        function hideTip() {
            tip.classList.remove('show');
            if (tipHost && tipTitle !== null) tipHost.setAttribute('title', tipTitle);
            tipHost = tipTitle = null;
        }
        sidebar.addEventListener('mouseover', function (e) {
            if (!isOn() || !isCollapsed()) return;
            var el = e.target.closest('nav a');
            if (!el || el === tipHost) { if (!el) hideTip(); return; }
            hideTip();
            var label = (el.getAttribute('data-sb-tip') || el.getAttribute('title') || el.textContent || '').replace(/\s+/g, ' ').trim();   // [UBAH v3.2] data-sb-tip: teks tooltip khusus saat minimize (dipakai hsrm-app)
            if (!label) return;
            tipHost = el; tipTitle = el.getAttribute('title');
            if (tipTitle !== null) el.removeAttribute('title');     // cegah tooltip bawaan browser dobel
            tip.textContent = label;
            var z = zoom(), r = el.getBoundingClientRect();
            tip.style.left = (r.right / z + 12) + 'px';
            tip.style.top = ((r.top + r.height / 2) / z) + 'px';
            tip.style.transform = 'translate(-4px,-50%)';
            requestAnimationFrame(function () { tip.style.transform = 'translate(0,-50%)'; tip.classList.add('show'); });
        });
        sidebar.addEventListener('mouseleave', hideTip);
        sidebar.addEventListener('scroll', hideTip, true);
        window.addEventListener('resize', hideTip);

        /* ---------- panel mengambang: sub-menu grup & profil saat minimize ---------- */
        var fly = document.createElement('div');
        fly.className = 'sb-fly';
        fly.setAttribute('role', 'menu');
        document.body.appendChild(fly);
        var flyHost = null, openT = null, closeT = null;

        function cleanClone(node) {
            // clone tidak boleh membawa atribut Alpine / id ganda
            node.querySelectorAll('form').forEach(function (f) { f.remove(); });
            var all = Array.prototype.slice.call(node.querySelectorAll('*'));
            all.unshift(node);                                   // termasuk elemen akar clone
            all.forEach(function (el) {
                Array.prototype.slice.call(el.attributes).forEach(function (a) {
                    if (a.name.indexOf('x-') === 0 || a.name.charAt(0) === ':' || a.name.charAt(0) === '@' || a.name === 'id') el.removeAttribute(a.name);
                });
            });
            return node;
        }
        function labelOf(host) {
            var s = host.querySelector('span');
            return ((s && s.textContent) || host.getAttribute('title') || '').replace(/\s+/g, ' ').trim();
        }
        function place(kind) {
            var z = zoom();
            var sr = sidebar.getBoundingClientRect(), hr = flyHost.getBoundingClientRect();
            var vh = window.innerHeight / z;
            fly.style.maxHeight = (vh - 16) + 'px';
            var h = fly.getBoundingClientRect().height / z;
            var top = kind === 'profile' ? hr.bottom / z - h : hr.top / z;
            fly.style.left = (sr.right / z + 8) + 'px';
            fly.style.top = Math.max(8, Math.min(top, vh - h - 8)) + 'px';
        }
        function openFly(host, kind) {
            clearTimeout(openT); clearTimeout(closeT);
            if (!isOn() || !isCollapsed()) return;
            var body;
            if (kind === 'group') {
                var ul = host.closest('.nav-group') && host.closest('.nav-group').querySelector('.dropdown-child');
                if (!ul) return;
                fly.className = 'sb-fly sb-fly-dark';
                fly.innerHTML = '<div class="sb-fly-title"></div>';
                fly.firstChild.textContent = labelOf(host);
                fly.appendChild(cleanClone(ul.cloneNode(true)));
            } else {
                var menu = sidebar.querySelector('.profile-menu');
                if (!menu) return;
                fly.className = 'sb-fly sb-fly-light';
                fly.innerHTML = '';
                var c = cleanClone(menu.cloneNode(true));
                c.removeAttribute('style'); c.removeAttribute('class'); c.className = 'profile-menu';
                fly.appendChild(c);
            }
            hideTip();
            if (flyHost) flyHost.classList.remove('sb-fly-open');
            flyHost = host; host.classList.add('sb-fly-open');
            place(kind);
            fly.classList.add('show');
        }
        function closeFly() {
            clearTimeout(openT); clearTimeout(closeT);
            fly.classList.remove('show');
            if (flyHost) flyHost.classList.remove('sb-fly-open');
            flyHost = null;
        }
        function scheduleClose() { clearTimeout(closeT); closeT = setTimeout(closeFly, 220); }
        var kindOf = function (h) { return h.classList.contains('dropdown-toggle') ? 'group' : 'profile'; };

        // klik/tap: tampilkan panel (dan cegah Alpine ikut membuka/menutup grup bawaan)
        sidebar.addEventListener('click', function (e) {
            if (!isOn() || !isCollapsed()) return;
            var host = e.target.closest('.dropdown-toggle') || e.target.closest('.sidebar > div:last-child button');
            if (!host) return;
            e.stopPropagation();
            if (flyHost === host) closeFly(); else openFly(host, kindOf(host));
        }, true);

        // hover (mouse): buka sub-menu grup dengan jeda singkat
        sidebar.addEventListener('mouseover', function (e) {
            if (!isOn() || !isCollapsed() || !hoverOK) return;
            var t = e.target.closest('nav .dropdown-toggle');
            clearTimeout(openT);
            if (t) { clearTimeout(closeT); if (flyHost !== t) openT = setTimeout(function () { openFly(t, 'group'); }, 90); }
            else if (flyHost) scheduleClose();
        });
        sidebar.addEventListener('mouseleave', function () { clearTimeout(openT); if (flyHost) scheduleClose(); });
        fly.addEventListener('mouseenter', function () { clearTimeout(closeT); });
        fly.addEventListener('mouseleave', scheduleClose);

        document.addEventListener('click', function (e) {
            if (!flyHost) return;
            if (fly.contains(e.target) || e.target.closest('.sidebar .dropdown-toggle') || e.target.closest('.sidebar > div:last-child button')) return;
            closeFly();
        });
        sidebar.addEventListener('scroll', closeFly, true);
        window.addEventListener('resize', closeFly);
    });
</script>