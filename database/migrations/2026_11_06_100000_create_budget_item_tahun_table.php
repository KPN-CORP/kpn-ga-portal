<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Item terdaftar per tahun. Item (beserta subkategori dan kategorinya) hanya tampil di tahun yang terdaftar,
 * jadi tahun baru kosong sampai item diambil lewat "Salin dari tahun lain" atau dibuat di tahun itu.
 * Data lama: setiap item didaftarkan ke tahun yang sudah ada sampai tahun berjalan (sesuai tahun_mulai/tahun_akhir),
 * jadi tampilan tahun-tahun itu tidak berubah. Tahun mendatang tidak didaftarkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_item_tahun', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('item_id');
            $t->unsignedSmallInteger('tahun');
            $t->timestamps();
            $t->unique(['item_id', 'tahun']);
            $t->index('tahun');
        });

        $tahunList = DB::table('budget_tahun')->where('tahun', '<=', (int) date('Y'))->pluck('tahun')->map(fn ($v) => (int) $v)->all();
        $sekarang = now();

        DB::table('budget_item')->orderBy('id')->chunkById(500, function ($items) use ($tahunList, $sekarang) {
            $baris = [];
            foreach ($items as $i) {
                foreach ($tahunList as $y) {
                    if (($i->tahun_mulai === null || (int) $i->tahun_mulai <= $y) && ($i->tahun_akhir === null || (int) $i->tahun_akhir >= $y)) {
                        $baris[] = ['item_id' => $i->id, 'tahun' => $y, 'created_at' => $sekarang, 'updated_at' => $sekarang];
                    }
                }
            }
            foreach (array_chunk($baris, 500) as $c) {
                DB::table('budget_item_tahun')->insert($c);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_item_tahun');
    }
};
