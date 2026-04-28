<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShiftResource\Pages;
use App\Models\Shift;
use Filament\Forms;
use Filament\Forms\Form;
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

    /**
     * ✅ Ocultar del menú lateral.
     * El CRUD sigue funcionando, solo no se muestra en el sidebar.
     */
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return (auth()->user()?->can('shifts.view') ?? false) && !auth()->user()?->hasRole('sales');
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

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Turno')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(80),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Activo')
                        ->default(true),

                    Forms\Components\TimePicker::make('start_time')
                        ->label('Hora Inicio')
                        ->seconds(false),

                    Forms\Components\TimePicker::make('end_time')
                        ->label('Hora Fin')
                        ->seconds(false),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Turno')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('start_time')->label('Inicio')->sortable(),
                Tables\Columns\TextColumn::make('end_time')->label('Fin')->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Activo')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->dateTime('d/m/Y H:i:s')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
                Tables\Actions\DeleteAction::make()->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Eliminar seleccionados'),
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
            'index' => Pages\ListShifts::route('/'),
            'create' => Pages\CreateShift::route('/create'),
            'edit' => Pages\EditShift::route('/{record}/edit'),
        ];
    }
}
