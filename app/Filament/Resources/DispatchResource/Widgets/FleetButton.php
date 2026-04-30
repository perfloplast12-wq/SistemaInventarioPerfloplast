<?php

namespace App\Filament\Resources\DispatchResource\Widgets;

use Filament\Widgets\Widget;

class FleetButton extends Widget
{
    protected static string $view = 'filament.resources.dispatch-resource.widgets.fleet-button';
    
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()->can('trucks.view') && !auth()->user()->hasRole('conductor');
    }
}
