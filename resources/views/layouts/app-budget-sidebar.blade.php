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
        .bud .bar{height:8px;border-radius:4px;background:var(--bar);overflow:hidden;min-width:90px}
        .bud .bar i{display:block;height:100%;background:var(--teal)}
        .bud .bar i.wn{background:var(--amber)}
        .bud .bar i.er{background:var(--er)}
        .bud .bd{display:inline-block;padding:1px 8px;border-radius:10px;font-size:12px;border:1px solid currentColor}
        .bud .b-ok{color:var(--ok)}.bud .b-wn{color:var(--wn)}.bud .b-er{color:var(--er)}.bud .b-mu{color:var(--mu)}
        .bud .msg{padding:9px 13px;border-radius:8px;margin-bottom:14px;border:1px solid;background:var(--card)}
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

        .bud .tag{display:inline-block;font-size:11px;font-weight:600;padding:1px 8px;border-radius:4px;margin-right:8px;vertical-align:1px}
        .bud details.kat.lv1{--kc:#1f3a5f;border:1px solid var(--kc);border-left-width:6px;overflow:hidden}
        .bud details.kat.lv1.c1{--kc:#0f766e}
        .bud details.kat.lv1.c2{--kc:#6d28d9}
        .bud details.kat.lv1.c3{--kc:#b45309}
        .bud details.kat.lv1.c4{--kc:#be123c}
        .bud details.kat.lv1>summary{padding:14px 16px;background:var(--kc);color:#fff}
        .bud details.kat.lv1>summary .nm>b{font-size:15px}
        .bud details.kat.lv1>summary .lbl{color:rgba(255,255,255,.82)}
        .bud details.kat.lv1>summary>.pg::before{color:#fff}
        .bud details.kat.lv1>summary .bar{background:rgba(255,255,255,.28)}
        .bud details.kat.lv1>summary .bar i{background:#fff}
        .bud details.kat.lv1>summary .bar i.wn{background:#fde68a}
        .bud details.kat.lv1>summary .bar i.er{background:#fecaca}
        .bud details.kat.lv1>summary .bd{color:#fff;background:rgba(255,255,255,.18);border-color:rgba(255,255,255,.55)}
        .bud details.kat.lv1>summary .bd.b-wn{color:#fde68a}
        .bud details.kat.lv1>summary .bd.b-er{color:#fecaca}
        .bud details.kat.lv1>summary .tag.t-kat{background:rgba(255,255,255,.22);color:#fff}
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
                            <span>Dashboard Budget</span>
                        </a>
                    </li>

                    <!-- Grup: Pembudgetan -->
                    <li>
                        <button type="button" @click="g = g === 'pembudgetan' ? '' : 'pembudgetan'" title="Pembudgetan"
                                class="sidebar-link w-full flex items-center p-3 rounded-lg text-gray-700 {{ $grupBudget ? 'font-semibold text-blue-600' : '' }}">
                            <i class="fas fa-calculator w-5 mr-3 {{ $grupBudget ? 'text-blue-600' : 'text-gray-400' }}"></i>
                            <span>Pembudgetan</span>
                            <i class="fas fa-chevron-down ml-auto text-[10px] text-gray-400 transition-transform duration-200" :class="g === 'pembudgetan' ? 'rotate-180' : ''"></i>
                        </button>
                        <ul x-show="g === 'pembudgetan'" x-transition x-cloak class="mt-1 ml-5 pl-3 border-l soft-border space-y-1">
                            <li>
                                <a href="{{ route('budgeting.kategori') }}"
                                   class="sidebar-link flex items-center px-3 py-2 rounded-lg text-sm text-gray-600 {{ request()->routeIs('budgeting.kategori*') ? 'active' : '' }}" style="min-height:2.5rem">
                                    <span>Kategori Budget</span>
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('budgeting.budget.index') }}"
                                   class="sidebar-link flex items-center px-3 py-2 rounded-lg text-sm text-gray-600 {{ request()->routeIs('budgeting.budget.*') ? 'active' : '' }}" style="min-height:2.5rem">
                                    <span>Rencana Budget</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Grup: Pencatatan -->
                    <li>
                        <button type="button" @click="g = g === 'pencatatan' ? '' : 'pencatatan'" title="Pencatatan"
                                class="sidebar-link w-full flex items-center p-3 rounded-lg text-gray-700 {{ $grupCatat ? 'font-semibold text-blue-600' : '' }}">
                            <i class="fas fa-pen-to-square w-5 mr-3 {{ $grupCatat ? 'text-blue-600' : 'text-gray-400' }}"></i>
                            <span>Pencatatan</span>
                            <i class="fas fa-chevron-down ml-auto text-[10px] text-gray-400 transition-transform duration-200" :class="g === 'pencatatan' ? 'rotate-180' : ''"></i>
                        </button>
                        <ul x-show="g === 'pencatatan'" x-transition x-cloak class="mt-1 ml-5 pl-3 border-l soft-border space-y-1">
                            <li>
                                <a href="{{ route('budgeting.tagihan.index') }}"
                                   class="sidebar-link flex items-center px-3 py-2 rounded-lg text-sm text-gray-600 {{ request()->routeIs('budgeting.tagihan.*') ? 'active' : '' }}" style="min-height:2.5rem">
                                    <span>Realisasi Budget</span>
                                </a>
                            </li>
                        </ul>
                    </li>

                    <!-- Grup: Pengaturan -->
                    <li>
                        <button type="button" @click="g = g === 'pengaturan' ? '' : 'pengaturan'" title="Pengaturan"
                                class="sidebar-link w-full flex items-center p-3 rounded-lg text-gray-700 {{ $grupAtur ? 'font-semibold text-blue-600' : '' }}">
                            <i class="fas fa-gear w-5 mr-3 {{ $grupAtur ? 'text-blue-600' : 'text-gray-400' }}"></i>
                            <span>Pengaturan</span>
                            <i class="fas fa-chevron-down ml-auto text-[10px] text-gray-400 transition-transform duration-200" :class="g === 'pengaturan' ? 'rotate-180' : ''"></i>
                        </button>
                        <ul x-show="g === 'pengaturan'" x-transition x-cloak class="mt-1 ml-5 pl-3 border-l soft-border space-y-1">
                            @if($peranBudget === 'admin')
                                <li>
                                    <a href="{{ route('budgeting.master') }}"
                                       class="sidebar-link flex items-center px-3 py-2 rounded-lg text-sm text-gray-600 {{ request()->routeIs('budgeting.master*') ? 'active' : '' }}" style="min-height:2.5rem">
                                        <span>Master Data</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('budgeting.akses') }}"
                                       class="sidebar-link flex items-center px-3 py-2 rounded-lg text-sm text-gray-600 {{ request()->routeIs('budgeting.akses*') ? 'active' : '' }}" style="min-height:2.5rem">
                                        <span>Hak Akses</span>
                                    </a>
                                </li>
                            @else
                                <li>
                                    <a href="{{ route('budgeting.master') }}"
                                       class="sidebar-link flex items-center px-3 py-2 rounded-lg text-sm text-gray-600 {{ request()->routeIs('budgeting.master*') ? 'active' : '' }}" style="min-height:2.5rem">
                                        <span>Tambah Kategori</span>
                                    </a>
                                </li>
                                @if($peranBudget === 'dept_head')
                                    <li>
                                        <a href="{{ route('budgeting.akses') }}"
                                           class="sidebar-link flex items-center px-3 py-2 rounded-lg text-sm text-gray-600 {{ request()->routeIs('budgeting.akses*') ? 'active' : '' }}" style="min-height:2.5rem">
                                            <span>PIC Saya</span>
                                        </a>
                                    </li>
                                @endif
                            @endif
                        </ul>
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
                        <p class="text-xs">{{ \App\Support\budget_s\BudgetPeran::label($peranBudget) }}</p>
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
                        <p class="text-xs text-gray-500 truncate">{{ \App\Support\budget_s\BudgetPeran::label($peranBudget) }}</p>
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
                    @if(session('ok'))<div class="msg ok" role="status">{{ session('ok') }}</div>@endif
                    @if($errors->any())
                        <div class="msg er" role="alert">
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

        /* Grafik budget vs realisasi (tanpa library): params = {tahun, item_id?, bu?, kategori_id?} */
        async function grafik(params){
          const box=document.getElementById('grafik-isi'), jd=document.getElementById('grafik-judul');
          jd.textContent='Memuat...'; box.innerHTML=''; buka('modal-grafik');
          const r=await fetch(URL_GRAFIK+'?'+new URLSearchParams(params),{headers:{Accept:'application/json'}});
          if(!r.ok){jd.textContent='Grafik gagal dimuat';return}
          const d=await r.json(); jd.textContent=d.judul;
          const max=Math.max(1,...d.budget,...d.realisasi);
          let h='<div class="chart">';
          d.labels.forEach((l,i)=>{
            const b=d.budget[i]/max*100, s=d.realisasi[i]/max*100, over=d.realisasi[i]>d.budget[i]&&d.realisasi[i]>0;
            h+=`<div class="m" title="${esc(l)}: budget ${rp(d.budget[i])}, realisasi ${rp(d.realisasi[i])}"><div class="bb"><i style="height:${b}%;background:var(--navy)"></i><i style="height:${s}%;background:${over?'var(--er)':'var(--teal)'}"></i></div><small>${esc(l)}</small></div>`;
          });
          h+='</div><p class="lbl"><span class="sw" style="background:var(--navy)"></span>Budget <span class="sw" style="background:var(--teal);margin-left:12px"></span>Realisasi (merah bila melebihi budget bulan itu)</p>';
          h+='<div class="scroll"><table><tr><th>Bulan</th><th class="n">Budget</th><th class="n">Realisasi</th></tr>';
          d.labels.forEach((l,i)=>{h+=`<tr><td>${esc(l)}</td><td class="n">${rp(d.budget[i])}</td><td class="n">${rp(d.realisasi[i])}</td></tr>`});
          box.innerHTML=h+'</table></div>';
        }

        /* Cari user: input teks -> daftar hasil -> isi hidden id + label */
        function pasangCariUser(inp,list,hid,lab){
          let t; inp.addEventListener('input',()=>{
            clearTimeout(t); const q=inp.value.trim();
            if(q.length<2){list.innerHTML='';return}
            t=setTimeout(async()=>{
              const r=await fetch(URL_USER+'?q='+encodeURIComponent(q),{headers:{Accept:'application/json'}});
              const d=await r.json(); list.innerHTML='';
              if(!d.length){list.innerHTML='<div class="lbl" style="padding:6px">Tidak ada user yang cocok.</div>';return}
              d.forEach(u=>{
                const b=document.createElement('button'); b.type='button'; b.className='li';
                b.textContent=u.name+' - '+u.email+(u.employee_no?' ('+u.employee_no+')':'')+(u.peran?' [sudah: '+u.peran+']':'');
                b.onclick=()=>{hid.value=u.id;lab.textContent='Dipilih: '+u.name+' ('+u.email+')';list.innerHTML='';inp.value=''};
                list.appendChild(b);
              });
            },250);
          });
        }
    </script>

    @stack('scripts')
</body>
</html>