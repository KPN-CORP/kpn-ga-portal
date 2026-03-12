<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Observers\UserObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /**
         * ===============================
         * 🔥 WAJIB: Register User Observer
         * ===============================
         */
        User::observe(UserObserver::class);

        /**
         * ===============================
         * Share global view data
         * ===============================
         */
        View::composer('*', function ($view) {
            $user = Auth::user();

            $isGAAdmin = $user && in_array($user->role, [
                'ga_admin',
                'admin',
                'superadmin'
            ]);

            $view->with([
                'isGAAdmin'  => $isGAAdmin,
                'currentUser'=> $user
            ]);
        });

        /**
         * ===============================
         * Sidebar menu (GA)
         * ===============================
         */
        View::composer('layouts.app-sidebar', function ($view) {
            $user = Auth::user();

            $isGAAdmin = $user && in_array($user->role, [
                'ga_admin',
                'admin',
                'superadmin'
            ]);

            $helpMenu = [
                [
                    'title'  => 'GA Tiket',
                    'icon'   => 'fas fa-ticket-alt',
                    'url'    => route('help.tiket.index'),
                    'active' => request()->is('help/tiket*')
                        && !request()->is('help/tiket/buat')
                ]
            ];

            // User biasa
            if (!$isGAAdmin) {
                $helpMenu[] = [
                    'title'  => 'Buat Tiket',
                    'icon'   => 'fas fa-plus-circle',
                    'url'    => route('help.tiket.create'),
                    'active' => request()->is('help/tiket/buat')
                ];
            }

            // GA Admin
            if ($isGAAdmin) {
                $helpMenu[] = [
                    'title'  => 'Log Sistem',
                    'icon'   => 'fas fa-history',
                    'url'    => route('help.log.index'),
                    'active' => request()->is('help/log*')
                ];
            }

            $view->with('helpMenu', $helpMenu);
        });
    }
}
