<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftResource\Pages;
use App\Models\Shift;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationLabel = 'Turnos';
    protected static ?string $modelLabel = 'Turno';
    protected static ?string $pluralModelLabel = 'Turnos';

    // ✅ AGRUPACIÓN EN CATÁLOGOS
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?int $navigationSort = 2;

    /* ==========================
     |  PERMISOS
     ========================== */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('shifts.view') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('shifts.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('shifts.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('shifts.edit') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('shifts.delete') ?? false;
    }

    /* ==========================
     |  FORMULARIO
     ========================== */
    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del turno')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Turno')
                        ->required()
                        ->maxLength(50),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Activo')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    /* ==========================
     |  TABLA
     ========================== */
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Turno')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
                Tables\Actions\DeleteAction::make()->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->label('Eliminar seleccionados'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListShifts::route('/'),
            'create' => Pages\CreateShift::route('/create'),
            'edit'   => Pages\EditShift::route('/{record}/edit'),
        ];
    }
}
