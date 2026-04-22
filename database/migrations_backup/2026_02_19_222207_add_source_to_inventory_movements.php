<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            // Campos para vincular movimiento a una Venta (u otro origen futuro)
            $table->string('source_type')->nullable()->after('created_by'); 
            $table->unsignedBigInteger('source_id')->nullable()->after('source_type');
            
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex(['source_type', 'source_id']);
            $table->dropColumn(['source_type', 'source_id']);
        });
    }
};
