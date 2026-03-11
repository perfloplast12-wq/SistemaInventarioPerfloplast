<?php

namespace App\Filament\Resources\ProductionResource\Pages;

use App\Filament\Resources\ProductionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduction extends EditRecord
{
    protected static string $resource = ProductionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('confirm')
                ->label('Finalizar Producción')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('¿Finalizar Producción por completo?')
                ->modalDescription('Esta acción descontará materias primas de bodega e ingresará el producto terminado. No se puede deshacer.')
                ->visible(fn ($record) => $record->status === 'draft')
                ->action(function ($record) {
                    $record->confirm();
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Producción Finalizada Correctamente')
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $color = \App\Models\Color::find($data['color_id'] ?? null);
        if ($color) {
            $data['color_name_filter'] = $color->name;
        }
 
        return $data;
    }
}
