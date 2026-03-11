<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TruckResource\Pages;
use App\Models\Truck;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TruckResource extends Resource
{
    protected static ?string $model = Truck::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Camiones';
    protected static ?string $modelLabel = 'Camión';
    protected static ?string $pluralModelLabel = 'Camiones';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('trucks.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('trucks.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('trucks.edit') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('trucks.delete') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Camión')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre del camion')
                        ->helperText('Camion 1, Camion rojo')
                        ->maxLength(255)
                        ->dehydrated(true),
                    Forms\Components\TextInput::make('plate')
                        ->label('Placa')
                        ->required()
                        ->maxLength(20)
                        ->unique(ignoreRecord: true)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set, callable $get){
                            if (blank($get('name')) && filled($state)) {
                                $set('name', 'Camion ' . $state);
                            }
                        }),

                    Forms\Components\TextInput::make('driver_name')
                        ->label('Piloto')
                        ->required()
                        ->maxLength(120),

                    Forms\Components\TextInput::make('brand')
                        ->label('Marca')
                        ->maxLength(60),

                    Forms\Components\TextInput::make('model')
                        ->label('Modelo')
                        ->maxLength(60),

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
                Tables\Columns\TextColumn::make('plate')->label('Placa')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('driver_name')->label('Piloto')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('brand')->label('Marca')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('model')->label('Modelo')->searchable()->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Activo')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->dateTime('d/m/Y H:i:s')->sortable(),
            ])
            ->actions([

            
                Tables\Actions\Action::make('kardex')
                    ->label('Kardex')
                    ->icon('heroicon-o-arrows-right-left')
                    ->url(fn ($record) =>
                        \App\Filament\Resources\InventoryMovementResource::getUrl('index')
                        . '?truck_id=' . $record->id
                    )
                    ->openUrlInNewTab(),


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
            'index'  => Pages\ListTrucks::route('/'),
            'create' => Pages\CreateTruck::route('/create'),
            'edit'   => Pages\EditTruck::route('/{record}/edit'),
        ];
    }
}
