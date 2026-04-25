<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LocationResource\Pages;
use App\Models\Location;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Ubicaciones';
    protected static ?string $modelLabel = 'Ubicación';
    protected static ?string $pluralModelLabel = 'Ubicaciones';

    // ✅ NO mostrar en menú lateral (solo entrar desde Bodegas o Camiones)
    protected static ?string $navigationGroup = 'Logística y Pedidos';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Ubicación')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Tipo')
                        ->required()
                        ->options([
                            'warehouse' => 'Bodega',
                            'truck' => 'Camión',
                        ])
                        ->live(),

                    Forms\Components\Select::make('warehouse_id')
                        ->label('Bodega')
                        ->relationship('warehouse', 'name')
                        ->searchable()
                        ->preload()
                        ->visible(fn (Forms\Get $get) => $get('type') === 'warehouse')
                        ->required(fn (Forms\Get $get) => $get('type') === 'warehouse'),

                    Forms\Components\Select::make('truck_id')
                        ->label('Camión')
                        ->relationship('truck', 'name') // si tu campo no es name, cambia a 'plate' o el que uses
                        ->searchable()
                        ->preload()
                        ->visible(fn (Forms\Get $get) => $get('type') === 'truck')
                        ->required(fn (Forms\Get $get) => $get('type') === 'truck'),

                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(120),

                    Forms\Components\TextInput::make('code')
                        ->label('Código')
                        ->required()
                        ->maxLength(30)
                        ->unique(ignoreRecord: true),

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
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'warehouse' ? 'Bodega' : 'Camión'),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Bodega')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('truck.name')
                    ->label('Camión')
                    ->toggleable(),

                Tables\Columns\ToggleColumn::make('is_active')->label('Activo')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
                Tables\Actions\DeleteAction::make()->label('Eliminar'),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // ✅ Filtrar por bodega: /admin/locations?warehouse_id=1
        if ($warehouseId = request()->integer('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        // ✅ Filtrar por camión: /admin/locations?truck_id=1
        if ($truckId = request()->integer('truck_id')) {
            $query->where('truck_id', $truckId);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'edit'   => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
}
