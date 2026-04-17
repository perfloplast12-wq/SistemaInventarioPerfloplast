<?php

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $user = auth()->user();

        if ($user?->hasRole('conductor')) {
            // Redirigir conductores directamente a sus despachos
            return redirect()->to(\App\Filament\Resources\DispatchResource::getUrl('index'));
        }

        if ($user?->can('dashboard.view')) {
            return redirect()->to(\App\Filament\Pages\Dashboard::getUrl());
        }

        // Por defecto, buscar la primera página disponible
        return redirect()->to(filament()->getHomeUrl());
    }
}
