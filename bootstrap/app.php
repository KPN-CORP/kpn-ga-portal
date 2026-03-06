<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\SsoDarwinboxMiddleware;
use App\Http\Middleware\CheckEmployeesAccess;
use App\Http\Middleware\CheckSettingAccess;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        // HAPUS atau COMMENT baris api jika tidak ada routes/api.php
        // api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Register middleware aliases
        $middleware->alias([
            'auth' => \App\Http\Middleware\Authenticate::class,
            'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
            'auth.session' => \Illuminate\Session\Middleware\AuthenticateSession::class,
            'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
            'can' => \Illuminate\Auth\Middleware\Authorize::class,
            'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
            'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
            'precognitive' => \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            'signed' => \App\Http\Middleware\ValidateSignature::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            
            
            // Middleware custom Anda
            // 'module.access' => \App\Http\Middleware\ModuleAccessMiddleware::class,
            
            // TAMBAHKAN INI:
            'check.idcard' => \App\Http\Middleware\CheckIDCardAccess::class,

            // messenger access
            'messenger.access' => \App\Http\Middleware\CheckMessengerAccess::class,

            // SSO Darwinbox
            'sso.darwinbox' => SsoDarwinboxMiddleware::class,

            // Akses employees
            'employees.access' => \App\Http\Middleware\CheckEmployeesAccess::class,

            'mailing.access' => \App\Http\Middleware\CheckMailingAccess::class,

            'apartemen.access' => \App\Http\Middleware\CheckApartemenAccess::class,

            'setting.access' => CheckSettingAccess::class,

            'ga.help.admin' => \App\Http\Middleware\CheckGAHelpAdmin::class,
            'founddesk.access' => \App\Http\Middleware\FounddeskAccess::class,
            'public.access' => \App\Http\Middleware\CheckPublicAccess::class,
        ]);
        
        // Middleware groups
        $middleware->group('web', [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
