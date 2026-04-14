<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Bitácora';
    protected static ?string $modelLabel = 'Evento';
    protected static ?string $pluralModelLabel = 'Bitácora';
    protected static ?int $navigationSort = 2;

    // ✅ Solo super_admin y admin ven bitácora
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function form(Form $form): Form
    {
        // ✅ SOLO LECTURA (vista)
        return $form->schema([
            Forms\Components\Section::make('Detalle del evento')
                ->schema([
                    Forms\Components\TextInput::make('event')
                        ->label('Acción')
                        ->disabled()
                        ->formatStateUsing(fn ($state) => static::humanEvent($state)),

                    Forms\Components\TextInput::make('module')
                        ->label('Módulo')
                        ->disabled()
                        ->formatStateUsing(fn ($state) => static::humanModule($state)),

                    Forms\Components\TextInput::make('user.name')
                        ->label('Usuario')
                        ->disabled(),

                    Forms\Components\TextInput::make('ip_address')
                        ->label('IP')
                        ->disabled(),

                    Forms\Components\TextInput::make('method')
                        ->label('Método')
                        ->disabled()
                        ->formatStateUsing(fn ($state) => strtoupper((string) $state)),

                    Forms\Components\TextInput::make('url')
                        ->label('URL')
                        ->disabled(),

                    Forms\Components\Textarea::make('description')
                        ->label('Descripción')
                        ->disabled()
                        ->rows(2),
                ])
                ->columns(2),

            Forms\Components\Section::make('Detalle de cambios')
                ->schema([
                    // ✅ Resumen humano (lo que la empresa entiende)
                    Forms\Components\Textarea::make('changes_human')
                        ->label('Resumen de cambios')
                        ->disabled()
                        ->rows(6)
                        ->dehydrated(false)
                        ->formatStateUsing(function ($state, $record) {
                            return static::humanChangesSummary($record);
                        }),

                    // ✅ Si deseas dejar la evidencia técnica (opcional)
                    Forms\Components\Textarea::make('old_values')
                        ->label('Valores anteriores')
                        ->disabled()
                        ->rows(8)
                        ->formatStateUsing(fn ($state) => static::prettyJson($state)),

                    Forms\Components\Textarea::make('new_values')
                        ->label('Valores nuevos')
                        ->disabled()
                        ->rows(8)
                        ->formatStateUsing(fn ($state) => static::prettyJson($state)),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    // ✅ respeta tu zona horaria configurada en APP_TIMEZONE
                    ->dateTime('d/m/Y H:i:s', config('app.timezone'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('module')
                    ->label('Módulo')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => static::humanModule($state)),

                Tables\Columns\TextColumn::make('event')
                    ->label('Acción')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => static::humanEvent($state)),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('auditable_type')
                    ->label('Registro (tipo)')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => static::humanModel($state))
                    ->limit(40),

                Tables\Columns\TextColumn::make('auditable_id')
                    ->label('Registro (ID)')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('module')
                    ->label('Módulo')
                    ->options(fn () => AuditLog::query()
                        ->select('module')
                        ->distinct()
                        ->pluck('module', 'module')
                        ->map(fn ($m) => static::humanModule($m))
                        ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('event')
                    ->label('Acción')
                    ->options(fn () => AuditLog::query()
                        ->select('event')
                        ->distinct()
                        ->pluck('event', 'event')
                        ->map(fn ($e) => static::humanEvent($e))
                        ->toArray()
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Ver'),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('user');
    }

    public static function getPages(): array
    {
        
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view'  => Pages\ViewAuditLog::route('/{record}'),
        ];
    }

    // =========================
    // Helpers (todo en 1 archivo)
    // =========================

    protected static function humanModule(?string $module): string
    {
        $module = (string) $module;

        $map = [
            'auth' => 'Acceso / Autenticación',
            'users' => 'Usuarios',
            'roles' => 'Roles y permisos',
            'products' => 'Productos',
            'catalogs' => 'Catálogos',
            'inventory' => 'Inventario / Kardex',
            'purchases' => 'Compras',
            'sales' => 'Ventas',
            'dispatch' => 'Despachos / Camiones',
            'reports' => 'Reportes',
            'audit' => 'Bitácora',
        ];

        return $map[$module] ?? ucfirst(str_replace('_', ' ', $module));
    }

    protected static function humanEvent(?string $event): string
    {
        $event = (string) $event;

        $map = [
            'login' => 'Inicio de sesión',
            'logout' => 'Cierre de sesión',
            'created' => 'Creación',
            'updated' => 'Actualización',
            'deleted' => 'Eliminación',
            'restored' => 'Restauración',
            'roles_updated' => 'Cambio de roles',
            'status_updated' => 'Cambio de estado (activo/inactivo)',
        ];

        return $map[$event] ?? ucfirst(str_replace('_', ' ', $event));
    }

    protected static function humanModel(?string $model): string
    {
        $model = (string) $model;

        // Ej: App\Models\User -> Usuario
        $map = [
            'App\Models\User' => 'Usuario',
            'App\Models\Product' => 'Producto',
        ];

        return $map[$model] ?? class_basename($model);
    }

    protected static function prettyJson($value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '';
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '';
            }
            return $value;
        }

        return (string) $value;
    }

    protected static function humanLabelForKey(string $key): string
    {
        $map = [
            'name' => 'Nombre',
            'email' => 'Correo',
            'password' => 'Contraseña',
            'is_active' => 'Activo',
            'created_by' => 'Creado por',
            'created_at' => 'Fecha de creación',
            'updated_at' => 'Fecha de actualización',
        ];

        return $map[$key] ?? ucfirst(str_replace('_', ' ', $key));
    }

    protected static function humanChangesSummary($record): string
    {
        // old_values y new_values pueden ser json string o array
        $old = $record->old_values;
        $new = $record->new_values;

        if (is_string($old)) $old = json_decode($old, true);
        if (is_string($new)) $new = json_decode($new, true);

        $old = is_array($old) ? $old : [];
        $new = is_array($new) ? $new : [];

        // si no hay cambios, mostramos algo humano
        if (empty($old) && empty($new)) {
            return 'No se registraron cambios detallados.';
        }

        // unimos llaves para comparar
        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));

        $lines = [];
        foreach ($keys as $key) {
            // evitamos ruido
            if (in_array($key, ['remember_token'], true)) continue;

            $before = $old[$key] ?? null;
            $after  = $new[$key] ?? null;

            // si es igual, no lo listamos
            if ($before === $after) continue;

            $label = static::humanLabelForKey($key);

            $beforeText = is_scalar($before) || $before === null ? (string) ($before ?? '—') : '[dato complejo]';
            $afterText  = is_scalar($after)  || $after === null  ? (string) ($after  ?? '—') : '[dato complejo]';

            // ocultar contraseña
            if ($key === 'password') {
                $beforeText = '********';
                $afterText  = '********';
            }

            $lines[] = "{$label}: \"{$beforeText}\" → \"{$afterText}\"";
        }

        if (empty($lines)) {
            return 'No se detectaron diferencias relevantes.';
        }

        return implode("\n", $lines);
    }
}
