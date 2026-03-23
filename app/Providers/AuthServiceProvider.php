<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // Daftarkan policy di sini jika ada
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::define('isApprover', function (User $user) {
            return $user->isApprover();
        });

        Gate::define('isDrmsAdmin', function (User $user) {
            return $user->isDrmsAdmin();
        });

        // (Opsional) Gate untuk superadmin
        // Gate::define('isDrmsSuperAdmin', function (User $user) {
        //     return $user->isDrmsSuperAdmin();
        // });
    }
}