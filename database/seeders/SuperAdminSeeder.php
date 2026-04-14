<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;

class SuperAdminSeeder extends Seeder
{
    /**
     * Email fijo del super admin (inamovible).
     * Si quieres cambiarlo algún día, cámbialo aquí.
     */
    private const SUPER_ADMIN_EMAIL = 'admin@perfloplast.com';

    public function run(): void
    {
        // Limpia caché de permisos
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Password bootstrap (solo para creación inicial / caso password vacío)
        $password = env('SUPER_ADMIN_PASSWORD');

        $user = User::withoutEvents(function () use ($password) {

            $existing = User::where('email', self::SUPER_ADMIN_EMAIL)->first();

            // ── Si NO existe: crearlo sí o sí ─────────
            if (!$existing) {
                if (empty($password)) {
                    throw new RuntimeException(
                        'SUPER_ADMIN_PASSWORD no está definido. ' .
                        'Es obligatorio para crear el Super Administrador inicial.'
                    );
                }

                return User::create([
                    'name'              => 'Super Administrador',
                    'email'             => self::SUPER_ADMIN_EMAIL,
                    'password'          => Hash::make($password),
                    'email_verified_at' => now(),
                ]);
            }

            // ── Si ya existe: NO sobrescribir password ─────────
            // Solo asegurar que esté activo
            $existing->is_active = true;
            
            // Solo setear password si está vacío (caso raro)
            if (empty($existing->password)) {
                if (empty($password)) {
                    throw new RuntimeException(
                        'El Super Admin existe pero su password está vacío y SUPER_ADMIN_PASSWORD no está definido.'
                    );
                }
                $existing->password = Hash::make($password);
            }
            
            $existing->save();

            return $existing;
        });

        // Asegurar rol super_admin
        $user->syncRoles(['super_admin']);

    }
}
