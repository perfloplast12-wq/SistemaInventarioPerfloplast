<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Validation\ValidationException;

class UserObserver
{
    private const SUPER_ADMIN_EMAIL = 'admin@perfloplast.com';
    private const DOMAIN = '@perfloplast.com';

    public function saving(User $user): void
    {
        // Normalizar email a lowercase
        if (! empty($user->email)) {
            $user->email = strtolower($user->email);
        }

        // Forzar dominio corporativo
        if (! empty($user->email) && ! str_ends_with($user->email, self::DOMAIN)) {
            throw ValidationException::withMessages([
                'email' => 'El correo debe pertenecer al dominio corporativo ' . self::DOMAIN,
            ]);
        }

        // Bloquear cambio de email del super admin
        if ($user->exists && $user->getOriginal('email') === self::SUPER_ADMIN_EMAIL) {
            if ($user->isDirty('email')) {
                throw ValidationException::withMessages([
                    'email' => 'No se puede modificar el correo del Super Administrador.',
                ]);
            }
        }

        // Asegurar que el Super Admin siempre esté activo
        if (isset($user->email) && $user->email === self::SUPER_ADMIN_EMAIL) {
            $user->is_active = true;
        }
    }


    public function deleting(User $user): void
    {
        if ($user->hasRole('super_admin')) {
            $count = User::role('super_admin')->count();

            if ($count <= 1) {
                throw ValidationException::withMessages([
                    'user' => 'No se puede eliminar el único Super Administrador del sistema.',
                ]);
            }
        }
    }
}
