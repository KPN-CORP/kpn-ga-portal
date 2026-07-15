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

        Gate::define('superadmin', function (User $user) {
            // Gunakan relasi drmsProfile untuk cek is_drms_superadmin
            return $user->drmsProfile && $user->drmsProfile->is_drms_superadmin == 1;
        });
    }
}