<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Apartemen - GA Portal</title>
    <link rel="shortcut icon" href="{{ asset('KPN123.png') }}" type="image/x-icon">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js for dropdowns -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        html { zoom: 0.8; }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
        }
        .soft-border { border-color: rgba(229,231,235,0.5) !important; }
        .soft-border-bottom { border-bottom-color: rgba(229,231,235,0.5) !important; }
        .soft-shadow { box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03); }
        .soft-shadow-sidebar { box-shadow: 1px 0 8px rgba(0,0,0,0.03), 2px 0 4px rgba(0,0,0,0.01); }
        .sidebar-link {
            transition: all 0.3s ease;
        }
        .sidebar-link:hover {
            background-color: rgba(248,250,252,0.8);
            transform: translateX(3px);
        }
        .sidebar-link.active {
            background-color: rgba(59,130,246,0.1);
            border-left: 3px solid rgba(59,130,246,0.5);
            font-weight: 500;
        }
        .header-soft {
            border-bottom: 1px solid rgba(229,231,235,0.4);
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(8px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background-color: rgba(0,0,0,0.15);
            z-index: 50;
        }
        .overlay.active { display: block; }
        .sidebar {
            transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); z-index: 60; }
            .sidebar.active { transform: translateX(0); }
        }
        .smooth-transition { transition: all 0.25s cubic-bezier(0.4,0,0.2,1); }
    </style>
    @stack('styles')
