<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->time('start_time')->nullable()->after('name');
            $table->time('end_time')->nullable()->after('start_time');
        });

        // Set default times for existing shifts based on common naming
        $shifts = \App\Models\Shift::all();
        foreach ($shifts as $shift) {
            $name = mb_strtolower($shift->name);
            if (str_contains($name, 'mañana') || str_contains($name, 'morning') || str_contains($name, 'manana')) {
                $shift->update(['start_time' => '06:00', 'end_time' => '14:00']);
            } elseif (str_contains($name, 'tarde') || str_contains($name, 'afternoon')) {
                $shift->update(['start_time' => '14:00', 'end_time' => '22:00']);
            } elseif (str_contains($name, 'noche') || str_contains($name, 'night')) {
                $shift->update(['start_time' => '22:00', 'end_time' => '06:00']);
            }
        }
    }

    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
        });
    }
};
