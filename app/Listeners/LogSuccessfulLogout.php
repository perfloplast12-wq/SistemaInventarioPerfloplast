<?php

namespace App\Listeners;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    public function handle(Logout $event): void
    {
        $user = $event->user;

        AuditLogger::log(
            event: 'logout',
            module: 'auth',
            model: $user ?: null,
            old: $user ? ['user_id' => $user->id, 'email' => $user->email] : null,
            new: null,
            description: 'Cierre de sesión'
        );
    }
}
