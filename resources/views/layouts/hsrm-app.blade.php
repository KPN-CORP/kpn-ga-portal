<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>HSR Management - @yield('title')</title>
    <link rel="shortcut icon" href="{{ asset('KPN123.png') }}" type="image/x-icon">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <!-- Bootstrap CSS (for modal) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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
        [x-cloak] { display: none !important; }

        /* Status badge */
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-verified { background: #d1fae5; color: #059669; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
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
                    <p class="text-sm text-gray-500 opacity-80">HSR Management</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="p-4">
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('dashboard') }}" 
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="fas fa-home mr-3 text-gray-500 opacity-70"></i>
                            <span>Dashboard GA</span>
                        </a>
                    </li>
                    <li class="mt-4 mb-2 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Main Menu</li>
                    <li>
                        <a href="{{ route('hsrm.dashboard') }}"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('hsrm.dashboard') ? 'active' : '' }}">
                            <i class="fas fa-home mr-3 text-gray-500 opacity-70"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('hsrm.certificates.index') }}"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('hsrm.certificates.*') ? 'active' : '' }}">
                            <i class="fas fa-file-alt mr-3 text-gray-500 opacity-70"></i>
                            <span>Certificates & Licence</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('hsrm.equipments.index') }}"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('hsrm.equipments.*') ? 'active' : '' }}">
                            <i class="fas fa-fire-extinguisher mr-3 text-gray-500 opacity-70"></i>
                            <span>Equipments</span>
                        </a>
                    </li>

                    @if(session('hsrm_role') === 'admin')
                    <li class="mt-4 mb-2 px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Admin</li>
                    <li>
                        <a href="{{ route('hsrm.approvals.index') }}"
                        class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('hsrm.approvals.*') ? 'active' : '' }}">
                            <i class="fas fa-check-double mr-3 text-gray-500 opacity-70"></i>
                            <span>Approvals</span>
                            @php
                                $pendingCount = \App\Models\HSRM\HsrmCertificate::where('status_verif','pending')->count()
                                                + \App\Models\HSRM\HsrmEquipment::where('status_verif','pending')->count();
                            @endphp
                            @if($pendingCount > 0)
                                <span class="ml-auto bg-red-500 text-white text-xs rounded-full px-2 py-0.5">{{ $pendingCount }}</span>
                            @endif
                        </a>
                    </li>

                    <!-- Management Dropdown -->
                    <li x-data="{ open: false }">
                        <a href="#" @click.prevent="open = !open" 
                        class="sidebar-link flex items-center p-3 rounded-lg text-gray-700">
                            <i class="fas fa-cogs mr-3 text-gray-500 opacity-70"></i>
                            <span>Management</span>
                            <i class="fas fa-chevron-down ml-auto text-xs" :class="open ? 'rotate-180' : ''"></i>
                        </a>
                        <ul x-show="open" x-cloak class="ml-4 space-y-1">
                            <li>
                                <a href="{{ route('hsrm.certificate-types.index') }}"
                                class="sidebar-link flex items-center p-2 rounded-lg text-gray-700 text-sm {{ request()->routeIs('hsrm.certificate-types.*') ? 'active' : '' }}">
                                    <i class="fas fa-tags mr-2 text-gray-400"></i> Certificate Types
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('hsrm.equipment-types.index') }}"
                                class="sidebar-link flex items-center p-2 rounded-lg text-gray-700 text-sm {{ request()->routeIs('hsrm.equipment-types.*') ? 'active' : '' }}">
                                    <i class="fas fa-fire-extinguisher mr-2 text-gray-400"></i> Equipment Types
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('hsrm.logs.index') }}"
                                class="sidebar-link flex items-center p-2 rounded-lg text-gray-700 text-sm {{ request()->routeIs('hsrm.logs.*') ? 'active' : '' }}">
                                    <i class="fas fa-history mr-2 text-gray-400"></i> Logs
                                </a>
                            </li>
                        </ul>
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
                            @if(session('hsrm_role') === 'admin')
                                Admin HSRM
                            @elseif(session('hsrm_role') === 'pic')
                                PIC
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
                        <button id="notif-button" class="relative text-gray-600 hover:text-gray-800 focus:outline-none">
                            <i class="fas fa-bell text-xl opacity-80"></i>
                            <!-- @php
                                $pendingTotal = \App\Models\HSRM\HsrmCertificate::where('status_verif','pending')->count()
                                                + \App\Models\HSRM\HsrmEquipment::where('status_verif','pending')->count();
                            @endphp
                            @if($pendingTotal > 0)
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full px-1.5 py-0.5">{{ $pendingTotal }}</span>
                            @endif -->
                        </button>
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
                                <i class="fas fa-sign-out-alt mr-3 text-gray-500 opacity-70"></i>Logout
                            </a>
                            <form id="logout-form-header" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="p-6">
                @if(session('success'))
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-lg">
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg">
                        {{ session('error') }}
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Modal File Viewer -->
    <div class="modal fade" id="fileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="fileViewer" style="min-height:400px; display:flex; align-items:center; justify-content:center;">
                        <p class="text-gray-500">Loading...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('alpine:init', () => {
            window.sidebarComponent = function() {
                return {
                    initSidebar() {}
                }
            }
        });

        // Sidebar toggle
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

        document.addEventListener('click', (e) => {
            const userBtn = document.getElementById('user-menu-button');
            const userDropdown = document.getElementById('user-dropdown');
            if (userBtn && !userBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.classList.add('hidden');
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

        function viewFile(url, fileType = null) {
            const viewer = document.getElementById('fileViewer');
            let ext = fileType;
            if (!ext) {
                ext = url.split('.').pop().toLowerCase();
            }
            if (['jpg','jpeg','png','gif','webp'].includes(ext)) {
                viewer.innerHTML = `<img src="${url}" class="img-fluid" style="max-height:80vh; width:auto;">`;
            } else if (ext === 'pdf') {
                viewer.innerHTML = `<iframe src="${url}" width="100%" height="600px" style="border:none;"></iframe>`;
            } else {
                viewer.innerHTML = `<a href="${url}" target="_blank" class="btn btn-primary">Download File</a>`;
            }
            const modal = new bootstrap.Modal(document.getElementById('fileModal'));
            modal.show();
        }
    </script>

    @stack('scripts')
</body>
</html>