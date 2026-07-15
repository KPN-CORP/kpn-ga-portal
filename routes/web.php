<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MessengerController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\SSOController;
use App\Http\Controllers\DBLoginController;
use App\Http\Controllers\IDCard\ListController as IDCardListController;
use App\Http\Controllers\IDCard\RequestController as IDCardRequestController;
use App\Http\Controllers\IDCard\DetailController as IDCardDetailController;
use App\Http\Controllers\IDCard\ApprovalController as IDCardApprovalController;
use App\Http\Controllers\IDCard\ReportController as IDCardReportController;
use App\Http\Controllers\IDCard\GrafikController as IDCardGrafikController;
use App\Http\Controllers\IDCard\NonaktifController as IDCardNonaktifController;
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
use App\Http\Controllers\Drms\DriverDashboardController;
use App\Http\Controllers\Drms\VehicleController;
use App\Http\Controllers\Drms\VoucherController;
use App\Http\Controllers\Drms\VehicleDashboardController;
use App\Http\Controllers\Drms\VehicleDocumentController;
use App\Http\Controllers\Drms\ServiceScheduleController;
use App\Http\Controllers\Drms\RepairController;
use App\Http\Controllers\Drms\FuelLogController;
use App\Http\Controllers\Work\WorkReportController;
use App\Http\Controllers\Work\WorkReportCategoryController;
use App\Http\Controllers\Memos\MemosController;
use App\Http\Controllers\Apartemen\EmployeeSearchController;
use App\Http\Controllers\StockCtl\AntarUnitRequestController;
use App\Http\Controllers\StockCtl\AntarUnitApprovalController;
use App\Http\Controllers\Feedbacks\FeedbackController;
use App\Http\Controllers\Feedbacks\AdminFeedbackController;
use App\Http\Controllers\Drms\VehicleMapController;
use App\Http\Controllers\Task_M\TaskMonitorController;
use App\Http\Controllers\CompressFotomailingController;
use App\Http\Controllers\Supplies\SuppliesBarangController;
use App\Http\Controllers\Supplies\SuppliesStokController;
use App\Http\Controllers\Supplies\SuppliesPermintaanController;
use App\Http\Controllers\Supplies\SuppliesApprovalController;
use App\Http\Controllers\Supplies\SuppliesLaporanController;
use App\Http\Controllers\Drms\DriverTripLogController;
use App\Http\Controllers\Drms\AdminOperationalController;
use App\Http\Controllers\Drms\ImageController;
use App\Http\Controllers\HSRM\HsrmCertificateController;
use App\Http\Controllers\HSRM\HsrmEquipmentController;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.process');
});

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::get('/login/sso', [SSOController::class, 'redirect'])->name('login.sso');
Route::get('/login/sso/callback', [SSOController::class, 'callback'])->name('login.sso.callback');
Route::get('/dblogin', [DBLoginController::class, 'handle'])
    ->middleware('sso.darwinbox')
    ->name('sso.login');
