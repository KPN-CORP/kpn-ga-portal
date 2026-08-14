<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- ANTI ZOOM MOBILE --}}
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <title>GA Portal - @yield('page-title', 'Dashboard')</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root{
            --accent: 59 130 246;      /* blue-500 */
            --accent-dark: 37 99 235;  /* blue-600 */
            --ink: 30 41 59;           /* slate-800 */
        }
        body { font-family: 'Inter', sans-serif; background-color: #f7f8fa; color: rgb(var(--ink)); }

        .soft-border { border-color: rgba(226,232,240,0.7) !important; }
        .soft-border-bottom { border-bottom-color: rgba(226,232,240,0.7) !important; }
        .soft-border-top { border-top-color: rgba(226,232,240,0.7) !important; }
        .soft-shadow { box-shadow: 0 1px 2px rgba(15,23,42,0.04), 0 4px 12px rgba(15,23,42,0.04); }
        .soft-shadow-sidebar { box-shadow: 1px 0 0 rgba(15,23,42,0.04), 6px 0 24px rgba(15,23,42,0.03); }

        /* Sidebar */
        .sidebar { transition: transform .3s cubic-bezier(.4,0,.2,1); border-right: 1px solid rgba(226,232,240,0.7); }
        @media (max-width: 1023px) { .sidebar { transform: translateX(-100%); } .sidebar.active { transform: translateX(0); } }

        .brand-badge{
            background: linear-gradient(135deg, rgba(var(--accent)/0.12), rgba(var(--accent)/0.04));
            border: 1px solid rgba(var(--accent)/0.15);
        }

        .nav-label{ letter-spacing:.08em; white-space:nowrap; }

        .sidebar-link{
            position: relative;
            display: flex;
            align-items: center;
            min-height: 2.75rem;
            padding: .55rem 1rem;
            border-radius: .5rem;
            color: #475569;
            transition: background-color .2s ease, color .2s ease, transform .2s ease;
        }
        .sidebar-link:hover{ background-color: rgba(248,250,252,0.9); color: rgb(var(--ink)); transform: translateX(2px); }
        .sidebar-link i{ width: 1.25rem; transition: color .2s ease; }
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

        .overlay{ display:none; position:fixed; inset:0; background: rgba(15,23,42,.25); backdrop-filter: blur(1px); z-index:30; }
        .overlay.active{ display:block; }

        .avatar-ring{
            background: linear-gradient(135deg, rgba(var(--accent)/0.14), rgba(var(--accent)/0.05));
            border: 1px solid rgba(var(--accent)/0.18);
        }

        /* Tombol mobile mengambang (pengganti header) */
        .mobile-toggle{
            position: fixed; top: 1rem; left: 1rem; z-index: 45;
            width: 2.75rem; height: 2.75rem; border-radius: .75rem;
            background: #fff; display:flex; align-items:center; justify-content:center;
            box-shadow: 0 2px 8px rgba(15,23,42,.12);
        }
        @media (min-width: 1024px){ .mobile-toggle{ display:none; } }

        /* Profile & logout popup */
        .profile-menu{
            position:absolute; left: .75rem; right: .75rem; bottom: calc(100% + .5rem);
            transition: opacity .18s ease, transform .18s ease;
        }

        ::-webkit-scrollbar{ width:6px; }
        ::-webkit-scrollbar-thumb{ background: rgba(148,163,184,.4); border-radius: 999px; }
    </style>
</head>

<body class="bg-gray-100 overflow-x-hidden">

<!-- ===== MOBILE OVERLAY ===== -->
<div id="sidebarOverlay" class="overlay lg:hidden" onclick="toggleSidebar()"></div>

<button onclick="toggleSidebar()" class="mobile-toggle text-gray-600">
    <i class="fas fa-bars text-lg"></i>
</button>

<!-- ===== SIDEBAR ===== -->
<aside id="sidebar"
       class="sidebar fixed top-0 left-0 h-screen w-64 bg-white soft-shadow-sidebar z-40
              -translate-x-full lg:translate-x-0 overflow-y-auto overflow-x-visible flex flex-col">

    <!-- Logo -->
    <div class="p-5 soft-border-bottom border-b flex items-center gap-2">
        <div class="flex items-center space-x-3 min-w-0">
            <div class="brand-badge w-10 h-10 rounded-xl flex items-center justify-center shrink-0">
                <i class="fas fa-building text-blue-600"></i>
            </div>
            <div class="min-w-0">
                <h1 class="text-lg font-bold text-gray-800 leading-tight truncate">GA Portal</h1>
                <p class="text-xs text-gray-500 tracking-wide">General Affairs</p>
            </div>
        </div>
    </div>

    <!-- Menu -->
    <nav class="p-3 pb-24 flex-1">
        <p class="mt-2 mb-1 px-3 text-[11px] font-semibold text-gray-400 uppercase nav-label">Menu</p>
        <ul class="space-y-1">
            <li>
                <a href="/dashboard" title="Beranda" class="sidebar-link {{ request()->is('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-house mr-3 text-gray-400"></i>
                    <span>Beranda</span>
                </a>
            </li>
            <li>
                <a href="/karyawan" title="Karyawan & Grup" class="sidebar-link {{ request()->is('karyawan*') ? 'active' : '' }}">
                    <i class="fas fa-users mr-3 text-gray-400"></i>
                    <span>Karyawan & Grup</span>
                </a>
            </li>
            <li>
                <a href="/rules" title="Limit & Aturan" class="sidebar-link {{ request()->is('rules*') ? 'active' : '' }}">
                    <i class="fas fa-sliders mr-3 text-gray-400"></i>
                    <span>Limit & Aturan</span>
                </a>
            </li>
            <li>
                <a href="/riwayat" title="Riwayat" class="sidebar-link {{ request()->is('riwayat*') ? 'active' : '' }}">
                    <i class="fas fa-clock-rotate-left mr-3 text-gray-400"></i>
                    <span>Riwayat</span>
                </a>
            </li>
            <li>
                <a href="/pembayaran" title="Pembayaran" class="sidebar-link {{ request()->is('pembayaran*') ? 'active' : '' }}">
                    <i class="fas fa-credit-card mr-3 text-gray-400"></i>
                    <span>Pembayaran</span>
                </a>
            </li>
            <li>
                <a href="/voucher" title="Kode Voucher" class="sidebar-link {{ request()->is('voucher*') ? 'active' : '' }}">
                    <i class="fas fa-ticket mr-3 text-gray-400"></i>
                    <span>Kode Voucher</span>
                </a>
            </li>
            <li>
                <a href="/setelan" title="Setelan" class="sidebar-link {{ request()->is('setelan*') ? 'active' : '' }}">
                    <i class="fas fa-gear mr-3 text-gray-400"></i>
                    <span>Setelan</span>
                </a>
            </li>
            <li>
                <a href="/bantuan" title="Pusat Bantuan" class="sidebar-link {{ request()->is('bantuan*') ? 'active' : '' }}">
                    <i class="fas fa-circle-question mr-3 text-gray-400"></i>
                    <span>Pusat Bantuan</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- User Profile + Logout -->
    <div class="p-3 soft-border-top border-t bg-white relative"
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
                <i class="fas fa-arrow-right-from-bracket mr-3 text-gray-500"></i>Keluar
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
                <p class="text-xs text-gray-500 truncate">Online</p>
            </div>
            <i class="fas fa-ellipsis-vertical text-gray-400 text-sm"></i>
        </button>
    </div>
</aside>

<!-- ===== MAIN WRAPPER ===== -->
<div class="lg:ml-64 min-h-screen flex flex-col">

    <!-- ===== PAGE CONTENT ===== -->
    <main class="flex-1 p-4 sm:p-6 pt-20 lg:pt-6 overflow-x-hidden">
        <h1 class="text-xl font-bold text-gray-800 mb-4">@yield('page-title', 'Dashboard')</h1>
        @yield('content')
    </main>

</div>

<!-- ===== SCRIPT ===== -->
<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar')
    const overlay = document.getElementById('sidebarOverlay')

    sidebar.classList.toggle('-translate-x-full')
    sidebar.classList.toggle('active')
    overlay.classList.toggle('active')
}
</script>

</body>
</html>