<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\RepeatableEntry;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'Área Comercial';
    protected static ?string $modelLabel = 'Factura / Recibo';
    protected static ?string $pluralModelLabel = 'Facturas';

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('invoices.view') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]); // Read-only via pages
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Nro. Factura')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Cliente')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('sale_type')
                    ->label('Tipo Venta')
                    ->badge(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->formatStateUsing(fn ($state) => 'Q ' . number_format((float)$state, 2, '.', ',')),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('print')
                    ->label('Imprimir')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (Invoice $record): string => route('invoices.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()->label('Eliminar'),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export_excel')
                    ->label('Exportar a Excel')
                    ->icon('heroicon-o-table-cells')
                    ->color('success')
                    ->action(fn ($livewire) => \Maatwebsite\Excel\Facades\Excel::download(
                        new \App\Exports\InvoicesExport($livewire->getFilteredTableQuery()->get()),
                        'facturas_' . now()->format('Y-m-d_H-i') . '.xlsx'
                    )),
                Tables\Actions\Action::make('export_sat')
                    ->label('Exportar SAT')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('warning')
                    ->action(function ($livewire) {
                        $records = $livewire->getFilteredTableQuery()->with('items')->get();
                        $filename = "facturas_sat_" . now()->format('Ymd_His') . ".csv";
                        $headers = [
                            "Content-type"        => "text/csv",
                            "Content-Disposition" => "attachment; filename=$filename",
                        ];
                        $callback = function() use ($records) {
                            $file = fopen('php://output', 'w');
                            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
                            fwrite($file, "sep=;\n");
                            fputcsv($file, ['Nro Factura', 'B/S', 'Cantidad', 'Descripcion', 'Precio Unitario', 'Descuentos (Q)', 'Total (Q)'], ';');
                            foreach ($records as $record) {
                                foreach ($record->items as $item) {
                                    fputcsv($file, [
                                        $record->invoice_number,
                                        'Bien',
                                        number_format((float)$item->quantity, 2, '.', ''),
                                        $item->product_name,
                                        number_format((float)$item->unit_price, 2, '.', ''),
                                        '0.00',
                                        number_format((float)$item->total, 2, '.', '')
                                    ], ';');
                                }
                            }
                            fclose($file);
                        };
                        return response()->stream($callback, 200, $headers);
                    })
            ])
            ->filters([
                Tables\Filters\Filter::make('invoice_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Desde')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Hasta')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                    ])
                    ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data): \Illuminate\Database\Eloquent\Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('invoice_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (\Illuminate\Database\Eloquent\Builder $query, $date): \Illuminate\Database\Eloquent\Builder => $query->whereDate('invoice_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators['from'] = 'Desde ' . \Illuminate\Support\Carbon::parse($data['from'])->format('d/m/Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators['until'] = 'Hasta ' . \Illuminate\Support\Carbon::parse($data['until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulk_export_excel')
                        ->label('Exportar Seleccionados')
                        ->icon('heroicon-o-table-cells')
                        ->color('success')
                        ->action(fn ($records) => \Maatwebsite\Excel\Facades\Excel::download(
                            new \App\Exports\InvoicesExport($records),
                            'facturas_seleccionadas_' . now()->format('Y-m-d_H-i') . '.xlsx'
                        )),
                    Tables\Actions\BulkAction::make('bulk_export_sat')
                        ->label('Exportar SAT')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('warning')
                        ->action(function (\Illuminate\Support\Collection $records) {
                            $filename = "facturas_sat_seleccionadas_" . now()->format('Ymd_His') . ".csv";
                            $headers = [
                                "Content-type"        => "text/csv",
                                "Content-Disposition" => "attachment; filename=$filename",
                            ];
                            $callback = function() use ($records) {
                                $file = fopen('php://output', 'w');
                                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
                                fwrite($file, "sep=;\n");
                                fputcsv($file, ['Nro Factura', 'B/S', 'Cantidad', 'Descripcion', 'Precio Unitario', 'Descuentos (Q)', 'Total (Q)'], ';');
                                foreach ($records as $record) {
                                    foreach ($record->items as $item) {
                                        fputcsv($file, [
                                            $record->invoice_number,
                                            'Bien',
                                            number_format((float)$item->quantity, 2, '.', ''),
                                            $item->product_name,
                                            number_format((float)$item->unit_price, 2, '.', ''),
                                            '0.00',
                                            number_format((float)$item->total, 2, '.', '')
                                        ], ';');
                                    }
                                }
                                fclose($file);
                            };
                            return response()->stream($callback, 200, $headers);
                        }),
                ])
            ])
            ->poll('10s');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Datos de Factura')
                    ->schema([
                        TextEntry::make('invoice_number')->label('Nro. Factura'),
                        TextEntry::make('invoice_date')->label('Fecha')->dateTime('d/m/Y H:i'),
                        TextEntry::make('customer_name')->label('Cliente'),
                        TextEntry::make('customer_nit')->label('NIT / DPI'),
                        TextEntry::make('payment_method')->label('Método de Pago')->badge(),
                        TextEntry::make('sale_type')->label('Tipo de Venta')->badge(),
                    ])->columns(3),

                Section::make('Detalle de Productos')
                    ->schema([
                        RepeatableEntry::make('items')
                            ->label('')
                            ->schema([
                                TextEntry::make('quantity')->label('Cantidad'),
                                TextEntry::make('product_name')->label('Producto'),
                                TextEntry::make('unit_price')->label('P. Unitario')->formatStateUsing(fn ($state) => 'Q ' . number_format((float)$state, 2, '.', ',')),
                                TextEntry::make('total')->label('Subtotal')->formatStateUsing(fn ($state) => 'Q ' . number_format((float)$state, 2, '.', ',')),
                            ])
                            ->columns(4)
                    ]),

                Section::make('Resumen Financiero')
                    ->schema([
                        TextEntry::make('subtotal')->formatStateUsing(fn ($state) => 'Q ' . number_format((float)$state, 2, '.', ',')),
                        TextEntry::make('discount_amount')->label('Descuento')->formatStateUsing(fn ($state) => 'Q ' . number_format((float)$state, 2, '.', ',')),
                        TextEntry::make('total')->label('Total Neto')->formatStateUsing(fn ($state) => 'Q ' . number_format((float)$state, 2, '.', ','))->size(TextEntry\TextEntrySize::Large)->color('success'),
                    ])->columns(3),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageInvoices::route('/'),
        ];
    }
}
