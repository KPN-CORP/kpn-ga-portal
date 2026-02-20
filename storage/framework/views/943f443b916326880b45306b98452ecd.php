<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title>GA Portal</title>
    <link rel="shortcut icon" href="<?php echo e(asset('KPN123.png')); ?>" type="image/x-icon">

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Custom Styles: Soft Borders & Shadows -->
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb; /* Soft gray */
        }

        /* Soft border helpers */
        .soft-border { border-color: rgba(229,231,235,0.5) !important; }
        .soft-border-top { border-top-color: rgba(229,231,235,0.5) !important; }
        .soft-border-bottom { border-bottom-color: rgba(229,231,235,0.5) !important; }
        .soft-border-left { border-left-color: rgba(229,231,235,0.5) !important; }
        .soft-border-right { border-right-color: rgba(229,231,235,0.5) !important; }

        /* Soft shadow helpers */
        .soft-shadow { box-shadow: 0 1px 3px rgba(0,0,0,0.05), 0 1px 2px rgba(0,0,0,0.03); }
        .soft-shadow-sidebar { box-shadow: 1px 0 8px rgba(0,0,0,0.03), 2px 0 4px rgba(0,0,0,0.01); }

        /* Sidebar links */
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

        /* Header & Footer */
        .header-soft {
            border-bottom: 1px solid rgba(229,231,235,0.4);
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(8px);
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .footer-soft { border-top: 1px solid rgba(229,231,235,0.4); }

        /* Cards / content boxes */
        .card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        /* Overlay for mobile */
        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background-color: rgba(0,0,0,0.15);
            z-index: 50;
        }
        .overlay.active { display: block; }

        /* Sidebar mobile */
        .sidebar {
            transition: transform 0.3s cubic-bezier(0.4,0,0.2,1);
        }
        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); z-index: 60; }
            .sidebar.active { transform: translateX(0); }
        }

        /* Smooth transitions */
        .smooth-transition { transition: all 0.25s cubic-bezier(0.4,0,0.2,1); }
    </style>

    <?php echo $__env->yieldContent('head'); ?>
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body>

    <!-- Mobile Overlay -->
    <div id="overlay" class="overlay"></div>

    <div class="flex min-h-screen">

        <!-- Sidebar -->
        <aside class="sidebar w-64 bg-white fixed h-full soft-shadow-sidebar">
            <!-- Logo -->
            <div class="p-6 soft-border-bottom flex items-center space-x-3">
                <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center soft-border">
                    <img src="<?php echo e(asset('KPN123.png')); ?>" alt="Logo" class="w-6 h-6 opacity-90">
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-800">GA Portal</h1>
                    <p class="text-sm text-gray-500 opacity-80">General Affairs</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="p-4">
                <ul class="space-y-1">
                    <li>
                        <a href="<?php echo e(route('dashboard')); ?>" class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 <?php echo e(request()->is('dashboard') ? 'active' : ''); ?>">
                            <i class="fas fa-home mr-3 text-gray-500 opacity-70"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo e(url('/informasi')); ?>" class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 <?php echo e(request()->is('informasi*') ? 'active' : ''); ?>">
                            <i class="fas fa-box mr-3 text-gray-500 opacity-70"></i>
                            <span>Informasi</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo e(url('/no-access')); ?>" class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 <?php echo e(request()->is('maintenance*') ? 'active' : ''); ?>">
                            <i class="fas fa-tools mr-3 text-gray-500 opacity-70"></i>
                            <span>Maintenance</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo e(url('/no-access')); ?>" class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 <?php echo e(request()->is('reports*') ? 'active' : ''); ?>">
                            <i class="fas fa-chart-bar mr-3 text-gray-500 opacity-70"></i>
                            <span>Laporan</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo e(url('/no-access')); ?>" class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 <?php echo e(request()->is('settings*') ? 'active' : ''); ?>">
                            <i class="fas fa-cog mr-3 text-gray-500 opacity-70"></i>
                            <span>Pengaturan</span>
                        </a>
                    </li>
                </ul>

                <div class="my-6 soft-border-top"></div>

                <ul class="space-y-1">
                    <li>
                        <a href="<?php echo e(url('/help/tiket')); ?>" class="sidebar-link flex items-center p-3 rounded-lg text-gray-700 <?php echo e(request()->is('help*') ? 'active' : ''); ?>">
                            <i class="fas fa-question-circle mr-3 text-gray-500 opacity-70"></i>
                            <span>Bantuan & Feedback</span>
                        </a>
                    </li>
                </ul>
            </nav>

            <!-- User Profile -->
            <div class="absolute bottom-0 left-0 right-0 p-4 soft-border-top bg-white">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center soft-border">
                        <span class="font-semibold text-blue-600 opacity-90">
                            <?php echo e(strtoupper(substr(auth()->user()->username ?? 'AD', 0, 2))); ?>

                        </span>
                    </div>
                    <div>
                        <h3 class="font-medium text-gray-800"><?php echo e(auth()->user()->username ?? 'User'); ?></h3>
                        <p class="text-xs text-gray-500 opacity-70"><?php echo e(auth()->user()->role ?? 'Online'); ?></p>
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
                        <?php if (! empty(trim($__env->yieldContent('breadcrumb')))): ?>
                            <div class="text-sm text-gray-500 opacity-80 mt-1">
                                <?php echo $__env->yieldContent('breadcrumb'); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex items-center space-x-4">
                    <!-- Notifications -->
                    <div class="relative">
                        <button id="notif-button" class="relative text-gray-600 hover:text-gray-800 focus:outline-none smooth-transition">
                            <i class="fas fa-bell text-xl opacity-80"></i>
                            <?php if(isset($unreadNotifications) && $unreadNotifications > 0): ?>
                                <span class="absolute -top-1 -right-1 bg-red-400 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center soft-shadow">
                                    <?php echo e($unreadNotifications); ?>

                                </span>
                            <?php endif; ?>
                        </button>
                        <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-80 bg-white soft-shadow rounded-lg soft-border py-2 z-50">
                            <div class="px-4 py-2 soft-border-bottom">
                                <h3 class="font-medium text-gray-800">Notifikasi</h3>
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                <div class="px-4 py-3 hover:bg-gray-50">
                                    <p class="text-sm text-gray-700">Tidak ada notifikasi baru</p>
                                </div>
                            </div>
                            <a href="<?php echo e(url('/notifications')); ?>" class="block px-4 py-2 text-sm text-center text-blue-500 hover:bg-gray-50 soft-border-top">
                                Lihat Semua
                            </a>
                        </div>
                    </div>

                    <!-- User Dropdown -->
                    <div class="relative">
                        <button id="user-menu-button" class="flex items-center space-x-2 focus:outline-none smooth-transition hover:text-gray-800">
                            <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center soft-border">
                                <span class="font-semibold text-blue-600 text-sm opacity-90">
                                    <?php echo e(strtoupper(substr(auth()->user()->name ?? 'AD', 0, 2))); ?>

                                </span>
                            </div>
                            <span class="hidden md:inline text-gray-700 opacity-90"><?php echo e(auth()->user()->name ?? 'Admin'); ?></span>
                            <i class="fas fa-chevron-down text-gray-500 text-sm opacity-70"></i>
                        </button>
                        <div id="user-dropdown" class="hidden absolute right-0 mt-2 w-48 bg-white soft-shadow rounded-lg soft-border py-2 z-50">
                            <!-- <a href="<?php echo e(url('/profile')); ?>" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 smooth-transition">
                                <i class="fas fa-user mr-3 text-gray-500 opacity-70"></i>Profil Saya
                            </a> -->
                            <div class="px-4 py-2 text-sm text-gray-500 soft-border-bottom">
                                <p><?php echo e(auth()->user()->name ?? '-'); ?></p>
                                <p class="text-xs"><?php echo e(auth()->user()->email ?? '-'); ?></p>
                            </div>
                            <div class="soft-border-top my-1"></div>
                            <a href="<?php echo e(route('logout')); ?>" onclick="event.preventDefault(); document.getElementById('logout-form-header').submit();" class="flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 smooth-transition">
                                <i class="fas fa-sign-out-alt mr-3 text-gray-500 opacity-70"></i>Keluar
                            </a>
                            <form id="logout-form-header" action="<?php echo e(route('logout')); ?>" method="POST" class="hidden"><?php echo csrf_field(); ?></form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="p-6">
                <?php echo $__env->yieldContent('content'); ?>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        const sidebar = document.querySelector('.sidebar');
        const overlay = document.getElementById('overlay');

        document.getElementById('sidebar-toggle').addEventListener('click', () => {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        });

        document.getElementById('user-menu-button').addEventListener('click', () => {
            document.getElementById('user-dropdown').classList.toggle('hidden');
        });

        document.getElementById('notif-button').addEventListener('click', () => {
            document.getElementById('notif-dropdown').classList.toggle('hidden');
        });

        document.addEventListener('click', (e) => {
            const userBtn = document.getElementById('user-menu-button');
            const userDropdown = document.getElementById('user-dropdown');
            const notifBtn = document.getElementById('notif-button');
            const notifDropdown = document.getElementById('notif-dropdown');

            if (!userBtn.contains(e.target) && !userDropdown.contains(e.target)) userDropdown.classList.add('hidden');
            if (!notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) notifDropdown.classList.add('hidden');
        });

        // Close sidebar on resize
        window.addEventListener('resize', () => {
            if(window.innerWidth >= 768){
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            }
        });

        // Close sidebar on link click (mobile)
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

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /var/www/html/ga-portal/resources/views/layouts/app-sidebar.blade.php ENDPATH**/ ?>