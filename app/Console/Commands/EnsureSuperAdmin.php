<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class EnsureSuperAdmin extends Command
{
    protected $signature = 'superadmin:ensure {--password= : Password para el super admin (si no se pasa, se solicitará)} {--force : Forzar reseteo de password si el usuario ya existe}';

    protected $description = 'Crea o restablece el usuario Super Administrador (admin@perfloplast.com) y asigna el rol super_admin.';

    public function handle(): int
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $email = 'admin@perfloplast.com';

        $password = $this->option('password') ?: $this->secret('Password para el Super Admin');
        $force = $this->option('force');

        if (empty($password) && ! $force) {
            $this->error('Password no puede estar vacío para la creación inicial. Pasa --password o introdúcelo cuando se solicite.');
            return self::FAILURE;
        }

        $this->info('Creando o actualizando usuario: ' . $email);

        $user = User::withoutEvents(function () use ($email, $password, $force) {
            $existing = User::where('email', $email)->first();

            if (! $existing) {
                return User::create([
                    'name' => 'Super Administrador',
                    'email' => $email,
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]);
            }

            // Si existe y se solicita forzar password, actualizar; en caso contrario NO tocar password.
            if ($force && ! empty($password)) {
                $existing->password = Hash::make($password);
            }

            // Asegurar activo
            $existing->is_active = true;
            $existing->email_verified_at = $existing->email_verified_at ?: now();
            $existing->save();

            return $existing;
        });

        // Asegurar que el rol existe y tenga permisos (si hay permisos definidos)
        $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        try {
            $role->syncPermissions(Permission::all());
        } catch (\Throwable $e) {
            // Si algo falla (tabla permissions no migrada aún), ignoramos y seguimos.
            $this->warn('No se pudieron sincronizar permisos al rol super_admin: ' . $e->getMessage());
        }

        $user->syncRoles([$role->name]);

        $this->info('Super Admin creado/actualizado correctamente. Email: ' . $email);
        $this->line('Usa este comando para restablecer la contraseña en futuros despliegues:');
        $this->line("php artisan superadmin:ensure --password=TU_PASSWORD_SEGURO");

        return self::SUCCESS;
    }
}
