<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('lat', 10, 8)->nullable()->after('note');
            $table->decimal('lng', 11, 8)->nullable()->after('lat');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('lat', 10, 8)->nullable()->after('notes');
            $table->decimal('lng', 11, 8)->nullable()->after('lat');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng']);
        });
    }
};
