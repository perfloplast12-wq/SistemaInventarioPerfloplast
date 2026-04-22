<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Concerns\HandlesSoftDeletedDuplicates;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Spatie\Permission\Models\Role;

class CreateUser extends CreateRecord
{
    use HandlesSoftDeletedDuplicates;

    protected static string $resource = UserResource::class;

    protected array $rolesToSync = [];

    protected function getUniqueFieldsForRestore(): array
    {
        return ['email'];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->rolesToSync = $data['roles'] ?? [];
        unset($data['roles']);

        return $data;
    }

    protected function afterCreate(): void
    {
        if (empty($this->rolesToSync)) {
            return;
        }

        // Filter out roles that don't exist in the database to prevent crashes
        $existingRoles = Role::whereIn('name', $this->rolesToSync)
            ->where('guard_name', 'web')
            ->pluck('name')
            ->toArray();

        $missingRoles = array_diff($this->rolesToSync, $existingRoles);
        
        // Create missing roles automatically
        foreach ($missingRoles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        if (! UserResource::validateRoleRules($this->record, $this->rolesToSync)) {
            return;
        }

        $this->record->syncRoles($this->rolesToSync);
        $this->record->refresh();

        Notification::make()
            ->title('Usuario creado correctamente.')
            ->success()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver')
                ->label('Volver')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(UserResource::getUrl('index')),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
