<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colors', function (Blueprint $table) {
            $table->string('hex_code', 7)->nullable()->after('name');
            $table->integer('brightness')->default(100)->after('hex_code');
            $table->integer('contrast')->default(100)->after('brightness');
        });
    }

    public function down(): void
    {
        Schema::table('colors', function (Blueprint $table) {
            $table->dropColumn(['hex_code', 'brightness', 'contrast']);
        });
    }
};
