<?php

namespace App\Filament\Resources\SaleResource\Pages;

use App\Filament\Resources\SaleResource;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Sale;
use App\Services\SaleService;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuickSale extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = SaleResource::class;

    protected static string $view = 'filament.resources.sale-resource.pages.quick-sale';

    protected static ?string $title = 'Venta Rápida (Fábrica)';

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')
                ->label('Volver a Inventario')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(route('filament.admin.pages.inventario')),
        ];
    }

    public ?array $data = [];

    public function mount(): void
    {
        $factories = Warehouse::where('is_factory', true)->get();

        if ($factories->isEmpty()) {
            Notification::make()
                ->title('Error de Configuración')
                ->body('Debe configurar una bodega como Fábrica antes de realizar ventas rápidas.')
                ->danger()
                ->persistent()
                ->send();
            
            $this->redirect(SaleResource::getUrl('index'));
            return;
        }

        if ($factories->count() > 1) {
            Notification::make()
                ->title('Error de Configuración')
                ->body('Existe más de una bodega marcada como fábrica. Solo debe existir una para este proceso.')
                ->danger()
                ->persistent()
                ->send();
            
            $this->redirect(SaleResource::getUrl('index'));
            return;
        }

        $this->form->fill([
            'sale_date' => now(),
            'customer_name' => 'Consumidor Final',
            'customer_nit' => 'C/F',
            'from_warehouse_id' => $factories->first()->id,
            'factory_name' => $factories->first()->name,
            'items' => [[]],
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(['default' => 1, 'lg' => 12])
                    ->schema([
                        // Info General
                        Section::make('Información de la Venta Fast')
                            ->schema([
                                TextInput::make('sale_number')
                                    ->label('Nro. Venta')
                                    ->placeholder('Autogenerado')
                                    ->disabled()
                                    ->dehydrated(false),
                                
                                TextInput::make('customer_name')
                                    ->label('Nombre del Cliente')
                                    ->required()
                                    ->minLength(3),
                                
                                TextInput::make('customer_nit')
                                    ->label('NIT')
                                    ->maxLength(20)
                                    ->default('C/F'),
                                
                                TextInput::make('factory_name')
                                    ->label('Bodega Origen (Fábrica)')
                                    ->disabled()
                                    ->dehydrated(false),

                                \Filament\Forms\Components\Hidden::make('from_warehouse_id'),
                            ])->columnSpan(['default' => 'full', 'lg' => 4]),

                        // Listado de Items
                        Section::make('Productos Seleccionados')
                            ->schema([
                                Repeater::make('items')
                                    ->schema([
                                        Grid::make(12)
                                            ->schema([
                                                Select::make('product_id')
                                                    ->label('Producto')
                                                    ->options(function (Get $get) {
                                                        $warehouseId = $get('../../from_warehouse_id');
                                                        if (!$warehouseId) return [];

                                                        return Product::where('is_active', true)
                                                            ->where('type', 'finished_product')
                                                            ->whereHas('stocks', fn($q) => $q->where('warehouse_id', $warehouseId)->where('quantity', '>', 0))
                                                            ->get()
                                                            ->mapWithKeys(function ($p) use ($warehouseId) {
                                                                $stock = $p->stocks()->where('warehouse_id', $warehouseId)->sum('quantity');
                                                                return [$p->id => "{$p->name} — [Disp: " . number_format($stock, 2) . "]"];
                                                            })->toArray();
                                                    })
                                                    ->required()
                                                    ->searchable()
                                                    ->live()
                                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                        $set('color_id', null);
                                                        if (!$state) {
                                                            $set('unit_price', null);
                                                            $set('quantity', null);
                                                            $set('subtotal', 0);
                                                            return;
                                                        }
                                                        $product = Product::find($state);
                                                        $price = $product ? (float)$product->sale_price : 0;
                                                        $set('unit_price', $price);
                                                        if (!$get('quantity')) {
                                                            $set('quantity', 1);
                                                            $set('subtotal', $price);
                                                        } else {
                                                            $set('subtotal', $price * (float)$get('quantity'));
                                                        }
                                                    })
                                                    ->columnSpan(['default' => 12, 'md' => 3]),

                                                Select::make('color_id')
                                                    ->label('Color / Variante')
                                                    ->options(function (Get $get) {
                                                        $productId = $get('product_id');
                                                        $warehouseId = $get('../../from_warehouse_id');
                                                        if (!$productId || !$warehouseId) return [];

                                                        $product = Product::with('color')->find($productId);
                                                        $stocks = \App\Models\Stock::with('color')
                                                            ->where('product_id', $productId)
                                                            ->where('warehouse_id', $warehouseId)
                                                            ->where('quantity', '>', 0)
                                                            ->get();

                                                        return $stocks->groupBy(fn($s) => $s->color_id ?? 'null')->mapWithKeys(function ($group, $key) use ($product) {
                                                            $totalQty = $group->sum('quantity');
                                                            $stockRecord = $group->first();
                                                            $stockColor = $stockRecord->color;
                                                            $colorLabel = $stockColor ? $stockColor->display_name : ($product->color ? $product->color->display_name . ' (Catálogo)' : 'Sin Color');
                                                            
                                                            return [
                                                                $key => $colorLabel . " — [Disp: " . number_format($totalQty, 2) . "]"
                                                            ];
                                                        })->toArray();
                                                    })
                                                    ->required()
                                                    ->searchable()
                                                    ->live()
                                                    ->columnSpan(['default' => 12, 'md' => 3]),
                                                TextInput::make('quantity')
                                                    ->label('Cant.')
                                                    ->numeric()
                                                    ->default(1)
                                                    ->minValue(0.001)
                                                    ->required()
                                                    ->live(debounce: 500)
                                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                        $price = (float)($get('unit_price') ?? 0);
                                                        $qty = (float)($state ?: 0);
                                                        $set('subtotal', $qty * $price);
                                                    })
                                                    ->columnSpan(['default' => 4, 'md' => 2]),
                                                TextInput::make('unit_price')
                                                    ->label('Precio Q')
                                                    ->numeric()
                                                    ->required()
                                                    ->readOnly()
                                                    ->prefix('Q')
                                                    ->columnSpan(['default' => 4, 'md' => 2]),
                                                
                                                Placeholder::make('subtotal_item_display')
                                                    ->label('Subtotal')
                                                    ->content(fn (Get $get) => 'Q ' . number_format((float)($get('subtotal') ?? 0), 2))
                                                    ->extraAttributes(['class' => 'font-bold pt-2 text-right'])
                                                    ->columnSpan(['default' => 4, 'md' => 2]),

                                                \Filament\Forms\Components\Hidden::make('subtotal'),
                                            ]),
                                    ])
                                    ->defaultItems(1)
                                    ->addActionLabel('Añadir OTRO Producto')
                                    ->reorderable(false)
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, Get $get) => $this->recalculateAll($set, $get)),
                            ])->columnSpan(['default' => 'full', 'lg' => 8]),
                    ]),
                
                Section::make('Finalización y Cobro')
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                Placeholder::make('gross_subtotal')
                                    ->label('Subtotal Bruto')
                                    ->content(fn (Get $get) => 'Q ' . number_format($this->getGrossSubtotal($get), 2)),

                                \Filament\Forms\Components\Group::make([
                                    Select::make('discount_type')
                                        ->label('Tipo Descuento')
                                        ->options([
                                            'none'    => 'N/A',
                                            'percent' => '%',
                                            'fixed'   => 'Q',
                                        ])
                                        ->default('none')
                                        ->live()
                                        ->afterStateUpdated(fn (Set $set, Get $get) => $this->recalculateAll($set, $get)),

                                    TextInput::make('discount_value')
                                        ->label('Valor Decto.')
                                        ->numeric()
                                        ->default(0)
                                        ->visible(fn (Get $get) => $get('discount_type') !== 'none')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Set $set, Get $get) => $this->recalculateAll($set, $get)),
                                ])->columns(2),

                                Placeholder::make('total_to_pay')
                                    ->label('TOTAL FINAL')
                                    ->content(fn (Get $get) => 'Q ' . number_format((float)($get('total') ?? 0), 2))
                                    ->extraAttributes(['class' => 'text-3xl font-black text-primary-600']),

                                \Filament\Forms\Components\Group::make([
                                    Select::make('payment_method')
                                        ->label('Método Pago')
                                        ->options([
                                            'cash' => 'Efectivo',
                                            'transfer' => 'Transferencia',
                                            'card' => 'Tarjeta',
                                        ])
                                        ->required()
                                        ->default('cash'),
                                    
                                    TextInput::make('payment_amount')
                                        ->label('Monto Recibido')
                                        ->numeric()
                                        ->required()
                                        ->prefix('Q')
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn (Set $set, Get $get) => $this->recalculateAll($set, $get)),
                                ])->columns(2),
                            ]),
                        
                        \Filament\Forms\Components\Section::make('Resumen Financiero')
                            ->schema([
                                Grid::make(3)->schema([
                                    Placeholder::make('summary_total')
                                        ->label('Total a Cobrar')
                                        ->content(fn (Get $get) => 'Q ' . number_format((float)($get('total') ?? 0), 2)),
                                    
                                    Placeholder::make('summary_balance')
                                        ->label('Saldo Pendiente')
                                        ->content(fn (Get $get) => 'Q ' . number_format((float)($get('balance') ?? 0), 2))
                                        ->extraAttributes(fn (Get $get) => [
                                            'class' => (float)$get('balance') > 0.01 ? 'text-danger-600 font-bold' : 'text-success-600 font-bold'
                                        ]),

                                    Placeholder::make('summary_change')
                                        ->label('Cambio / Vuelto')
                                        ->visible(fn (Get $get) => (float)($get('payment_amount') ?? 0) > (float)($get('total') ?? 0))
                                        ->content(fn (Get $get) => 'Q ' . number_format(max(0, (float)($get('payment_amount') ?? 0) - (float)($get('total') ?? 0)), 2))
                                        ->extraAttributes(['class' => 'text-success-600 font-bold']),
                                ]),
                            ])->compact(),
                        
                        \Filament\Forms\Components\Hidden::make('total')->default(0)->live(),
                        \Filament\Forms\Components\Hidden::make('discount_amount')->default(0)->live(),
                        \Filament\Forms\Components\Hidden::make('balance')->default(0)->live(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getGrossSubtotal(Get $get): float
    {
        return collect($get('items'))->sum(fn ($i) => (float)($i['quantity'] ?? 0) * (float)($i['unit_price'] ?? 0));
    }

    public function recalculateAll(Set $set, Get $get): void
    {
        $subtotal = $this->getGrossSubtotal($get);
        $type = $get('discount_type');
        $val = (float)($get('discount_value') ?? 0);
        
        $discountAmount = 0;
        if ($type === 'percent') {
            $discountAmount = $subtotal * (min(100, $val) / 100);
        } elseif ($type === 'fixed') {
            $discountAmount = min($subtotal, $val);
        }

        $total = max(0, $subtotal - $discountAmount);
        $paid = (float)($get('payment_amount') ?? 0);
        $balance = max(0, $total - $paid);

        $set('discount_amount', $discountAmount);
        $set('total', $total);
        $set('balance', $balance);
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        try {
            DB::transaction(function () use ($data) {
                // 1. Crear la venta en borrador
                $sale = Sale::create([
                    'sale_date' => now(),
                    'customer_name' => $data['customer_name'],
                    'customer_nit' => $data['customer_nit'] ?? 'C/F',
                    'origin_type' => 'warehouse',
                    'from_warehouse_id' => $data['from_warehouse_id'],
                    'status' => 'draft',
                    'sale_date' => now(),
                    'total' => $data['total'],
                    'discount_type' => $data['discount_type'] ?? 'none',
                    'discount_value' => $data['discount_value'] ?? 0,
                    'discount_amount' => $data['discount_amount'] ?? 0,
                    'created_by' => auth()->id(),
                ]);

                // 2. Agregar items
                foreach ($data['items'] as $item) {
                    if (empty($item['product_id'])) continue;
                    
                    $colorId = ($item['color_id'] === 'null') ? null : $item['color_id'];

                    $sale->items()->create([
                        'product_id' => $item['product_id'],
                        'color_id' => $colorId,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount_amount' => 0,
                        'subtotal' => (float)$item['quantity'] * (float)$item['unit_price'],
                        'total' => (float)$item['quantity'] * (float)$item['unit_price'],
                    ]);
                }

                // 3. Agregar pago
                if ((float)$data['payment_amount'] > 0) {
                    $sale->payments()->create([
                        'payment_method' => $data['payment_method'],
                        'amount' => $data['payment_amount'],
                        'payment_date' => now(),
                    ]);
                }

                // 4. Confirmar usando SaleService
                $service = new SaleService();
                $service->confirm($sale);
            });

            Notification::make()
                ->title('Venta Rápida Confirmada')
                ->success()
                ->send();

            $this->redirect(route('filament.admin.pages.inventario'));

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error al procesar venta')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
