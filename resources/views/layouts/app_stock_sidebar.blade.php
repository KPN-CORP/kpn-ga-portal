<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>GA Portal</title>
    <link rel="shortcut icon" href="{{ asset('KPN123.png') }}" type="image/x-icon">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js for dropdowns -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        html{
            zoom:0.8;
        }

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
                    <h1 class="text-xl font-bold text-gray-800">GA Portal</h1>
                    <p class="text-sm text-gray-500 opacity-80">General Affairs</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="p-4">
                <ul class="space-y-1">
                    {{-- Dashboard GA Portal --}}
                    <li>
                        <a href="{{ route('dashboard') }}" 
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="fas fa-home mr-3 text-gray-500 opacity-70"></i>
                            <span>Dashboard GA</span>
                        </a>
                    </li>

                    {{-- Menu ATK dari provider --}}
                    @if(!empty($access))
                        <li class="mt-4 mb-2 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Stock Management</li>
                        
                        {{-- Loop menu ATK dari $atkMenu --}}
                        @foreach($atkMenu as $menu)
                            @if(isset($menu['submenu']))
                                {{-- Dropdown --}}
                                <li x-data="{ open: false }" class="relative">
                                    <button @click="open = !open" class="sidebar-link flex items-center justify-between w-full p-3 rounded-lg text-gray-700">
                                        <span class="flex items-center">
                                            <i class="{{ $menu['icon'] }} mr-3 text-gray-500 opacity-70"></i>
                                            <span>{{ $menu['title'] }}</span>
                                        </span>
                                        <i class="fas fa-chevron-down text-xs transition-transform" :class="{ 'rotate-180': open }"></i>
                                    </button>
                                    <ul x-show="open" x-collapse class="pl-8 space-y-1 mt-1">
                                        @foreach($menu['submenu'] as $sub)
                                        <li>
                                            <a href="{{ $sub['url'] }}" class="sidebar-link block p-2 rounded-lg text-gray-600 hover:bg-gray-50 {{ request()->url() == $sub['url'] ? 'active' : '' }}">
                                                {{ $sub['title'] }}
                                            </a>
                                        </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @else
                                <li>
                                    <a href="{{ $menu['url'] }}" class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->url() == $menu['url'] ? 'active' : '' }}">
                                        <i class="{{ $menu['icon'] }} mr-3 text-gray-500 opacity-70"></i>
                                        <span>{{ $menu['title'] }}</span>
                                    </a>
                                </li>
                            @endif
                        @endforeach

                        {{-- Approval L1 (hanya untuk atasan) --}}
                        @if($isApprover)
                        <li>
                            <a href="{{ route('stock-ctl.approval.l1.index') }}" 
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('stock-ctl.approval.l1.*') ? 'active' : '' }}">
                                <i class="fas fa-user-check mr-3 text-gray-500 opacity-70"></i>
                                <span>Approval L1</span>
                                @if($pendingL1Count > 0)
                                    <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1">{{ $pendingL1Count }}</span>
                                @endif
                            </a>
                        </li>
                        @endif

                        {{-- Approval Admin --}}
                        @if(($access['is_admin'] ?? false) && $pendingAdminCount > 0)
                        <li>
                            <a href="{{ route('stock-ctl.approval.admin.index') }}" 
                               class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('stock-ctl.approval.admin.*') ? 'active' : '' }}">
                                <i class="fas fa-check-double mr-3 text-gray-500 opacity-70"></i>
                                <span>Approval Admin</span>
                                <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1">{{ $pendingAdminCount }}</span>
                            </a>
                        </li>
                        @endif
                    @endif
                </ul>
            </nav>

            <!-- User Profile -->
            <div class="absolute bottom-0 left-0 right-0 p-4 soft-border-top bg-white">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center soft-border">
                        <span class="font-semibold text-blue-600 opacity-90">
                            {{ strtoupper(substr(auth()->user()->username ?? 'AD', 0, 2)) }}
                        </span>
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-800">{{ auth()->user()->name ?? 'User' }}</h3>
                        <p class="text-xs text-gray-500 opacity-70">
                            @if(isset($access['is_super']) && $access['is_super'])
                                Superadmin
                            @elseif(isset($access['is_admin']) && $access['is_admin'])
                                Admin
                            @else
                                User
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
                    <!-- Notifications -->
                    <div class="relative">
                        <button id="notif-button" class="relative text-gray-600 hover:text-gray-800 focus:outline-none smooth-transition">
                            <i class="fas fa-bell text-xl opacity-80"></i>
                            <!-- @if(isset($unreadNotifications) && $unreadNotifications > 0)
                                <span class="absolute -top-1 -right-1 bg-red-400 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center soft-shadow">
                                    {{ $unreadNotifications }}
                                </span>
                            @endif -->
                        </button>
                        <!-- <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white soft-shadow rounded-lg soft-border py-2 z-50">
                            <div class="px-4 py-2 soft-border-bottom">
                                <h3 class="font-medium text-gray-800">Notifikasi</h3>
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                @forelse($notifications ?? [] as $notif)
                                    <div class="px-4 py-3 hover:bg-gray-50 border-b last:border-0">
                                        <p class="text-sm text-gray-700">{{ $notif->data['message'] ?? 'Notifikasi' }}</p>
                                        <p class="text-xs text-gray-500 mt-1">{{ $notif->created_at->diffForHumans() }}</p>
                                    </div>
                                @empty
                                    <div class="px-4 py-3 text-sm text-gray-500">Tidak ada notifikasi baru</div>
                                @endforelse
                            </div>
                            <a href="{{ url('/notifications') }}" class="block px-4 py-2 text-sm text-center text-blue-500 hover:bg-gray-50 soft-border-top">
                                Lihat Semua
                            </a>
                        </div> -->
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