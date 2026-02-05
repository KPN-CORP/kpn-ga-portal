<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IDCardController;
use App\Http\Controllers\MessengerController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SSOController;
use App\Http\Controllers\DBLoginController;
use App\Http\Middleware\CheckIDCardAccess;
use App\Http\Middleware\CheckMessengerAccess;
use App\Http\Controllers\MailingController;
use App\Http\Controllers\HelpTiketController;
use App\Http\Controllers\TrackRController;
use App\Http\Controllers\HelpTiketApprovalController;
use App\Http\Controllers\SettingAccessController;
use App\Http\Controllers\MenuInformationController;
use App\Http\Controllers\Apartemen\UserController;
use App\Http\Controllers\Apartemen\AdminController;
use App\Http\Controllers\Apartemen\AssignController;
use App\Http\Controllers\Apartemen\DetailController;

/*
|--------------------------------------------------------------------------
| AUTHENTICATION (MANUAL LOGIN)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.process');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| ROOT
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| DARWINBOX SSO (WAJIB PUBLIC ❗❗❗) - FIXED
|--------------------------------------------------------------------------
*/

Route::get('/login/sso', [SSOController::class, 'redirect'])
    ->name('login.sso');

Route::get('/login/sso/callback', [SSOController::class, 'callback'])
    ->name('login.sso.callback');

// VERSI 1: Dengan middleware (standard)
Route::get('/dblogin', [DBLoginController::class, 'handle'])
    ->middleware('sso.darwinbox')
    ->name('sso.login');