Route::get('/dblogin-fallback', [DBLoginController::class, 'handle'])
    ->name('sso.login.fallback')
    ->withoutMiddleware(['sso.darwinbox']);

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

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/access-data', [DashboardController::class, 'getAccessData'])->name('api.access.data');

    Route::prefix('messenger')->middleware('auth')->group(function () {
        Route::middleware('messenger.access:status_messenger')
            ->get('/', [MessengerController::class, 'index'])
            ->name('messenger.index');
        Route::middleware('messenger.access:request_messenger')
            ->get('/request', [MessengerController::class, 'request'])
            ->name('messenger.request');
        Route::middleware('messenger.access:request_messenger')
            ->post('/', [MessengerController::class, 'store'])
            ->name('messenger.store');
        Route::middleware('messenger.access:proses_messenger')
            ->get('/proses', [MessengerController::class, 'proses'])
            ->name('messenger.proses');
        Route::middleware('messenger.access:proses_messenger')->group(function () {
            Route::post('/{no_transaksi}/antar', [MessengerController::class, 'antar'])->name('messenger.antar');
            Route::post('/{no_transaksi}/tolak', [MessengerController::class, 'tolak'])->name('messenger.tolak');
            Route::post('/{no_transaksi}/selesaikan', [MessengerController::class, 'selesaikan'])->name('messenger.selesaikan');
        });
        Route::middleware('messenger.access:detail_messenger')->group(function () {
            Route::get('/{no_transaksi}/print', [MessengerController::class, 'print'])->name('messenger.print');
            Route::post('/{no_transaksi}/cancel', [MessengerController::class, 'cancel'])->name('messenger.cancel');
        });
        Route::middleware('messenger.access:detail_messenger')
            ->get('/file/{type}/{filename}', [MessengerController::class, 'getFile'])
            ->name('messenger.file');
        Route::middleware('messenger.access:detail_messenger')
            ->get('/{id}', [MessengerController::class, 'detail'])
            ->name('messenger.detail');
    });

    Route::prefix('mailing')->name('mailing.')->middleware(['auth'])->group(function () {
        Route::get('/', [MailingController::class, 'index'])->name('index');
        Route::get('/proses', [MailingController::class, 'proses'])->name('proses');
        Route::get('/create', [MailingController::class, 'create'])->name('create');
        Route::post('/store-bulk', [MailingController::class, 'storeBulk'])->name('store.bulk');
        Route::post('/lantai47/{id}', [MailingController::class, 'lantai47'])->name('lantai47');
        Route::post('/selesai/{id}', [MailingController::class, 'selesai'])->name('selesai');
        Route::post('/bulk-lantai47', [MailingController::class, 'bulkLantai47'])->name('bulk-lantai47');
        Route::post('/bulk-selesai', [MailingController::class, 'bulkSelesai'])->name('bulk-selesai');
        Route::get('/pelanggans', [MailingController::class, 'getPelanggans'])->name('get-pelanggans');
        Route::get('/foto/{id}', [MailingController::class, 'viewFoto'])->name('view-foto');
        Route::get('/kompres', [CompressFotomailingController::class, 'index'])->name('kompres');
        Route::post('/kompres-proses', [CompressFotomailingController::class, 'proses'])->name('kompres.proses');
        Route::get('/browse', [CompressFotomailingController::class, 'browse'])->name('kompres.browse');
        Route::get('/kompres/image', [CompressFotomailingController::class, 'showImage'])->name('mailing.kompres.image');
        Route::get('/kompres/browse', [CompressFotomailingController::class, 'browse'])->name('mailing.kompres.browse');
        Route::get('/kompres/image', [CompressFotomailingController::class, 'showImage']);
    });

    Route::middleware(['auth'])->prefix('help')->name('help.')->group(function () {
        Route::get('/tiket/{tiket}/pdf', [HelpTiketPDFController::class, 'download'])->name('tiket.pdf');

        Route::prefix('tiket')->name('tiket.')->group(function () {
            Route::get('/', [HelpTiketController::class, 'index'])->name('index');
            Route::get('/buat', [HelpTiketController::class, 'create'])->name('create');
            Route::post('/', [HelpTiketController::class, 'store'])->name('store');
            Route::get('/{tiket}', [HelpTiketController::class, 'show'])->name('show');
            Route::post('/{tiket}/komentar', [HelpTiketController::class, 'addKomentar'])->name('add-komentar');
            Route::prefix('lampiran')->name('lampiran.')->group(function () {
                Route::get('/{lampiran}/preview', [HelpTiketController::class, 'previewLampiran'])->name('preview');
                Route::get('/{lampiran}/download', [HelpTiketController::class, 'downloadLampiran'])->name('download');
            });
        });

        Route::prefix('proses')->name('proses.')->group(function () {
            Route::prefix('lampiran')->name('lampiran.')->group(function () {
                Route::get('/{lampiran}/preview', [HelpTiketApprovalController::class, 'previewLampiran'])->name('preview');
                Route::get('/{lampiran}/download', [HelpTiketApprovalController::class, 'downloadLampiran'])->name('download');
            });
            Route::get('/', [HelpProsesController::class, 'index'])
                ->middleware('ga.help.admin')
                ->name('index');
            Route::get('/download', [HelpProsesController::class, 'download'])->name('download');
            Route::get('/{id}', [HelpProsesController::class, 'show'])->name('show');
            Route::post('/{id}/take', [HelpProsesController::class, 'take'])->name('take');
            Route::post('/{tiket}/waiting', [HelpTiketApprovalController::class, 'waiting'])->name('waiting');
            Route::post('/{tiket}/resume', [HelpTiketApprovalController::class, 'resume'])->name('resume');
            Route::post('/{tiket}/selesaikan', [HelpTiketApprovalController::class, 'complete'])->name('complete');
            Route::post('/{tiket}/tutup', [HelpTiketApprovalController::class, 'close'])->name('close');
            Route::post('/{tiket}/transfer-to-corp', [HelpTiketApprovalController::class, 'transferToCorp'])->name('transfer-to-corp');
            Route::post('/{tiket}/upload-foto-selesai', [HelpTiketApprovalController::class, 'uploadFotoSelesai'])->name('upload-foto-selesai');
            Route::post('/{tiket}/komentar', [HelpTiketApprovalController::class, 'addKomentar'])->name('add-komentar');
            Route::post('/{tiket}/reassign', [HelpTiketApprovalController::class, 'reassign'])->name('reassign');
        });

        Route::get('/log-sistem', [HelpTiketController::class, 'logSistem'])
            ->name('log-sistem')
            ->middleware('ga_admin');
    });

    Route::middleware(['auth', 'stock.ctl:user'])->prefix('stock-ctl')->name('stock-ctl.')->group(function () {
        Route::get('/dashboard', [StockDashboardController::class, 'index'])->name('dashboard');
        Route::resource('permintaan', PermintaanController::class)->only(['index', 'create', 'store', 'show']);
        Route::get('permintaan/{id}/history', [PermintaanController::class, 'history'])->name('permintaan.history');
        Route::get('approval/l1', [ApprovalL1Controller::class, 'index'])->name('approval.l1.index');
        Route::post('approval/l1/{id}/approve', [ApprovalL1Controller::class, 'approve'])->name('approval.l1.approve');
        Route::post('approval/l1/{id}/reject', [ApprovalL1Controller::class, 'reject'])->name('approval.l1.reject');
        Route::post('/stock-ctl/approval/l1/{id}/update-jumlah', [ApprovalL1Controller::class, 'updateJumlah'])->name('stock-ctl.approval.l1.update-jumlah');

        Route::middleware('stock.ctl:admin')->group(function () {
            Route::get('approval/admin', [ApprovalAdminController::class, 'index'])->name('approval.admin.index');
            Route::post('approval/admin/{id}/approve', [ApprovalAdminController::class, 'approve'])->name('approval.admin.approve');
            Route::post('approval/admin/{id}/reject', [ApprovalAdminController::class, 'reject'])->name('approval.admin.reject');
            Route::get('approval/admin/cek-stok/{id}', [ApprovalAdminController::class, 'cekStok'])->name('approval.admin.cek-stok');

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
            Route::get('cek-stok', [App\Http\Controllers\StockCtl\TransaksiController::class, 'cekStok'])->name('cek-stok');
            Route::resource('opname', OpnameController::class);
            Route::get('laporan', [LaporanController::class, 'index'])->name('laporan.index');
            Route::post('laporan/excel', [LaporanController::class, 'excel'])->name('laporan.excel');
            Route::get('transaksi', [TransaksiController::class, 'index'])->name('transaksi.index');
            Route::get('laporan/history', [LaporanController::class, 'history'])->name('laporan.history');
        });

        Route::middleware('stock.ctl:superadmin')->group(function () {
            Route::resource('area-kerja', SuperadminController::class)->parameters(['area-kerja' => 'area'])->names('area');
            Route::get('user-profil', [SuperadminController::class, 'userProfilIndex'])->name('user-profil.index');
            Route::get('user-profil/{id}', [SuperadminController::class, 'getUserProfil'])->name('user-profil.get');
            Route::post('user-profil/{id}', [SuperadminController::class, 'userProfilUpdate'])->name('user-profil.update');
        });

        Route::prefix('antar-unit')->group(function () {
            Route::get('/', [AntarUnitRequestController::class, 'index'])->name('antar-unit.index');
            Route::get('/create', [AntarUnitRequestController::class, 'create'])->name('antar-unit.create');
            Route::post('/store', [AntarUnitRequestController::class, 'store'])->name('antar-unit.store');
            Route::get('/approval', [AntarUnitApprovalController::class, 'index'])->name('antar-unit.approval');
            Route::get('cek-stok-unit', [AntarUnitApprovalController::class, 'cekStokUnit'])->name('stock-ctl.cek-stok-unit');
            Route::get('get-areas', [AntarUnitApprovalController::class, 'getAreasByUnit'])->name('get-areas');
            Route::get('/{id}', [AntarUnitRequestController::class, 'show'])->name('antar-unit.show');
            Route::post('/{id}/approve', [AntarUnitApprovalController::class, 'approve'])->name('antar-unit.approve');
            Route::post('/{id}/reject', [AntarUnitApprovalController::class, 'reject'])->name('antar-unit.reject');
        });
    });

    Route::middleware(['auth'])->prefix('drms')->name('drms.')->group(function () {
        // ===== REQUESTS =====
        Route::get('requests', [RequestController::class, 'index'])->name('requests.index');
        Route::get('requests/create', [RequestController::class, 'create'])->name('requests.create');
        Route::post('requests', [RequestController::class, 'store'])->name('requests.store');
        Route::get('requests/{driverRequest}', [RequestController::class, 'show'])->name('requests.show');

        // ===== APPROVAL L1 =====
        Route::middleware(['can:isApprover'])->prefix('approval/l1')->name('approval.l1.')->group(function () {
            Route::get('/', [AppL1Controller::class, 'index'])->name('index');
            Route::post('{id}/approve', [AppL1Controller::class, 'approve'])->name('approve');
            Route::post('{id}/reject', [AppL1Controller::class, 'reject'])->name('reject');
        });

        // ===== APPROVAL ADMIN =====
        Route::middleware(['can:isDrmsAdmin'])->prefix('approval/admin')->name('approval.admin.')->group(function () {
            Route::get('/', [AppAdminController::class, 'index'])->name('index');
            Route::get('export', [AppAdminController::class, 'export'])->name('export');
            Route::get('{id}/edit', [AppAdminController::class, 'edit'])->name('edit');
            Route::put('{id}', [AppAdminController::class, 'update'])->name('update');
            Route::put('{id}/reject', [AppAdminController::class, 'reject'])->name('reject');
            Route::post('{driverRequest}/complete', [AppAdminController::class, 'complete'])->name('complete');
            Route::put('{id}/forward', [AppAdminController::class, 'forward'])->name('forward');
            Route::get('/operational-export', [AdminOperationalController::class, 'exportDashboard'])->name('admin.operational.export');
            Route::get('/operational-dashboard/export', [AdminOperationalController::class, 'exportDashboard'])->name('admin.operational.export');
        });

        // ===== DRIVER DASHBOARD =====
        Route::middleware(['is_driver'])->group(function () {
            Route::get('/driver/dashboard', [DriverDashboardController::class, 'index'])->name('driver.dashboard');
            Route::get('/driver/requests/{driverRequest}', [DriverDashboardController::class, 'show'])->name('driver.requests.show');
            Route::post('/driver/requests/{driverRequest}/complete', [DriverDashboardController::class, 'complete'])->name('driver.requests.complete');
        });

        Route::get('drivers/dashboard', function () {
            return redirect()->route('drms.driver.dashboard');
        });

        // ===== VEHICLES MAP (HARUS DI ATAS RESOURCE) =====
        Route::get('vehicles/map', [VehicleMapController::class, 'index'])
            ->name('vehicles.map')
            ->middleware('can:superadmin');

        Route::get('vehicles/{vehicle}/map', [VehicleMapController::class, 'show'])
            ->name('vehicles.map.single');

        // ===== MASTER DATA (Admin) =====
        Route::middleware(['can:isDrmsAdmin'])->group(function () {
            Route::get('drivers/schedule', [DriverController::class, 'schedule'])->name('drivers.schedule');
            Route::resource('drivers', DriverController::class)->except(['show']);
            Route::resource('vehicles', VehicleController::class);  // ← resource di bawah custom route
            Route::resource('vouchers', VoucherController::class);
        });

        // ===== DRIVER TRIP LOG =====
        Route::prefix('driver')->group(function () {
            Route::get('/trip-log/{requestId}/create', [DriverTripLogController::class, 'create'])
                ->name('driver.trip.log.create');
            Route::post('/trip-log/{requestId}/store', [DriverTripLogController::class, 'store'])
                ->name('driver.trip.log.store');
        });

        // ===== ADMIN OPERATIONAL =====
        Route::prefix('admin')->middleware(['auth'])->group(function () {
            Route::get('/operational-dashboard', [AdminOperationalController::class, 'dashboard'])
                ->name('admin.operational.dashboard');
            Route::get('/monitoring-logs', [AdminOperationalController::class, 'monitoringLogs'])
                ->name('admin.monitoring.logs');
            Route::get('/verify-log/{logId}', [AdminOperationalController::class, 'verifyLogForm'])
                ->name('admin.verify.log');
            Route::post('/verify-log/{logId}', [AdminOperationalController::class, 'verifyLog'])
                ->name('admin.verify.log.post');
            Route::get('/operational-export', [AdminOperationalController::class, 'export'])
                ->name('admin.operational.export');
            Route::get('/monitoring-logs', [AdminOperationalController::class, 'monitoringLogs'])
                ->name('admin.monitoring.logs');
        });

        
        // ===== SERVIS RUTIN =====
        Route::resource('service-schedules', ServiceScheduleController::class);

        // ===== PERBAIKAN =====
        Route::patch('repairs/{id}/status', [RepairController::class, 'updateStatus'])->name('repairs.updateStatus');
        Route::resource('repairs', RepairController::class);

        // ===== BBM =====
        Route::get('fuel-logs/analytics', [FuelLogController::class, 'analytics'])->name('fuel-logs.analytics');
        Route::patch('fuel-logs/{id}/verify', [FuelLogController::class, 'verify'])->name('fuel-logs.verify');
        Route::resource('fuel-logs', FuelLogController::class);

        // ===== DOKUMEN KENDARAAN =====
        Route::resource('vehicle-documents', VehicleDocumentController::class);

        // ===== PRIVATE IMAGE =====
        Route::get('/private-image/{path}', [ImageController::class, 'show'])
            ->where('path', '.*')
            ->middleware(['auth'])
            ->name('private.image');
    });

    Route::prefix('apartemen')->group(function () {
        Route::middleware('apartemen.access:apt_user')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('apartemen.user.index');
            Route::get('/requests', [UserController::class, 'requests'])->name('apartemen.user.requests');
            Route::get('/create', [UserController::class, 'create'])->name('apartemen.user.create');
            Route::post('/store', [UserController::class, 'store'])->name('apartemen.user.store');
            Route::get('/show/{id}', [UserController::class, 'show'])->name('apartemen.user.show');
        });

        Route::middleware('apartemen.access:apt_admin')
            ->prefix('admin')
            ->group(function () {
                Route::get('/', [AdminController::class, 'index'])->name('apartemen.admin.index');
                Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('apartemen.admin.dashboard');
                Route::get('/approve/{id}', [AdminController::class, 'approve'])->name('apartemen.admin.approve');
                Route::post('/approve/{id}', [AdminController::class, 'approveProcess'])->name('apartemen.admin.approve.process');
                Route::get('/assign/{id}', [AdminController::class, 'assign'])->name('apartemen.admin.assign');
                Route::post('/assign', [AssignController::class, 'store'])->name('apartemen.admin.assign.store');
                Route::put('/assign/{id}', [AssignController::class, 'update'])->name('apartemen.admin.assign.update');
                Route::get('/monitoring', [AdminController::class, 'monitoring'])->name('apartemen.admin.monitoring');
                Route::get('/history', [AdminController::class, 'history'])->name('apartemen.admin.history');
                Route::get('/apartemen', [AdminController::class, 'apartemen'])->name('apartemen.admin.apartemen');
                Route::get('/apartemen/{id}', [AdminController::class, 'apartemenDetail'])->name('apartemen.admin.apartemen.detail');
                Route::post('/penghuni/{id}/checkout', [AdminController::class, 'checkoutPenghuni'])->name('apartemen.admin.penghuni.checkout');
                Route::post('/penghuni/{id}/checkin', [AdminController::class, 'checkin'])->name('checkin');
                Route::post('/transfer/{id}', [AssignController::class, 'transfer'])->name('apartemen.admin.transfer');
                Route::post('/maintenance/{id}', [AdminController::class, 'setMaintenance'])->name('apartemen.admin.maintenance');
                Route::get('/request/{id}/detail', [AdminController::class, 'detail'])->name('apartemen.admin.detail');
                Route::get('/report', [AdminController::class, 'report'])->name('apartemen.admin.report');
                Route::post('/unit/store', [AdminController::class, 'storeUnit'])->name('apartemen.admin.unit.store');
                Route::post('/unit/delete', [AdminController::class, 'deleteUnit'])->name('apartemen.admin.unit.delete');
                Route::post('/unit/maintenance', [AdminController::class, 'setMaintenance'])->name('apartemen.admin.setMaintenance');
                Route::post('/apartemen/store', [AdminController::class, 'storeApartemen'])->name('apartemen.admin.apartemen.store');

                Route::get('/access-codes', [QRCodeAdminController::class, 'index'])->name('apartemen.admin.access-codes');
                Route::post('/generate-qr', [QRCodeAdminController::class, 'generate'])->name('apartemen.admin.generate-qr');
                Route::post('/access-codes/{id}/deactivate', [QRCodeAdminController::class, 'deactivate'])->name('apartemen.admin.deactivate-code');
                Route::post('/access-codes/{id}/activate', [QRCodeAdminController::class, 'activate'])->name('apartemen.admin.activate-code');
                Route::delete('/access-codes/{id}', [QRCodeAdminController::class, 'destroy'])->name('apartemen.admin.delete-code');
                Route::get('/access-codes/{id}/print', [QRCodeAdminController::class, 'print'])->name('apartemen.admin.print-code');

                Route::get('/calendar-events', [AdminController::class, 'calendarEvents'])->name('apartemen.admin.calendar.events');
                Route::get('/report/export', [AdminController::class, 'exportReport'])->name('apartemen.admin.report.export');
                Route::get('/unit/create/{apartemen_id}', [AdminController::class, 'createUnitForm'])->name('unit.create');
                Route::post('/unit/store', [AdminController::class, 'storeUnit'])->name('unit.store');
                Route::get('/unit/{id}/edit', [AdminController::class, 'editUnitForm'])->name('unit.edit');
                Route::put('/unit/{id}', [AdminController::class, 'updateUnit'])->name('unit.update');
            });

        Route::middleware('apartemen.access:apt_detail')->group(function () {
            Route::get('/detail/{unit_id}', [DetailController::class, 'show'])->name('apartemen.detail');
        });

        Route::middleware('apartemen.access:apt_history')->group(function () {
            Route::get('/history', function () { return view('apartemen.history'); })->name('apartemen.history');
        });
    });

    Route::middleware(['auth', 'setting.access'])
        ->get('/setting-access', [SettingAccessController::class, 'index'])
        ->name('setting.access.index');
    Route::middleware(['auth', 'setting.access'])
        ->post('/setting-access', [SettingAccessController::class, 'store'])
        ->name('setting.access.store');
    Route::middleware(['auth', 'setting.access'])
        ->get('/setting-access/export', [SettingAccessController::class, 'export'])
        ->name('setting.access.export');
    Route::middleware(['auth', 'setting.access'])
        ->post('/setting-access/export-all', [SettingAccessController::class, 'exportAll'])
        ->name('setting.access.exportAll');

    Route::middleware(['auth'])
        ->get('/informasi', [MenuInformationController::class, 'index'])
        ->name('menu.information');

    Route::prefix('idcard')
    ->name('idcard.')
    ->middleware(['auth', 'check.idcard.access'])
    ->group(function () {

        // Halaman utama (list)
        Route::get('/', [IDCardListController::class, 'index'])
            ->name('index')
            ->middleware('check.idcard.access:list');

        // STATIC ROUTES (tanpa parameter)
        Route::get('/aktif', [IDCardListController::class, 'aktif'])
            ->name('aktif')
            ->middleware('check.idcard.access:list');

        Route::get('/inaktif', [IDCardListController::class, 'inaktif'])
            ->name('inaktif')
            ->middleware('check.idcard.access:list');

        Route::get('/request', [IDCardRequestController::class, 'create'])
            ->name('request')
            ->middleware('check.idcard.access:request');

        Route::post('/', [IDCardRequestController::class, 'store'])
            ->name('store')
            ->middleware('check.idcard.access:request');

        Route::get('/grafik', [IDCardGrafikController::class, 'index'])
            ->name('grafik')
            ->middleware('check.idcard.access:grafik');

        Route::get('/report', [IDCardReportController::class, 'index'])
            ->name('report')
            ->middleware('check.idcard.access:report');

        Route::get('/report/download', [IDCardReportController::class, 'download'])
            ->name('report.download')
            ->middleware('check.idcard.access:report');

        Route::get('/photo/{filename}', [IDCardDetailController::class, 'photo'])
            ->name('photo')
            ->middleware('auth');

        Route::get('/{id}', [IDCardDetailController::class, 'detail'])
            ->name('detail')
            ->middleware('check.idcard.access:detail');

        Route::get('/{id}/edit', [IDCardDetailController::class, 'edit'])
            ->name('edit')
            ->middleware('check.idcard.access:proses');

        Route::put('/{id}', [IDCardDetailController::class, 'update'])
            ->name('update')
            ->middleware('check.idcard.access:proses');

        Route::post('/{id}/approve', [IDCardApprovalController::class, 'approve'])
            ->name('approve')
            ->middleware('check.idcard.access:proses');

        Route::post('/{id}/reject', [IDCardApprovalController::class, 'reject'])
            ->name('reject')
            ->middleware('check.idcard.access:proses');

        Route::post('/{id}/nonaktifkan', [IDCardNonaktifController::class, 'nonaktifkanSatu'])
            ->name('nonaktifkan.satu')
            ->middleware('check.idcard.access:proses');

        Route::post('/{id}/aktifkan', [IDCardNonaktifController::class, 'aktifkanSatu'])
            ->name('aktifkan.satu')
            ->middleware('check.idcard.access:proses');
    });

    Route::prefix('employees')->middleware(['web', 'auth'])->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])
            ->name('employees.index')
            ->middleware('employees.access:emp_index');
        Route::get('/{employee}', [EmployeeController::class, 'show'])
            ->name('employees.show')
            ->middleware('employees.access:emp_show');
    });
    Route::get('/employee/search', [EmployeeSearchController::class, 'search'])->name('employee.search');

    Route::middleware(['auth'])->prefix('track-r')->group(function () {
        Route::get('/', [TrackRController::class, 'index'])->name('track-r.index');
        Route::get('/create', [TrackRController::class, 'create'])->name('track-r.create');
        Route::post('/', [TrackRController::class, 'store'])->name('track-r.store');
        Route::get('/{id}', [TrackRController::class, 'show'])->name('track-r.show');
        Route::post('/{id}/terima', [TrackRController::class, 'terima'])->name('track-r.terima');
        Route::post('/{id}/tolak', [TrackRController::class, 'tolak'])->name('track-r.tolak');
        Route::post('/{id}/teruskan', [TrackRController::class, 'teruskan'])->name('track-r.teruskan');
        Route::get('/{id}/pdf', [TrackRController::class, 'pdf'])->name('track-r.pdf');
        Route::get('track-r/export', [TrackRController::class, 'export'])->name('track-r.export');
        Route::get('/{document}/foto/{foto}/download', [TrackRController::class, 'downloadFoto'])
            ->name('track-r.foto.download');
        Route::get('/track-foto/view/{id}', [App\Http\Controllers\TrackFotoController::class, 'view'])
            ->name('track-foto.view');
        Route::get('/track-foto/download/{id}', [App\Http\Controllers\TrackFotoController::class, 'download'])
            ->name('track-foto.download');
    });

    Route::middleware(['auth'])->prefix('work-reports')->name('work-reports.')->group(function () {
        Route::get('/', [WorkReportController::class, 'index'])->name('index');
        Route::get('/create', [WorkReportController::class, 'create'])->name('create');
        Route::post('/', [WorkReportController::class, 'store'])->name('store');
        Route::get('/export', [WorkReportController::class, 'export'])->name('export');
        Route::get('/chart', [WorkReportController::class, 'chart'])->name('chart');
        Route::get('/{workReport}/edit', [WorkReportController::class, 'edit'])->name('edit');
        Route::put('/{workReport}', [WorkReportController::class, 'update'])->name('update');
        Route::delete('/{workReport}', [WorkReportController::class, 'destroy'])->name('destroy');
    });

    Route::get('/private-storage/{path}', function ($path) {
        $fullPath = storage_path('app/private/' . $path);
        if (!file_exists($fullPath)) {
            abort(404);
        }
        return response()->file($fullPath);
    })->where('path', '.*')->name('private.storage')->middleware('auth');

    Route::middleware(['auth'])->prefix('work-reports-categories')->name('work-reports.categories.')->group(function () {
        Route::get('/', [WorkReportCategoryController::class, 'index'])->name('index');
        Route::get('/create', [WorkReportCategoryController::class, 'create'])->name('create');
        Route::post('/', [WorkReportCategoryController::class, 'store'])->name('store');
        Route::get('/{workReportCategory}/edit', [WorkReportCategoryController::class, 'edit'])->name('edit');
        Route::put('/{workReportCategory}', [WorkReportCategoryController::class, 'update'])->name('update');
        Route::delete('/{workReportCategory}', [WorkReportCategoryController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('founddesk')
        ->name('founddesk.')
        ->middleware(['auth', 'founddesk.access'])
        ->group(function () {
            Route::get('/', [FounddeskController::class, 'index'])->name('index');
            Route::get('/create', [FounddeskController::class, 'create'])->name('create');
            Route::post('/', [FounddeskController::class, 'store'])->name('store');
            Route::delete('/{id}', [FounddeskController::class, 'destroy'])->name('destroy');
            Route::get('/photo/{id}', [FounddeskController::class, 'showPhoto'])->name('photo');
            Route::get('/export/csv', [FounddeskController::class, 'export'])->name('export');

            Route::prefix('disposition')->name('disposition.')->group(function () {
                Route::get('/create', [FounddeskDispositionController::class, 'create'])->name('create');
                Route::post('/', [FounddeskDispositionController::class, 'store'])->name('store');
                Route::get('/{id}', [FounddeskDispositionController::class, 'show'])->name('show');
                Route::get('/photo/{id}/{type}', [FounddeskDispositionController::class, 'showPhoto'])->name('photo');
                Route::patch('/{id}/cancel', [FounddeskDispositionController::class, 'cancel'])->name('cancel');
            });
        });

    Route::middleware(['auth'])->group(function () {
        Route::resource('memos', MemosController::class);
        Route::patch('memos/attachment/{attachment}/checklist', [MemosController::class, 'updateChecklist'])->name('memos.checklist');
        Route::get('/api/terbilang/{amount}', [MemosController::class, 'terbilang'])->name('api.terbilang');
    });

    Route::middleware(['auth'])->prefix('feedbacks')->name('feedbacks.')->group(function () {
        Route::get('/', [FeedbackController::class, 'index'])->name('index');
        Route::get('/create', [FeedbackController::class, 'create'])->name('create');
        Route::post('/', [FeedbackController::class, 'store'])->name('store');
        Route::get('/{id}', [FeedbackController::class, 'show'])->name('show');
        Route::post('/{id}/reply', [FeedbackController::class, 'reply'])->name('reply');
    });

    Route::middleware(['auth', 'feedback.admin'])->prefix('admin/feedbacks')->name('feedbacks.admin.')->group(function () {
        Route::get('/', [AdminFeedbackController::class, 'index'])->name('index');
        Route::get('/{id}', [AdminFeedbackController::class, 'show'])->name('show');
        Route::post('/{id}/reply', [AdminFeedbackController::class, 'reply'])->name('reply');
        Route::patch('/{id}/toggle-status', [AdminFeedbackController::class, 'toggleStatus'])->name('toggle-status');
    });

    Route::prefix('task-m')->name('task-m.')->middleware('auth')->group(function () {
        Route::get('/', [TaskMonitorController::class, 'index'])->name('index');
        Route::get('/user/{userId}/projects', [TaskMonitorController::class, 'userProjects'])->name('user.projects');
        Route::post('/', [TaskMonitorController::class, 'store'])->name('store');
        Route::get('/units', [TaskMonitorController::class, 'unitsList'])->name('units');
        Route::get('/{id}', [TaskMonitorController::class, 'show'])->name('show');
        Route::put('/{id}', [TaskMonitorController::class, 'update'])->name('update');
        Route::delete('/{id}', [TaskMonitorController::class, 'destroy'])->name('destroy');
        Route::post('/{projectId}/units', [TaskMonitorController::class, 'addUnit'])->name('add-unit');
        Route::patch('/{projectId}/units/{unitId}/status', [TaskMonitorController::class, 'updateUnitStatus'])->name('update-unit-status');
        Route::put('/{projectId}/units/{unitId}/description', [TaskMonitorController::class, 'updateUnitDescription'])->name('update-unit-description');
        Route::delete('/{projectId}/units/{unitId}', [TaskMonitorController::class, 'deleteUnit'])->name('delete-unit');
    });

    Route::prefix('supplies')->name('supplies.')->middleware('auth')->group(function () {
        Route::middleware('supplies.access:user')->group(function () {
            Route::resource('permintaan', SuppliesPermintaanController::class)->only(['index', 'create', 'store', 'show']);
        });
        Route::middleware('supplies.access:admin')->group(function () {
            Route::resource('barang', SuppliesBarangController::class)->except(['show']);
            Route::get('stok', [SuppliesStokController::class, 'index'])->name('stok.index');
            Route::get('stok/masuk', [SuppliesStokController::class, 'createMasuk'])->name('stok.masuk');
            Route::post('stok/masuk', [SuppliesStokController::class, 'storeMasuk'])->name('stok.masuk.store');
            Route::get('approval', [SuppliesApprovalController::class, 'index'])->name('approval.index');
            Route::post('approval/{id}/approve', [SuppliesApprovalController::class, 'approve'])->name('approval.approve');
            Route::post('approval/{id}/reject', [SuppliesApprovalController::class, 'reject'])->name('approval.reject');
            Route::get('laporan', [SuppliesLaporanController::class, 'index'])->name('laporan.index');
            Route::post('laporan/pdf', [SuppliesLaporanController::class, 'pdf'])->name('laporan.pdf');
            Route::get('laporan/history', [SuppliesLaporanController::class, 'history'])->name('laporan.history');
            Route::post('laporan/export', [SuppliesLaporanController::class, 'export'])->name('laporan.export');
        });
    });

    Route::middleware(['auth', 'hsrm.access'])->prefix('hsrm')->name('hsrm.')->group(function () {
        Route::get('/', [App\Http\Controllers\HSRM\HsrmDashboardController::class, 'index'])->name('dashboard');
        Route::get('certificates/export', [App\Http\Controllers\HSRM\HsrmCertificateController::class, 'export'])->name('certificates.export');
        Route::get('equipments/export', [App\Http\Controllers\HSRM\HsrmEquipmentController::class, 'export'])->name('equipments.export');
        Route::resource('certificates', App\Http\Controllers\HSRM\HsrmCertificateController::class);
        Route::resource('equipments', App\Http\Controllers\HSRM\HsrmEquipmentController::class);
        Route::post('certificates/{certificate}/approve', [App\Http\Controllers\HSRM\HsrmCertificateController::class, 'approve'])->name('certificates.approve');
        Route::post('certificates/{certificate}/reject', [App\Http\Controllers\HSRM\HsrmCertificateController::class, 'reject'])->name('certificates.reject');
        Route::post('equipments/{equipment}/approve', [App\Http\Controllers\HSRM\HsrmEquipmentController::class, 'approve'])->name('equipments.approve');
        Route::post('equipments/{equipment}/reject', [App\Http\Controllers\HSRM\HsrmEquipmentController::class, 'reject'])->name('equipments.reject');
        Route::resource('certificate-types', App\Http\Controllers\HSRM\HsrmCertificateTypeController::class)->except(['show']);
        Route::resource('equipment-types', App\Http\Controllers\HSRM\HsrmEquipmentTypeController::class)->except(['show']);
        Route::get('logs', [App\Http\Controllers\HSRM\HsrmLogController::class, 'index'])->name('logs.index');
        Route::get('download/{type}/{id}/{old_index?}', [App\Http\Controllers\HSRM\HsrmFileController::class, 'download'])->name('file.download');
        Route::get('/certificates/filter/{filter}', [HsrmCertificateController::class, 'index'])->name('certificates.filter');
        Route::get('/equipments/filter/{filter}', [HsrmEquipmentController::class, 'index'])->name('equipments.filter');
        Route::get('approvals', [App\Http\Controllers\HSRM\HsrmApprovalController::class, 'index'])->name('approvals.index');

        // === Admin Quota Management ===
        Route::get('admin/quotas', [App\Http\Controllers\HSRM\HsrmQuotaController::class, 'index'])->name('admin.quotas.index');
        Route::post('admin/quotas/update', [App\Http\Controllers\HSRM\HsrmQuotaController::class, 'update'])->name('admin.quotas.update');
        Route::get('admin/quotas/export', [App\Http\Controllers\HSRM\HsrmQuotaController::class, 'export'])->name('admin.quotas.export');
    });

    Route::get('/no-access', function () {
        return view('no-access');
    })->name('no-access');

    Route::get('/upload', [ImageController::class, 'index'])->name('image.form');
    Route::post('/upload', [ImageController::class, 'upload'])->name('image.upload');
});