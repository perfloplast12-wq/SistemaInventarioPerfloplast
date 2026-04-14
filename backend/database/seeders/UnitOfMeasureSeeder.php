<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UnitOfMeasureResource\Pages;
use App\Models\UnitOfMeasure;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UnitOfMeasureResource extends Resource
{
    protected static ?string $model = UnitOfMeasure::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';
    protected static ?string $navigationLabel = 'Unidades de medida';
    protected static ?string $modelLabel = 'Unidad de medida';
    protected static ?string $pluralModelLabel = 'Unidades de medida';

    // ✅ AGRUPACIÓN EN CATÁLOGOS
    protected static ?string $navigationGroup = 'Catálogos';
    protected static ?int $navigationSort = 1;

    /* ==========================
     |  PERMISOS
     ========================== */
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('uom.view') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('uom.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('uom.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('uom.edit') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('uom.delete') ?? false;
    }

    /* ==========================
     |  FORMULARIO
     ========================== */
    public static function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos de la unidad de medida')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(50),

                    Forms\Components\TextInput::make('abbreviation')
                        ->label('Abreviatura')
                        ->maxLength(10)
                        ->nullable(),

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
                    ->label('Unidad')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('abbreviation')
                    ->label('Abreviatura')
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
            'index'  => Pages\ListUnitOfMeasures::route('/'),
            'create' => Pages\CreateUnitOfMeasure::route('/create'),
            'edit'   => Pages\EditUnitOfMeasure::route('/{record}/edit'),
        ];
    }
}
