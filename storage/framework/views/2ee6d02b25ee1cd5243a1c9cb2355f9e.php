<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startSection('head'); ?>
<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    :root {
        --primary-color: #2c3e50;
        --secondary-color: #3498db;
        --success-color: #27ae60;
        --warning-color: #f39c12;
        --danger-color: #e74c3c;
        --light-color: #ecf0f1;
        --dark-color: #2c3e50;
        --admin-color: #800a0aff;
        --stock-color: #e67e22; /* Warna untuk Management Stok */
        --memo-color: #8e44ad; /* Warna untuk E-Memo */
    }
    
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100vh;
        padding: 15px;
    }
    
    .dashboard-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    /* Header Styles */
    .dashboard-header {
        background: linear-gradient(90deg, var(--primary-color), #4a6491);
        border-radius: 15px;
        padding: 20px;
        color: white;
        margin-bottom: 25px;
        box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        position: relative;
        overflow: hidden;
    }
    
    .access-badge {
        display: inline-block;
        background: rgba(255,255,255,0.2);
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 0.8rem;
        margin-left: 8px;
        backdrop-filter: blur(10px);
    }
    
    /* Menu Grid Styles - Kartu lebih kecil */
    .menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 18px;
        padding: 5px 0;
    }
    
    .menu-item {
        background: white;
        border-radius: 15px;
        padding: 20px 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        border: 2px solid transparent;
        position: relative;
        overflow: hidden;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    
    .menu-item::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, var(--secondary-color), var(--success-color));
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }
    
    .menu-item.admin-item::before {
        background: linear-gradient(90deg, var(--admin-color), #9b59b6);
    }
    
    .menu-item:hover {
        transform: translateY(-5px) scale(1.02);
        box-shadow: 0 10px 25px rgba(52, 152, 219, 0.15);
        border-color: var(--secondary-color);
    }
    
    .menu-item.admin-item:hover {
        border-color: var(--admin-color);
        box-shadow: 0 10px 25px rgba(142, 68, 173, 0.15);
    }
    
    .menu-item:hover::before {
        transform: scaleX(1);
    }
    
    .icon-box {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        margin: 0 auto 12px;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .menu-item:hover .icon-box {
        transform: scale(1.08) rotate(3deg);
    }
    
    .icon-bg {
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        opacity: 0.1;
    }
    
    .icon-svg {
        width: 28px;
        height: 28px;
        position: relative;
        z-index: 2;
    }
    
    .menu-title {
        font-size: 1rem;
        font-weight: 600;
        color: var(--dark-color);
        margin-bottom: 6px;
    }
    
    .menu-desc {
        font-size: 0.75rem;
        color: #7f8c8d;
        line-height: 1.3;
        max-height: 2.6em;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    
    /* Admin Badge */
    .admin-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        background: var(--admin-color);
        color: white;
        font-size: 0.6rem;
        padding: 2px 8px;
        border-radius: 10px;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    /* Section Title */
    .section-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--dark-color);
        margin: 25px 0 15px;
        padding-bottom: 8px;
        border-bottom: 2px solid #eee;
        position: relative;
    }
    
    .section-title::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        width: 60px;
        height: 2px;
        background: var(--secondary-color);
    }
    
    .section-title.admin-title::after {
        background: var(--admin-color);
    }
    
    /* No Access Styles */
    .no-access-container {
        grid-column: 1 / -1;
        text-align: center;
        padding: 40px 20px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    
    .no-access-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }
    
    /* Animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .menu-item {
        animation: fadeIn 0.4s ease forwards;
    }
    
    /* Logout Button */
    .logout-btn {
        background: rgba(255,255,255,0.2);
        border: 1px solid rgba(255,255,255,0.3);
        color: white;
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        cursor: pointer;
    }
    
    .logout-btn:hover {
        background: rgba(255,255,255,0.3);
        transform: translateY(-2px);
        text-decoration: none;
        color: white;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        body {
            padding: 10px;
        }
        
        .menu-grid {
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 15px;
        }
        
        .menu-item {
            padding: 15px 10px;
        }
        
        .icon-box {
            width: 50px;
            height: 50px;
            margin-bottom: 10px;
        }
        
        .icon-svg {
            width: 24px;
            height: 24px;
        }
        
        .menu-title {
            font-size: 0.9rem;
        }
        
        .menu-desc {
            font-size: 0.7rem;
        }
        
        .dashboard-header {
            padding: 15px;
        }
        
        .admin-badge {
            font-size: 0.5rem;
            padding: 1px 6px;
        }
    }
    
    @media (max-width: 576px) {
        .menu-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
        }
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div class="dashboard-container">
    <!-- Header Dashboard -->
    <div class="dashboard-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3 mb-2 fw-bold">Selamat Datang, <span class="text-warning"><?php echo e(Auth::user()->name ?? Auth::user()->username); ?></span>!</h1>
                <?php if(isset($access) && $access && $access->bu_access && $access->bu_access != '1'): ?>
                <div class="d-flex align-items-center flex-wrap">
                    <p class="mb-0 opacity-90 me-2">
                        <i class="fas fa-building me-1"></i><?php echo e($access->bu_access); ?>

                    </p>
                    <?php if(isset($totalAccess) && $totalAccess > 0): ?>
                    <span class="access-badge">
                        <i class="fas fa-key me-1"></i><?php echo e($totalAccess); ?> Modul
                    </span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="col-md-4 text-md-end mt-2 mt-md-0">
                <div class="d-flex flex-column align-items-md-end">
                    <div class="text-white opacity-90 mb-2">
                        <i class="fas fa-calendar-alt me-1"></i>
                        <span id="current-date"><?php echo e(date('l, d F Y')); ?></span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-white opacity-80">
                            <i class="fas fa-user me-1"></i><?php echo e(Auth::user()->username); ?>

                        </small>
                        <!-- Form Logout -->
                        <form action="<?php echo e(route('logout')); ?>" method="POST" id="logout-form">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="logout-btn">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Definisi menu utama - TAMBAHKAN 2 MENU BARU
    $menus = [
        'messenger' => [
            'field' => 'messenger_dash',
            'title' => 'Messenger',
            'desc' => 'Paket dalam Kota',
            'url' => '/messenger',
            'color' => '#3498db',
            'icon' => 'M9 20l-5.447-2.724A1 1 0 013 16.382V6.618a1 1 0 01.553-.894L9 3m0 17l6 3m-6-3V3m6 20l5.447-2.724A1 1 0 0021 18.382V8.618a1 1 0 00-.553-.894L15 3m0 20V3'
        ],
        'mailing' => [
            'field' => 'ma_room_dash',
            'title' => 'Mailing Room',
            'desc' => 'Penerimaan Paket',
            'url' => '/mailing',
            'color' => '#2ecc71',
            'icon' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'
        ],
        'trackreceipt' => [
            'field' => 'receipt_dash',
            'title' => 'Track Receipt',
            'desc' => 'Lacak dokumen',
            'url' => '/track-r',
            'color' => '#9b59b6',
            'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'
        ],
        'idcard' => [
            'field' => 'idcard_dash',
            'title' => 'ID Card',
            'desc' => 'Kelola ID Card',
            'url' => '/idcard',
            'color' => '#e74c3c',
            'icon' => 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2'
        ],
        'car' => [
            'field' => 'car_dash',
            'title' => 'Car Service',
            'desc' => 'Layanan mobil',
            'url' => '#',
            'color' => '#16a085',
            'icon' => 'M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z M21 12a9 9 0 11-18 0 9 9 0 0118 0z'
        ],
        'apart' => [
            'field' => 'apart_dash',
            'title' => 'Apartemen',
            'desc' => 'Kelola apartemen',
            'url' => 'apartemen',
            'color' => '#d35400',
            'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'
        ],
        'receptionist' => [
            'field' => 'receptionist_dash',
            'title' => 'Receptionist',
            'desc' => 'Front office',
            'url' => '#',
            'color' => '#8e44ad',
           'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5'
        ],
        'helpdesk' => [
            'field' => 'helpdest_dash',
            'title' => 'GA Helpdesk',
            'desc' => 'Bantuan GA',
            'url' => '/help/tiket',
            'color' => '#2980b9',
            'icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z'
        ],
        'employees' => [
            'field' => 'employees_dash',
            'title' => 'Employees',
            'desc' => 'Data karyawan',
            'url' => '/employees',
            'color' => '#f39c12',
            'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'
        ],
        'reports' => [
            'field' => 'reports_dash',
            'title' => 'Reports',
            'desc' => 'Laporan & statistik',
            'url' => '/reports',
            'color' => '#1abc9c',
            'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z'
        ],
        // MENU MANAGEMENT STOK - BARU
        'stock' => [
            'field' => 'stock_dash',
            'title' => 'Stock Control',
            'desc' => 'Kelola stok barang',
            'url' => '/stock/user',
            'color' => '#e67e22',
            'icon' => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4'
        ],
        // MENU E-MEMO - BARU
        'ememo' => [
            'field' => 'ememo_dash',
            'title' => 'E-Memo',
            'desc' => 'Memo elektronik',
            'url' => '/ememo',
            'color' => '#8e44ad',
            'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
        ]
    ];
    
    // Menu Admin - TAMBAHKAN JUGA VERSI ADMIN JIKA DIPERLUKAN
    $adminMenus = [
        'messenger_admin' => [
            'field' => 'messenger_admin_dash',
            'title' => 'GA Messenger',
            'desc' => 'Pengiriman',
            'url' => '/messenger/proses',
            'color' => '#3498db',
            'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'
        ],
        'mailing_admin' => [
            'field' => 'maroom_admin_dash',
            'title' => 'GA Mailing Room',
            'desc' => 'Admin Penerimaan',
            'url' => '/mailing/proses',
            'color' => '#2ecc71',
            'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'
        ],
        'apart_admin' => [
            'field' => 'apart_admin_dash',
            'title' => 'GA Apartemen',
            'desc' => 'Admin apartemen',
            'url' => '/apartemen/admin',
            'color' => '#d35400',
            'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'
        ],
        'car_admin' => [
            'field' => 'car_admin_dash',
            'title' => 'GA Car Service',
            'desc' => 'Admin mobil',
            'url' => '/car/admin',
            'color' => '#16a085',
            'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'
        ],
        'helpdesk_admin' => [
            'field' => 'helpdesk_admin_dash',
            'title' => 'GA Helpdesk',
            'desc' => 'Admin bantuan',
            'url' => '/help/proses',
            'color' => '#2980b9',
            'icon' => 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z'
        ],
        // ADMIN MANAGEMENT STOK - OPSIONAL
        'stock_admin' => [
            'field' => 'stock_admin_dash',
            'title' => 'GA Stock Control',
            'desc' => 'Admin stok barang',
            'url' => '/stock/admin',
            'color' => '#e67e22',
            'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'
        ],
        // ADMIN E-MEMO - OPSIONAL
        'ememo_admin' => [
            'field' => 'ememo_admin_dash',
            'title' => 'GA E-Memo',
            'desc' => 'Admin memo elektronik',
            'url' => '/ememo/admin',
            'color' => '#8e44ad',
            'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'
        ]
    ];
    
    // Hitung total akses (termasuk admin) untuk ditampilkan di badge
    $totalAccess = 0;
    $hasMainAccess = false;
    $hasAdminAccess = false;
    $delayCounter = 0.1;
    
    if(isset($access) && $access) {
        // Cek akses menu utama
        foreach($menus as $menu) {
            if(isset($access->{$menu['field']}) && $access->{$menu['field']} == 1) {
                $hasMainAccess = true;
                $totalAccess++;
            }
        }
        
        // Cek akses menu admin
        foreach($adminMenus as $menu) {
            if(isset($access->{$menu['field']}) && $access->{$menu['field']} == 1) {
                $hasAdminAccess = true;
                $totalAccess++;
            }
        }
    }
    ?>
    
    <?php if(isset($access) && $access): ?>
        <!-- Section Menu Utama (jika ada akses menu utama) -->
        <?php if($hasMainAccess): ?>
            <h3 class="section-title">Modul</h3>
            <div class="menu-grid">
                <?php $__currentLoopData = $menus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $menu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if(isset($access->{$menu['field']}) && $access->{$menu['field']} == 1): ?>
                        <?php 
                        $animationDelay = $delayCounter . 's';
                        $delayCounter += 0.05;
                        ?>
                        <div class="menu-item" onclick="window.location.href='<?php echo e($menu['url']); ?>'" style="animation-delay: <?php echo e($animationDelay); ?>;">
                            <div class="icon-box">
                                <div class="icon-bg" style="background: <?php echo e($menu['color']); ?>;"></div>
                                <svg class="icon-svg" fill="none" stroke="<?php echo e($menu['color']); ?>" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo e($menu['icon']); ?>"/>
                                </svg>
                            </div>
                            <h3 class="menu-title"><?php echo e($menu['title']); ?></h3>
                            <p class="menu-desc"><?php echo e($menu['desc']); ?></p>
                        </div>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
        
        <!-- Section Admin (jika ada akses admin) -->
        <?php if($hasAdminAccess): ?>
            <h3 class="section-title admin-title">Admin</h3>
            <div class="menu-grid">
                <?php $__currentLoopData = $adminMenus; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $menu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if(isset($access->{$menu['field']}) && $access->{$menu['field']} == 1): ?>
                        <?php 
                        $animationDelay = $delayCounter . 's';
                        $delayCounter += 0.05;
                        ?>
                        <div class="menu-item admin-item" onclick="window.location.href='<?php echo e($menu['url']); ?>'" style="animation-delay: <?php echo e($animationDelay); ?>;">
                            <div class="admin-badge">Admin</div>
                            <div class="icon-box">
                                <div class="icon-bg" style="background: <?php echo e($menu['color']); ?>;"></div>
                                <svg class="icon-svg" fill="none" stroke="<?php echo e($menu['color']); ?>" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="<?php echo e($menu['icon']); ?>"/>
                                </svg>
                            </div>
                            <h3 class="menu-title"><?php echo e($menu['title']); ?></h3>
                            <p class="menu-desc"><?php echo e($menu['desc']); ?></p>
                        </div>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php endif; ?>
        
        <!-- Tampilkan "Tidak Ada Akses" hanya jika TIDAK ADA akses sama sekali -->
        <?php if(!$hasMainAccess && !$hasAdminAccess): ?>
            <div class="no-access-container">
                <div class="no-access-icon">
                    <svg width="40" height="40" fill="none" stroke="#95a5a6" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h3 class="h4 mb-3 text-gray-700">Tidak Ada Akses</h3>
                <p class="text-muted mb-3">Anda tidak memiliki akses ke modul apapun.</p>
                <a href="mailto:sudetlin.sugito@kpn-corp.com" class="btn btn-sm btn-primary">
                    <i class="fas fa-envelope me-1"></i>Hubungi GA Portal
                </a>
            </div>
        <?php endif; ?>
        
    <?php else: ?>
        <!-- Jika data akses tidak ditemukan -->
        <div class="no-access-container">
            <div class="no-access-icon">
                <svg width="40" height="40" fill="none" stroke="#e74c3c" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="h4 mb-3 text-gray-700">Profil Akses Tidak Ditemukan</h3>
            <p class="text-muted mb-3">Data akses tidak ditemukan di sistem.</p>
            <div class="alert alert-warning mb-3 py-2">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Silakan hubungi administrator.
            </div>
            <button onclick="window.location.reload()" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-redo me-1"></i>Refresh
            </button>
        </div>
    <?php endif; ?>
    
    <!-- Footer -->
    <div class="mt-4 pt-3 border-top text-center text-muted">
        <p class="mb-1 small">GA Portal &copy; <?php echo e(date('Y')); ?> - KPN Corporate</p>
        <small class="text-muted">v3.0.0</small>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Update date and time
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const dateStr = now.toLocaleDateString('id-ID', options);
            document.getElementById('current-date').textContent = dateStr;
        }
        
        updateDateTime();
        setInterval(updateDateTime, 60000);
        
        // Efek klik pada menu item
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            item.addEventListener('click', function(e) {
                // Efek ripple
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(52, 152, 219, 0.3);
                    transform: scale(0);
                    animation: ripple 0.5s linear;
                    width: ${size}px;
                    height: ${size}px;
                    top: ${y}px;
                    left: ${x}px;
                    pointer-events: none;
                `;
                
                // Admin item dengan warna berbeda
                if (this.classList.contains('admin-item')) {
                    ripple.style.background = 'rgba(142, 68, 173, 0.3)';
                }
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                    const onclickAttr = this.getAttribute('onclick');
                    const match = onclickAttr.match(/'(.*?)'/);
                    if (match && match[1]) {
                        window.location.href = match[1];
                    }
                }, 200);
            });
        });
        
        // Animasi ripple CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(3);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
        
        // Konfirmasi logout
        const logoutForm = document.getElementById('logout-form');
        if (logoutForm) {
            logoutForm.addEventListener('submit', function(e) {
                if (!confirm('Anda yakin ingin logout?')) {
                    e.preventDefault();
                }
            });
        }
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/ga-portal/resources/views/dashboard.blade.php ENDPATH**/ ?>