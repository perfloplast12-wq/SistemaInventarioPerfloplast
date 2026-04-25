<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinishedProductResource\Pages;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\InventoryMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FinishedProductResource extends Resource
{
    protected static ?string $model = Product::class;

    // ✅ Oculto del menú
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationLabel = 'Producto terminado';
    protected static ?string $modelLabel = 'Producto terminado';
    protected static ?string $pluralModelLabel = 'Productos terminados';

    protected static ?string $navigationGroup = 'Producción e Inventario';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    // ✅ PERMISOS (mismos products.*)
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

    // ✅ Solo producto terminado con stock total
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('type', 'finished_product')
            ->with(['unitOfMeasure', 'presentationUnit'])
            ->withSum('stocks as stock_total', 'quantity');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Producto terminado')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(150),

                    Forms\Components\TextInput::make('sku')
                        ->label('SKU / Código (opcional)')
                        ->maxLength(60)
                        ->unique(ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->whereNull('deleted_at')),

                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('sale_price')
                            ->label('Precio de venta')
                            ->numeric()
                            ->step(0.01)
                            ->prefix('Q')
                            ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ''))
                            ->required()
                            ->default(0),

                        Forms\Components\Select::make('unit_of_measure_id')
                            ->label('Unidad de medida')
                            ->relationship('unitOfMeasure', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('presentation_unit_id')
                            ->label('Unidad de Presentación')
                            ->relationship('presentationUnit', 'name')
                            ->helperText('Ej: Saco, Tonelada')
                            ->searchable()
                            ->preload(),

                        Forms\Components\Hidden::make('units_per_presentation')
                            ->default(1),

                        Forms\Components\Placeholder::make('stock_total_display')
                            ->label('Stock Actual (Base)')
                            ->content(fn ($record) => number_format($record?->stocks()->sum('quantity') ?? 0, 2) . ' ' . ($record?->unitOfMeasure?->name ?? ''))
                            ->extraAttributes(['class' => 'font-bold text-primary-600']),
                    ]),


                    Forms\Components\Textarea::make('description')
                        ->label('Descripción (opcional)')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Activo')
                        ->default(true),

                    // ✅ Fijamos tipo
                    Forms\Components\Hidden::make('type')
                        ->default('finished_product'),
                ])
                ->columns(2),

        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('stock_total')
                    ->label('Stock')
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 2, '.', ','))
                    ->suffix(fn ($record) => ' ' . $record->unitOfMeasure?->name)
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state <= 0 => 'danger',
                        $state < 10 => 'warning',
                        default => 'success',
                    }),


                Tables\Columns\TextColumn::make('sale_price')
                    ->label('Precio')
                    ->formatStateUsing(fn ($state) => 'Q ' . number_format((float)$state, 2, '.', ','))
                    ->sortable(),

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
                        Forms\Components\Select::make('to_warehouse_id')
                            ->label('Bodega de Destino')
                            ->options(Warehouse::where('is_active', true)->pluck('name', 'id'))
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('quantity')
                            ->label('Cantidad a Ingresar')
                            ->numeric()
                            ->required()
                            ->minValue(0.01),
                        Forms\Components\Textarea::make('note')
                            ->label('Nota / Referencia')
                            ->rows(2),
                    ])
                    ->action(function (Product $record, array $data) {
                        InventoryMovement::create([
                            'type' => 'in',
                            'product_id' => $record->id,
                            'color_id' => null,
                            'to_warehouse_id' => $data['to_warehouse_id'],
                            'quantity' => $data['quantity'],
                            'unit_cost' => 0,
                            'note' => $data['note'] ?? 'Entrada rápida desde catálogo',
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
            'index'  => Pages\ListFinishedProducts::route('/'),
            'create' => Pages\CreateFinishedProduct::route('/create'),
            'edit'   => Pages\EditFinishedProduct::route('/{record}/edit'),
        ];
    }
}
