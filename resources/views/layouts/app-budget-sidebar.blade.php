<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Budgeting') - GA Portal</title>
    <link rel="shortcut icon" href="{{ asset('KPN123.png') }}" type="image/x-icon">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <style>
        :root{
            --accent: 59 130 246;      /* blue-500 */
            --accent-dark: 37 99 235;  /* blue-600 */
            --ink: 30 41 59;           /* slate-800 */
            --side-navy: 30 41 59;     /* slate-800 - panel dropdown */
            --side-navy-deep: 15 23 42;/* slate-900 */

            /* Variabel untuk isi halaman budget (dipakai view & inline style) */
            --bg:#f7f8fa; --card:#fff; --mu:#64748b; --ln:#e2e8f0;
            --ac:#2563eb; --ac-t:#fff;
            --navy:#1f3a5f; --teal:#2a9d8f; --amber:#e9a23b;
            --ok:#15803d; --wn:#b45309; --er:#dc2626; --bar:#e8edf3;
        }
        html { zoom: 0.8; }
        body { font-family: 'Inter', sans-serif; background-color: #f7f8fa; color: rgb(var(--ink)); }
        [x-cloak]{ display:none !important; }

        .soft-border { border-color: rgba(226,232,240,0.7) !important; }
        .soft-border-bottom { border-bottom-color: rgba(226,232,240,0.7) !important; }
        .soft-border-top { border-top-color: rgba(226,232,240,0.7) !important; }
        .soft-shadow { box-shadow: 0 1px 2px rgba(15,23,42,0.04), 0 4px 12px rgba(15,23,42,0.04); }
        .soft-shadow-sidebar { box-shadow: 1px 0 0 rgba(15,23,42,0.04), 6px 0 24px rgba(15,23,42,0.03); }

        /* Sidebar */
        .sidebar { transition: transform .3s cubic-bezier(.4,0,.2,1); border-right: 1px solid rgba(226,232,240,0.7); }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); z-index: 60; width: 16rem !important; } .sidebar.active { transform: translateX(0); } }

        .brand-badge{
            background: linear-gradient(135deg, rgba(var(--accent)/0.12), rgba(var(--accent)/0.04));
            border: 1px solid rgba(var(--accent)/0.15);
        }

        .nav-label{ letter-spacing:.08em; white-space:nowrap; }

        .sidebar-link{
            position: relative;
            min-height: 2.75rem;
            transition: background-color .2s ease, color .2s ease, transform .2s ease;
        }
        .sidebar-link:hover{ background-color: rgba(248,250,252,0.9); transform: translateX(2px); }
        .sidebar-link i{ transition: color .2s ease, transform .2s ease; }
        .sidebar-link.active{
            background: linear-gradient(90deg, rgba(var(--accent)/0.10), rgba(var(--accent)/0.02));
            color: rgb(var(--accent-dark));
            font-weight: 600;
        }
        .sidebar-link.active::before{
            content:"";
            position:absolute; left:0; top:8px; bottom:8px; width:3px; border-radius:4px;
            background: rgb(var(--accent-dark));
        }
        .sidebar-link.active i{ color: rgb(var(--accent-dark)); }

        /* Dropdown group - panel navy gelap saat terbuka */
        .nav-group{ position: relative; border-radius: .75rem; overflow: hidden; transition: background-color .3s ease, box-shadow .3s ease; }
        .nav-group.open{
            background: linear-gradient(180deg, rgb(var(--side-navy)), rgb(var(--side-navy-deep)));
            box-shadow: 0 8px 20px rgba(15,23,42,.25);
        }
        .dropdown-toggle{ cursor:pointer; user-select:none; min-height:2.75rem; transition: background-color .2s ease, color .2s ease; }
        .dropdown-toggle:hover{ background-color: rgba(248,250,252,0.9); }
        .dropdown-toggle i, .dropdown-toggle span{ transition: color .2s ease; }
        .nav-group.open .dropdown-toggle{ background-color: transparent; }
        .nav-group.open .dropdown-toggle:hover{ background-color: rgba(255,255,255,.06); }
        .nav-group.open .dropdown-toggle i,
        .nav-group.open .dropdown-toggle span{ color: #fff; }
        .dropdown-toggle .fa-chevron-down{ transition: transform .25s ease; color: rgb(148 163 184); }
        .nav-group.open .fa-chevron-down{ transform: rotate(180deg); color: #fff; }

        .dropdown-content{ overflow:hidden; transition: max-height .3s ease, opacity .25s ease; opacity: 0; max-height: 0; }
        .dropdown-content.open{ max-height: 600px; opacity: 1; }
        .dropdown-content.closed{ max-height: 0 !important; opacity: 0; }

        .dropdown-child .sidebar-link{
            font-size:.875rem; padding:.55rem 1rem .55rem 2.6rem;
            color: rgba(255,255,255,.65);
        }
        .dropdown-child .sidebar-link:hover{ background-color: rgba(255,255,255,.06); color:#fff; transform:none; }
        .dropdown-child .sidebar-link i{ color: rgba(255,255,255,.45); }
        .dropdown-child .sidebar-link.active{ background: rgba(255,255,255,.1); color: #fff; font-weight: 600; }
        .dropdown-child .sidebar-link.active::before{ background: rgb(96 165 250); }
        .dropdown-child .sidebar-link.active i{ color: rgb(96 165 250); }

        .overlay{ display:none; position:fixed; inset:0; background: rgba(15,23,42,.25); backdrop-filter: blur(1px); z-index:50; }
        .overlay.active{ display:block; }

        .avatar-ring{
            background: linear-gradient(135deg, rgba(var(--accent)/0.14), rgba(var(--accent)/0.05));
            border: 1px solid rgba(var(--accent)/0.18);
        }

        /* Tombol mobile mengambang (pengganti header) */
        .mobile-toggle{
            position: fixed; top: 1rem; left: 1rem; z-index: 55;
            width: 2.75rem; height: 2.75rem; border-radius: .75rem;
            background: #fff; display:flex; align-items:center; justify-content:center;
            box-shadow: 0 2px 8px rgba(15,23,42,.12);
        }
        @media (min-width: 769px){ .mobile-toggle{ display:none; } }

        /* Profile & logout popup */
        .profile-menu{
            position:absolute; left: .75rem; right: .75rem; bottom: calc(100% + .5rem);
            transition: opacity .18s ease, transform .18s ease;
        }

        ::-webkit-scrollbar{ width:6px; }
        ::-webkit-scrollbar-thumb{ background: rgba(148,163,184,.4); border-radius: 999px; }

        /* ===== Isi halaman budget (di-scope ke .bud agar tidak bentrok dengan reset Tailwind) ===== */
        .bud{font-size:14px;line-height:1.5}
        .bud a{color:var(--ac);text-decoration:none}
        .bud h2{font-size:20px;font-weight:700;margin:0}
        .bud h3{font-size:15px;font-weight:600;margin:0 0 10px}
        .bud .top{display:flex;flex-wrap:wrap;gap:10px;align-items:center;justify-content:space-between;margin-bottom:16px}
        .bud .row{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
        .bud .card{background:var(--card);border:1px solid rgba(226,232,240,.7);border-radius:10px;padding:16px;margin-bottom:16px;box-shadow:0 1px 2px rgba(15,23,42,.04),0 4px 12px rgba(15,23,42,.04)}
        .bud .kpi{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px;margin-bottom:16px}
        .bud .kpi .card{margin:0}
        .bud .kpi b{display:block;font-size:20px;margin-top:2px}
        .bud .lbl{color:var(--mu);font-size:12.5px}
        .bud input,.bud select,.bud textarea{font:inherit;color:rgb(var(--ink));background:var(--card);border:1px solid var(--ln);border-radius:6px;padding:6px 9px;max-width:100%}
        .bud input:focus,.bud select:focus,.bud textarea:focus,.bud button:focus-visible,.bud summary:focus-visible,.bud a:focus-visible{outline:2px solid var(--ac);outline-offset:1px}
        .bud textarea{width:100%}
        .bud .btn{font:inherit;border:1px solid var(--ln);background:var(--card);color:rgb(var(--ink));border-radius:6px;padding:6px 12px;cursor:pointer;display:inline-block}
        .bud .btn:hover{border-color:var(--ac)}
        .bud .btn.pr{background:var(--ac);border-color:var(--ac);color:var(--ac-t);font-weight:600}
        .bud .btn.dg{color:var(--er)}
        .bud .btn.sm{padding:3px 9px;font-size:13px}
        .bud table{border-collapse:collapse;width:100%}
        .bud th,.bud td{text-align:left;padding:7px 9px;border-bottom:1px solid var(--ln);vertical-align:middle}
        .bud th{font-weight:600;color:var(--mu);font-size:12.5px;white-space:nowrap}
        .bud td.n,.bud th.n{text-align:right;white-space:nowrap}
        .bud .scroll{overflow-x:auto}
        /* Tabel budget bulanan: tinggi dibatasi, geser di dalam kotak, header dan kolom Item dibekukan */
        .bud .tbl-beku{overflow:auto;max-height:calc(100vh - 220px);min-height:340px;overscroll-behavior:contain}
        .bud .tbl-beku table{border-collapse:separate;border-spacing:0}
        .bud .tbl-beku thead th{position:sticky;top:0;z-index:3;background:var(--card);box-shadow:0 1px 0 var(--ln)}
        .bud .tbl-beku .beku{position:sticky;left:0;z-index:2;background:var(--card);box-shadow:1px 0 0 var(--ln)}
        .bud .tbl-beku thead th.beku{z-index:4}
        .bud .tbl-beku tfoot th{position:sticky;bottom:0;z-index:3;background:var(--card);box-shadow:0 -1px 0 var(--ln)}
        .bud .tbl-beku tfoot th.beku{z-index:4}

        /* Halaman Kategori: kedua kolom punya TINGGI TETAP yang sama (tidak bergantung jumlah item,
           dan sama sejak akses pertama sebelum subkategori dipilih).
           --jml-baris = jumlah baris item yang terlihat; ubah 12 menjadi 10 bila ingin tepat 10 item */
        .bud .grid2.grid-kat{--baris:37px;--jml-baris:12;--tinggi:calc(var(--baris) * var(--jml-baris) + 190px);align-items:start}
        .bud .grid-kat .kat-nav,.bud .grid-kat .kat-det{height:var(--tinggi);min-width:0}
        .bud .grid-kat .kat-nav{
            overflow:auto;
            overscroll-behavior:contain;
            padding-right:4px;
        }
        .bud .grid-kat .kat-det{display:flex;flex-direction:column}
        .bud .grid-kat .kat-det>*{flex:1;min-height:0;margin-bottom:0}
        .bud .grid-kat .kat-card{display:flex;flex-direction:column}
        .bud .grid-kat .kat-card .tbl-beku{flex:1;min-height:0;max-height:none}
        .bud .grid-kat .kat-card tbody tr{height:var(--baris)}
        .bud .grid-kat .kat-card tbody td{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:260px}
        @media (max-width:860px){
            .bud .grid-kat .kat-nav,.bud .grid-kat .kat-det{height:auto}
            .bud .grid-kat .kat-nav{max-height:50vh}
            .bud .grid-kat .kat-card .tbl-beku{flex:none;max-height:70vh}
        }

        .bud .tbl-beku .grp-label{position:sticky;left:10px;display:inline-block}
        .bud .bar{height:8px;border-radius:4px;background:var(--bar);overflow:hidden;min-width:90px}
        .bud .bar i{display:block;height:100%;background:var(--teal)}
        .bud .bar i.wn{background:var(--amber)}
        .bud .bar i.er{background:var(--er)}
        .bud .bd{display:inline-block;padding:1px 8px;border-radius:10px;font-size:12px;border:1px solid currentColor}
        .bud .b-ok{color:var(--ok)}.bud .b-wn{color:var(--wn)}.bud .b-er{color:var(--er)}.bud .b-mu{color:var(--mu)}
        .bud .msg{scroll-margin-top:80px;padding:9px 13px;border-radius:8px;margin-bottom:14px;border:1px solid;background:var(--card)}
        .bud .msg.ok{color:var(--ok)}.bud .msg.er{color:var(--er)}.bud .msg.wn{color:var(--wn)}
        .bud details.kat{border:1px solid rgba(226,232,240,.7);border-radius:10px;background:var(--card);margin-bottom:10px}
        .bud details.kat>summary{cursor:pointer;padding:10px 14px;list-style:none}
        .bud details.kat>summary::-webkit-details-marker{display:none}
        .bud details.kat .isi{padding:0 14px 10px}
        .bud .grid2{display:grid;grid-template-columns:290px 1fr;gap:16px;align-items:start}
        .bud .pg{display:flex;gap:10px;align-items:center}
        .bud .pg .nm{flex:1;min-width:0}.bud .pg .bw{width:230px}.bud .pg .ang{width:260px;text-align:right}
        .bud dialog{border:1px solid var(--ln);border-radius:12px;background:var(--card);color:rgb(var(--ink));padding:0;width:min(720px,94vw);max-height:92vh}
        .bud dialog::backdrop{background:rgba(15,23,42,.45)}
        .bud .dh{display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid var(--ln)}
        .bud .db{padding:16px;overflow:auto;max-height:calc(92vh - 60px)}
        .bud .f{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px}
        .bud .f label{display:block}.bud .f label span{display:block;color:var(--mu);font-size:12.5px;margin-bottom:2px}
        .bud .f input,.bud .f select{width:100%}
        .bud .li{display:block;width:100%;text-align:left;padding:6px 9px;border:0;border-bottom:1px solid var(--ln);background:var(--card);color:rgb(var(--ink));cursor:pointer;font:inherit}
        .bud .li:hover{background:var(--bar)}
        .bud .chart{display:flex;gap:6px;align-items:flex-end;height:200px;padding-top:10px}
        .bud .chart .m{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;height:100%;justify-content:flex-end}
        .bud .chart .bb{display:flex;gap:2px;align-items:flex-end;width:100%;height:calc(100% - 20px)}
        .bud .chart .bb i{flex:1;border-radius:2px 2px 0 0;min-height:1px}
        .bud .chart .m small{font-size:11px;color:var(--mu)}
        .bud .sw{display:inline-block;width:10px;height:10px;border-radius:2px;margin-right:4px}

        /* Baris filter: kolom dan lebar seragam */
        .bud .fb{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px 12px;align-items:end;padding:12px 14px}
        .bud .fb>label{display:block;min-width:0;margin:0}
        .bud .fb>label>.lbl{display:block;margin-bottom:3px}
        .bud .fb input,.bud .fb select{width:100%;box-sizing:border-box;height:36px}
        .bud .fb .fb-act{display:flex;gap:8px;align-items:center;height:36px}
        /* Dropdown yang bisa diketik (select[data-cari]) */
        .bud .cs{position:relative}
        .bud .cs>select{position:absolute;left:0;top:0;width:100%;height:100%;opacity:0;pointer-events:none}
        .bud .cs-in{width:100%;box-sizing:border-box;padding-right:28px;text-overflow:ellipsis;background:var(--card) url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'><path d='M2 4l4 4 4-4' fill='none' stroke='%2364748b' stroke-width='1.6'/></svg>") no-repeat right 10px center}
        .bud .cs-in:disabled{opacity:.6;cursor:not-allowed}
        .bud .cs-ls{display:none;position:absolute;z-index:80;left:0;right:0;top:calc(100% + 2px);max-height:240px;overflow:auto;background:var(--card);border:1px solid var(--ln);border-radius:6px;box-shadow:0 6px 18px rgba(15,23,42,.14);margin:0;padding:3px;list-style:none}
        .bud .cs.open .cs-ls{display:block}
        .bud .cs-ls li{padding:6px 9px;border-radius:4px;cursor:pointer;font-size:13.5px}
        .bud .cs-ls li.on{background:var(--bar)}
        .bud .cs-ls li.sel{font-weight:600}
        .bud .cs-ls li.kosong{color:var(--mu);cursor:default}

        .bud .tag{display:inline-block;font-size:11px;font-weight:600;padding:1px 8px;border-radius:4px;margin-right:8px;vertical-align:1px}
        .bud details.dept{margin-bottom:18px}
        .bud details.dept>summary{cursor:pointer;list-style:none;padding:14px 16px;background:#0f172a;color:#fff;border-radius:10px}
        .bud details.dept>summary::-webkit-details-marker{display:none}
        .bud details.dept>summary .nm>b{font-size:16px}
        .bud details.dept>summary .lbl{color:rgba(255,255,255,.75)}
        .bud details.dept>summary>.pg::before{color:#fff}
        .bud details.dept>summary .bar{background:rgba(255,255,255,.25)}
        .bud details.dept>summary .bar i{background:#fff}
        .bud details.dept>summary .bar i.wn{background:#fde68a}
        .bud details.dept>summary .bar i.er{background:#fecaca}
        .bud details.dept>summary .bd{color:#fff;background:rgba(255,255,255,.15);border-color:rgba(255,255,255,.5)}
        .bud details.dept>summary .bd.b-wn{color:#fde68a}
        .bud details.dept>summary .bd.b-er{color:#fecaca}
        .bud .tag.t-dept{background:rgba(255,255,255,.2);color:#fff}
        .bud details.dept>.isi-dept{margin:10px 0 0 10px;padding:4px 0 4px 14px;border-left:2px solid #cbd5e1}
        /* Kategori: latar terang lembut + garis aksen berwarna di kiri (tidak lagi blok warna pekat) */
        .bud details.kat.lv1{--kc:#4a6485;--kb:#f1f4f8;border:1px solid #e2e8f0;border-left:5px solid var(--kc);overflow:hidden}
        .bud details.kat.lv1.c1{--kc:#4f8a83;--kb:#f0f6f5}
        .bud details.kat.lv1.c2{--kc:#7b6fa6;--kb:#f4f2f9}
        .bud details.kat.lv1.c3{--kc:#a8814a;--kb:#f8f5ef}
        .bud details.kat.lv1.c4{--kc:#a8566b;--kb:#f8f1f3}
        .bud details.kat.lv1>summary{padding:14px 16px;background:var(--kb);color:#1e293b}
        .bud details.kat.lv1>summary:hover{filter:brightness(.98)}
        .bud details.kat.lv1>summary .nm>b{font-size:15px}
        .bud details.kat.lv1>summary .bar i{background:var(--kc)}
        .bud details.kat.lv1>summary .bar i.wn{background:var(--amber)}
        .bud details.kat.lv1>summary .bar i.er{background:var(--er)}
        .bud details.kat.lv1>summary .tag.t-kat,.bud details.kat.lv1>summary .tag.t-kategori{background:#fff;color:var(--kc);border:1px solid var(--kc)}
        .bud details.kat.lv1 .isi{padding:4px 14px 14px}
        .bud .tag.t-sub{background:color-mix(in srgb,var(--kc,#1f3a5f) 16%,#fff);color:var(--kc,#1f3a5f)}
        .bud details.sub{margin:10px 0 0 18px;border:1px solid color-mix(in srgb,var(--kc,#1f3a5f) 30%,#fff);border-left:4px solid var(--kc,#1f3a5f);border-radius:0 8px 8px 0;background:#fff}
        .bud details.sub>summary{cursor:pointer;padding:9px 12px;list-style:none;background:color-mix(in srgb,var(--kc,#1f3a5f) 9%,#fff)}
        .bud details.sub>summary .bar i:not(.wn):not(.er){background:var(--kc,#1f3a5f)}
        .bud details.sub>summary::-webkit-details-marker{display:none}
        .bud details.sub .itm{margin:0 12px 0 28px;border-top:1px dashed var(--ln);padding:8px 0}
        .bud details.sub .itm .nm>b{font-weight:500}
        .bud details.sub .isi-sub{padding-bottom:6px}
        .bud details>summary>.pg::before{content:"\25B8";color:var(--mu);font-size:13px;width:14px;flex-shrink:0;transition:transform .15s}
        .bud details[open]>summary>.pg::before{transform:rotate(90deg)}
        @media (max-width:860px){.bud .grid2{grid-template-columns:1fr}.bud .pg{flex-wrap:wrap}.bud .pg .bw,.bud .pg .ang{width:100%;text-align:left}}
        @media (prefers-reduced-motion:reduce){*{transition:none!important;animation:none!important}}
    </style>

    @yield('head')
    @stack('styles')
</head>
<body>
    @php
        $peranBudget = $budgetPeran ?? null;
        $namaUser = auth()->user()->username ?? auth()->user()->name ?? 'User';
        $grupBudget = request()->routeIs('budgeting.kategori*', 'budgeting.budget.*');
        $grupCatat  = request()->routeIs('budgeting.tagihan.*');
        $grupAtur   = request()->routeIs('budgeting.master*', 'budgeting.akses*');
    @endphp

    <div id="overlay" class="overlay"></div>

    <button id="sidebar-toggle" class="mobile-toggle text-gray-600">
        <i class="fas fa-bars text-lg"></i>
    </button>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="sidebar w-64 bg-white fixed h-full soft-shadow-sidebar flex flex-col overflow-hidden">

            <!-- Logo -->
            <div class="p-5 shrink-0 soft-border-bottom border-b flex items-center gap-2">
                <div class="flex items-center space-x-3 min-w-0">
                    <div class="brand-badge w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
                        <img src="{{ asset('KPN123.png') }}" alt="Logo" class="w-6 h-6">
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-lg font-bold text-gray-800 leading-tight truncate">GA Portal</h1>
                        <p class="text-xs text-gray-500 tracking-wide">Budgeting</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 min-h-0 overflow-y-auto p-3">
                <p class="mt-2 mb-1 px-3 text-[11px] font-semibold text-gray-400 uppercase nav-label">Navigation</p>
                <ul class="space-y-1" x-data="{ g: '{{ $grupBudget ? 'pembudgetan' : ($grupCatat ? 'pencatatan' : ($grupAtur ? 'pengaturan' : '')) }}' }">

                    <!-- Portal -->
                    <li>
                        <a href="{{ route('dashboard') }}" title="GA Portal"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700">
                            <i class="fas fa-home w-5 mr-3 text-gray-400"></i>
                            <span>GA Portal</span>
                        </a>
                    </li>

                    <!-- Dashboard -->
                    <li>
                        <a href="{{ route('budgeting.dashboard') }}" title="Dashboard Budget"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('budgeting.dashboard') ? 'active' : '' }}">
                            <i class="fas fa-chart-pie w-5 mr-3 text-gray-400"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <!-- Grup: Pembudgetan -->
                    <li class="nav-group" :class="{ open: g === 'pembudgetan' }">
                        <div @click="g = g === 'pembudgetan' ? '' : 'pembudgetan'" title="Pembudgetan"
                             class="dropdown-toggle flex items-center justify-between p-3 rounded-lg text-gray-700">
                            <div class="flex items-center">
                                <i class="fas fa-calculator w-5 mr-3 text-gray-400"></i>
                                <span class="font-medium">Pembudgetan</span>
                            </div>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </div>
                        <div class="dropdown-content" :class="g === 'pembudgetan' ? 'open' : 'closed'">
                            <ul class="dropdown-child space-y-1 pb-2">
                                <li>
                                    <a href="{{ route('budgeting.kategori') }}"
                                       class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('budgeting.kategori*') ? 'active' : '' }}">
                                        <i class="fas fa-tags w-4 mr-3"></i>
                                        <span>Kategori Budget</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('budgeting.budget.index') }}"
                                       class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('budgeting.budget.*') ? 'active' : '' }}">
                                        <i class="fas fa-wallet w-4 mr-3"></i>
                                        <span>Rencana Budget</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Grup: Pencatatan -->
                    <li class="nav-group" :class="{ open: g === 'pencatatan' }">
                        <div @click="g = g === 'pencatatan' ? '' : 'pencatatan'" title="Pencatatan"
                             class="dropdown-toggle flex items-center justify-between p-3 rounded-lg text-gray-700">
                            <div class="flex items-center">
                                <i class="fas fa-pen-to-square w-5 mr-3 text-gray-400"></i>
                                <span class="font-medium">Pencatatan</span>
                            </div>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </div>
                        <div class="dropdown-content" :class="g === 'pencatatan' ? 'open' : 'closed'">
                            <ul class="dropdown-child space-y-1 pb-2">
                                <li>
                                    <a href="{{ route('budgeting.tagihan.index') }}"
                                       class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('budgeting.tagihan.*') ? 'active' : '' }}">
                                        <i class="fas fa-file-invoice-dollar w-4 mr-3"></i>
                                        <span>Realisasi Budget</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Grup: Pengaturan -->
                    <li class="nav-group" :class="{ open: g === 'pengaturan' }">
                        <div @click="g = g === 'pengaturan' ? '' : 'pengaturan'" title="Pengaturan"
                             class="dropdown-toggle flex items-center justify-between p-3 rounded-lg text-gray-700">
                            <div class="flex items-center">
                                <i class="fas fa-gear w-5 mr-3 text-gray-400"></i>
                                <span class="font-medium">Pengaturan</span>
                            </div>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </div>
                        <div class="dropdown-content" :class="g === 'pengaturan' ? 'open' : 'closed'">
                            <ul class="dropdown-child space-y-1 pb-2">
                                @if($peranBudget === 'admin')
                                <li>
                                    <a href="{{ route('budgeting.master') }}"
                                       class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('budgeting.master*') ? 'active' : '' }}">
                                        <i class="fas fa-database w-4 mr-3"></i>
                                        <span>Master Data</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('budgeting.akses') }}"
                                       class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('budgeting.akses*') ? 'active' : '' }}">
                                        <i class="fas fa-user-shield w-4 mr-3"></i>
                                        <span>Hak Akses</span>
                                    </a>
                                </li>
                                @else
                                <li>
                                    <a href="{{ route('budgeting.master') }}"
                                       class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('budgeting.master*') ? 'active' : '' }}">
                                        <i class="fas fa-folder-plus w-4 mr-3"></i>
                                        <span>Tambah Kategori</span>
                                    </a>
                                </li>
                                @if($peranBudget === 'dept_head')
                                <li>
                                    <a href="{{ route('budgeting.akses') }}"
                                       class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('budgeting.akses*') ? 'active' : '' }}">
                                        <i class="fas fa-user-tie w-4 mr-3"></i>
                                        <span>PIC Saya</span>
                                    </a>
                                </li>
                                @endif
                                @endif
                            </ul>
                        </div>
                    </li>

                </ul>
            </nav>

            <!-- User Profile + Logout -->
            <div class="relative shrink-0 p-3 soft-border-top border-t bg-white"
                 x-data="{ openProfile: false }" @click.outside="openProfile = false">

                <div x-show="openProfile" x-transition x-cloak
                     class="profile-menu bg-white soft-shadow rounded-lg soft-border border py-2 z-50">
                    <div class="px-4 py-2 text-sm text-gray-500 soft-border-bottom border-b">
                        <p class="font-medium text-gray-700">{{ $namaUser }}</p>
                        <p class="text-xs">{{ \App\Support\budget_s\BudgetPeran::labelUser(auth()->user()) }}</p>
                    </div>
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form-profile').submit();"
                       class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                        <i class="fas fa-sign-out-alt mr-3 text-gray-500"></i>Keluar
                    </a>
                    <form id="logout-form-profile" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
                </div>

                <button @click="openProfile = !openProfile" class="w-full flex items-center space-x-3 p-1 rounded-lg hover:bg-gray-50 transition">
                    <div class="avatar-ring w-10 h-10 rounded-full flex items-center justify-center shrink-0">
                        <span class="font-semibold text-blue-600 text-sm">
                            {{ strtoupper(substr($namaUser ?: 'AD', 0, 2)) }}
                        </span>
                    </div>
                    <div class="min-w-0 text-left flex-1">
                        <h3 class="font-medium text-gray-800 truncate">{{ $namaUser }}</h3>
                        <p class="text-xs text-gray-500 truncate">{{ \App\Support\budget_s\BudgetPeran::labelUser(auth()->user()) }}</p>
                    </div>
                    <i class="fas fa-ellipsis-vertical text-gray-400 text-sm"></i>
                </button>
            </div>
        </aside>

        <!-- Main Content (header dihapus) -->
        <div class="flex-1 min-w-0 ml-0 md:ml-64 transition-all duration-300">
            <main class="p-6 pt-20 md:pt-6">
                @hasSection('breadcrumb')
                    <div class="text-sm text-gray-500 mb-4">@yield('breadcrumb')</div>
                @endif

                <div class="bud">
                    @if(session('ok'))<div class="msg ok" id="pesan" role="status">{{ session('ok') }}</div>@endif
                    @if($errors->any())
                        <div class="msg er" id="pesan" role="alert">
                            @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                        </div>
                    @endif
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        const sidebarEl = document.querySelector('.sidebar');
        const overlayEl = document.getElementById('overlay');

        document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
            sidebarEl.classList.toggle('active');
            overlayEl.classList.toggle('active');
            document.body.style.overflow = sidebarEl.classList.contains('active') ? 'hidden' : '';
        });

        overlayEl.addEventListener('click', () => {
            sidebarEl.classList.remove('active');
            overlayEl.classList.remove('active');
            document.body.style.overflow = '';
        });

        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                sidebarEl.classList.remove('active');
                overlayEl.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 768) {
                    sidebarEl.classList.remove('active');
                    overlayEl.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
    </script>

    <!-- Helper modul budget -->
    <script>
        const CSRF = document.querySelector('meta[name=csrf-token]').content;
        const URL_GRAFIK = @json(route('budgeting.grafik'));
        const URL_USER = @json(route('budgeting.lookup.user'));
        function buka(id){document.getElementById(id).showModal()}
        function tutup(id){document.getElementById(id).close()}
        function rp(n){return 'Rp '+Math.round(Number(n)||0).toLocaleString('id-ID')}
        function esc(s){const d=document.createElement('div');d.textContent=s??'';return d.innerHTML}

        /* Grafik budget vs realisasi (tanpa library): params = {tahun, item_id?, bu?, kategori_id?, pie?}
           Batang Budget dan Realisasi per bulan + garis kumulatif (realisasi vs budget sampai bulan itu, %).
           Satu popup per bulan berisi budget, realisasi, dan kumulatif sekaligus. params.pie = tambah pie Realisasi dan Sisa budget
           (untuk Admin: juga pie tiap Dept Head). */
        let gfBulan = [];
        function ringkasRp(n){
          const a=Math.abs(n), f=(x,s)=>(Math.round(x*10)/10).toLocaleString('id-ID')+s;
          return a>=1e9?f(n/1e9,' M'):a>=1e6?f(n/1e6,' jt'):a>=1e3?f(n/1e3,' rb'):String(Math.round(n));
        }
        function sumbuRapi(max){
          const raw=(max/4)||1, p=Math.pow(10,Math.floor(Math.log10(raw))), f=raw/p;
          const step=(f<=1?1:f<=2?2:f<=2.5?2.5:f<=5?5:10)*p;
          return {step, top:Math.ceil(max/step)*step};
        }
        function warnaPersen(p){ return p>100?'var(--er)':p>=80?'var(--wn)':'var(--ok)'; }
        function grafikBatang(d){
          const W=760,H=310,L=58,R=46,T=24,B=30, pw=W-L-R, ph=H-T-B, n=d.labels.length;
          const {step,top}=sumbuRapi(Math.max(1,...d.budget,...d.realisasi));
          let cb=0,cr=0,last=-1; d.realisasi.forEach((v,i)=>{ if(v>0) last=i; });
          gfBulan=d.labels.map((l,i)=>{
            cb+=d.budget[i]; cr+=d.realisasi[i];
            return {label:l,budget:d.budget[i],real:d.realisasi[i],kumB:cb,kumR:cr,pct:(i<=last&&cb>0)?cr/cb*100:null};
          });
          const mp=Math.max(100,Math.ceil(Math.max(0,...gfBulan.map(b=>b.pct===null?0:b.pct))/25)*25);
          const yv=v=>T+ph-v/top*ph, yp=p=>T+ph-p/mp*ph, slot=pw/n, bw=Math.min(22,slot*0.34);
          let s=`<div id="gf-wrap" style="position:relative"><svg viewBox="0 0 ${W} ${H}" style="width:100%;height:auto;display:block" role="img" aria-label="Grafik per bulan">`;
          for(let k=0;k<=Math.round(top/step);k++){
            const v=k*step, y=yv(v);
            s+=`<line x1="${L}" x2="${W-R}" y1="${y}" y2="${y}" style="stroke:var(--ln)"/><text x="${L-6}" y="${y+4}" text-anchor="end" style="font-size:11px;fill:var(--mu)">${ringkasRp(v)}</text>`;
          }
          s+=`<text x="4" y="11" style="font-size:10.5px;font-weight:600;fill:var(--mu)">Rupiah per bulan</text>`
            +`<text x="${W-4}" y="11" text-anchor="end" style="font-size:10.5px;font-weight:600;fill:var(--mu)">% kumulatif</text>`;
          const langkahP=mp<=100?25:mp<=200?50:100;
          for(let p=0;p<=mp;p+=langkahP) s+=`<text x="${W-R+6}" y="${yp(p)+4}" style="font-size:11px;fill:var(--mu)">${p}%</text>`;
          // garis acuan 100% = sesuai rencana
          s+=`<line x1="${L}" x2="${W-R}" y1="${yp(100)}" y2="${yp(100)}" style="stroke:var(--mu);stroke-width:1;stroke-dasharray:5 4"/>`
            +`<text x="${W-R-4}" y="${yp(100)-4}" text-anchor="end" style="font-size:10px;fill:var(--mu)">100% = sesuai rencana</text>`;
          d.labels.forEach((l,i)=>{
            const cx=L+slot*i+slot/2, b=d.budget[i], r=d.realisasi[i], over=r>b&&r>0;
            s+=`<rect class="gf-bg" data-i="${i}" x="${L+slot*i}" y="${T}" width="${slot}" height="${ph}" style="fill:var(--navy);opacity:0"/>`
              +`<rect x="${cx-bw-1}" y="${yv(b)}" width="${bw}" height="${T+ph-yv(b)}" rx="2" style="fill:#8aa0b8"/>`
              +`<rect x="${cx+1}" y="${yv(r)}" width="${bw}" height="${T+ph-yv(r)}" rx="2" style="fill:${over?'var(--er)':'#2563eb'}"/>`
              +`<text x="${cx}" y="${H-10}" text-anchor="middle" style="font-size:11px;fill:var(--mu)">${esc(l)}</text>`;
          });
          const titik=gfBulan.map((b,i)=>b.pct===null?null:[L+slot*i+slot/2,yp(b.pct),b.pct]).filter(Boolean);
          if(titik.length){
            s+=`<polyline class="gf-garis" points="${titik.map(t=>t[0]+','+t[1]).join(' ')}" style="fill:none;stroke:var(--navy);stroke-width:2"/>`;
            const u=titik[titik.length-1];
            s+=`<text class="gf-garis" x="${Math.min(u[0]+8,W-R-2)}" y="${u[1]-11}" text-anchor="end" style="font-size:12px;font-weight:700;fill:var(--navy)">Garis kumulatif</text>`;
            titik.forEach(t=>{ s+=`<circle class="gf-garis" cx="${t[0]}" cy="${t[1]}" r="4" style="fill:${warnaPersen(t[2])};stroke:var(--card);stroke-width:1.5"/>`; });
          }
          // area sentuh per bulan: satu popup berisi ketiganya
          d.labels.forEach((l,i)=>{ s+=`<rect class="gf-hit" data-i="${i}" x="${L+slot*i}" y="${T}" width="${slot}" height="${ph}" style="fill:transparent;cursor:default"/>`; });
          s+='</svg><div id="gf-tip" style="position:absolute;display:none;pointer-events:none;z-index:5;min-width:210px;padding:8px 10px;background:var(--card);border:1px solid var(--ln);border-radius:8px;box-shadow:0 6px 18px rgba(15,23,42,.18);font-size:12.5px;line-height:1.5"></div></div>';
          s+='<div class="row" style="margin-top:6px;justify-content:space-between;gap:10px;align-items:flex-start"><p class="lbl" style="margin:0">'
            +'<span class="sw" style="background:#8aa0b8"></span>Budget per bulan <span class="sw" style="background:#2563eb;margin-left:10px"></span>Realisasi per bulan (merah bila melebihi budget bulan itu) '
            +'<span class="sw" style="background:var(--navy);margin-left:10px"></span><b style="color:var(--navy);font-size:13px">Garis kumulatif</b> = pemakaian sejak awal tahun (%), titik '
            +'<span class="sw" style="background:var(--ok)"></span>&lt;80% <span class="sw" style="background:var(--wn)"></span>80-100% <span class="sw" style="background:var(--er)"></span>&gt;100%</p>'
            +'<button type="button" class="btn sm" id="gf-tombol-garis" onclick="gfGaris()">Sembunyikan garis kumulatif</button></div>'
            +'<p class="lbl" style="margin-top:6px"><b style="color:var(--navy);font-size:13px">Cara baca garis kumulatif:</b> batang mengikuti angka rupiah di kiri (per bulan). <b>Garis kumulatif</b> mengikuti persen di kanan: total realisasi dari Januari sampai bulan itu dibagi total budget dari Januari sampai bulan itu. '
            +'Di bawah garis putus-putus 100% berarti pemakaian masih di bawah rencana.</p>';
          return s;
        }
        function tipBulan(i){
          const b=gfBulan[i]; if(!b) return '';
          const lebih=b.real>b.budget&&b.real>0, selisih=b.kumB-b.kumR, awal=gfBulan[0].label;
          const pBulan=b.budget>0?Math.round(b.real/b.budget*1000)/10:null;
          let h=`<b>${esc(b.label)}</b>`
            +`<div class="lbl" style="margin-top:4px;font-weight:600">Bulan ${esc(b.label)} saja</div>`
            +`<div class="row" style="justify-content:space-between;gap:12px"><span><span class="sw" style="background:#8aa0b8"></span>Budget</span><b>${rp(b.budget)}</b></div>`
            +`<div class="row" style="justify-content:space-between;gap:12px"><span><span class="sw" style="background:${lebih?'var(--er)':'#2563eb'}"></span>Realisasi</span><b>${rp(b.real)}</b></div>`
            +(pBulan===null?'':`<div class="lbl">Terpakai ${pBulan}% dari budget bulan ini</div>`);
          h+=`<div style="border-top:1px solid var(--ln);margin-top:6px;padding-top:6px"><div class="lbl" style="font-weight:700;color:var(--navy)">Garis kumulatif: ${esc(awal)} sampai ${esc(b.label)}</div>`
            +`<div class="row" style="justify-content:space-between;gap:12px"><span>Realisasi</span><b>${rp(b.kumR)}</b></div>`
            +`<div class="row" style="justify-content:space-between;gap:12px"><span>Budget</span><b>${rp(b.kumB)}</b></div>`
            +(b.pct===null?'<div class="lbl">Belum ada realisasi.</div>'
              :`<div style="margin-top:2px"><b style="color:${warnaPersen(b.pct)}">Terpakai ${Math.round(b.pct*10)/10}%</b> <span class="lbl">| ${selisih>=0?'sisa':'lebih'} ${rp(Math.abs(selisih))}</span></div>`)
            +'</div>';
          return h;
        }
        function gfGaris(){
          const w=document.getElementById('gf-wrap'), t=document.getElementById('gf-tombol-garis'); if(!w||!t) return;
          const sembunyi=t.dataset.s!=='1'; t.dataset.s=sembunyi?'1':'0';
          w.querySelectorAll('.gf-garis').forEach(x=>x.style.display=sembunyi?'none':'');
          t.textContent=sembunyi?'Tampilkan garis kumulatif':'Sembunyikan garis kumulatif';
        }
        function pasangTip(box){
          const wrap=box.querySelector('#gf-wrap'), tip=box.querySelector('#gf-tip'); if(!wrap||!tip) return;
          const bg=i=>wrap.querySelector(`.gf-bg[data-i="${i}"]`);
          const tampil=(e,i)=>{
            wrap.querySelectorAll('.gf-bg').forEach(x=>x.style.opacity=0); const k=bg(i); if(k) k.style.opacity=.08;
            tip.innerHTML=tipBulan(i); tip.style.display='block';
            const w=wrap.getBoundingClientRect(), x=(e.clientX||0)-w.left, y=(e.clientY||0)-w.top;
            tip.style.left=Math.max(0,Math.min(x+14,w.width-tip.offsetWidth-4))+'px';
            tip.style.top=Math.max(0,Math.min(y+14,w.height-tip.offsetHeight-4))+'px';
          };
          const sembunyi=()=>{ tip.style.display='none'; wrap.querySelectorAll('.gf-bg').forEach(x=>x.style.opacity=0); };
          wrap.querySelectorAll('.gf-hit').forEach(el=>{
            const i=+el.dataset.i;
            el.addEventListener('mouseenter',e=>tampil(e,i)); el.addEventListener('mousemove',e=>tampil(e,i));
            el.addEventListener('mouseleave',sembunyi);
            el.addEventListener('touchstart',e=>{ const t=e.touches[0]; tampil({clientX:t.clientX,clientY:t.clientY},i); },{passive:true});
          });
        }
        // Pie Realisasi vs Sisa budget; ukuran = lebar piksel
        function pieSvg(tb,tr,ukuran){
          const over=tr>tb, p1=Math.min(1,tr/tb), pr=Math.round(tr/tb*1000)/10, ps=Math.round(Math.max(0,tb-tr)/tb*1000)/10;
          const cx=110, cy=110, r=100, warnaR=over?'var(--er)':'#2563eb', warnaS='#cbd5e1';
          const pt=a=>[cx+r*Math.cos(a-Math.PI/2), cy+r*Math.sin(a-Math.PI/2)];
          const irisan=(a0,a1,w)=>{ const [x0,y0]=pt(a0),[x1,y1]=pt(a1); return `<path d="M${cx},${cy} L${x0},${y0} A${r},${r} 0 ${a1-a0>Math.PI?1:0} 1 ${x1},${y1} Z" style="fill:${w};stroke:var(--card);stroke-width:2"/>`; };
          const teks=(a0,a1,t,w)=>{ const m=(a0+a1)/2, x=cx+r*0.62*Math.cos(m-Math.PI/2), y=cy+r*0.62*Math.sin(m-Math.PI/2); return `<text x="${x}" y="${y+5}" text-anchor="middle" style="font-size:17px;font-weight:700;fill:${w}">${t}</text>`; };
          const a1=p1*2*Math.PI;
          let s=`<svg viewBox="0 0 220 220" style="width:${ukuran}px;max-width:100%;height:auto" role="img" aria-label="Realisasi dan sisa budget">`;
          if(p1>=1) s+=`<circle cx="${cx}" cy="${cy}" r="${r}" style="fill:${warnaR}"/>`+teks(0,2*Math.PI,pr+'%','#fff');
          else if(p1<=0) s+=`<circle cx="${cx}" cy="${cy}" r="${r}" style="fill:${warnaS}"/>`+teks(0,2*Math.PI,ps+'%','#334155');
          else {
            s+=irisan(0,a1,warnaR)+irisan(a1,2*Math.PI,warnaS);
            if(p1>=0.06) s+=teks(0,a1,pr+'%','#fff');
            if(1-p1>=0.06) s+=teks(a1,2*Math.PI,ps+'%','#334155');
          }
          return s+'</svg>';
        }
        function legendaPie(tb,tr){
          const over=tr>tb, sisa=Math.max(0,tb-tr), pr=Math.round(tr/tb*1000)/10, ps=Math.round(sisa/tb*1000)/10, wr=over?'var(--er)':'#2563eb';
          return `<div class="row" style="gap:8px;margin-bottom:6px"><span class="sw" style="background:${wr}"></span><b>Realisasi</b> <span>${rp(tr)}</span> <b>${pr}%</b></div>`
            +`<div class="row" style="gap:8px;margin-bottom:6px"><span class="sw" style="background:#cbd5e1"></span><b>Sisa budget</b> <span>${rp(sisa)}</span> <b>${ps}%</b></div>`
            +`<div class="lbl">Total budget ${rp(tb)}</div>`
            +(over?`<div style="margin-top:4px;color:var(--er);font-weight:600">Melebihi budget ${rp(tr-tb)} (${Math.round((tr/tb-1)*1000)/10}%)</div>`:'');
        }
        function grafikPie(tb,tr){
          if(!(tb>0)) return '<p class="lbl">Pie tidak bisa dibuat karena budget masih 0.</p>';
          return '<div class="row" style="gap:20px;align-items:center;flex-wrap:wrap">'+pieSvg(tb,tr,220)+'<div style="min-width:220px;flex:1">'+legendaPie(tb,tr)+'</div></div>';
        }
        // Admin: satu pie per Dept Head
        function grafikPieDept(list){
          let h='<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px">';
          list.forEach(x=>{
            h+='<div style="border:1px solid var(--ln);border-radius:10px;padding:10px">'
              +`<b>${esc(x.nama)}</b> <span class="lbl">(${x.jumlah} item)</span><div style="text-align:center;margin:8px 0">`
              +(x.budget>0?pieSvg(x.budget,x.realisasi,150):'<p class="lbl">Budget masih 0.</p>')+'</div>'
              +(x.budget>0?legendaPie(x.budget,x.realisasi):'')+'</div>';
          });
          return h+'</div>';
        }
        async function grafik(params){
          const box=document.getElementById('grafik-isi'), jd=document.getElementById('grafik-judul');
          jd.textContent='Memuat...'; box.innerHTML=''; buka('modal-grafik');
          const r=await fetch(URL_GRAFIK+'?'+new URLSearchParams(params),{headers:{Accept:'application/json'}});
          if(!r.ok){jd.textContent='Grafik gagal dimuat';box.innerHTML='<p class="lbl">Kode '+r.status+(r.status===403?': Anda tidak berhak melihat data ini.':'. Muat ulang halaman lalu coba lagi.')+'</p>';return}
          const d=await r.json(); jd.textContent=d.judul;
          const tb=d.budget.reduce((a,b)=>a+b,0), tr=d.realisasi.reduce((a,b)=>a+b,0);
          let h='<b>Grafik per bulan</b>'+grafikBatang(d);
          if(params.pie) h+='<div style="margin-top:14px"><b>Realisasi dan sisa budget</b><div style="margin-top:8px">'+grafikPie(tb,tr)+'</div></div>';
          if(params.pie && d.per_dept && d.per_dept.length) h+='<div style="margin-top:14px"><b>Per Dept Head</b><div style="margin-top:8px">'+grafikPieDept(d.per_dept)+'</div></div>';
          h+='<div class="scroll" style="margin-top:14px"><table><tr><th>Bulan</th><th class="n">Budget</th><th class="n">Realisasi</th></tr>';
          d.labels.forEach((l,i)=>{h+=`<tr><td>${esc(l)}</td><td class="n">${rp(d.budget[i])}</td><td class="n">${rp(d.realisasi[i])}</td></tr>`});
          h+=`<tr><th>Total (${d.jumlah_item} item)</th><th class="n">${rp(tb)}</th><th class="n">${rp(tr)}</th></tr>`;
          box.innerHTML=h+'</table></div>';
          pasangTip(box);
        }

        /* Cari user: input teks -> daftar hasil -> isi hidden id + label (nama tetap tampil di kotak setelah dipilih) */
        function pasangCariUser(inp,list,hid,lab){
          const kosong='Belum ada user dipilih.';
          let t; inp.addEventListener('input',()=>{
            // mengetik ulang = pilihan sebelumnya dibatalkan
            hid.value=''; lab.textContent=kosong; lab.style.color=''; lab.style.fontWeight='';
            clearTimeout(t); const q=inp.value.trim();
            if(q.length<2){list.innerHTML='';return}
            t=setTimeout(async()=>{
              const r=await fetch(URL_USER+'?q='+encodeURIComponent(q),{headers:{Accept:'application/json'}});
              const d=await r.json(); list.innerHTML='';
              if(!d.length){list.innerHTML='<div class="lbl" style="padding:6px">Tidak ada user yang cocok.</div>';return}
              d.forEach(u=>{
                const b=document.createElement('button'); b.type='button'; b.className='li';
                b.textContent=u.name+' - '+u.email+(u.employee_no?' ('+u.employee_no+')':'')+(u.peran?' [sudah: '+u.peran+']':'');
                b.onclick=()=>{
                  hid.value=u.id; inp.value=u.name; list.innerHTML='';
                  lab.textContent='Dipilih: '+u.name+' ('+u.email+')'+(u.peran?' - peran saat ini: '+u.peran:'');
                  lab.style.color='var(--ok)'; lab.style.fontWeight='600';
                };
                list.appendChild(b);
              });
            },250);
          });
        }
    </script>

    <!-- Dropdown yang bisa diketik: select[data-cari] dipakai apa adanya (nilai, required, onchange), tampilannya diganti kotak ketik + daftar -->
    <script>
        function csBuat(sel){
          if(sel._cs) return sel._cs;
          const w=document.createElement('div'); w.className='cs';
          sel.parentNode.insertBefore(w,sel);
          const inp=document.createElement('input'); inp.type='text'; inp.className='cs-in'; inp.autocomplete='off';
          inp.setAttribute('role','combobox'); inp.setAttribute('aria-expanded','false');
          const ul=document.createElement('ul'); ul.className='cs-ls'; ul.setAttribute('role','listbox');
          w.append(inp,ul,sel); sel.tabIndex=-1;
          let li=[], aktif=-1;
          const teks=()=>{const o=sel.options[sel.selectedIndex]; return o&&o.value!==''?o.text:''};
          const sinkron=()=>{
            const kosong=[...sel.options].find(o=>o.value==='');
            inp.value=teks(); inp.placeholder=kosong?kosong.text:'Ketik untuk mencari';
            inp.disabled=sel.disabled;
          };
          const soroti=n=>{
            li.forEach(x=>x.classList.remove('on'));
            aktif=li.length?Math.max(0,Math.min(n,li.length-1)):-1;
            if(aktif>=0){li[aktif].classList.add('on'); li[aktif].scrollIntoView({block:'nearest'})}
          };
          const render=q=>{
            ul.innerHTML=''; li=[];
            const k=q.trim().toLowerCase();
            [...sel.options].forEach(o=>{
              if(o.value===''){ if(k) return; }
              else if(k && !o.text.toLowerCase().includes(k)) return;
              const e=document.createElement('li'); e.textContent=o.text; e.setAttribute('role','option');
              if(o.value===sel.value && o.value!=='') e.classList.add('sel');
              e.addEventListener('mousedown',ev=>{ev.preventDefault(); pilih(o.value)});
              ul.appendChild(e); li.push(e);
            });
            if(!li.length){const e=document.createElement('li'); e.className='kosong'; e.textContent='Tidak ada yang cocok'; ul.appendChild(e)}
            soroti(k?0:-1);
          };
          const buka=()=>{ if(inp.disabled) return; w.classList.add('open'); inp.setAttribute('aria-expanded','true'); render(''); inp.select(); };
          const tutup=()=>{ w.classList.remove('open'); inp.setAttribute('aria-expanded','false'); sinkron(); };
          function pilih(v){
            const beda=sel.value!==v; sel.value=v; tutup();
            if(beda) sel.dispatchEvent(new Event('change',{bubbles:true}));
          }
          inp.addEventListener('focus',buka);
          inp.addEventListener('click',()=>{ if(!w.classList.contains('open')) buka(); });
          inp.addEventListener('input',()=>{ w.classList.add('open'); render(inp.value); });
          inp.addEventListener('blur',tutup);
          inp.addEventListener('keydown',e=>{
            if(e.key==='ArrowDown'){e.preventDefault(); if(!w.classList.contains('open')) buka(); else soroti(aktif+1)}
            else if(e.key==='ArrowUp'){e.preventDefault(); soroti(aktif-1)}
            else if(e.key==='Enter' && w.classList.contains('open')){
              e.preventDefault();
              const dipilih=li[aktif>=0?aktif:(li.length===1?0:-1)];
              if(dipilih) dipilih.dispatchEvent(new MouseEvent('mousedown',{bubbles:true,cancelable:true}));
            }
            else if(e.key==='Escape'){tutup(); inp.blur()}
          });
          sel.addEventListener('change',sinkron);
          if(sel.form) sel.form.addEventListener('reset',()=>setTimeout(sinkron,0));
          new MutationObserver(sinkron).observe(sel,{childList:true,subtree:true,attributes:true,attributeFilter:['disabled']});
          sel._cs={sinkron}; sinkron();
          return sel._cs;
        }
        /* Panggil setelah mengubah nilai select lewat kode (select.value = ...) supaya kotak ketiknya ikut berubah */
        function csSync(sel){ if(sel && sel._cs) sel._cs.sinkron(); }
        document.querySelectorAll('select[data-cari]').forEach(csBuat);
    </script>

    @stack('scripts')
</body>
</html>