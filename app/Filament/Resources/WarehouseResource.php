<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseResource\Pages;
use App\Models\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $navigationLabel = 'Bodegas';
    protected static ?string $modelLabel = 'Bodega';
    protected static ?string $pluralModelLabel = 'Bodegas';

    protected static ?string $navigationGroup = 'Catálogos / Maestros';
    protected static ?int $navigationSort = 2;
    protected static bool $shouldRegisterNavigation = false;

    public static function canViewAny(): bool
    {
        return (auth()->user()?->can('warehouses.view') ?? false) && !auth()->user()?->hasRole('sales');
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('warehouses.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('warehouses.edit') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('warehouses.delete') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Bodega')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(120),

                    Forms\Components\TextInput::make('code')
                        ->label('Código')
                        ->helperText('Ej: BOD-01, MOS-01, CAM-01')
                        ->required()
                        ->maxLength(30)
                        ->unique(ignoreRecord: true),

                    Forms\Components\Textarea::make('notes')
                        ->label('Notas')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_factory')
                        ->label('¿Es bodega de fábrica?')
                        ->helperText('Solo una bodega puede ser marcada como fábrica para ventas rápidas.')
                        ->default(false),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Activo')
                        ->default(true),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('code')->label('Código')->searchable()->sortable(),

                Tables\Columns\IconColumn::make('is_factory')
                    ->label('Fábrica')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Activo')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->dateTime('d/m/Y H:i:s')->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('kardex')
                    ->label('Kardex')
                    ->icon('heroicon-o-arrows-right-left')
                    ->url(fn ($record) =>
                        \App\Filament\Resources\InventoryMovementResource::getUrl('index')
                        . '?warehouse_id=' . $record->id
                    ),

                Tables\Actions\EditAction::make()->label('Editar'),
                Tables\Actions\DeleteAction::make()->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'edit'   => Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }
}