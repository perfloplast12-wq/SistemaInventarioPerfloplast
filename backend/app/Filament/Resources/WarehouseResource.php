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

    // ✅ NO en menú lateral (solo desde Catálogos)
    protected static bool $shouldRegisterNavigation = false;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('warehouses.view') ?? true;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('warehouses.create') ?? true;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('warehouses.edit') ?? true;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('warehouses.delete') ?? true;
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

                    Forms\Components\Select::make('type')
                        ->label('Tipo')
                        ->required()
                        ->options([
                            'warehouse' => 'Bodega',
                            'showroom'  => 'Mostrador',
                            'mobile'    => 'Bodega móvil (camión)',
                        ]),

                    Forms\Components\Textarea::make('notes')
                        ->label('Notas')
                        ->rows(3)
                        ->columnSpanFull(),

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
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipo')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'warehouse' => 'Bodega',
                        'showroom' => 'Mostrador',
                        'mobile' => 'Bodega móvil',
                        default => $state,
                    })
                    ->badge(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Activo')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->dateTime('d/m/Y H:i:s')->sortable(),
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