<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Filament\Tables\Columns\ToggleColumn;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';
    protected static ?string $navigationGroup = 'Catálogos / Maestros';
    protected static ?int $navigationSort = 4;

    public static function roleOptions(): array
    {
        return [
            'super_admin' => 'Súper administrador',
            'admin'       => 'Administrador',
            'warehouse'   => 'Bodeguero',
            'sales'       => 'Vendedor',
            'production'  => 'Producción',
            'accounting'  => 'Contabilidad',
            'conductor'   => 'Conductor / Piloto',
            'mantenimiento' => 'Mantenimiento',
            'viewer'      => 'Solo lectura',
        ];
    }

    public static function isSuperAdmin(): bool
    {
        return auth()->user()?->hasRole('super_admin') ?? false;
    }

    public static function isAdminOrSuper(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    /**
     * Solo super_admin y admin pueden ver el módulo Usuarios.
     * (si ya tienes permisos users.view, puedes combinarlo después)
     */
    public static function shouldRegisterNavigation(): bool
    {
        return self::isAdminOrSuper();
    }

    public static function canViewAny(): bool
    {
        return self::isAdminOrSuper();
    }

    public static function canCreate(): bool
    {
        return self::isAdminOrSuper();
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }
    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['auditOldSnapshot'], $data['auditNewSnapshot'], $data['auditDiff']);
        return $data;
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        unset($data['auditOldSnapshot'], $data['auditNewSnapshot'], $data['auditDiff']);
        return $data;
    }




    /**
     * Reglas críticas de roles:
     * - Solo 1 super_admin
     * - No dejar el sistema sin super_admin
     * - No dejar el sistema sin al menos 1 admin
     * - admin NO puede asignar super_admin
     */
    public static function validateRoleRules(User $record, array $roles): bool
    {
        $roles = array_values($roles);

        // Regla: admin NO puede asignar super_admin
        if (! self::isSuperAdmin() && in_array('super_admin', $roles, true)) {
            Notification::make()
                ->title('Regla del sistema')
                ->body('Solo el Súper administrador puede asignar el rol Súper administrador.')
                ->danger()
                ->send();
            return false;
        }

        $existingSuperAdmin = User::role('super_admin')->first();

        // Regla: Solo 1 super_admin
        if (in_array('super_admin', $roles, true)) {
            if ($existingSuperAdmin && $existingSuperAdmin->id !== $record->id) {
                Notification::make()
                    ->title('Regla del sistema')
                    ->body('Solo puede existir un Súper administrador.')
                    ->danger()
                    ->send();
                return false;
            }
        }

        // Regla: No quitar super_admin al único super admin
        if ($record->hasRole('super_admin') && ! in_array('super_admin', $roles, true)) {
            Notification::make()
                ->title('Regla del sistema')
                ->body('El sistema no puede quedarse sin Súper administrador.')
                ->danger()
                ->send();
            return false;
        }

        // Regla: No dejar el sistema sin admins
        if ($record->hasRole('admin') && ! in_array('admin', $roles, true)) {
            $adminsCount = User::role('admin')->count();
            if ($adminsCount <= 1) {
                Notification::make()
                    ->title('Regla del sistema')
                    ->body('Debe existir al menos un Administrador.')
                    ->danger()
                    ->send();
                return false;
            }
        }

        return true;
    }

    /**
     * Reglas críticas de estado activo:
     * - No desactivar super_admin (solo hay 1)
     * - No desactivar el último admin activo
     * - No permitir que super_admin o admin se desactive a sí mismo
     */
    public static function validateActiveToggle(User $record, bool $newState): bool
    {
        // Solo validamos cuando se intenta desactivar
        if ($newState === true) {
            return true;
        }

        // No permitir auto-desactivarse si es admin/super_admin
        if ($record->id === auth()->id() && $record->hasAnyRole(['super_admin', 'admin'])) {
            Notification::make()
                ->title('Regla del sistema')
                ->body('No puedes desactivar tu propia cuenta (Admin/Súper admin).')
                ->danger()
                ->send();
            return false;
        }

        // No permitir desactivar al super_admin
        if ($record->hasRole('super_admin')) {
            Notification::make()
                ->title('Regla del sistema')
                ->body('No puedes desactivar al Súper administrador.')
                ->danger()
                ->send();
            return false;
        }

        // No permitir desactivar el último admin activo
        if ($record->hasRole('admin')) {
            $adminsActivos = User::role('admin')->where('is_active', 1)->count();
            if ($adminsActivos <= 1) {
                Notification::make()
                    ->title('Regla del sistema')
                    ->body('No puedes desactivar al último Administrador activo.')
                    ->danger()
                    ->send();
                return false;
            }
        }

        return true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información del usuario')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Correo electrónico')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at'))
                        ->rules(['ends_with:@perfloplast.com'])
                        ->validationMessages([
                            'ends_with' => 'El correo debe pertenecer al dominio corporativo @perfloplast.com',
                        ])
                        ->placeholder('usuario@perfloplast.com'),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Activo')
                        ->default(true),

                    // Roles solo visibles a super_admin/admin
                    Forms\Components\Select::make('roles')
                        ->label('Roles')
                        ->multiple()
                        ->options(self::roleOptions())
                        ->searchable()
                        ->required()
                        ->dehydrated(true)
                        ->visible(fn () => self::isAdminOrSuper()),
                ])
                ->columns(2),

            Forms\Components\Section::make('Contraseña')
                ->schema([
                    Forms\Components\TextInput::make('password')
                        ->label('Contraseña')
                        ->password()
                        ->helperText('Déjalo vacío para conservar la contraseña actual (al editar).')
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->maxLength(255),
                ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->sortable(),

                // ✅ Toggle "Activo" con reglas (sin mensajes, solo bloquear)
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Activo')
                    ->onColor('success')
                    ->offColor('danger')
                    ->sortable()

                    // Bloquea el switch según reglas
                    ->disabled(function ($record) {
                        $auth = auth()->user();

                        if (! $auth) return true;

                        // Solo super_admin/admin pueden mover el switch
                        if (! $auth->hasAnyRole(['super_admin', 'admin'])) {
                            return true;
                        }

                        // Nadie puede desactivar al super_admin si NO es super_admin
                        if ($record->hasRole('super_admin') && ! $auth->hasRole('super_admin')) {
                            return true;
                        }

                        // El super_admin (único) nunca se puede desactivar
                        if ($record->hasRole('super_admin')) {
                            return true;
                        }

                        // Si se intenta desactivar un ADMIN, no permitir si quedaría sin admins activos
                        // (esto aplica tanto para admin como para super_admin al desactivar admins)
                        if ($record->hasRole('admin') && $record->is_active) {
                            $adminsActivos = \App\Models\User::role('admin')
                                ->where('is_active', 1)
                                ->count();

                            // si solo queda 1 admin activo (este), no se puede desactivar
                            if ($adminsActivos <= 1) {
                                return true;
                            }
                        }

                        return false;
                    })

                    // Validación final al actualizar (por si alguien intenta saltarse UI)
                    ->beforeStateUpdated(function ($record, $state) {
                        $auth = auth()->user();

                        // Si lo quieren poner INACTIVO (false), aplican reglas
                        if ($state === false) {

                            // No existe desactivar super_admin
                            if ($record->hasRole('super_admin')) {
                                return false;
                            }

                            // admin no puede tocar super_admin (redundante por seguridad)
                            if ($record->hasRole('super_admin') && ! $auth->hasRole('super_admin')) {
                                return false;
                            }

                            // No permitir dejar el sistema sin admins activos
                            if ($record->hasRole('admin') && $record->is_active) {
                                $adminsActivos = \App\Models\User::role('admin')
                                    ->where('is_active', 1)
                                    ->count();

                                if ($adminsActivos <= 1) {
                                    return false;
                                }
                            }
                        }

                        return true;
                    }),

                Tables\Columns\TagsColumn::make('roles_list')
                    ->label('Roles')
                    ->getStateUsing(function ($record) {
                        $map = [
                            'super_admin' => 'Súper administrador',
                            'admin' => 'Administrador',
                            'warehouse' => 'Bodeguero',
                            'sales' => 'Vendedor',
                            'production' => 'Producción',
                            'accounting' => 'Contabilidad',
                            'conductor' => 'Conductor / Piloto',
                            'mantenimiento' => 'Mantenimiento',
                            'viewer' => 'Solo lectura',
                        ];

                        return collect($record->getRoleNames())
                            ->map(fn ($r) => $map[$r] ?? $r)
                            ->values()
                            ->toArray();
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activo'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                // ✅ Acción Roles (solo super_admin/admin)
                Tables\Actions\Action::make('roles')
                    ->label('Roles')
                    ->icon('heroicon-o-key')
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false)
                    ->form([
                        Forms\Components\Select::make('roles')
                            ->label('Roles')
                            ->multiple()
                            ->options([
                                'super_admin' => 'Súper administrador',
                                'admin' => 'Administrador',
                                'warehouse' => 'Bodeguero',
                                'sales' => 'Vendedor',
                                'production' => 'Producción',
                                'accounting' => 'Contabilidad',
                                'conductor' => 'Conductor / Piloto',
                                'mantenimiento' => 'Mantenimiento',
                                'viewer' => 'Solo lectura',
                            ])
                            ->required(),
                    ])
                    ->mountUsing(function ($form, $record) {
                        $form->fill([
                            'roles' => $record->getRoleNames()->toArray(),
                        ]);
                    })
                    ->action(function (array $data, $record) {
                        $roles = $data['roles'] ?? [];

                        // Regla: solo un super_admin
                        $currentSuperAdminId = \App\Models\User::role('super_admin')->value('id');
                        if (in_array('super_admin', $roles, true) && $currentSuperAdminId && $currentSuperAdminId !== $record->id) {
                            return; // sin mensajes
                        }

                        // Regla: no quitar super_admin al único super_admin
                        if ($record->hasRole('super_admin') && ! in_array('super_admin', $roles, true)) {
                            return;
                        }

                        // Regla: siempre debe existir al menos 1 admin
                        if ($record->hasRole('admin') && ! in_array('admin', $roles, true)) {
                            $adminsCount = \App\Models\User::role('admin')->count();
                            if ($adminsCount <= 1) return;
                        }

                        $record->syncRoles($roles);
                        $record->refresh();
                    }),

                Tables\Actions\EditAction::make()->label('Editar'),

                // ✅ Eliminar (ROJO) funcional
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar')
                    ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false)
                    ->disabled(function ($record) {
                        // No eliminar super_admin
                        if ($record->hasRole('super_admin')) return true;

                        // No eliminar si es el único admin
                        if ($record->hasRole('admin')) {
                            $adminsTotal = \App\Models\User::role('admin')->count();
                            if ($adminsTotal <= 1) return true;
                        }

                        return false;
                    }),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar')
                        ->visible(fn () => auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false),
                ]),
            ]);
    }


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }


    public static function canToggleActive(User $record, bool $newState): bool
    {
    // Si lo quieren activar (ON) siempre permitir
    if ($newState === true) {
        return true;
    }

    // Si intenta desactivar (OFF), aplicar reglas:

    // 1) No permitir auto-desactivarse si es admin/super_admin
    if ($record->id === auth()->id() && $record->hasAnyRole(['super_admin', 'admin'])) {
        return false;
    }

    // 2) No permitir desactivar al super_admin (único)
    if ($record->hasRole('super_admin')) {
        return false;
    }

    // 3) No permitir desactivar al último admin ACTIVO
    if ($record->hasRole('admin')) {
        $adminsActivos = User::role('admin')->where('is_active', 1)->count();
        if ($adminsActivos <= 1) {
            return false;
        }
    }

    return true;
    }





}
