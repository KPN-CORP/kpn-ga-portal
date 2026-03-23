<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ViewComposerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        view()->composer(
            'layouts.app_car_sidebar',
            \App\View\Composers\DrmsSidebarComposer::class
        );
    }

    public function register()
    {
        //
    }
}