<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Form realisasi buatan pengguna (Dept / PIC):
|   budget_form        : judul form + item budget tujuan (satu form = satu item)
|   budget_form_field  : field tambahan (teks, angka, tanggal, pilihan)
|   budget_form_isian  : satu isian = satu invoice; nilai field disimpan di kolom JSON "data"
|   budget_pengaturan  : tarif PPN dan nilai materai
|   budget_tagihan.isian_id : penanda baris tagihan yang berasal dari form (unik: satu isian = satu baris)
| Simpan file ini di database/migrations/ lalu: php artisan migrate
*/
return new class extends Migration {
    public function up(): void
    {
        Schema::create('budget_pengaturan', function (Blueprint $t) {
            $t->string('kunci', 50)->primary();
            $t->string('nilai', 100);
            $t->timestamps();
        });
        DB::table('budget_pengaturan')->insert([
            ['kunci' => 'ppn_persen', 'nilai' => '11', 'created_at' => now(), 'updated_at' => now()],
            ['kunci' => 'materai_nominal', 'nilai' => '10000', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('budget_form', function (Blueprint $t) {
            $t->id();
            $t->string('nama', 100);
            $t->string('deskripsi', 255)->nullable();
            $t->unsignedBigInteger('dept_head_user_id')->index();
            $t->unsignedBigInteger('item_id')->index();
            $t->boolean('pakai_ppn')->default(true);
            $t->boolean('pakai_materai')->default(false);
            $t->boolean('aktif')->default(true);
            $t->unsignedBigInteger('dibuat_oleh')->nullable();
            $t->timestamps();
            $t->foreign('item_id')->references('id')->on('budget_item')->restrictOnDelete();
        });

        Schema::create('budget_form_field', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('form_id');
            $t->string('label', 60);
            $t->string('tipe', 20);              // teks | angka | tanggal | pilihan
            $t->text('pilihan')->nullable();     // satu opsi per baris (tipe pilihan)
            $t->boolean('wajib')->default(false);
            $t->unsignedSmallInteger('urut')->default(0);
            $t->boolean('aktif')->default(true); // false = diarsipkan (field yang sudah punya isian tidak dihapus)
            $t->timestamps();
            $t->foreign('form_id')->references('id')->on('budget_form')->cascadeOnDelete();
        });

        Schema::create('budget_form_isian', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('form_id');
            $t->date('tanggal');
            $t->string('no_dokumen', 100)->nullable();
            $t->decimal('dpp', 18, 2)->default(0);
            $t->decimal('ppn_persen', 5, 2)->nullable();   // tarif yang dipakai saat itu (arsip)
            $t->decimal('ppn', 18, 2)->default(0);
            $t->decimal('materai', 18, 2)->default(0);
            $t->decimal('total', 18, 2)->default(0);
            $t->json('data')->nullable();                  // {"id_field": "nilai", ...}
            $t->unsignedBigInteger('input_oleh')->nullable();
            $t->timestamps();
            $t->index(['form_id', 'tanggal']);
            $t->foreign('form_id')->references('id')->on('budget_form')->restrictOnDelete();
        });

        Schema::table('budget_tagihan', function (Blueprint $t) {
            $t->unsignedBigInteger('isian_id')->nullable()->unique()->after('item_id');
            $t->foreign('isian_id')->references('id')->on('budget_form_isian')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('budget_tagihan', function (Blueprint $t) {
            $t->dropForeign(['isian_id']);
            $t->dropColumn('isian_id');
        });
        Schema::dropIfExists('budget_form_isian');
        Schema::dropIfExists('budget_form_field');
        Schema::dropIfExists('budget_form');
        Schema::dropIfExists('budget_pengaturan');
    }
};