// VERSI 2: Tanpa middleware (fallback untuk debug)
Route::get('/dblogin-fallback', [DBLoginController::class, 'handle'])
    ->name('sso.login.fallback')
    ->withoutMiddleware(['sso.darwinbox']);

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES (SETELAH LOGIN)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/api/access-data', [DashboardController::class, 'getAccessData'])
        ->name('api.access.data');

     /*
|--------------------------------------------------------------------------
| MESSENGER (RAPI & ANTI TABRAKAN)
|--------------------------------------------------------------------------
*/
Route::prefix('messenger')->middleware('auth')->group(function () {

    // =========================
    // INDEX & REQUEST
    // =========================
    Route::middleware('messenger.access:status_messenger')
        ->get('/', [MessengerController::class, 'index'])
        ->name('messenger.index');

    Route::middleware('messenger.access:request_messenger')
        ->get('/request', [MessengerController::class, 'request'])
        ->name('messenger.request');

    Route::middleware('messenger.access:request_messenger')
        ->post('/', [MessengerController::class, 'store'])
        ->name('messenger.store');

    // =========================
    // PROSES
    // =========================
    Route::middleware('messenger.access:proses_messenger')
        ->get('/proses', [MessengerController::class, 'proses'])
        ->name('messenger.proses');

    // =========================
    // ACTIONS (POST)
    // =========================
    Route::middleware('messenger.access:proses_messenger')->group(function () {
        Route::post('/{no_transaksi}/antar', [MessengerController::class, 'antar'])
            ->name('messenger.antar');

        Route::post('/{no_transaksi}/tolak', [MessengerController::class, 'tolak'])
            ->name('messenger.tolak');

        Route::post('/{no_transaksi}/selesaikan', [MessengerController::class, 'selesaikan'])
            ->name('messenger.selesaikan');
    });

    // =========================
    // PRINT & CANCEL
    // =========================
    Route::middleware('messenger.access:detail_messenger')->group(function () {
        Route::get('/{no_transaksi}/print', [MessengerController::class, 'print'])
            ->name('messenger.print');

        Route::post('/{no_transaksi}/cancel', [MessengerController::class, 'cancel'])
            ->name('messenger.cancel');
    });

    // =========================
    // FILE
    // =========================
    Route::middleware('messenger.access:detail_messenger')
        ->get('/file/{type}/{filename}', [MessengerController::class, 'getFile'])
        ->name('messenger.file');

    // =========================
    // DETAIL (PALING BAWAH)
    // =========================
    Route::middleware('messenger.access:detail_messenger')
        ->get('/{id}', [MessengerController::class, 'detail'])
        ->name('messenger.detail');
});


    /*
    |--------------------------------------------------------------------------
    | MAILING & RECEIPT
    |--------------------------------------------------------------------------
    */
    // [file name]: web.php - bagian mailing routes

    Route::prefix('mailing')->name('mailing.')->middleware(['auth'])->group(function () {
        // ... routes yang sudah ada ...
        
        // Index - arsip selesai
        Route::get('/', [MailingController::class, 'index'])->name('index');
        
        // Proses - mailing dalam proses
        Route::get('/proses', [MailingController::class, 'proses'])->name('proses');
        
        // CRUD
        Route::get('/create', [MailingController::class, 'create'])->name('create');
        Route::post('/store-bulk', [MailingController::class, 'storeBulk'])->name('store.bulk');
        
        // Proses status
        Route::post('/lantai47/{id}', [MailingController::class, 'lantai47'])->name('lantai47');
        Route::post('/selesai/{id}', [MailingController::class, 'selesai'])->name('selesai');
        
        // Bulk actions
        Route::post('/bulk-lantai47', [MailingController::class, 'bulkLantai47'])->name('bulk-lantai47');
        Route::post('/bulk-selesai', [MailingController::class, 'bulkSelesai'])->name('bulk-selesai');
        
        // API untuk pelanggan (TAMBAHKAN INI!)
        Route::get('/pelanggans', [MailingController::class, 'getPelanggans'])->name('get-pelanggans');
        
        // View foto
        Route::get('/foto/{id}', [MailingController::class, 'viewFoto'])->name('view-foto');
    });

    // Help Tiket System
    Route::middleware(['auth'])->prefix('help')->name('help.')->group(function () {
        // ============================================
        // USER TICKET ROUTES
        // ============================================
        Route::prefix('tiket')->name('tiket.')->group(function () {
            // Halaman utama tiket user
            Route::get('/', [HelpTiketController::class, 'index'])->name('index');
            Route::get('/buat', [HelpTiketController::class, 'create'])->name('create');
            Route::post('/', [HelpTiketController::class, 'store'])->name('store');
            
            // Detail tiket & komentar
            Route::get('/{tiket}', [HelpTiketController::class, 'show'])->name('show');
            Route::post('/{tiket}/komentar', [HelpTiketController::class, 'addKomentar'])->name('add-komentar');

            // Lampiran untuk user tiket
            Route::get('/lampiran/{lampiran}/download', [HelpTiketController::class, 'downloadLampiran'])
                ->name('lampiran.download');
                
            Route::get('/lampiran/{lampiran}/preview', [HelpTiketController::class, 'previewLampiran'])
                ->name('lampiran.preview'); // Nama: help.tiket.lampiran.preview
        });
        
        // ============================================
// APPROVAL ROUTES (UNTUK GA ADMIN)
// ============================================
Route::prefix('proses')->name('proses.')->group(function () {
    // Halaman proses tiket untuk admin
    Route::get('/', [HelpTiketApprovalController::class, 'index'])->name('index');
    
    // **RUTE LAMPIRAN HARUS DITEMPATKAN SEBELUM {tiket}**
    Route::get('/lampiran/{lampiran}/preview', [HelpTiketApprovalController::class, 'previewLampiran'])
        ->name('lampiran.preview');
    
    Route::get('/lampiran/{lampiran}/download', [HelpTiketApprovalController::class, 'downloadLampiran'])
        ->name('lampiran.download');
    
    // Setelah itu baru rute dengan parameter tiket
    Route::get('/{tiket}', [HelpTiketApprovalController::class, 'show'])->name('show');
    
    // Aksi admin pada tiket
    Route::post('/{tiket}/ambil', [HelpTiketApprovalController::class, 'take'])->name('take');
    Route::post('/{tiket}/selesaikan', [HelpTiketApprovalController::class, 'complete'])->name('complete');
    Route::post('/{tiket}/tutup', [HelpTiketApprovalController::class, 'close'])->name('close');
    
    // Komentar dari admin
    Route::post('/{tiket}/komentar', [HelpTiketApprovalController::class, 'addKomentar'])->name('add-komentar');
});
        
        // ============================================
        // LOG SISTEM (OPSIONAL - JIKA ADA)
        // ============================================
        Route::get('/log-sistem', [HelpTiketController::class, 'logSistem'])
            ->name('log-sistem')
            ->middleware('ga_admin'); // Middleware khusus untuk GA Admin
    });

    /*
    |--------------------------------------------------------------------------
    | APARTEMEN / MESS (UI ONLY - PHASE 1)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth'])
    ->prefix('apartemen')
    ->group(function () {

        // USER
        Route::middleware('apartemen.access:apt_user')->group(function () {
            Route::get('/', [UserController::class, 'index'])
                ->name('apartemen.user.index');

            // TAMBAHKAN ROUTE INI ↓
            Route::get('/requests', [UserController::class, 'requests'])
                ->name('apartemen.user.requests');

            Route::get('/create', [UserController::class, 'create'])
                ->name('apartemen.user.create');

            Route::post('/store', [UserController::class, 'store'])
                ->name('apartemen.user.store');

            Route::get('/show/{id}', [UserController::class, 'show'])
                ->name('apartemen.user.show');
        });

        // ADMIN
        Route::middleware('apartemen.access:apt_admin')
            ->prefix('admin')
            ->group(function () {

                Route::get('/', [AdminController::class, 'index'])
                    ->name('apartemen.admin.index');

                Route::get('/dashboard', [AdminController::class, 'dashboard'])
                    ->name('apartemen.admin.dashboard');

                Route::get('/approve/{id}', [AdminController::class, 'approve'])
                    ->name('apartemen.admin.approve');

                Route::post('/approve/{id}', [AdminController::class, 'approveProcess'])
                    ->name('apartemen.admin.approve.process');

                Route::get('/assign/{id}', [AdminController::class, 'assign'])
                    ->name('apartemen.admin.assign');

                Route::post('/assign', [AssignController::class, 'store'])
                    ->name('apartemen.admin.assign.store');

                Route::put('/assign/{id}', [AssignController::class, 'update'])
                    ->name('apartemen.admin.assign.update');

                Route::get('/monitoring', [AdminController::class, 'monitoring'])
                    ->name('apartemen.admin.monitoring');

                Route::get('/history', [AdminController::class, 'history'])
                    ->name('apartemen.admin.history');

                Route::get('/apartemen', [AdminController::class, 'apartemen'])
                    ->name('apartemen.admin.apartemen');

                Route::get('/apartemen/{id}', [AdminController::class, 'apartemenDetail'])
                    ->name('apartemen.admin.apartemen.detail');

                // PERBAIKAN: Ganti ApartemenAdminController dengan AdminController
                Route::post('/penghuni/{id}/checkout', [AdminController::class, 'checkoutPenghuni'])
                    ->name('apartemen.admin.penghuni.checkout');

                Route::post('/transfer/{id}', [AssignController::class, 'transfer'])
                    ->name('apartemen.admin.transfer');

                Route::post('/maintenance/{id}', [AdminController::class, 'setMaintenance'])
                    ->name('apartemen.admin.maintenance');

                Route::get('/request/{id}/detail', [AdminController::class, 'detail'])
                    ->name('apartemen.admin.detail');

                Route::get('/report', [AdminController::class, 'report'])
                    ->name('apartemen.admin.report');

                // Pindahkan route ini ke dalam group admin
                Route::post('/unit/store', [AdminController::class, 'storeUnit'])
                    ->name('apartemen.admin.unit.store');
                    
                Route::post('/unit/delete', [AdminController::class, 'deleteUnit'])
                    ->name('apartemen.admin.unit.delete');

                Route::post('/unit/maintenance', [AdminController::class, 'setMaintenance'])
                    ->name('apartemen.admin.setMaintenance');

                Route::post('/apartemen/store', [AdminController::class, 'storeApartemen'])
                    ->name('apartemen.admin.apartemen.store');
            });

        // DETAIL UNIT
        Route::middleware('apartemen.access:apt_detail')->group(function () {
            Route::get('/detail/{unit_id}', [DetailController::class, 'show'])
                ->name('apartemen.detail');
        });

        // HISTORY
        Route::middleware('apartemen.access:apt_history')->group(function () {
            Route::get('/history', function () {
                return view('apartemen.history');
            })->name('apartemen.history');
        });
    });


    /*
    |--------------------------------------------------------------------------
    | Setting Akses
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth', 'setting.access'])
        ->get('/setting-access', [SettingAccessController::class, 'index'])
        ->name('setting-access.index');

    Route::middleware(['auth', 'setting.access'])
        ->post('/setting-access', [SettingAccessController::class, 'store'])
        ->name('setting-access.store');

    /*
    |--------------------------------------------------------------------------
    | Infomasi
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth'])
        ->get('/informasi', [MenuInformationController::class, 'index'])
        ->name('menu.information');


    /*
    |--------------------------------------------------------------------------
    | ID CARD
    |--------------------------------------------------------------------------
    */
    Route::middleware(CheckIDCardAccess::class . ':list')->group(function () {
    Route::get('/idcard', [IDCardController::class, 'index'])->name('idcard');
    });

    Route::middleware(CheckIDCardAccess::class . ':request')->group(function () {
        Route::get('/idcard/request', [IDCardController::class, 'create'])->name('idcard.request');
        Route::post('/idcard', [IDCardController::class, 'store'])->name('idcard.store');
    });

    Route::middleware(CheckIDCardAccess::class . ':detail')->group(function () {
        Route::get('/idcard/{id}', [IDCardController::class, 'detail'])->name('idcard.detail');
    });

    Route::get('/idcard/photo/{filename}', [IDCardController::class, 'photo'])
        ->name('idcard.photo');

    Route::middleware(CheckIDCardAccess::class . ':proses')->group(function () {
        Route::post('/idcard/{id}/approve', [IDCardController::class, 'approve'])->name('idcard.approve');
        Route::post('/idcard/{id}/reject', [IDCardController::class, 'reject'])->name('idcard.reject');
    });

    /*
    |--------------------------------------------------------------------------
    | EMPLOYEES & REPORTS
    |--------------------------------------------------------------------------
    */
    // Employee Routes with Permission Middleware
    Route::prefix('employees')->middleware(['web', 'auth'])->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])
            ->name('employees.index')
            ->middleware('employees.access:emp_index');
        
        Route::get('/{employee}', [EmployeeController::class, 'show'])
            ->name('employees.show')
            ->middleware('employees.access:emp_show');
    });

    /*
    |--------------------------------------------------------------------------
    | Track Rec
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth'])->prefix('track-r')->group(function () {
        Route::get('/', [TrackRController::class, 'index'])->name('track-r.index');
        Route::get('/create', [TrackRController::class, 'create'])->name('track-r.create');
        Route::post('/', [TrackRController::class, 'store'])->name('track-r.store');
        Route::get('/{id}', [TrackRController::class, 'show'])->name('track-r.show');
        Route::post('/{id}/terima', [TrackRController::class, 'terima'])->name('track-r.terima');
        Route::post('/{id}/tolak', [TrackRController::class, 'tolak'])->name('track-r.tolak');
        Route::post('/{id}/teruskan', [TrackRController::class, 'teruskan'])->name('track-r.teruskan');
        Route::get('/{id}/pdf', [TrackRController::class, 'pdf'])->name('track-r.pdf');
        
        // Foto routes - hanya download
        Route::get('/{document}/foto/{foto}/download', [TrackRController::class, 'downloadFoto'])
            ->name('track-r.foto.download');
    });

    /*
    |--------------------------------------------------------------------------
    | NO ACCESS
    |--------------------------------------------------------------------------
    */
    Route::get('/no-access', function () {
        return view('no-access');
    })->name('no-access');

});