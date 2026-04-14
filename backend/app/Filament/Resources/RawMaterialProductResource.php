<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RawMaterialProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RawMaterialProductResource extends Resource
{
    protected static ?string $model = Product::class;

    // ✅ Oculto del menú (porque entras desde el Panel Inventario)
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationLabel = 'Materia prima';
    protected static ?string $modelLabel = 'Materia prima';
    protected static ?string $pluralModelLabel = 'Materia prima';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    // ✅ PERMISOS (usa los mismos que ProductResource)
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('products.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('products.create') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('products.edit') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('products.delete') ?? false;
    }

    // ✅ Solo materia prima
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('type', 'raw_material');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Materia prima')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(150),

                    Forms\Components\TextInput::make('sku')
                        ->label('SKU / Código (opcional)')
                        ->maxLength(60)
                        ->unique(ignoreRecord: true),

                    Forms\Components\Select::make('unit_of_measure_id')
                        ->label('Unidad de medida')
                        ->relationship('unitOfMeasure', 'name')
                        ->searchable()
                        ->preload()
                        ->required(),

                    Forms\Components\TextInput::make('color')
                        ->label('Color / Variación (opcional)')
                        ->maxLength(80),

                    Forms\Components\Textarea::make('description')
                        ->label('Descripción (opcional)')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Activo')
                        ->default(true),

                    // ✅ Fijamos el tipo siempre
                    Forms\Components\Hidden::make('type')
                        ->default('raw_material'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Materia prima')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('sku')->label('SKU')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('unitOfMeasure.name')->label('U. Medida')->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')->label('Activo')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Creado')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Editar'),
                Tables\Actions\DeleteAction::make()->label('Eliminar'),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRawMaterialProducts::route('/'),
            'create' => Pages\CreateRawMaterialProduct::route('/create'),
            'edit'   => Pages\EditRawMaterialProduct::route('/{record}/edit'),
        ];
    }
}
