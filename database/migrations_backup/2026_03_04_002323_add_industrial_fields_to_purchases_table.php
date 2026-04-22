<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('invoice_series', 20)->nullable()->after('purchase_number');
            $table->string('supplier_invoice_number', 50)->nullable()->after('invoice_series');
            $table->enum('payment_condition', ['cash', 'credit'])->default('cash')->after('purchase_date');
            $table->date('due_date')->nullable()->after('payment_condition');
            $table->decimal('tax_amount', 14, 3)->default(0)->after('total');
            $table->string('category')->nullable()->after('tax_amount');
            $table->text('notes')->nullable()->after('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            //
        });
    }
};
