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
        // 1. PURGE: Drop existing tables if they exist to start from zero
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('injection_report_items');
        Schema::dropIfExists('injection_reports');
        Schema::enableForeignKeyConstraints();

        // 2. REBUILD: Create the fresh schema
        Schema::create('injection_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('employee_name');
            $table->string('position')->nullable();
            $table->string('department')->nullable();
            $table->string('week_range')->nullable();
            $table->text('proposals')->nullable();
            $table->text('next_week_plan')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('injection_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('injection_report_id')->constrained()->cascadeOnDelete();
            $table->date('date')->nullable();
            $table->string('day')->nullable();
            $table->string('activity')->nullable();
            $table->text('description')->nullable();
            $table->text('result')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('injection_report_items');
        Schema::dropIfExists('injection_reports');
    }
};
