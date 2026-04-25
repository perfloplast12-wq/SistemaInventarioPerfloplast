<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ColorResource\Pages;
use App\Filament\Resources\ColorResource\RelationManagers;
use App\Models\Color;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ColorResource extends Resource
{
    protected static ?string $model = Color::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Colores';

    protected static ?string $modelLabel = 'Color';

    protected static ?string $pluralModelLabel = 'Colores';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('colors.view') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nombre del Color')
                    ->required()
                    ->maxLength(255)
                    ->live(debounce: 500)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                        // Si el nombre está vacío, limpiar el código
                        if (empty(trim($state ?? ''))) {
                            $set('code', '');
                            return;
                        }

                        // Limpiar y separar por palabras
                        $clean = preg_replace('/[^a-z\s]/i', '', Str::ascii($state));
                        $words = array_filter(explode(' ', $clean));
                        $words = array_values($words); // Reindexar

                        $prefix = '';
                        $countWords = count($words);

                        if ($countWords === 1) {
                            // 1 palabra: primeras 3 letras
                            $prefix = substr($words[0], 0, 3);
                        } elseif ($countWords === 2) {
                            // 2 palabras: 1ra letra de la primera + primeras 2 de la segunda
                            $prefix = substr($words[0], 0, 1) . substr($words[1], 0, 2);
                        } else {
                            // 3+ palabras: 1ra letra de las primeras 3 palabras
                            $prefix = substr($words[0], 0, 1) . substr($words[1], 0, 1) . substr($words[2], 0, 1);
                        }

                        $prefix = strtoupper(str_pad($prefix, 3, 'X'));

                        // Calcular correlativo basado en el prefijo
                        $count = Color::where('code', 'like', "{$prefix}-%")->count();
                        $nextNumber = str_pad($count + 1, 3, '0', STR_PAD_LEFT);

                        $set('code', "{$prefix}-{$nextNumber}");
                    }),

                Forms\Components\TextInput::make('code')
                    ->label('Código')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->readOnly()
                    ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                    ->extraInputAttributes(['style' => 'text-transform: uppercase; opacity: 0.8; cursor: not-allowed;']),
                Forms\Components\Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Código')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Activos')
                    ->falseLabel('Solo Inactivos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('products');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageColors::route('/'),
        ];
    }
}
