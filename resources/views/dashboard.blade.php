@extends('layouts.app')

@section('title', 'Dashboard')

@section('head')
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
        --stock-color: #e67e22;
        --memo-color: #8e44ad;
        --found-color: #00bcd4;
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
    
    .menu-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
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
        margin-bottom: 0;
    }
    
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
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .menu-item {
        animation: fadeIn 0.4s ease forwards;
    }
    
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
    
    @media (max-width: 768px) {
        body {
            padding: 10px;
        }
        .menu-grid {
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
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
@endsection

@section('content')
<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3 mb-2 fw-bold">Selamat Datang, <span class="text-warning">{{ Auth::user()->name ?? Auth::user()->username }}</span>!</h1>
                @if(isset($access) && $access && $access->bu_access && $access->bu_access != '1')
                <div class="d-flex align-items-center flex-wrap">
                    <p class="mb-0 opacity-90 me-2">
                        <i class="fas fa-building me-1"></i>{{ $access->bu_access }}
                    </p>
                    @if(isset($totalAccess) && $totalAccess > 0)
                    <span class="access-badge">
                        <i class="fas fa-key me-1"></i>{{ $totalAccess }} Modul
                    </span>
                    @endif
                </div>
                @endif
            </div>
            <div class="col-md-4 text-md-end mt-2 mt-md-0">
                <div class="d-flex flex-column align-items-md-end">
                    <div class="text-white opacity-90 mb-2">
                        <i class="fas fa-calendar-alt me-1"></i>
                        <span id="current-date">{{ date('l, d F Y') }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <small class="text-white opacity-80">
                            <i class="fas fa-user me-1"></i>{{ Auth::user()->username }}
                        </small>
                        <form action="{{ route('logout') }}" method="POST" id="logout-form">
                            @csrf
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
    // Daftar menu dari database (tb_dashboard_menu). Hak akses tetap dicek lewat $access (tb_access_dash) di bawah.
    $dbMenus = \Illuminate\Support\Facades\DB::table('tb_dashboard_menu')
        ->where('is_active', 1)->orderBy('sort_order')->get()
        ->map(fn ($r) => [
            'type'  => $r->type,
            'field' => $r->field,
            'title' => $r->title,
            'url'   => ($r->route_name && \Illuminate\Support\Facades\Route::has($r->route_name)) ? route($r->route_name) : $r->url,
            'color' => $r->color,
            'icon'  => $r->icon,
        ]);

    $menus      = $dbMenus->where('type', 'main')->values()->all();
    $adminMenus = $dbMenus->where('type', 'admin')->values()->all();
    $driverMenu = Auth::user()->driver ? $dbMenus->firstWhere('type', 'driver') : null;
    
    $totalAccess = 0;
    $hasMainAccess = false;
    $hasAdminAccess = false;
    $delayCounter = 0.1;
    
    if(isset($access) && $access) {
        foreach($menus as $menu) {
            if(isset($access->{$menu['field']}) && $access->{$menu['field']} == 1) {
                $hasMainAccess = true;
                $totalAccess++;
            }
        }
        foreach($adminMenus as $menu) {
            if(isset($access->{$menu['field']}) && $access->{$menu['field']} == 1) {
                $hasAdminAccess = true;
                $totalAccess++;
            }
        }
    }
    ?>
    
    @if(isset($access) && $access)
        @if($hasMainAccess || $driverMenu)
            <h3 class="section-title">Tools</h3>
            <div class="menu-grid">
                @foreach($menus as $menu)
                    @if(isset($access->{$menu['field']}) && $access->{$menu['field']} == 1)
                        <?php 
                        $animationDelay = $delayCounter . 's';
                        $delayCounter += 0.05;
                        ?>
                        <div class="menu-item" onclick="window.location.href='{{ $menu['url'] }}'" style="animation-delay: {{ $animationDelay }};">
                            <div class="icon-box">
                                <div class="icon-bg" style="background: {{ $menu['color'] }};"></div>
                                <svg class="icon-svg" fill="none" stroke="{{ $menu['color'] }}" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $menu['icon'] }}"/>
                                </svg>
                            </div>
                            <h3 class="menu-title">{{ $menu['title'] }}</h3>
                        </div>
                    @endif
                @endforeach

                @if($driverMenu)
                    <?php 
                    $animationDelay = $delayCounter . 's';
                    $delayCounter += 0.05;
                    ?>
                    <div class="menu-item" onclick="window.location.href='{{ $driverMenu['url'] }}'" style="animation-delay: {{ $animationDelay }};">
                        <div class="icon-box">
                            <div class="icon-bg" style="background: {{ $driverMenu['color'] }};"></div>
                            <svg class="icon-svg" fill="none" stroke="{{ $driverMenu['color'] }}" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $driverMenu['icon'] }}"/>
                            </svg>
                        </div>
                        <h3 class="menu-title">{{ $driverMenu['title'] }}</h3>
                    </div>
                @endif
            </div>
        @endif
        
        @if($hasAdminAccess)
            <h3 class="section-title admin-title">Admin</h3>
            <div class="menu-grid">
                @foreach($adminMenus as $menu)
                    @if(isset($access->{$menu['field']}) && $access->{$menu['field']} == 1)
                        <?php 
                        $animationDelay = $delayCounter . 's';
                        $delayCounter += 0.05;
                        ?>
                        <div class="menu-item admin-item" onclick="window.location.href='{{ $menu['url'] }}'" style="animation-delay: {{ $animationDelay }};">
                            <div class="admin-badge">Admin</div>
                            <div class="icon-box">
                                <div class="icon-bg" style="background: {{ $menu['color'] }};"></div>
                                <svg class="icon-svg" fill="none" stroke="{{ $menu['color'] }}" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $menu['icon'] }}"/>
                                </svg>
                            </div>
                            <h3 class="menu-title">{{ $menu['title'] }}</h3>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
        
        @if(!$hasMainAccess && !$hasAdminAccess && !$driverMenu)
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
        @endif
        
    @else
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
                Silakan hubungkan administrator.
            </div>
            <button onclick="window.location.reload()" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-redo me-1"></i>Refresh
            </button>
        </div>
    @endif
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function updateDateTime() {
            const now = new Date();
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            document.getElementById('current-date').textContent = now.toLocaleDateString('id-ID', options);
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);
        
        const menuItems = document.querySelectorAll('.menu-item');
        menuItems.forEach(item => {
            item.addEventListener('click', function(e) {
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
                if (this.classList.contains('admin-item')) {
                    ripple.style.background = 'rgba(142, 68, 173, 0.3)';
                }
                this.appendChild(ripple);
                setTimeout(() => {
                    ripple.remove();
                    const onclickAttr = this.getAttribute('onclick');
                    const match = onclickAttr.match(/'(.*?)'/);
                    if (match && match[1]) window.location.href = match[1];
                }, 200);
            });
        });
        
        const style = document.createElement('style');
        style.textContent = `@keyframes ripple { to { transform: scale(3); opacity: 0; } }`;
        document.head.appendChild(style);
        
        const logoutForm = document.getElementById('logout-form');
        if (logoutForm) {
            logoutForm.addEventListener('submit', function(e) {
                if (!confirm('Anda yakin ingin logout?')) e.preventDefault();
            });
        }
    });
</script>
@endsection