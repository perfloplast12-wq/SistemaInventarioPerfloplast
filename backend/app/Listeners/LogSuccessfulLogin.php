<?php

namespace App\Listeners;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        AuditLogger::log(
            event: 'login',
            module: 'auth',
            model: $event->user,
            old: null,
            new: ['user_id' => $event->user->id, 'email' => $event->user->email],
            description: 'Inicio de sesión'
        );
    }
}
