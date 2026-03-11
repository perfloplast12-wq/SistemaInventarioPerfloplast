<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,
            RolesSeeder::class,
            SuperAdminSeeder::class,
            ShiftSeeder::class,
            UnitOfMeasureSeeder::class,
        ]);
    }
}
