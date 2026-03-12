<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockCtlLaporanHistoryTable extends Migration
{
    public function up()
    {
        Schema::create('stock_ctl_laporan_history', function (Blueprint $table) {
            $table->id('id_history');
            $table->unsignedBigInteger('id_user');
            $table->string('jenis', 20); // stok, mutasi, permintaan
            $table->unsignedBigInteger('id_area')->nullable();
            $table->unsignedBigInteger('id_barang')->nullable();
            $table->date('tanggal_awal')->nullable();
            $table->date('tanggal_akhir')->nullable();
            $table->timestamp('dicetak_pada')->useCurrent();
            $table->string('nama_file')->nullable(); // opsional
            $table->foreign('id_user')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_area')->references('id_area_kerja')->on('stock_ctl_area_kerja')->onDelete('set null');
            $table->foreign('id_barang')->references('id_barang')->on('stock_ctl_barang')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_ctl_laporan_history');
    }
}