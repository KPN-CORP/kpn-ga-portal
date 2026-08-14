<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>GA Portal - ID Card</title>
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
        .badge-count-green{
            background: linear-gradient(135deg,#22c55e,#16a34a);
            box-shadow: 0 2px 6px rgba(34,197,94,.35);
        }

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

    @yield('head')
    @stack('styles')
</head>
<body>
    <div id="overlay" class="overlay"></div>

    <button id="sidebar-toggle" class="mobile-toggle text-gray-600">
        <i class="fas fa-bars text-lg"></i>
    </button>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="sidebar w-64 bg-white fixed h-full soft-shadow-sidebar overflow-y-auto overflow-x-visible">

            <!-- Logo -->
            <div class="p-5 soft-border-bottom border-b flex items-center gap-2">
                <div class="flex items-center space-x-3 min-w-0">
                    <div class="brand-badge w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
                        <img src="{{ asset('KPN123.png') }}" alt="Logo" class="w-6 h-6">
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-lg font-bold text-gray-800 leading-tight truncate">GA Portal</h1>
                        <p class="text-xs text-gray-500 tracking-wide">ID Card</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="p-3 pb-24">
                <p class="mt-2 mb-1 px-3 text-[11px] font-semibold text-gray-400 uppercase nav-label">Navigation</p>
                <ul class="space-y-1">

                    <!-- Dashboard -->
                    <li>
                        <a href="{{ route('dashboard') }}" title="Dashboard"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->is('dashboard') ? 'active' : '' }}">
                            <i class="fas fa-home w-5 mr-3 text-gray-400"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <!-- Semua Request -->
                    <li>
                        <a href="{{ url('/idcard') }}" title="Semua Request"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->is('idcard') || request()->is('idcard/*') ? 'active' : '' }}">
                            <i class="fas fa-list w-5 mr-3 text-gray-400"></i>
                            <span>Semua Request</span>
                        </a>
                    </li>

                    @php
                        $user = auth()->user();

                        // Cek superadmin
                        $isSuperAdmin = $user->username == 'admin' ||
                            DB::table('tb_access_menu')
                                ->where('username', $user->username)
                                ->where('proses_idcard', 1)
                                ->exists();

                        // Cek admin BU
                        $adminBUIds = DB::table('request_idcard_accesbu')
                            ->where('user_id', $user->id)
                            ->pluck('bisnis_unit_id')
                            ->toArray();

                        // Hitung aktif berdasarkan akses
                        if ($isSuperAdmin) {
                            $aktifCount = \App\Models\IDCard\RequestIdCard::where('is_active', 1)->count();
                        } elseif (!empty($adminBUIds)) {
                            $aktifCount = \App\Models\IDCard\RequestIdCard::where('is_active', 1)
                                ->whereIn('bisnis_unit_id', $adminBUIds)->count();
                        } else {
                            $aktifCount = \App\Models\IDCard\RequestIdCard::where('is_active', 1)
                                ->where('user_id', $user->id)->count();
                        }

                        // Hitung inaktif berdasarkan akses
                        if ($isSuperAdmin) {
                            $inaktifCount = \App\Models\IDCard\RequestIdCard::where('is_active', 0)->count();
                        } elseif (!empty($adminBUIds)) {
                            $inaktifCount = \App\Models\IDCard\RequestIdCard::where('is_active', 0)
                                ->whereIn('bisnis_unit_id', $adminBUIds)->count();
                        } else {
                            $inaktifCount = \App\Models\IDCard\RequestIdCard::where('is_active', 0)
                                ->where('user_id', $user->id)->count();
                        }
                    @endphp

                    <!-- Aktif -->
                    <li>
                        <a href="{{ url('/idcard/aktif') }}" title="Aktif"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 relative {{ request()->is('idcard/aktif*') ? 'active' : '' }}">
                            <i class="fas fa-check-circle w-5 mr-3 text-gray-400"></i>
                            <span>Aktif</span>
                            @if($aktifCount > 0)
                                <span class="badge-count-green ml-auto text-white text-xs rounded-full px-2 py-0.5">{{ $aktifCount }}</span>
                            @endif
                        </a>
                    </li>

                    <!-- Inaktif -->
                    <li>
                        <a href="{{ url('/idcard/inaktif') }}" title="Inaktif"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 relative {{ request()->is('idcard/inaktif*') ? 'active' : '' }}">
                            <i class="fas fa-times-circle w-5 mr-3 text-gray-400"></i>
                            <span>Inaktif</span>
                            @if($inaktifCount > 0)
                                <span class="badge-count ml-auto text-white text-xs rounded-full px-2 py-0.5">{{ $inaktifCount }}</span>
                            @endif
                        </a>
                    </li>

                    <!-- Grafik -->
                    <li>
                        <a href="{{ url('/idcard/grafik') }}" title="Grafik"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->is('idcard/grafik*') ? 'active' : '' }}">
                            <i class="fas fa-chart-bar w-5 mr-3 text-gray-400"></i>
                            <span>Grafik</span>
                        </a>
                    </li>

                    <!-- Report -->
                    <li>
                        <a href="{{ url('/idcard/report') }}" title="Report"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->is('idcard/report*') ? 'active' : '' }}">
                            <i class="fas fa-file-alt w-5 mr-3 text-gray-400"></i>
                            <span>Report</span>
                        </a>
                    </li>

                </ul>
            </nav>

            <!-- User Profile + Logout -->
            <div class="absolute bottom-0 left-0 right-0 p-3 soft-border-top border-t bg-white"
                 x-data="{ openProfile: false }" @click.outside="openProfile = false">

                <div x-show="openProfile" x-transition x-cloak
                     class="profile-menu bg-white soft-shadow rounded-lg soft-border border py-2 z-50">
                    <div class="px-4 py-2 text-sm text-gray-500 soft-border-bottom border-b">
                        <p class="font-medium text-gray-700">{{ auth()->user()->username ?? '-' }}</p>
                        <p class="text-xs">{{ auth()->user()->role ?? '-' }}</p>
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
                        <h3 class="font-medium text-gray-800 truncate">{{ auth()->user()->username ?? 'User' }}</h3>
                        <p class="text-xs text-gray-500 truncate">{{ auth()->user()->role ?? 'Online' }}</p>
                    </div>
                    <i class="fas fa-ellipsis-vertical text-gray-400 text-sm"></i>
                </button>
            </div>
        </aside>

        <!-- Main Content (header dihapus) -->
        <div class="flex-1 ml-0 md:ml-64 transition-all duration-300">
            <main class="p-6 pt-20 md:pt-6">
                @hasSection('breadcrumb')
                    <div class="text-sm text-gray-500 mb-4">@yield('breadcrumb')</div>
                @endif
                @yield('content')
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

    @stack('scripts')
</body>
</html>