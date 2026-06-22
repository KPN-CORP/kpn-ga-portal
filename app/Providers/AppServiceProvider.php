<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Observers\UserObserver;
use App\Models\StockCtl\Permintaan;
use App\Models\StockCtl\UserProfil;
use App\Models\AccessMenu;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
	    URL::forceScheme('https');

        // Observer untuk User
        User::observe(UserObserver::class);

        // Data global untuk semua view
        View::composer('*', function ($view) {
            $user = Auth::user();

            $isGAAdmin = $user && in_array($user->role, [
                'ga_admin', 'admin', 'superadmin'
            ]);

            $view->with([
                'isGAAdmin'  => $isGAAdmin,
                'currentUser' => $user,
            ]);
        });

        // Composer khusus untuk sidebar (layout ATK)
        View::composer('layouts.app_stock_sidebar', function ($view) {
            $user = Auth::user();
            $unreadNotifications = 0;
            $notifications = collect();
            $pendingL1Count = 0;
            $pendingAdminCount = 0;
            $isApprover = false;
            $access = []; // hak akses ATK

            if ($user) {
                // Notifikasi Laravel default
                $unreadNotifications = $user->unreadNotifications->count();
                $notifications = $user->notifications()->latest()->take(5)->get();

                // Ambil hak akses ATK dari tabel tb_access_menu
                $accessMenu = AccessMenu::where('username', $user->username)->first();
                if ($accessMenu) {
                    $access = [
                        'is_super' => $accessMenu->stock_ctl_superadmin ?? false,
                        'is_admin' => $accessMenu->stock_ctl_admin ?? false,
                        'is_user'  => $accessMenu->stock_ctl_user ?? false,
                        'id_area_kerja' => null,
                        'id_bisnis_unit' => null,
                    ];

                    // Ambil profil user (area kerja, atasan)
                    $profil = UserProfil::where('id_user', $user->id)->first();
                    if ($profil) {
                        $access['id_area_kerja'] = $profil->id_area_kerja;
                        $access['id_bisnis_unit'] = $profil->id_bisnis_unit;
                    }

                    // Cek apakah user adalah atasan (memiliki bawahan)
                    $isApprover = UserProfil::where('id_approver', $user->id)->exists();

                    // Hitung pending L1 jika user adalah atasan
                    if ($isApprover) {
                        $pendingL1Count = Permintaan::where('status', Permintaan::STATUS_PENDING_L1)
                            ->whereExists(function ($q) use ($user) {
                                $q->select(DB::raw(1))
                                  ->from('stock_ctl_user_profil')
                                  ->whereColumn('stock_ctl_user_profil.id_user', 'stock_ctl_permintaan.id_user_pemohon')
                                  ->where('stock_ctl_user_profil.id_approver', $user->id);
                            })
                            ->count();
                    }

                    // Hitung pending admin jika user adalah admin
                    if ($access['is_admin'] ?? false) {
                        $pendingAdminCount = Permintaan::where('status', Permintaan::STATUS_PENDING_ADMIN)
                            ->whereExists(function ($q) use ($access) {
                                $q->select(DB::raw(1))
                                  ->from('stock_ctl_user_profil')
                                  ->whereColumn('stock_ctl_user_profil.id_user', 'stock_ctl_permintaan.id_user_pemohon')
                                  ->where('stock_ctl_user_profil.id_bisnis_unit', $access['id_bisnis_unit']);
                            })
                            ->count();
                    }
                }
            }

            // Menu Help Desk (opsional, jika ada di sidebar ATK)
            $isGAAdmin = $user && in_array($user->role, ['ga_admin', 'admin', 'superadmin']);
            $helpMenu = [
                [
                    'title'  => 'GA Tiket',
                    'icon'   => 'fas fa-ticket-alt',
                    'url'    => route('help.tiket.index'),
                    'active' => request()->is('help/tiket*') && !request()->is('help/tiket/buat')
                ]
            ];

            if (!$isGAAdmin) {
                $helpMenu[] = [
                    'title'  => 'Buat Tiket',
                    'icon'   => 'fas fa-plus-circle',
                    'url'    => route('help.tiket.create'),
                    'active' => request()->is('help/tiket/buat')
                ];
            }

            if ($isGAAdmin) {
                $helpMenu[] = [
                    'title'  => 'Log Sistem',
                    'icon'   => 'fas fa-history',
                    'url'    => route('help.log.index'),
                    'active' => request()->is('help/log*')
                ];
            }

            // Menu ATK (dibangun dari $access) - tanpa "Buat Permintaan"
            $atkMenu = [];
            if (!empty($access)) {
                if ($access['is_super'] ?? false) {
                    $atkMenu[] = ['title' => 'Dashboard ATK', 'url' => route('stock-ctl.dashboard'), 'icon' => 'fas fa-chart-pie'];
                    $atkMenu[] = ['title' => 'Area Kerja', 'url' => route('stock-ctl.area.index'), 'icon' => 'fas fa-map-marker-alt'];
                    $atkMenu[] = ['title' => 'User Profil', 'url' => route('stock-ctl.user-profil.index'), 'icon' => 'fas fa-users-cog'];
                }
                if (($access['is_admin'] ?? false) || ($access['is_super'] ?? false)) {
                    $atkMenu[] = ['title' => 'Barang', 'url' => route('stock-ctl.barang.index'), 'icon' => 'fas fa-box'];
                    $atkMenu[] = ['title' => 'Stok', 'url' => route('stock-ctl.stok.index'), 'icon' => 'fas fa-warehouse'];
                    $atkMenu[] = ['title' => 'Transaksi', 'url' => '#', 'icon' => 'fas fa-exchange-alt', 'submenu' => [
                        ['title' => 'Barang Masuk', 'url' => route('stock-ctl.transaksi.masuk')],
                        ['title' => 'Barang Keluar', 'url' => route('stock-ctl.transaksi.keluar')],
                        ['title' => 'Transfer', 'url' => route('stock-ctl.transaksi.transfer')],
                    ]];
                    $atkMenu[] = ['title' => 'Opname', 'url' => route('stock-ctl.opname.index'), 'icon' => 'fas fa-clipboard-list'];
                    $atkMenu[] = ['title' => 'Laporan', 'url' => route('stock-ctl.laporan.index'), 'icon' => 'fas fa-file-alt'];
                }
                // Hanya tampilkan "Permintaan Saya" di sidebar, bukan "Buat Permintaan"
                if (($access['is_user'] ?? false) || ($access['is_admin'] ?? false) || ($access['is_super'] ?? false)) {
                    $atkMenu[] = ['title' => 'Permintaan Saya', 'url' => route('stock-ctl.permintaan.index'), 'icon' => 'fas fa-shopping-cart'];
                }
            }

            $view->with([
                'unreadNotifications' => $unreadNotifications,
                'notifications' => $notifications,
                'helpMenu' => $helpMenu,
                'atkMenu' => $atkMenu,
                'access' => $access,
                'isApprover' => $isApprover,
                'pendingL1Count' => $pendingL1Count,
                'pendingAdminCount' => $pendingAdminCount,
            ]);
        });
    }
}
