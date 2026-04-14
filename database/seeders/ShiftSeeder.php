<?php

namespace Database\Seeders;

use App\Models\Shift;
use Illuminate\Database\Seeder;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        $shifts = [
            ['name' => 'Mañana',  'is_active' => true],
            ['name' => 'Tarde',   'is_active' => true],
            ['name' => 'Noche',   'is_active' => true],
        ];

        foreach ($shifts as $shift) {
            Shift::firstOrCreate(
                ['name' => $shift['name']],
                $shift
            );
        }
    }
}
