<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fingrab_invoices', function (Blueprint $t) {
            $t->id();
            $t->string('invoice_no', 30)->unique();
            $t->string('bcr_no', 20)->index();          // angka di nama file invoice_<angka>_... dan bcr_<angka>_...
            $t->string('gfb_id', 20)->nullable();
            $t->string('pt_name', 150)->nullable();
            $t->date('invoice_date')->nullable();
            $t->date('due_date')->nullable();
            $t->date('period_from')->nullable();
            $t->date('period_to')->nullable();
            $t->decimal('fee_pct', 5, 2)->nullable();
            $t->bigInteger('reimbursement')->default(0); // total invoice (tanpa PPN) dikurangi fee
            $t->bigInteger('fee')->default(0);           // Management Fee sebelum PPN
            $t->bigInteger('tax_base')->nullable();      // Total Tax Based Value (DPP nilai lain)
            $t->bigInteger('vat')->default(0);           // PPN total invoice
            $t->bigInteger('total_excl')->default(0);
            $t->bigInteger('total_incl')->default(0);
            $t->string('file_name', 255)->nullable();
            $t->timestamps();
        });

        Schema::table('fingrab_transactions', function (Blueprint $t) {
            $t->string('bcr_no', 20)->nullable()->after('trip_code')->index();
            $t->unsignedBigInteger('invoice_id')->nullable()->after('bcr_no')->index();
            $t->bigInteger('fee_amount')->default(0)->after('amount');
            $t->bigInteger('vat_amount')->default(0)->after('fee_amount');
        });
    }

    public function down(): void
    {
        Schema::table('fingrab_transactions', function (Blueprint $t) {
            $t->dropColumn(['bcr_no', 'invoice_id', 'fee_amount', 'vat_amount']);
        });
        Schema::dropIfExists('fingrab_invoices');
    }
};