</head>
<body>
    <div id="overlay" class="overlay"></div>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="sidebar w-64 bg-white fixed h-full soft-shadow-sidebar overflow-y-auto">
            <!-- Logo -->
            <div class="p-6 soft-border-bottom flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center soft-border">
                    <img src="{{ asset('KPN123.png') }}" alt="Logo" class="w-6 h-6 opacity-90">
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">Apartemen</h1>
                    <p class="text-sm text-gray-500 opacity-80">GA Portal</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="p-4">
                <ul class="space-y-1">
                    @if(auth()->user()->can('apartemen.admin') ?? false)
                        {{-- ADMIN MENU --}}
                        {{-- Dashboard GA (main GA dashboard) --}}
                        @if(Route::has('dashboard'))
                        <li>
                            <a href="{{ route('dashboard') }}" 
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                                <i class="fas fa-tachometer-alt mr-3 text-gray-500 opacity-70"></i>
                                <span>Dashboard GA</span>
                            </a>
                        </li>
                        @endif

                        {{-- Dashboard Apartemen --}}
                        <li>
                            <a href="{{ route('apartemen.admin.dashboard') }}" 
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('apartemen.admin.dashboard') ? 'active' : '' }}">
                                <i class="fas fa-building mr-3 text-gray-500 opacity-70"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>

                        {{-- Status Aktif (user view) --}}
                        <li>
                            <a href="{{ route('apartemen.user.index') }}" 
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('apartemen.user.index') ? 'active' : '' }}">
                                <i class="fas fa-home mr-3 text-gray-500 opacity-70"></i>
                                <span>Status Aktif</span>
                            </a>
                        </li>

                        {{-- Riwayat Permintaan (user view) --}}
                        <li>
                            <a href="{{ route('apartemen.user.requests') }}" 
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('apartemen.user.requests') ? 'active' : '' }}">
                                <i class="fas fa-file-alt mr-3 text-gray-500 opacity-70"></i>
                                <span>Riwayat Permintaan</span>
                            </a>
                        </li>

                        {{-- Pengajuan Baru (user view) --}}
                        <li>
                            <a href="{{ route('apartemen.user.create') }}" 
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('apartemen.user.create') ? 'active' : '' }}">
                                <i class="fas fa-plus-circle mr-3 text-gray-500 opacity-70"></i>
                                <span>Pengajuan Baru</span>
                            </a>
                        </li>

                        <li class="mt-4 mb-2 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Manajemen</li>

                        {{-- Permintaan Pending --}}
                        <li>
                            <a href="{{ route('apartemen.admin.index') }}" 
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('apartemen.admin.index') ? 'active' : '' }}">
                                <i class="fas fa-inbox mr-3 text-gray-500 opacity-70"></i>
                                <span>Permintaan</span>
                                @php
                                    $pendingCount = \App\Models\Apartemen\ApartemenRequest::where('status', 'PENDING')->count();
                                @endphp
                                @if($pendingCount > 0)
                                    <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1">{{ $pendingCount }}</span>
                                @endif
                            </a>
                        </li>

                        {{-- Unit (Apartemen & Unit) --}}
                        <li>
                            <a href="{{ route('apartemen.admin.apartemen') }}" 
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('apartemen.admin.apartemen*') ? 'active' : '' }}">
                                <i class="fas fa-building mr-3 text-gray-500 opacity-70"></i>
                                <span>Unit</span>
                            </a>
                        </li>

                        {{-- Penghuni Aktif (Monitoring) --}}
                        <li>
                            <a href="{{ route('apartemen.admin.monitoring') }}" 
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('apartemen.admin.monitoring') ? 'active' : '' }}">
                                <i class="fas fa-users mr-3 text-gray-500 opacity-70"></i>
                                <span>Penghuni Aktif</span>
                            </a>
                        </li>

                        {{-- Riwayat (history penghuni) --}}
                        <li>
                            <a href="{{ route('apartemen.admin.history') }}" 
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('apartemen.admin.history') ? 'active' : '' }}">
                                <i class="fas fa-history mr-3 text-gray-500 opacity-70"></i>
                                <span>Riwayat</span>
                            </a>
                        </li>

                        {{-- QR Code Management (opsional) --}}
                        @if(Route::has('apartemen.admin.access-codes'))
                        <li>
                            <a href="{{ route('apartemen.admin.access-codes') }}" 
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('apartemen.admin.access-codes*') ? 'active' : '' }}">
                                <i class="fas fa-qrcode mr-3 text-gray-500 opacity-70"></i>
                                <span>QR Code Akses</span>
                            </a>
                        </li>
                        @endif

                        {{-- Laporan (opsional) --}}
                        @if(Route::has('apartemen.admin.report'))
                        <li>
                            <a href="{{ route('apartemen.admin.report') }}" 
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('apartemen.admin.report') ? 'active' : '' }}">
                                <i class="fas fa-chart-line mr-3 text-gray-500 opacity-70"></i>
                                <span>Laporan</span>
                            </a>
                        </li>
                        @endif
                    @else
                        {{-- USER MENU --}}
                        <li>
                            <a href="{{ route('apartemen.user.index') }}" 
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('apartemen.user.index') ? 'active' : '' }}">
                                <i class="fas fa-home mr-3 text-gray-500 opacity-70"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('apartemen.user.index') }}" 
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('apartemen.user.index') ? 'active' : '' }}">
                                <i class="fas fa-check-circle mr-3 text-gray-500 opacity-70"></i>
                                <span>Status Aktif</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('apartemen.user.requests') }}" 
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('apartemen.user.requests') ? 'active' : '' }}">
                                <i class="fas fa-file-alt mr-3 text-gray-500 opacity-70"></i>
                                <span>Riwayat Permintaan</span>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('apartemen.user.create') }}" 
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('apartemen.user.create') ? 'active' : '' }}">
                                <i class="fas fa-plus-circle mr-3 text-gray-500 opacity-70"></i>
                                <span>Pengajuan Baru</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </nav>

            <!-- User Profile -->
            <div class="absolute bottom-0 left-0 right-0 p-4 soft-border-top bg-white">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center soft-border">
                        <span class="font-semibold text-blue-600 opacity-90">
                            {{ strtoupper(substr(auth()->user()->name ?? 'AD', 0, 2)) }}
                        </span>
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-800">{{ auth()->user()->name ?? 'User' }}</h3>
                        <p class="text-xs text-gray-500 opacity-70">
                            @if(auth()->user()->can('apartemen.admin') ?? false)
                                Admin Apartemen
                            @else
                                Penghuni
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 ml-0 md:ml-64">
            <header class="header-soft sticky top-0 z-40 soft-shadow px-6 py-4 flex justify-between items-center">
                <div class="flex items-center">
                    <button id="sidebar-toggle" class="md:hidden text-gray-600 focus:outline-none smooth-transition hover:text-gray-800">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <div class="ml-4">
                        @hasSection('breadcrumb')
                            <div class="text-sm text-gray-500 opacity-80 mt-1">
                                @yield('breadcrumb')
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Notifications placeholder -->
                    <div class="relative">
                        <button id="notif-button" class="relative text-gray-600 hover:text-gray-800 focus:outline-none smooth-transition">
                            <i class="fas fa-bell text-xl opacity-80"></i>
                        </button>
                        <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white soft-shadow rounded-lg soft-border py-2 z-50">
                            <div class="px-4 py-2 soft-border-bottom">
                                <h3 class="font-medium text-gray-800">Notifikasi</h3>
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                <div class="px-4 py-3 text-sm text-gray-500">Belum ada notifikasi</div>
                            </div>
                        </div>
                    </div>

                    <!-- User Dropdown -->
                    <div class="relative">
                        <button id="user-menu-button" class="flex items-center space-x-2 focus:outline-none smooth-transition hover:text-gray-800">
                            <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center soft-border">
                                <span class="font-semibold text-blue-600 text-sm opacity-90">
                                    {{ strtoupper(substr(auth()->user()->name ?? 'AD', 0, 2)) }}
                                </span>
                            </div>
                            <span class="hidden md:inline text-gray-700 opacity-90">{{ auth()->user()->name ?? 'Admin' }}</span>
                            <i class="fas fa-chevron-down text-gray-500 text-sm opacity-70"></i>
                        </button>
                        <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white soft-shadow rounded-lg soft-border py-2 z-50">
                            <div class="px-4 py-2 text-sm text-gray-500 soft-border-bottom">
                                <p>{{ auth()->user()->name ?? '-' }}</p>
                                <p class="text-xs">{{ auth()->user()->email ?? '-' }}</p>
                            </div>
                            <div class="soft-border-top my-1"></div>
                            <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form-header').submit();" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 smooth-transition">
                                <i class="fas fa-sign-out-alt mr-3 text-gray-500 opacity-70"></i>Keluar
                            </a>
                            <form id="logout-form-header" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="p-6">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('overlay');

        document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        });

        document.getElementById('user-menu-button')?.addEventListener('click', () => {
            document.getElementById('user-dropdown').classList.toggle('hidden');
        });

        document.getElementById('notif-button')?.addEventListener('click', () => {
            document.getElementById('notif-dropdown').classList.toggle('hidden');
        });

        document.addEventListener('click', (e) => {
            const userBtn = document.getElementById('user-menu-button');
            const userDropdown = document.getElementById('user-dropdown');
            const notifBtn = document.getElementById('notif-button');
            const notifDropdown = document.getElementById('notif-dropdown');

            if (userBtn && !userBtn.contains(e.target) && !userDropdown.contains(e.target)) userDropdown.classList.add('hidden');
            if (notifBtn && !notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) notifDropdown.classList.add('hidden');
        });

        window.addEventListener('resize', () => {
            if(window.innerWidth >= 768){
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', () => {
                if(window.innerWidth < 768){
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
    </script>

    @stack('scripts')
</body>
</html>