<?php

namespace App\Filament\Resources\ProductionResource\Widgets;

use Filament\Widgets\Widget;

class ShiftsButton extends Widget
{
    protected static string $view = 'filament.resources.production-resource.widgets.shifts-button';
    
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()->can('shifts.view') && !auth()->user()->hasRole('sales');
    }
}
