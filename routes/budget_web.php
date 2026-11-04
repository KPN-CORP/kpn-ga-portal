<?php

use App\Http\Controllers\budget_c\AksesController;
use App\Http\Controllers\budget_c\BudgetAllController;
use App\Http\Controllers\budget_c\DashboardController;
use App\Http\Controllers\budget_c\GrafikController;
use App\Http\Controllers\budget_c\KategoriController;
use App\Http\Controllers\budget_c\LookupController;
use App\Http\Controllers\budget_c\MasterController;
use App\Http\Controllers\budget_c\TagihanController;
use App\Http\Controllers\budget_c\TemplateController;
use App\Http\Middleware\budget_mw\CekAksesBudget;
use Illuminate\Support\Facades\Route;

/*
| Modul Budgeting GA. Pintu masuk: /budgeting
| Di-require dari routes/web.php (satu baris). Middleware ditulis dengan nama kelas,
| jadi TIDAK perlu didaftarkan di Kernel.php / bootstrap/app.php.
| Nama controller memakai namespace budget_c, jadi tidak bentrok dengan DashboardController/GrafikController yang sudah ada.
*/

$cek = CekAksesBudget::class;

Route::middleware(['auth', $cek])->prefix('budgeting')->name('budgeting.')->group(function () use ($cek) {

    // Semua peran (admin, dept head, PIC): data otomatis disaring sesuai hak
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/grafik', [GrafikController::class, 'data'])->name('grafik');
    Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori');
    Route::get('/kategori/item/{item}', [KategoriController::class, 'item'])->name('kategori.item');
    Route::get('/budget', [BudgetAllController::class, 'index'])->name('budget.index');
    Route::post('/budget/bulan', [BudgetAllController::class, 'simpanBulan'])->name('budget.bulan');
    Route::get('/tagihan', [TagihanController::class, 'index'])->name('tagihan.index');
    Route::post('/tagihan', [TagihanController::class, 'simpan'])->name('tagihan.simpan');
    Route::delete('/tagihan/{tagihan}', [TagihanController::class, 'hapus'])->name('tagihan.hapus');
    Route::get('/unduh', [TemplateController::class, 'unduh'])->name('unduh');

    // Kategori/subkategori: semua peran boleh MELIHAT dan MENAMBAH (ubah/hapus tetap Admin, dijaga di request & controller)
    Route::get('/master', [MasterController::class, 'index'])->name('master');
    Route::post('/master/kategori', [MasterController::class, 'simpanKategori'])->name('master.simpan');

    // Admin dan Dept Head
    Route::middleware($cek . ':admin,dept_head')->group(function () {
        Route::post('/budget/item', [BudgetAllController::class, 'simpanItem'])->name('budget.item.simpan');
        Route::delete('/budget/item/{item}', [BudgetAllController::class, 'hapusItem'])->name('budget.item.hapus');
        Route::post('/budget/salin', [BudgetAllController::class, 'salinTahun'])->name('budget.salin');

        Route::get('/akses', [AksesController::class, 'index'])->name('akses');
        Route::post('/akses/pic', [AksesController::class, 'tambahPic'])->name('akses.pic.tambah');
        Route::delete('/akses/pic/{picHead}', [AksesController::class, 'lepasPic'])->name('akses.pic.lepas');

        Route::get('/lookup/user', [LookupController::class, 'user'])->name('lookup.user');
        Route::get('/lookup/bu', [LookupController::class, 'bu'])->name('lookup.bu');
        Route::get('/lookup/pt', [LookupController::class, 'pt'])->name('lookup.pt');
    });

    // Khusus Admin
    Route::middleware($cek . ':admin')->group(function () {
        Route::post('/akses', [AksesController::class, 'simpan'])->name('akses.simpan');

        Route::post('/master/tahun', [MasterController::class, 'tambahTahun'])->name('master.tahun');
        Route::post('/master/tahun/{tahun}/kunci', [MasterController::class, 'kunci'])->whereNumber('tahun')->name('master.kunci');
        Route::post('/master/tahun/{tahun}/buka', [MasterController::class, 'buka'])->whereNumber('tahun')->name('master.buka');
        Route::delete('/master/kategori/{kategori}', [MasterController::class, 'hapusKategori'])->name('master.kategori.hapus');
        Route::delete('/master/sub/{sub}', [MasterController::class, 'hapusSub'])->name('master.sub.hapus');
    });
});