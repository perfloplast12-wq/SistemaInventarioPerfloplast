<?php

namespace Database\Seeders;

use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

class UnitOfMeasureSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Unidad',    'abbreviation' => 'u'],
            ['name' => 'Kilogramo', 'abbreviation' => 'kg'],
            ['name' => 'Libra',     'abbreviation' => 'lb'],
            ['name' => 'Paquete',   'abbreviation' => 'pq'],
            ['name' => 'Metro',     'abbreviation' => 'm'],
            ['name' => 'Rollo',     'abbreviation' => 'rollo'],
        ];

        foreach ($units as $unit) {
            UnitOfMeasure::firstOrCreate(
                ['name' => $unit['name']],
                array_merge($unit, ['is_active' => true])
            );
        }
    }
}
