<?php

use App\Http\Controllers\budget_c\AksesController;
use App\Http\Controllers\budget_c\BudgetAllController;
use App\Http\Controllers\budget_c\DashboardController;
use App\Http\Controllers\budget_c\FormController;
use App\Http\Controllers\budget_c\GrafikController;
use App\Http\Controllers\budget_c\IsianController;
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

    // Semua peran (admin, Dept, PIC): data otomatis disaring sesuai hak
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/grafik', [GrafikController::class, 'data'])->name('grafik');
    Route::get('/kategori', [KategoriController::class, 'index'])->name('kategori');
    Route::get('/kategori/item/{item}', [KategoriController::class, 'item'])->name('kategori.item');
    Route::get('/budget', [BudgetAllController::class, 'index'])->name('budget.index');
    Route::post('/budget/bulan', [BudgetAllController::class, 'simpanBulan'])->name('budget.bulan');
    Route::get('/tagihan', [TagihanController::class, 'index'])->name('tagihan.index');
    Route::get('/tagihan/cek', [TagihanController::class, 'cek'])->name('tagihan.cek');
    Route::post('/tagihan', [TagihanController::class, 'simpan'])->name('tagihan.simpan');
    Route::delete('/tagihan/{tagihan}', [TagihanController::class, 'hapus'])->name('tagihan.hapus');
    Route::get('/unduh', [TemplateController::class, 'unduh'])->name('unduh');

    // Kategori/subkategori: semua peran boleh MELIHAT dan MENAMBAH (ubah/hapus tetap Admin, dijaga di request & controller)
    Route::get('/master', [MasterController::class, 'index'])->name('master');
    Route::post('/master/kategori', [MasterController::class, 'simpanKategori'])->name('master.simpan');

    // Admin, Dept, dan PIC (PIC punya akses data yang sama dengan Dept-nya)
    Route::middleware($cek . ':admin,dept_head,pic')->group(function () {
        Route::post('/budget/item', [BudgetAllController::class, 'simpanItem'])->name('budget.item.simpan');
        Route::delete('/budget/item/{item}', [BudgetAllController::class, 'hapusItem'])->name('budget.item.hapus');
        Route::post('/budget/salin', [BudgetAllController::class, 'salinTahun'])->name('budget.salin');

        // Form realisasi buatan pengguna (hak per item dan per Dept dicek di controller)
        Route::get('/form', [FormController::class, 'index'])->name('form');
        Route::post('/form', [FormController::class, 'simpan'])->name('form.simpan');
        Route::post('/form/{form}/arsip', [FormController::class, 'arsip'])->whereNumber('form')->name('form.arsip');
        Route::delete('/form/{form}', [FormController::class, 'hapus'])->whereNumber('form')->name('form.hapus');
        Route::get('/isian', [IsianController::class, 'index'])->name('isian');
        Route::get('/isian/unduh', [IsianController::class, 'unduh'])->name('isian.unduh');
        Route::post('/isian', [IsianController::class, 'simpan'])->name('isian.simpan');
        Route::delete('/isian/{isian}', [IsianController::class, 'hapus'])->whereNumber('isian')->name('isian.hapus');

        Route::get('/lookup/bu', [LookupController::class, 'bu'])->name('lookup.bu');
        Route::get('/lookup/pt', [LookupController::class, 'pt'])->name('lookup.pt');
    });

    // Admin dan Dept
    Route::middleware($cek . ':admin,dept_head')->group(function () {
        Route::get('/akses', [AksesController::class, 'index'])->name('akses');
        Route::get('/lookup/user', [LookupController::class, 'user'])->name('lookup.user');
    });

    // Hanya Dept yang menambah dan melepas PIC
    Route::middleware($cek . ':dept_head')->group(function () {
        Route::post('/akses/pic', [AksesController::class, 'tambahPic'])->name('akses.pic.tambah');
        Route::delete('/akses/pic/{picHead}', [AksesController::class, 'lepasPic'])->name('akses.pic.lepas');
    });

    // Khusus Admin
    Route::middleware($cek . ':admin')->group(function () {
        Route::post('/akses', [AksesController::class, 'simpan'])->name('akses.simpan');
        Route::delete('/akses/{akses}', [AksesController::class, 'hapus'])->whereNumber('akses')->name('akses.hapus');
        Route::post('/akses/pindah', [AksesController::class, 'pindahDept'])->name('akses.pindah');

        Route::post('/form/pengaturan', [FormController::class, 'pengaturan'])->name('form.pengaturan');

        Route::post('/master/tahun', [MasterController::class, 'tambahTahun'])->name('master.tahun');
        Route::post('/master/tahun/{tahun}/kunci', [MasterController::class, 'kunci'])->whereNumber('tahun')->name('master.kunci');
        Route::post('/master/tahun/{tahun}/buka', [MasterController::class, 'buka'])->whereNumber('tahun')->name('master.buka');
        Route::delete('/master/kategori/{kategori}', [MasterController::class, 'hapusKategori'])->name('master.kategori.hapus');
        Route::delete('/master/sub/{sub}', [MasterController::class, 'hapusSub'])->name('master.sub.hapus');
    });
});