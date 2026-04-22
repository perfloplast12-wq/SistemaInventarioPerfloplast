<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RawMaterialProductResource\Pages;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\InventoryMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
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

    // ✅ Solo materia prima con stock total
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('type', 'raw_material')
            ->withSum('stocks as stock_total', 'quantity');
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
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at')),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('unit_of_measure_id')
                            ->label('Unidad de Medida (Cómo se pesa)')
                            ->relationship('unitOfMeasure', 'name')
                            ->helperText('Generalmente es "Kilogramos (KG)" o "Libras". Es la unidad mínima de uso.')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('presentation_unit_id')
                            ->label('¿Cómo viene empacado? (Sacos/Pacas/etc)')
                            ->relationship('presentationUnit', 'name')
                            ->helperText('Ej: Selecciona "Saco" si la materia prima entra por sacos.')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Hidden::make('units_per_presentation')
                            ->default(1),

                        Forms\Components\Placeholder::make('stock_total_display')
                            ->label('Stock Actual (Base)')
                            ->content(fn ($record) => number_format((float)($record?->stock_total ?? 0), 2, '.', ',') . ' ' . ($record?->unitOfMeasure?->name ?? ''))
                            ->hidden(fn ($operation) => $operation === 'create')
                            ->extraAttributes(['class' => 'font-bold text-primary-600']),
                    ]),

                    Forms\Components\Section::make('Ingreso de Stock Inicial')
                        ->description('Usa esta sección solo si ya tienes stock físico disponible.')
                        ->visible(fn ($operation) => $operation === 'create')
                        ->schema([
                            Forms\Components\Grid::make(3)->schema([
                                Forms\Components\Select::make('initial_warehouse_id')
                                    ->label('Bodega')
                                    ->options(\App\Models\Warehouse::where('is_active', true)->pluck('name', 'id'))
                                    ->dehydrated(false)
                                    ->required(fn (Get $get) => filled($get('initial_stock'))),

                                Forms\Components\TextInput::make('initial_stock')
                                    ->label('Cantidad Inicial')
                                    ->numeric()
                                    ->step(0.01)
                                    ->dehydrated(false)
                                    ->helperText('En la unidad de medida base (KG)'),

                                Forms\Components\Placeholder::make('info')
                                    ->label('Nota')
                                    ->content('Esto creará un movimiento de entrada inicial automáticamente.')
                                    ->extraAttributes(['class' => 'text-xs text-gray-500']),
                            ])
                        ]),


                    Forms\Components\Toggle::make('is_active')
                        ->label('Activo')
                        ->default(true),

                    Forms\Components\Textarea::make('description')
                        ->label('Descripción (opcional)')
                        ->rows(3)
                        ->columnSpanFull(),

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
                Tables\Columns\TextColumn::make('name')
                    ->label('Materia prima')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_total')
                    ->label('Stock Disponible')
                    ->formatStateUsing(function ($state, Product $record) {
                        $baseUnit = $record->unitOfMeasure?->name ?? 'Sacos';
                        return number_format((float)$state, 2, '.', ',') . ' ' . $baseUnit;
                    })
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Activo')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('register_entry')
                    ->label('Entrada')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                         Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Select::make('to_warehouse_id')
                                ->label('Bodega de Destino')
                                ->options(Warehouse::where('is_active', true)->pluck('name', 'id'))
                                ->required()
                                ->searchable(),
                            
                            Forms\Components\Select::make('entry_unit')
                                ->label('Registrar por')
                                ->options(function (Product $record) {
                                    $options = ['base' => $record->unitOfMeasure?->name ?? 'Unidad Base'];
                                    if ($record->presentation_unit_id) {
                                        $options['presentation'] = $record->presentationUnit?->name ?? 'Sacos/Presentación';
                                    }
                                    return $options;
                                })
                                ->default(fn (Product $record) => $record->presentation_unit_id ? 'presentation' : 'base')
                                ->required()
                                ->live(),

                            Forms\Components\TextInput::make('quantity')
                                ->label(fn (Get $get) => $get('entry_unit') === 'presentation' ? 'Número de Sacos/Paquetes' : 'Cantidad Exacta')
                                ->numeric()
                                ->required()
                                ->minValue(0.01)
                                ->helperText(function (Get $get, Product $record) {
                                    if ($get('entry_unit') === 'presentation' && $record->units_per_presentation > 0) {
                                        return "Se ingresarán " . ($record->units_per_presentation * (float)($get('quantity') ?: 0)) . " " . ($record->unitOfMeasure?->name ?? '');
                                    }
                                    return null;
                                }),
                        ]),
                        Forms\Components\Textarea::make('note')
                            ->label('Nota / Referencia')
                            ->rows(2),
                    ])
                    ->action(function (Product $record, array $data) {
                        $qty = (float)$data['quantity'];
                        if ($data['entry_unit'] === 'presentation') {
                            $qty *= (float)($record->units_per_presentation ?: 1);
                        }

                        InventoryMovement::create([
                            'type' => 'in',
                            'product_id' => $record->id,
                            'to_warehouse_id' => $data['to_warehouse_id'],
                            'quantity' => $qty,
                            'unit_cost' => 0,
                            'note' => $data['note'] ?? 'Entrada por ' . ($data['entry_unit'] === 'presentation' ? 'Sacos' : 'Unidad Base'),
                            'created_by' => auth()->id(),
                        ]);
                        
                        Notification::make()
                            ->title('Stock actualizado')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('stock_details')
                    ->label('Ver Stock')
                    ->icon('heroicon-o-building-library')
                    ->color('info')
                    ->modalHeading(fn ($record) => "Detalle de Stock: {$record->name}")
                    ->modalSubmitAction(false)
                    ->modalContent(fn ($record) => view('filament.components.stock-details', [
                        'record' => $record,
                    ])),
                Tables\Actions\EditAction::make()->label('Editar'),
                Tables\Actions\DeleteAction::make()->label('Eliminar'),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
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

    public static function getRedirectUrl(): string
    {
        return static::getUrl('index');
    }
}
