<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductionResource\Pages;
use App\Filament\Resources\ProductionResource\RelationManagers;
use App\Models\Production;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class ProductionResource extends Resource
{
    protected static ?string $model = Production::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Producciones';
    protected static ?string $modelLabel = 'Producción';
    protected static ?string $pluralModelLabel = 'Producciones';

    protected static ?int $navigationSort = 21;

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('production.view') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('production.create') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Principal')
                    ->schema([
                        Forms\Components\Grid::make(['default' => 1, 'sm' => 2, 'md' => 3])
                            ->schema([
                                Forms\Components\TextInput::make('production_number')
                                    ->label('Nro. Producción')
                                    ->placeholder('Autogenerado')
                                    ->disabled()
                                    ->dehydrated(false),
                                
                                Forms\Components\DateTimePicker::make('production_date')
                                    ->label('Fecha de Producción')
                                    ->default(now())
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if (!$state) return;
                                        $hour = \Carbon\Carbon::parse($state)->format('H:i');
                                        $shifts = \App\Models\Shift::where('is_active', true)->get();
                                        foreach ($shifts as $shift) {
                                            if (!$shift->start_time || !$shift->end_time) continue;
                                            $start = $shift->start_time;
                                            $end = $shift->end_time;
                                            if ($start > $end) {
                                                if ($hour >= $start || $hour < $end) {
                                                    $set('shift_id', $shift->id);
                                                    return;
                                                }
                                            } else {
                                                if ($hour >= $start && $hour < $end) {
                                                    $set('shift_id', $shift->id);
                                                    return;
                                                }
                                            }
                                        }
                                    }),

                                Forms\Components\Select::make('shift_id')
                                    ->label('Turno')
                                    ->relationship('shift', 'name')
                                    ->options(fn () => \App\Models\Shift::where('is_active', true)->pluck('name', 'id'))
                                    ->required()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->hint('Automático')
                                    ->default(function () {
                                        $hour = now()->format('H:i');
                                        $shifts = \App\Models\Shift::where('is_active', true)->get();
                                        foreach ($shifts as $shift) {
                                            if (!$shift->start_time || !$shift->end_time) continue;
                                            $start = $shift->start_time;
                                            $end = $shift->end_time;
                                            if ($start > $end) {
                                                if ($hour >= $start || $hour < $end) return $shift->id;
                                            } else {
                                                if ($hour >= $start && $hour < $end) return $shift->id;
                                            }
                                        }
                                        return null;
                                    }),
                            ]),

                        Forms\Components\Grid::make(['default' => 1, 'sm' => 2])
                            ->schema([
                                Forms\Components\Select::make('color_id')
                                    ->label('Color / Variante')
                                    ->relationship('color', 'name', fn ($query) => $query->where('is_active', true))
                                    ->searchable(['name', 'code'])
                                    ->preload()
                                    ->required()
                                    ->live(),
                            ]),

                        Forms\Components\Select::make('product_id')
                            ->label('Producto Terminado a Producir')
                            ->options(fn () => \App\Models\Product::where('type', 'finished_product')
                                ->where('is_active', true)
                                ->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpanFull(),
                        
                        Forms\Components\Grid::make(['default' => 1, 'sm' => 2])
                            ->schema([
                                Forms\Components\Select::make('to_warehouse_id')
                                    ->label('Bodega de Destino')
                                    ->options(fn () => \App\Models\Warehouse::where('is_active', true)->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Cantidad Producida')
                                    ->numeric()
                                    ->step(0.01)
                                    ->required()
                                    ->minValue(0.01)
                                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ''))
                                    ->live(onBlur: true),
                            ]),
                    ]),

                        Forms\Components\Placeholder::make('status_display')
                            ->label('Estado Actual')
                            ->content(fn ($record) => match ($record?->status) {
                                'confirmed' => '✓ Confirmada (Inventario actualizado)',
                                'cancelled' => '✗ Cancelada',
                                default     => '⋯ Borrador (No ha afectado el inventario aún)',
                            })
                            ->extraAttributes(['class' => 'text-sm font-medium']),
                    ])->columns(1),

                Forms\Components\Section::make('Materias Primas Consumidas')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('product_id')
                                    ->label('Materia Prima')
                                    ->options(fn () => \App\Models\Product::where('type', 'raw_material')
                                        ->where('is_active', true)
                                        ->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload(),
                                
                                Forms\Components\TextInput::make('quantity')
                                    ->label('Cantidad Consumo')
                                    ->numeric()
                                    ->step(0.01)
                                    ->required()
                                    ->formatStateUsing(fn ($state) => number_format((float) $state, 2, '.', ''))
                                    ->minValue(0.01),
                            ])
                            ->columns(2)
                            ->addActionLabel('Añadir Materia Prima')
                            ->itemLabel(fn (array $state): ?string => 
                                \App\Models\Product::find($state['product_id'] ?? null)?->name ?? 'Nuevo Item'
                            )
                            ->collapsible()
                            ->minItems(1),
                    ]),

                Forms\Components\Section::make('Notas')
                    ->schema([
                        Forms\Components\Textarea::make('note')
                            ->label('Observaciones')
                            ->rows(2)
                            ->placeholder('Opcional...'),
                    ])->collapsed(),
                
                Forms\Components\Hidden::make('created_by')->default(auth()->id()),
                Forms\Components\Hidden::make('status')->default('draft'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('production_number')
                    ->label('Nro. Prod.')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->fontFamily('mono'),
                
                Tables\Columns\TextColumn::make('production_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('product.name')
                    ->label('Producto Producido')
                    ->sortable(),

                Tables\Columns\TextColumn::make('color.display_name')
                    ->label('Color')
                    ->sortable(),

                Tables\Columns\TextColumn::make('color.name')
                    ->label('Nombre Color')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('shift.name')
                    ->label('Turno')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->formatStateUsing(fn ($state) => number_format((float)$state, 2, '.', ','))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'confirmed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'confirmed' => 'Confirmada',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    }),

                Tables\Columns\TextColumn::make('toWarehouse.name')
                    ->label('Bodega')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Borrador',
                        'confirmed' => 'Confirmada',
                        'cancelled' => 'Cancelada',
                    ]),

                Tables\Filters\SelectFilter::make('shift_id')
                    ->label('Turno')
                    ->relationship('shift', 'name'),

                Tables\Filters\Filter::make('production_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Desde'),
                        Forms\Components\DatePicker::make('until')->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('production_date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('production_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->status === 'draft'),
                
                Tables\Actions\Action::make('confirm')
                    ->label('Confirmar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('¿Confirmar producción?')
                    ->modalDescription('Esto descontará materias primas e ingresará producto terminado.')
                    ->visible(fn ($record) => $record->status === 'draft')
                    ->action(function (Production $record) {
                        $record->confirm();

                        Notification::make()
                            ->title('Producción confirmada')
                            ->success()
                            ->send();
                    }),
                
                Tables\Actions\Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('¿Cancelar producción?')
                    ->modalDescription('Esto revertirá el stock (devolverá materias primas y descontará el producto terminado).')
                    ->visible(fn ($record) => $record->status === 'confirmed')
                    ->action(function (Production $record) {
                        $record->cancel();

                        Notification::make()
                            ->title('Producción cancelada')
                            ->body('El inventario ha sido revertido con éxito.')
                            ->success()
                            ->send();
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                    ->label('Exportar Excel Premium')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function ($livewire) {
                        $records = $livewire->getFilteredTableQuery()->get();
                        return \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\ProductionExport($records), 
                            "Produccion_Perfloplast_" . now()->format('Ymd_His') . ".xlsx"
                        );
                    }),
            ])
            ->poll('5s')
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductions::route('/'),
            'create' => Pages\CreateProduction::route('/create'),
            'edit' => Pages\EditProduction::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['product', 'color', 'shift', 'toWarehouse']);
    }
}
