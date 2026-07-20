<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>GA Portal - DRMS</title>
    <link rel="shortcut icon" href="{{ asset('KPN123.png') }}" type="image/x-icon">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <style>
        html { zoom: 0.8; }
        body { font-family: 'Inter', sans-serif; background-color: #f9fafb; }
        .soft-border { border-color: rgba(229,231,235,0.5) !important; }
        .soft-border-bottom { border-bottom-color: rgba(229,231,235,0.5) !important; }
        .soft-shadow { box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03); }
        .soft-shadow-sidebar { box-shadow: 1px 0 8px rgba(0,0,0,0.03), 2px 0 4px rgba(0,0,0,0.01); }
        .sidebar-link { transition: all 0.3s ease; }
        .sidebar-link:hover { background-color: rgba(248,250,252,0.8); transform: translateX(3px); }
        .sidebar-link.active { background-color: rgba(59,130,246,0.1); border-left: 3px solid rgba(59,130,246,0.5); font-weight: 500; }
        .header-soft { border-bottom: 1px solid rgba(229,231,235,0.4); background: rgba(255,255,255,0.95); backdrop-filter: blur(8px); box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .overlay { display: none; position: fixed; inset: 0; background-color: rgba(0,0,0,0.15); z-index: 50; }
        .overlay.active { display: block; }
        .sidebar { transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
        @media (max-width: 768px) { .sidebar { transform: translateX(-100%); z-index: 60; } .sidebar.active { transform: translateX(0); } }
        .smooth-transition { transition: all 0.25s cubic-bezier(0.4,0,0.2,1); }
        .dropdown-child { padding-left: 1.5rem; }
        .dropdown-child .sidebar-link { font-size: 0.9rem; padding: 0.5rem 1rem; }
        .dropdown-toggle { cursor: pointer; user-select: none; }
        .dropdown-toggle .fa-chevron-down { transition: transform 0.25s ease; }
        .dropdown-toggle.open .fa-chevron-down { transform: rotate(180deg); }
        .dropdown-content { overflow: hidden; transition: max-height 0.3s ease; }
        .dropdown-content.closed { max-height: 0 !important; }
        .dropdown-content.open { max-height: 600px; }
    </style>
    @stack('styles')
</head>
<body>
    <div id="overlay" class="overlay"></div>

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="sidebar w-64 bg-white fixed h-full soft-shadow-sidebar overflow-y-auto"
               x-data="sidebarComponent()" x-init="initSidebar()">
            <!-- Logo -->
            <div class="p-6 soft-border-bottom flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center soft-border">
                    <img src="{{ asset('KPN123.png') }}" alt="Logo" class="w-6 h-6 opacity-90">
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">GA Portal</h1>
                    <p class="text-sm text-gray-500 opacity-80">DRMS</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="p-4">
                <ul class="space-y-1">
                    {{-- Dashboard GA --}}
                    <li>
                        <a href="{{ route('dashboard') }}"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="fas fa-home mr-3 text-gray-500 opacity-70"></i>
                            <span>Dashboard GA</span>
                        </a>
                    </li>


                    {{-- Driver Request (User) --}}
                    @if(auth()->user() && auth()->user()->isDrmsUser())
                    <li class="mt-4 mb-2 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Driver Request</li>
                    <li>
                        <a href="{{ route('drms.requests.index') }}"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('drms.requests.*') ? 'active' : '' }}">
                            <i class="fas fa-car mr-3 text-gray-500 opacity-70"></i>
                            <span>My Requests</span>
                        </a>
                    </li>
                    @endif

                    {{-- Approval L1 --}}
                    @if(auth()->user() && auth()->user()->isApprover())
                    <li>
                        <a href="{{ route('drms.approval.l1.index') }}"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('drms.approval.l1.*') ? 'active' : '' }}">
                            <i class="fas fa-user-check mr-3 text-gray-500 opacity-70"></i>
                            <span>Approval L1</span>
                            @if(isset($pendingL1Count) && $pendingL1Count > 0)
                                <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1">{{ $pendingL1Count }}</span>
                            @endif
                        </a>
                    </li>
                    @endif

                    {{-- Approval Admin --}}
                    @if(auth()->user() && auth()->user()->isDrmsAdmin())
                    <li>
                        <a href="{{ route('drms.approval.admin.index') }}"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('drms.approval.admin.*') ? 'active' : '' }}">
                            <i class="fas fa-check-double mr-3 text-gray-500 opacity-70"></i>
                            <span>Approval Admin</span>
                            @if(isset($pendingAdminCount) && $pendingAdminCount > 0)
                                <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1">{{ $pendingAdminCount }}</span>
                            @endif
                        </a>
                    </li>

                    {{-- Manajemen Data --}}
                    <li class="mt-4">
                        <div @click="toggleMaster()"
                             class="dropdown-toggle flex items-center justify-between p-3 rounded-lg text-gray-700 hover:bg-gray-50 smooth-transition"
                             :class="{'open': openMaster}">
                            <div class="flex items-center">
                                <i class="fas fa-database mr-3 text-gray-500 opacity-70"></i>
                                <span class="font-medium">Manajemen Data</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 text-xs" :class="{'rotate-180': openMaster}"></i>
                        </div>
                        <div class="dropdown-content" :class="openMaster ? 'open' : 'closed'">
                            <ul class="dropdown-child space-y-1 mt-1 pb-2">
                                <li>
                                    <a href="{{ route('drms.drivers.index') }}"
                                       class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('drms.drivers.*') ? 'active' : '' }}">
                                        <i class="fas fa-users mr-3 text-gray-500 opacity-70"></i>
                                        <span>Drivers</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('drms.drivers.schedule') }}"
                                       class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('drms.drivers.schedule') ? 'active' : '' }}">
                                        <i class="fas fa-calendar-alt mr-3 text-gray-500 opacity-70"></i>
                                        <span>Jadwal Driver</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('drms.vehicles.index') }}"
                                       class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('drms.vehicles.*') ? 'active' : '' }}">
                                        <i class="fas fa-truck mr-3 text-gray-500 opacity-70"></i>
                                        <span>Vehicles</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('drms.vouchers.index') }}"
                                       class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('drms.vouchers.*') ? 'active' : '' }}">
                                        <i class="fas fa-ticket-alt mr-3 text-gray-500 opacity-70"></i>
                                        <span>Vouchers</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('drms.service-schedules.index') }}"
                                       class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('drms.service-schedules.*') ? 'active' : '' }}">
                                        <i class="fas fa-wrench mr-3 text-gray-500 opacity-70"></i>
                                        <span>Servis Rutin</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('drms.repairs.index') }}"
                                       class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('drms.repairs.*') ? 'active' : '' }}">
                                        <i class="fas fa-hammer mr-3 text-gray-500 opacity-70"></i>
                                        <span>Perbaikan</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('drms.vehicle-documents.index') }}"
                                       class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('drms.vehicle-documents.*') ? 'active' : '' }}">
                                        <i class="fas fa-file-alt mr-3 text-gray-500 opacity-70"></i>
                                        <span>Dokumen Kendaraan</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    {{-- Operasional & Monitoring --}}
                    <li class="mt-2">
                        <div @click="toggleOperasional()"
                             class="dropdown-toggle flex items-center justify-between p-3 rounded-lg text-gray-700 hover:bg-gray-50 smooth-transition"
                             :class="{'open': openOperasional}">
                            <div class="flex items-center">
                                <i class="fas fa-chart-pie mr-3 text-gray-500 opacity-70"></i>
                                <span class="font-medium">Operasional & Monitoring</span>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 text-xs" :class="{'rotate-180': openOperasional}"></i>
                        </div>
                        <div class="dropdown-content" :class="openOperasional ? 'open' : 'closed'">
                            <ul class="dropdown-child space-y-1 mt-1 pb-2">
                                <li>
                                    <a href="{{ route('drms.admin.operational.dashboard') }}"
                                       class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('drms.admin.operational.dashboard') ? 'active' : '' }}">
                                        <i class="fas fa-chart-line mr-3 text-gray-500 opacity-70"></i>
                                        <span>Dashboard Grafik</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('drms.admin.monitoring.logs') }}"
                                       class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('drms.admin.monitoring.logs') || request()->routeIs('drms.admin.verify.log*') ? 'active' : '' }}">
                                        <i class="fas fa-clipboard-check mr-3 text-gray-500 opacity-70"></i>
                                        <span>Monitoring Log Driver</span>
                                        @php
                                            $buId = auth()->user()->drmsProfile->business_unit_id ?? null;
                                            $pendingLogs = \App\Models\Drms\TripLog::where('is_submitted', 1)
                                                ->where('is_verified', 0)
                                                ->whereHas('request', function($q) use ($buId) {
                                                    if ($buId) {
                                                        $q->where('current_business_unit_id', $buId)
                                                          ->orWhereHas('requester.drmsProfile', function($q2) use ($buId) {
                                                              $q2->where('business_unit_id', $buId);
                                                          });
                                                    }
                                                })
                                                ->count();
                                        @endphp
                                        @if($pendingLogs > 0)
                                            <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1">{{ $pendingLogs }}</span>
                                        @endif
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('drms.fuel-logs.index') }}"
                                       class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('drms.fuel-logs.*') ? 'active' : '' }}">
                                        <i class="fas fa-gas-pump mr-3 text-gray-500 opacity-70"></i>
                                        <span> Logs Pengisian</span>
                                        @php
                                            $pendingFuel = \App\Models\Drms\FuelLog::where('is_verified', 0)->count();
                                        @endphp
                                        @if($pendingFuel > 0)
                                            <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-1">{{ $pendingFuel }}</span>
                                        @endif
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('drms.fuel-logs.analytics') }}"
                                       class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('drms.fuel-logs.analytics') ? 'active' : '' }}">
                                        <i class="fas fa-chart-bar mr-3 text-gray-500 opacity-70"></i>
                                        <span>Insight Pengisian</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    {{-- Peta Kendaraan (Superadmin) --}}
                    @if(auth()->user() && auth()->user()->isDrmsSuperAdmin())
                    <li class="mt-2">
                        <a href="{{ route('drms.vehicles.map') }}"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('drms.vehicles.map') ? 'active' : '' }}">
                            <i class="fas fa-map-marked-alt mr-3 text-gray-500 opacity-70"></i>
                            <span>Peta Kendaraan</span>
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
                            {{ strtoupper(substr(auth()->user()->name ?? 'AD', 0, 2)) }}
                        </span>
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-800">{{ auth()->user()->name ?? 'User' }}</h3>
                        <p class="text-xs text-gray-500 opacity-70">
                            @if(auth()->user() && auth()->user()->isDrmsSuperAdmin())
                                Superadmin
                            @elseif(auth()->user() && auth()->user()->isDrmsAdmin())
                                Admin DRMS
                            @elseif(auth()->user() && auth()->user()->isApprover())
                                Atasan
                            @elseif(auth()->user() && auth()->user()->driver)
                                Driver
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
                            <div class="text-sm text-gray-500 opacity-80 mt-1">@yield('breadcrumb')</div>
                        @endif
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <button id="notif-button" class="relative text-gray-600 hover:text-gray-800 focus:outline-none smooth-transition">
                        <i class="fas fa-bell text-xl opacity-80"></i>
                    </button>
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

    <script>
        document.addEventListener('alpine:init', () => {
            window.sidebarComponent = function() {
                return {
                    openMaster: false,
                    openOperasional: false,
                    initSidebar() {
                        const currentPath = window.location.pathname;
                        const masterPaths = [
                            '/drms/drivers', '/drms/vehicles', '/drms/vouchers',
                            '/drms/service-schedules', '/drms/repairs', '/drms/vehicle-documents',
                            '/drms/drivers/schedule'
                        ];
                        const operasionalPaths = [
                            '/drms/admin/operational-dashboard', '/drms/admin/monitoring-logs',
                            '/drms/fuel-logs'
                        ];

                        if (masterPaths.some(p => currentPath.startsWith(p))) this.openMaster = true;
                        if (operasionalPaths.some(p => currentPath.startsWith(p))) this.openOperasional = true;
                    },
                    toggleMaster() { this.openMaster = !this.openMaster; },
                    toggleOperasional() { this.openOperasional = !this.openOperasional; }
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

        document.getElementById('user-menu-button')?.addEventListener('click', () => {
            document.getElementById('user-dropdown').classList.toggle('hidden');
        });

        document.getElementById('notif-button')?.addEventListener('click', () => {
            document.getElementById('notif-dropdown')?.classList.toggle('hidden');
        });

        document.addEventListener('click', (e) => {
            const userBtn = document.getElementById('user-menu-button');
            const userDropdown = document.getElementById('user-dropdown');
            const notifBtn = document.getElementById('notif-button');
            const notifDropdown = document.getElementById('notif-dropdown');

            if (userBtn && !userBtn.contains(e.target) && userDropdown && !userDropdown.contains(e.target)) {
                userDropdown.classList.add('hidden');
            }
            if (notifBtn && !notifBtn.contains(e.target) && notifDropdown && !notifDropdown.contains(e.target)) {
                notifDropdown.classList.add('hidden');
            }
        });

        window.addEventListener('resize', () => {
            if(window.innerWidth >= 768){
                sidebarEl.classList.remove('active');
                overlayEl.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        document.querySelectorAll('.sidebar-link').forEach(link => {
            link.addEventListener('click', () => {
                if(window.innerWidth < 768){
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