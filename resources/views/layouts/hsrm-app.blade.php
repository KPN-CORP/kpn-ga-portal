<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>HSR Management - @yield('title')</title>
    <link rel="shortcut icon" href="{{ asset('KPN123.png') }}" type="image/x-icon">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    <!-- Bootstrap CSS (for modal) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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

        /* Dropdown group - gaya panel navy seperti referensi */
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

    <button id="sidebar-toggle" class="mobile-toggle text-gray-600">
        <i class="fas fa-bars text-lg"></i>
    </button>

    @php
        // Status dropdown "Management" dihitung di server supaya tidak ada
        // flicker (menu sempat kelihatan terbuka/tertutup) saat halaman refresh.
        $managementActive = request()->routeIs([
            'hsrm.admin.quotas.*',
            'hsrm.certificate-types.*',
            'hsrm.equipment-types.*',
            'hsrm.logs.*',
        ]);
    @endphp

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="sidebar w-64 bg-white fixed h-full soft-shadow-sidebar overflow-y-auto overflow-x-visible"
               x-data="sidebarComponent({{ $managementActive ? 'true' : 'false' }})">

            <!-- Logo -->
            <div class="p-5 soft-border-bottom border-b flex items-center gap-2">
                <div class="flex items-center space-x-3 min-w-0">
                    <div class="brand-badge w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
                        <img src="{{ asset('KPN123.png') }}" alt="Logo" class="w-6 h-6">
                    </div>
                    <div class="min-w-0">
                        <h1 class="text-lg font-bold text-gray-800 leading-tight truncate">GA Portal</h1>
                        <p class="text-xs text-gray-500 tracking-wide">HSR Management</p>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="p-3 pb-24">
                <p class="mt-2 mb-1 px-3 text-[11px] font-semibold text-gray-400 uppercase nav-label">Navigation</p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('dashboard') }}" title="Dashboard GA"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="fas fa-home w-5 mr-3 text-gray-400"></i>
                            <span>Dashboard GA</span>
                        </a>
                    </li>
                </ul>

                <p class="mt-5 mb-1 px-3 text-[11px] font-semibold text-gray-400 uppercase nav-label">Main Menu</p>
                <ul class="space-y-1">
                    <li>
                        <a href="{{ route('hsrm.dashboard') }}" title="Dashboard"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('hsrm.dashboard') ? 'active' : '' }}">
                            <i class="fas fa-home w-5 mr-3 text-gray-400"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('hsrm.certificates.index') }}" title="Certificates & Licence"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('hsrm.certificates.*') ? 'active' : '' }}">
                            <i class="fas fa-file-alt w-5 mr-3 text-gray-400"></i>
                            <span>Certificates & Licence</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('hsrm.equipments.index') }}" title="Equipments"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 {{ request()->routeIs('hsrm.equipments.*') ? 'active' : '' }}">
                            <i class="fas fa-fire-extinguisher w-5 mr-3 text-gray-400"></i>
                            <span>Equipments</span>
                        </a>
                    </li>

                    {{-- ============================================================ --}}
                    {{-- MENU APPROVALS – tampil jika admin ATAU punya hak approve di area --}}
                    {{-- ============================================================ --}}
                    @php
                        $user = auth()->user();
                        $isAdmin = session('hsrm_role') === 'admin';
                        $canApprove = $isAdmin || $user->hsrmUserRoles()->where('can_approve', true)->exists();

                        // Hitung pending hanya untuk area yang bisa di-approve (jika bukan admin)
                        $pendingCertQuery = \App\Models\HSRM\HsrmCertificate::where('status_verif', 'pending');
                        $pendingEqQuery   = \App\Models\HSRM\HsrmEquipment::where('status_verif', 'pending');

                        if (!$isAdmin && $canApprove) {
                            $areaIds = $user->hsrmUserRoles()->where('can_approve', true)->pluck('area_id')->toArray();
                            $pendingCertQuery->whereIn('area_id', $areaIds);
                            $pendingEqQuery->whereIn('area_id', $areaIds);
                        }
                        $pendingCount = $pendingCertQuery->count() + $pendingEqQuery->count();
                    @endphp

                    @if($canApprove)
                    <li>
                        <a href="{{ route('hsrm.approvals.index') }}" title="Approvals"
                           class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 relative {{ request()->routeIs('hsrm.approvals.*') ? 'active' : '' }}">
                            <i class="fas fa-check-double w-5 mr-3 text-gray-400"></i>
                            <span>Approvals</span>
                            @if($pendingCount > 0)
                                <span class="badge-count ml-auto text-white text-xs rounded-full px-2 py-0.5">{{ $pendingCount }}</span>
                            @endif
                        </a>
                    </li>
                    @endif
                </ul>

                {{-- ============================================================ --}}
                {{-- MENU MANAGEMENT – hanya untuk admin (system settings) --}}
                {{-- ============================================================ --}}
                @if($isAdmin)
                <p class="mt-5 mb-1 px-3 text-[11px] font-semibold text-gray-400 uppercase nav-label">Admin</p>
                <ul class="space-y-1">
                    <li class="nav-group" :class="{ open: openGroup === 'management' }">
                        <div @click="toggleGroup('management')" title="Management"
                             class="dropdown-toggle flex items-center justify-between p-3 rounded-lg text-gray-700">
                            <div class="flex items-center">
                                <i class="fas fa-cogs w-5 mr-3 text-gray-400"></i>
                                <span class="font-medium">Management</span>
                            </div>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </div>
                        <div class="dropdown-content" :class="openGroup === 'management' ? 'open' : 'closed'">
                            <ul class="dropdown-child space-y-1 pb-2">
                                <li>
                                    <a href="{{ route('hsrm.admin.quotas.index') }}"
                                       class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('hsrm.admin.quotas.*') ? 'active' : '' }}">
                                        <i class="fas fa-chart-pie w-4 mr-3"></i>
                                        <span>Budget & Quota</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('hsrm.certificate-types.index') }}"
                                       class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('hsrm.certificate-types.*') ? 'active' : '' }}">
                                        <i class="fas fa-tags w-4 mr-3"></i>
                                        <span>Certificate Types</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('hsrm.equipment-types.index') }}"
                                       class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('hsrm.equipment-types.*') ? 'active' : '' }}">
                                        <i class="fas fa-fire-extinguisher w-4 mr-3"></i>
                                        <span>Equipment Types</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="{{ route('hsrm.logs.index') }}"
                                       class="sidebar-link flex items-center rounded-lg {{ request()->routeIs('hsrm.logs.*') ? 'active' : '' }}">
                                        <i class="fas fa-history w-4 mr-3"></i>
                                        <span>Logs</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>
                </ul>
                @endif
            </nav>

            <!-- User Profile + Logout -->
            <div class="absolute bottom-0 left-0 right-0 p-3 soft-border-top border-t bg-white"
                 x-data="{ openProfile: false }" @click.outside="openProfile = false">

                <div x-show="openProfile" x-transition x-cloak
                     class="profile-menu bg-white soft-shadow rounded-lg soft-border border py-2 z-50">
                    <div class="px-4 py-2 text-sm text-gray-500 soft-border-bottom border-b">
                        <p class="font-medium text-gray-700">{{ auth()->user()->name ?? '-' }}</p>
                        <p class="text-xs">{{ auth()->user()->email ?? '-' }}</p>
                    </div>
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form-profile').submit();"
                       class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 transition">
                        <i class="fas fa-sign-out-alt mr-3 text-gray-500"></i>Logout
                    </a>
                    <form id="logout-form-profile" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
                </div>

                <button @click="openProfile = !openProfile" class="w-full flex items-center space-x-3 p-1 rounded-lg hover:bg-gray-50 transition">
                    <div class="avatar-ring w-10 h-10 rounded-full flex items-center justify-center shrink-0">
                        <span class="font-semibold text-blue-600 text-sm">
                            {{ strtoupper(substr(auth()->user()->name ?? 'AD', 0, 2)) }}
                        </span>
                    </div>
                    <div class="min-w-0 text-left flex-1">
                        <h3 class="font-medium text-gray-800 truncate">{{ auth()->user()->name ?? 'User' }}</h3>
                        <p class="text-xs text-gray-500 truncate">
                            @if(session('hsrm_role') === 'admin')
                                Admin HSRM
                            @elseif(session('hsrm_role') === 'pic')
                                PIC
                            @else
                                User
                            @endif
                        </p>
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
            window.sidebarComponent = function(managementActive) {
                return {
                    // Accordion: hanya satu group yang bisa terbuka ('management' | null)
                    openGroup: managementActive ? 'management' : null,
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