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
use App\Http\Controllers\HelpTiketPDFController;
use App\Http\Controllers\HelpProsesController;
use App\Http\Controllers\TrackFotoController;
use App\Http\Controllers\Founddesk\FounddeskController;
use App\Http\Controllers\Founddesk\FounddeskDispositionController;
use App\Http\Controllers\Apartemen\PublicAccessController;
use App\Http\Controllers\Apartemen\QRCodeAdminController;
use App\Http\Controllers\StockCtl\StockDashboardController;
use App\Http\Controllers\StockCtl\PermintaanController;
use App\Http\Controllers\StockCtl\ApprovalL1Controller;
use App\Http\Controllers\StockCtl\ApprovalAdminController;
use App\Http\Controllers\StockCtl\BarangController;
use App\Http\Controllers\StockCtl\StokController;
use App\Http\Controllers\StockCtl\TransaksiController;
use App\Http\Controllers\StockCtl\OpnameController;
use App\Http\Controllers\StockCtl\LaporanController;
use App\Http\Controllers\StockCtl\SuperadminController;
use App\Http\Controllers\Drms\RequestController;
use App\Http\Controllers\Drms\AppL1Controller;
use App\Http\Controllers\Drms\AppAdminController;
use App\Http\Controllers\Drms\DriverController;
use App\Http\Controllers\Drms\VehicleController;
use App\Http\Controllers\Drms\VoucherController;
use App\Http\Controllers\Work\WorkReportController;
use App\Http\Controllers\Work\WorkReportCategoryController;
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
| PUBLIC ACCESS APARTEMEN (TANPA LOGIN) - DIPINDAHKAN KE SINI
|--------------------------------------------------------------------------
*/
    Route::prefix('apartemen/public')->name('apartemen.public.')->group(function () {
        Route::get('/', [PublicAccessController::class, 'index'])->name('index');
        Route::post('/verify', [PublicAccessController::class, 'verify'])->name('verify');
        Route::get('/search', [PublicAccessController::class, 'search'])->name('search');
        Route::get('/find', [PublicAccessController::class, 'find'])->name('find');
        Route::post('/checkout/{id}', [PublicAccessController::class, 'checkout'])->name('checkout');
        Route::post('/checkin/{id}', [PublicAccessController::class, 'checkin'])->name('checkin');
        Route::get('/success', [PublicAccessController::class, 'success'])->name('success');
        Route::get('/logout', [PublicAccessController::class, 'logout'])->name('logout');
    });

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

    // ============================================
    // HELP TIKET SYSTEM ROUTES
    // ============================================
    Route::middleware(['auth'])->prefix('help')->name('help.')->group(function () {
            
    // ============================================
    // PDF REPORT ROUTES (Bisa diakses semua role)
    // ============================================
    Route::get('/tiket/{tiket}/pdf', [HelpTiketPDFController::class, 'download'])->name('tiket.pdf');

        // ============================================
        // USER TICKET ROUTES (Pelapor)
        // ============================================
        Route::prefix('tiket')->name('tiket.')->group(function () {
            // Halaman utama
            Route::get('/', [HelpTiketController::class, 'index'])->name('index');
            Route::get('/buat', [HelpTiketController::class, 'create'])->name('create');
            Route::post('/', [HelpTiketController::class, 'store'])->name('store');
            
            // Detail tiket - HARUS DIATAS ROUTE LAMPIRAN
            Route::get('/{tiket}', [HelpTiketController::class, 'show'])->name('show');
            
            // Komentar
            Route::post('/{tiket}/komentar', [HelpTiketController::class, 'addKomentar'])->name('add-komentar');
            
            // Lampiran - URL KHUSUS UNTUK LAMPIRAN
            Route::prefix('lampiran')->name('lampiran.')->group(function () {
                Route::get('/{lampiran}/preview', [HelpTiketController::class, 'previewLampiran'])->name('preview');
                Route::get('/{lampiran}/download', [HelpTiketController::class, 'downloadLampiran'])->name('download');
            });
        });
        
        // ============================================
        // STAFF GA ROUTES (Proses Tiket)
        // ============================================
        Route::prefix('proses')->name('proses.')->group(function () {
            
            // ============================================
            // ROUTE LAMPIRAN - DILETAKKAN PALING ATAS
            // ============================================
            Route::prefix('lampiran')->name('lampiran.')->group(function () {
                Route::get('/{lampiran}/preview', [HelpTiketApprovalController::class, 'previewLampiran'])->name('preview');
                Route::get('/{lampiran}/download', [HelpTiketApprovalController::class, 'downloadLampiran'])->name('download');
            });
            
            // ============================================
            // ROUTE UTAMA PROSES (MENGGUNAKAN HELPPROSESCONTROLLER)
            // ============================================
            // Halaman utama proses tiket
            Route::get('/', [HelpProsesController::class, 'index'])
    ->middleware('ga.help.admin')
    ->name('index');
            
            // Download report
            Route::get('/download', [HelpProsesController::class, 'download'])->name('download');
            
            // Detail tiket (menggunakan ID biasa, bukan model binding)
            Route::get('/{id}', [HelpProsesController::class, 'show'])->name('show');
            
            // Aksi mengambil tiket
            Route::post('/{id}/take', [HelpProsesController::class, 'take'])->name('take');
            
            // ============================================
            // ROUTE UNTUK STATUS TIKET (TETAP DI APPROVAL CONTROLLER)
            // ============================================
            Route::post('/{tiket}/waiting', [HelpTiketApprovalController::class, 'waiting'])->name('waiting');
            Route::post('/{tiket}/resume', [HelpTiketApprovalController::class, 'resume'])->name('resume');
            Route::post('/{tiket}/selesaikan', [HelpTiketApprovalController::class, 'complete'])->name('complete');
            Route::post('/{tiket}/tutup', [HelpTiketApprovalController::class, 'close'])->name('close');
            Route::post('/{tiket}/transfer-to-corp', [HelpTiketApprovalController::class, 'transferToCorp'])->name('transfer-to-corp');
            Route::post('/{tiket}/upload-foto-selesai', [HelpTiketApprovalController::class, 'uploadFotoSelesai'])->name('upload-foto-selesai');
            Route::post('/{tiket}/komentar', [HelpTiketApprovalController::class, 'addKomentar'])->name('add-komentar');
        });
        
        // ============================================
        // LOG SISTEM (Opsional)
        // ============================================
        Route::get('/log-sistem', [HelpTiketController::class, 'logSistem'])
            ->name('log-sistem')
            ->middleware('ga_admin'); // Middleware khusus GA Admin
    });

    /*
    |--------------------------------------------------------------------------
    | Stok Control
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth', 'stock.ctl:user'])->prefix('stock-ctl')->name('stock-ctl.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [StockDashboardController::class, 'index'])->name('dashboard');

        // Permintaan (user)
        Route::resource('permintaan', PermintaanController::class)->only(['index', 'create', 'store', 'show']);
        Route::get('permintaan/{id}/history', [PermintaanController::class, 'history'])->name('permintaan.history');

        // Approval L1 (atasan) - bisa diakses oleh user yang menjadi approver
        Route::get('approval/l1', [ApprovalL1Controller::class, 'index'])->name('approval.l1.index');
        Route::post('approval/l1/{id}/approve', [ApprovalL1Controller::class, 'approve'])->name('approval.l1.approve');
        Route::post('approval/l1/{id}/reject', [ApprovalL1Controller::class, 'reject'])->name('approval.l1.reject');
        Route::post('/stock-ctl/approval/l1/{id}/update-jumlah', [ApprovalL1Controller::class, 'updateJumlah'])->name('stock-ctl.approval.l1.update-jumlah');
        
        // Approval Admin (hanya admin)
        Route::middleware('stock.ctl:admin')->group(function () {
            Route::get('approval/admin', [ApprovalAdminController::class, 'index'])->name('approval.admin.index');
            Route::post('approval/admin/{id}/approve', [ApprovalAdminController::class, 'approve'])->name('approval.admin.approve');
            Route::post('approval/admin/{id}/reject', [ApprovalAdminController::class, 'reject'])->name('approval.admin.reject');
        });

        // Admin (stok, barang, transaksi, opname)
        Route::middleware('stock.ctl:admin')->group(function () {
            Route::resource('barang', BarangController::class);
            Route::get('stok', [StokController::class, 'index'])->name('stok.index');
            Route::get('stok/awal', [StokController::class, 'createAwal'])->name('stok.awal');
            Route::post('stok/awal', [StokController::class, 'storeAwal'])->name('stok.awal.store');
            Route::get('transaksi/masuk', [TransaksiController::class, 'createMasuk'])->name('transaksi.masuk');
            Route::post('transaksi/masuk', [TransaksiController::class, 'storeMasuk'])->name('transaksi.masuk.store');
            Route::get('transaksi/keluar', [TransaksiController::class, 'createKeluar'])->name('transaksi.keluar');
            Route::post('transaksi/keluar', [TransaksiController::class, 'storeKeluar'])->name('transaksi.keluar.store');
            Route::get('transaksi/transfer', [TransaksiController::class, 'createTransfer'])->name('transaksi.transfer');
            Route::post('transaksi/transfer', [TransaksiController::class, 'storeTransfer'])->name('transaksi.transfer.store');
            Route::resource('opname', OpnameController::class);
            Route::get('laporan', [LaporanController::class, 'index'])->name('laporan.index');
            Route::post('laporan/pdf', [LaporanController::class, 'pdf'])->name('laporan.pdf');
            Route::post('laporan/excel', [LaporanController::class, 'excel'])->name('laporan.excel');
            Route::get('transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
            Route::get('laporan/history', [LaporanController::class, 'history'])->name('laporan.history');
        });

        // Superadmin (kelola area, user profil)
        Route::middleware('stock.ctl:superadmin')->group(function () {
            Route::resource('area-kerja', SuperadminController::class)->parameters(['area-kerja' => 'area'])->names('area');
            Route::get('user-profil', [SuperadminController::class, 'userProfilIndex'])->name('user-profil.index');
            Route::get('user-profil/{id}', [SuperadminController::class, 'getUserProfil'])->name('user-profil.get');
            Route::post('user-profil/{id}', [SuperadminController::class, 'userProfilUpdate'])->name('user-profil.update');
        });
    });

/*
|--------------------------------------------------------------------------
| DRIVER REQUEST MANAGEMENT SYSTEM (DRMS)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->prefix('drms')->name('drms.')->group(function () {
    // Request User
    Route::get('requests', [RequestController::class, 'index'])->name('requests.index');
    Route::get('requests/create', [RequestController::class, 'create'])->name('requests.create');
    Route::post('requests', [RequestController::class, 'store'])->name('requests.store');
    Route::get('requests/{driverRequest}', [RequestController::class, 'show'])->name('requests.show');

    // Approval L1
    Route::middleware(['can:isApprover'])->prefix('approval/l1')->name('approval.l1.')->group(function () {
        Route::get('/', [AppL1Controller::class, 'index'])->name('index');
        Route::post('{id}/approve', [AppL1Controller::class, 'approve'])->name('approve');
        Route::post('{id}/reject', [AppL1Controller::class, 'reject'])->name('reject');
    });

    // Approval Admin
    Route::middleware(['can:isDrmsAdmin'])->prefix('approval/admin')->name('approval.admin.')->group(function () {
        Route::get('/', [AppAdminController::class, 'index'])->name('index');
        Route::get('{id}/edit', [AppAdminController::class, 'edit'])->name('edit');
        Route::put('{id}', [AppAdminController::class, 'update'])->name('update');
        Route::put('{id}/reject', [AppAdminController::class, 'reject'])->name('reject');
    });

    // Master Data (hanya admin)
    Route::middleware(['can:isDrmsAdmin'])->group(function () {
        // Letakkan route khusus SEBELUM resource
        Route::get('drivers/schedule', [DriverController::class, 'schedule'])->name('drivers.schedule');
        Route::resource('drivers', DriverController::class);
        Route::resource('vehicles', VehicleController::class);
        Route::resource('vouchers', VoucherController::class);
    });
});
    /*
    |--------------------------------------------------------------------------
    | APARTEMEN / MESS (UI ONLY - PHASE 1)
    |--------------------------------------------------------------------------
    */
    Route::prefix('apartemen')
        ->group(function () {

            // USER
            Route::middleware('apartemen.access:apt_user')->group(function () {
                Route::get('/', [UserController::class, 'index'])
                    ->name('apartemen.user.index');

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

                    Route::post('/penghuni/{id}/checkout', [AdminController::class, 'checkoutPenghuni'])
                        ->name('apartemen.admin.penghuni.checkout');

                    Route::post('/penghuni/{id}/checkin', [AdminController::class, 'checkin'])->name('checkin');

                    Route::post('/transfer/{id}', [AssignController::class, 'transfer'])
                        ->name('apartemen.admin.transfer');

                    Route::post('/maintenance/{id}', [AdminController::class, 'setMaintenance'])
                        ->name('apartemen.admin.maintenance');

                    Route::get('/request/{id}/detail', [AdminController::class, 'detail'])
                        ->name('apartemen.admin.detail');

                    Route::get('/report', [AdminController::class, 'report'])
                        ->name('apartemen.admin.report');

                    Route::post('/unit/store', [AdminController::class, 'storeUnit'])
                        ->name('apartemen.admin.unit.store');
                        
                    Route::post('/unit/delete', [AdminController::class, 'deleteUnit'])
                        ->name('apartemen.admin.unit.delete');

                    Route::post('/unit/maintenance', [AdminController::class, 'setMaintenance'])
                        ->name('apartemen.admin.setMaintenance');

                    Route::post('/apartemen/store', [AdminController::class, 'storeApartemen'])
                        ->name('apartemen.admin.apartemen.store');

                    // ============ QR CODE MANAGEMENT ============
                    Route::get('/access-codes', [PublicAccessController::class, 'manageCodes'])
                        ->name('apartemen.admin.access-codes');
                        
                    Route::post('/generate-qr', [PublicAccessController::class, 'generateQR'])
                        ->name('apartemen.admin.generate-qr');
                        
                    Route::post('/access-codes/{id}/deactivate', [PublicAccessController::class, 'deactivateCode'])
                        ->name('apartemen.admin.deactivate-code');
                        
                    Route::post('/access-codes/{id}/activate', [PublicAccessController::class, 'activateCode'])
                        ->name('apartemen.admin.activate-code');
                        
                    Route::delete('/access-codes/{id}', [PublicAccessController::class, 'deleteCode'])
                        ->name('apartemen.admin.delete-code');

                    Route::get('/access-codes', [QRCodeAdminController::class, 'index'])
                        ->name('apartemen.admin.access-codes');
                        
                    Route::post('/generate-qr', [QRCodeAdminController::class, 'generate'])
                        ->name('apartemen.admin.generate-qr');
                        
                    Route::post('/access-codes/{id}/deactivate', [QRCodeAdminController::class, 'deactivate'])
                        ->name('apartemen.admin.deactivate-code');
                        
                    Route::post('/access-codes/{id}/activate', [QRCodeAdminController::class, 'activate'])
                        ->name('apartemen.admin.activate-code');
                        
                    Route::delete('/access-codes/{id}', [QRCodeAdminController::class, 'destroy'])
                        ->name('apartemen.admin.delete-code');
                        
                    Route::get('/access-codes/{id}/print', [QRCodeAdminController::class, 'print'])
                        ->name('apartemen.admin.print-code');

                    Route::get('/calendar-events', [AdminController::class, 'calendarEvents'])->name('apartemen.admin.calendar.events');
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

        // Facility routes (user)
        Route::prefix('facilities')->name('facilities.')->group(function () {
            Route::get('/', [FacilityController::class, 'index'])->name('index');
            Route::get('/book/{id}', [FacilityController::class, 'bookForm'])->name('book');
            Route::post('/book/{id}', [FacilityController::class, 'store'])->name('store');
            Route::get('/history', [FacilityController::class, 'history'])->name('history');
            Route::post('/cancel/{id}', [FacilityController::class, 'cancel'])->name('cancel');
        });

        // Facility management
    Route::prefix('facilities')->name('facilities.')->group(function () {
        Route::get('/', [FacilityAdminController::class, 'index'])->name('index');
        Route::post('/', [FacilityAdminController::class, 'store'])->name('store');
        Route::put('/{id}', [FacilityAdminController::class, 'update'])->name('update');
        Route::delete('/{id}', [FacilityAdminController::class, 'destroy'])->name('destroy');

        Route::get('/bookings', [FacilityAdminController::class, 'bookings'])->name('bookings');
        Route::post('/bookings/{id}/approve', [FacilityAdminController::class, 'approve'])->name('approve');
        Route::post('/bookings/{id}/reject', [FacilityAdminController::class, 'reject'])->name('reject');
        Route::post('/bookings/{id}/checkin', [FacilityAdminController::class, 'checkin'])->name('checkin');
        Route::post('/bookings/{id}/checkout', [FacilityAdminController::class, 'checkout'])->name('checkout');
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
        
        // Foto routes
        Route::get('/{document}/foto/{foto}/download', [TrackRController::class, 'downloadFoto'])
            ->name('track-r.foto.download');

        // Route untuk menampilkan gambar (view) - menggunakan TrackFotoController
        Route::get('/track-foto/view/{id}', [App\Http\Controllers\TrackFotoController::class, 'view'])
            ->name('track-foto.view');
            
        // TAMBAHKAN INI - Route untuk download via TrackFotoController
        Route::get('/track-foto/download/{id}', [App\Http\Controllers\TrackFotoController::class, 'download'])
            ->name('track-foto.download');
    });

    // Work Reports Routes
    Route::middleware(['auth'])->prefix('work-reports')->name('work-reports.')->group(function () {
        Route::get('/', [WorkReportController::class, 'index'])->name('index');
        Route::get('/create', [WorkReportController::class, 'create'])->name('create');
        Route::post('/', [WorkReportController::class, 'store'])->name('store');
        Route::get('/{workReport}/edit', [WorkReportController::class, 'edit'])->name('edit');
        Route::put('/{workReport}', [WorkReportController::class, 'update'])->name('update');
        Route::delete('/{workReport}', [WorkReportController::class, 'destroy'])->name('destroy');
        Route::get('/export', [WorkReportController::class, 'export'])->name('export');
        Route::get('/work-reports/export', [WorkReportController::class, 'export'])->name('work-reports.export');
    });

    Route::get('/private-storage/{path}', function ($path) {
        $fullPath = storage_path('app/private/' . $path);
        if (!file_exists($fullPath)) {
            abort(404);
        }
        return response()->file($fullPath);
    })->where('path', '.*')->name('private.storage')->middleware('auth');


    // Work Categories Routes (hanya admin)
    Route::middleware(['auth'])->prefix('work-reports-categories')->name('work-reports.categories.')->group(function () {
        Route::get('/', [WorkReportCategoryController::class, 'index'])->name('index');
        Route::get('/create', [WorkReportCategoryController::class, 'create'])->name('create');
        Route::post('/', [WorkReportCategoryController::class, 'store'])->name('store');
        Route::get('/{workReportCategory}/edit', [WorkReportCategoryController::class, 'edit'])->name('edit');
        Route::put('/{workReportCategory}', [WorkReportCategoryController::class, 'update'])->name('update');
        Route::delete('/{workReportCategory}', [WorkReportCategoryController::class, 'destroy'])->name('destroy');
    });
    /*
    |--------------------------------------------------------------------------
    | foundesk
    |--------------------------------------------------------------------------
    */
    Route::prefix('founddesk')
    ->name('founddesk.')
    ->middleware(['auth', 'founddesk.access']) // ⬅️ INI KUNCI UTAMANYA
    ->group(function () {

        // Items
        Route::get('/', [FounddeskController::class, 'index'])->name('index');
        Route::get('/create', [FounddeskController::class, 'create'])->name('create');
        Route::post('/', [FounddeskController::class, 'store'])->name('store');
        Route::delete('/{id}', [FounddeskController::class, 'destroy'])->name('destroy');
        Route::get('/photo/{id}', [FounddeskController::class, 'showPhoto'])->name('photo');
        Route::get('/export/csv', [FounddeskController::class, 'export'])->name('export');

        // Dispositions
        Route::prefix('disposition')->name('disposition.')->group(function () {
            Route::get('/create', [FounddeskDispositionController::class, 'create'])->name('create');
            Route::post('/', [FounddeskDispositionController::class, 'store'])->name('store');
            Route::get('/{id}', [FounddeskDispositionController::class, 'show'])->name('show');
            Route::get('/photo/{id}/{type}', [FounddeskDispositionController::class, 'showPhoto'])->name('photo');
            Route::patch('/{id}/cancel', [FounddeskDispositionController::class, 'cancel'])->name('cancel');
        });
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