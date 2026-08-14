<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>GA Portal</title>
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
            --navy: 30 41 59;          /* slate-800 - panel dropdown */
            --navy-deep: 15 23 42;     /* slate-900 */
        }
        html { zoom: 0.8; }
        body { font-family: 'Inter', sans-serif; background-color: #f7f8fa; color: rgb(var(--ink)); }

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
        .dropdown-toggle{ min-height: 2.75rem; }
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

        .badge-count{
            background: linear-gradient(135deg,#ef4444,#dc2626);
            box-shadow: 0 2px 6px rgba(239,68,68,.35);
        }

        /* Dropdown group - gaya panel navy seperti DRMS */
        .nav-group{ position: relative; border-radius: .75rem; overflow: hidden; transition: background-color .3s ease, box-shadow .3s ease; }
        .nav-group.open{
            background: linear-gradient(180deg, rgb(var(--navy)), rgb(var(--navy-deep)));
            box-shadow: 0 8px 20px rgba(15,23,42,.25);
        }

        .dropdown-toggle{ cursor:pointer; user-select:none; transition: background-color .2s ease, color .2s ease; }
        .dropdown-toggle:hover{ background-color: rgba(248,250,252,0.9); }
        .dropdown-toggle i, .dropdown-toggle span{ transition: color .2s ease; }
        .nav-group.open .dropdown-toggle{ background-color: transparent; }
        .nav-group.open .dropdown-toggle:hover{ background-color: rgba(255,255,255,.06); }
        .nav-group.open .dropdown-toggle i,
        .nav-group.open .dropdown-toggle span{ color: #fff; }
        .dropdown-toggle .fa-chevron-down{ transition: transform .25s ease; color: rgb(148 163 184); }
        .nav-group.open .fa-chevron-down{ transform: rotate(180deg); color: #fff; }

        .dropdown-content{ overflow:hidden; transition: max-height .3s ease, opacity .25s ease; opacity: 0; }
        .dropdown-content.open{ max-height: 600px; opacity: 1; }
        .dropdown-content.closed{ max-height: 0 !important; opacity: 0; }

        .dropdown-child .sidebar-link{
            font-size:.875rem; padding:.55rem 1rem .55rem 2.6rem;
            color: rgba(255,255,255,.65);
        }
        .dropdown-child .sidebar-link:hover{ background-color: rgba(255,255,255,.06); color:#fff; transform:none; }
        .dropdown-child .sidebar-link i{ color: rgba(255,255,255,.45); }
        .dropdown-child .sidebar-link.active{
            background: rgba(255,255,255,.1);
            color: #fff; font-weight: 600;
        }
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
    </style>
    @stack('styles')
</head>
<body>
    <div id="overlay" class="overlay"></div>

    <button id="sidebar-toggle" class="mobile-toggle text-gray-600">
        <i class="fas fa-bars text-lg"></i>
    </button>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="sidebar w-64 bg-white fixed h-full soft-shadow-sidebar overflow-y-auto overflow-x-visible"
               x-data="sidebarComponent()" x-init="initSidebar()">

            <!-- Logo -->
            <div class="p-5 soft-border-bottom border-b flex items-center gap-2">
                <div class="flex items-center space-x-3 min-w-0">
                    <div class="brand-badge w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
                        <img src="{{ asset('KPN123.png') }}" alt="Logo" class="w-6 h-6">
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-lg font-bold text-gray-800 leading-tight truncate">GA Portal</h1>
                        <p class="text-xs text-gray-500 tracking-wide">General Affairs</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="p-3 pb-24">
                <p class="mt-2 mb-1 px-3 text-[11px] font-semibold text-gray-400 uppercase nav-label">Navigation</p>
                <ul class="space-y-1">

                    {{-- Dashboard GA --}}
                    <li>
                        <a href="{{ route('dashboard') }}" title="Dashboard GA"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="fas fa-home w-5 mr-3 text-gray-400"></i>
                            <span>Dashboard GA</span>
                        </a>
                    </li>

                    @if(!empty($access))
                        {{-- ===================== MANAGEMENT (DROPDOWN) ===================== --}}
                        @if(($access['is_admin'] ?? false) || ($access['is_super'] ?? false))
                        <li class="nav-group" :class="{ open: openGroup === 'management' }">
                            <div @click="toggleGroup('management')" title="Management"
                                 class="dropdown-toggle flex items-center justify-between p-3 rounded-lg text-gray-700">
                                <div class="flex items-center">
                                    <i class="fas fa-boxes w-5 mr-3 text-gray-400"></i>
                                    <span class="font-medium">Management</span>
                                </div>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                            <div class="dropdown-content" :class="openGroup === 'management' ? 'open' : 'closed'">
                                <ul class="dropdown-child space-y-1 pb-2">
                                    @if($access['is_super'] ?? false)
                                        <li>
                                            <a href="{{ route('stock-ctl.area.index') }}"
                                               class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('stock-ctl.area.*') ? 'active' : '' }}">
                                                <i class="fas fa-map-marker-alt w-4 mr-3"></i>
                                                <span>Area Kerja</span>
                                            </a>
                                        </li>
                                        <li>
                                            <a href="{{ route('stock-ctl.user-profil.index') }}"
                                               class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('stock-ctl.user-profil.*') ? 'active' : '' }}">
                                                <i class="fas fa-id-badge w-4 mr-3"></i>
                                                <span>User Profil</span>
                                            </a>
                                        </li>
                                    @endif
                                    <li>
                                        <a href="{{ route('stock-ctl.barang.index') }}"
                                           class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('stock-ctl.barang.*') ? 'active' : '' }}">
                                            <i class="fas fa-box w-4 mr-3"></i>
                                            <span>Barang</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('stock-ctl.stok.index') }}"
                                           class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('stock-ctl.stok.*') ? 'active' : '' }}">
                                            <i class="fas fa-warehouse w-4 mr-3"></i>
                                            <span>Stok</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('stock-ctl.opname.index') }}"
                                           class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('stock-ctl.opname.*') ? 'active' : '' }}">
                                            <i class="fas fa-clipboard-list w-4 mr-3"></i>
                                            <span>Opname</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('stock-ctl.laporan.index') }}"
                                           class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('stock-ctl.laporan.*') ? 'active' : '' }}">
                                            <i class="fas fa-chart-bar w-4 mr-3"></i>
                                            <span>Laporan</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </li>
                        @endif

                        {{-- ===================== TRANSAKSI (DROPDOWN) ===================== --}}
                        @if(($access['is_admin'] ?? false) || ($access['is_super'] ?? false))
                        <li class="nav-group" :class="{ open: openGroup === 'transaksi' }">
                            <div @click="toggleGroup('transaksi')" title="Transaksi"
                                 class="dropdown-toggle flex items-center justify-between p-3 rounded-lg text-gray-700">
                                <div class="flex items-center">
                                    <i class="fas fa-exchange-alt w-5 mr-3 text-gray-400"></i>
                                    <span class="font-medium">Transaksi</span>
                                </div>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                            <div class="dropdown-content" :class="openGroup === 'transaksi' ? 'open' : 'closed'">
                                <ul class="dropdown-child space-y-1 pb-2">
                                    <li>
                                        <a href="{{ route('stock-ctl.transaksi.masuk') }}"
                                           class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('stock-ctl.transaksi.masuk') ? 'active' : '' }}">
                                            <i class="fas fa-arrow-down w-4 mr-3"></i>
                                            <span>Barang Masuk</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('stock-ctl.transaksi.keluar') }}"
                                           class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('stock-ctl.transaksi.keluar') ? 'active' : '' }}">
                                            <i class="fas fa-arrow-up w-4 mr-3"></i>
                                            <span>Barang Keluar</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('stock-ctl.transaksi.transfer') }}"
                                           class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('stock-ctl.transaksi.transfer') ? 'active' : '' }}">
                                            <i class="fas fa-random w-4 mr-3"></i>
                                            <span>Transfer</span>
                                        </a>
                                    </li>
                                    @if(Route::has('stock-ctl.antar-unit.index'))
                                    <li>
                                        <a href="{{ route('stock-ctl.antar-unit.index') }}"
                                           class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('stock-ctl.antar-unit.index') ? 'active' : '' }}">
                                            <i class="fas fa-share-square w-4 mr-3"></i>
                                            <span>Permintaan Antar Unit</span>
                                        </a>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </li>
                        @endif

                        {{-- ===================== PERMINTAAN SAYA ===================== --}}
                        @if(($access['is_user'] ?? false) || ($access['is_admin'] ?? false) || ($access['is_super'] ?? false))
                        <li>
                            <a href="{{ route('stock-ctl.permintaan.index') }}" title="Permintaan Saya"
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('stock-ctl.permintaan.index') ? 'active' : '' }}">
                                <i class="fas fa-shopping-cart w-5 mr-3 text-gray-400"></i>
                                <span>Permintaan Saya</span>
                            </a>
                        </li>
                        @endif

                        {{-- ===================== APPROVAL L1 ===================== --}}
                        @if($isApprover)
                        <li>
                            <a href="{{ route('stock-ctl.approval.l1.index') }}" title="Approval L1"
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 relative {{ request()->routeIs('stock-ctl.approval.l1.*') ? 'active' : '' }}">
                                <i class="fas fa-user-check w-5 mr-3 text-gray-400"></i>
                                <span>Approval L1</span>
                                @if($pendingL1Count > 0)
                                    <span class="badge-count ml-auto text-white text-xs rounded-full px-2 py-0.5">{{ $pendingL1Count }}</span>
                                @endif
                            </a>
                        </li>
                        @endif

                        {{-- ===================== APPROVAL ADMIN ===================== --}}
                        @if(($access['is_admin'] ?? false))
                        <li>
                            <a href="{{ route('stock-ctl.approval.admin.index') }}" title="Approval Admin"
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 relative {{ request()->routeIs('stock-ctl.approval.admin.*') ? 'active' : '' }}">
                                <i class="fas fa-check-double w-5 mr-3 text-gray-400"></i>
                                <span>Approval Admin</span>
                                @if($pendingAdminCount > 0)
                                    <span class="badge-count ml-auto text-white text-xs rounded-full px-2 py-0.5">{{ $pendingAdminCount }}</span>
                                @endif
                            </a>
                        </li>
                        @endif

                        {{-- ===================== APPROVAL ANTAR UNIT ===================== --}}
                        @if(($access['is_admin'] ?? false) || ($access['is_super'] ?? false))
                            @php
                                $antarUnitApprovalRouteExists = Route::has('stock-ctl.antar-unit.approval');
                                $pendingAntarUnitCount = 0;
                                if ($antarUnitApprovalRouteExists) {
                                    $pendingAntarUnitCount = \App\Models\StockCtl\AntarUnitRequest::where('status', 'pending')
                                        ->where('id_bisnis_unit_tujuan', $access['id_bisnis_unit'] ?? 0)
                                        ->count();
                                }
                            @endphp
                            @if($antarUnitApprovalRouteExists)
                            <li>
                                <a href="{{ route('stock-ctl.antar-unit.approval') }}" title="Approval Antar Unit"
                                   class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 relative {{ request()->routeIs('stock-ctl.antar-unit.approval') ? 'active' : '' }}">
                                    <i class="fas fa-globe w-5 mr-3 text-gray-400"></i>
                                    <span>Approval Antar Unit</span>
                                    @if($pendingAntarUnitCount > 0)
                                        <span class="badge-count ml-auto text-white text-xs rounded-full px-2 py-0.5">{{ $pendingAntarUnitCount }}</span>
                                    @endif
                                </a>
                            </li>
                            @endif
                        @endif
                    @endif
                </ul>
            </nav>

            <!-- User Profile + Logout -->
            <div class="absolute bottom-0 left-0 right-0 p-3 soft-border-top border-t bg-white"
                 x-data="{ openProfile: false }" @click.outside="openProfile = false">

                <div x-show="openProfile" x-transition
                     class="profile-menu bg-white soft-shadow rounded-lg soft-border border py-2 z-50">
                    <div class="px-4 py-2 text-sm text-gray-500 soft-border-bottom border-b">
                        <p class="font-medium text-gray-700">{{ auth()->user()->name ?? '-' }}</p>
                        <p class="text-xs">{{ auth()->user()->email ?? '-' }}</p>
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
                            {{ strtoupper(substr(auth()->user()->username ?? 'AD', 0, 2)) }}
                        </span>
                    </div>
                    <div class="min-w-0 text-left flex-1">
                        <h3 class="font-medium text-gray-800 truncate">{{ auth()->user()->name ?? 'User' }}</h3>
                        <p class="text-xs text-gray-500">
                            @if(isset($access['is_super']) && $access['is_super'])
                                Superadmin
                            @elseif(isset($access['is_admin']) && $access['is_admin'])
                                Admin
                            @else
                                User
                            @endif
                        </p>
                    </div>
                    <i class="fas fa-ellipsis-vertical text-gray-400 text-sm"></i>
                </button>
            </div>
        </aside>

        <!-- Main Content (header dihapus, sama seperti DRMS) -->
        <div class="flex-1 ml-0 md:ml-64 transition-all duration-300">
            <main class="p-6 pt-20 md:pt-6">
                @hasSection('breadcrumb')
                    <div class="text-sm text-gray-500 mb-4">@yield('breadcrumb')</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            window.sidebarComponent = function() {
                return {
                    // Accordion: hanya satu group yang bisa terbuka ('management' | 'transaksi' | null)
                    openGroup: null,

                    initSidebar() {
                        const path = window.location.pathname;

                        const managementPaths = [
                            '/stock-ctl/area', '/stock-ctl/user-profil', '/stock-ctl/barang',
                            '/stock-ctl/stok', '/stock-ctl/opname', '/stock-ctl/laporan',
                        ];
                        const transaksiPaths = [
                            '/stock-ctl/transaksi', '/stock-ctl/antar-unit',
                        ];

                        if (managementPaths.some(p => path.startsWith(p))) {
                            this.openGroup = 'management';
                        } else if (transaksiPaths.some(p => path.startsWith(p)) && !path.startsWith('/stock-ctl/antar-unit/approval')) {
                            this.openGroup = 'transaksi';
                        }
                    },

                    toggleGroup(name) {
                        this.openGroup = this.openGroup === name ? null : name;
                    },
                }
            }
        });

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

    @stack('scripts')
</body>
</html>