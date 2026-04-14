<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\ChartWidget;

class InventoryTypeChart extends ChartWidget
{
    protected static ?string $heading = 'Distribución por tipo';

    // Esto ayuda a que se vea grande/bonito en dashboard
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array
    {
        $raw = Product::where('type', 'raw_material')->count();
        $fin = Product::where('type', 'finished_product')->count();

        return [
            'datasets' => [
                [
                    'label' => 'Productos',
                    'data' => [$raw, $fin],
                    'backgroundColor' => [
                        'rgba(245, 158, 11, 0.92)', // amber
                        'rgba(16, 185, 129, 0.92)', // emerald
                    ],
                    'borderColor' => [
                        'rgba(245, 158, 11, 1)',
                        'rgba(16, 185, 129, 1)',
                    ],
                    'borderWidth' => 2,
                    'hoverOffset' => 10,
                ],
            ],
            'labels' => ['Materia prima', 'Producto terminado'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '68%',
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'color' => '#E5E7EB', // texto claro para dark mode
                        'padding' => 18,
                        'boxWidth' => 12,
                        'boxHeight' => 12,
                    ],
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
        ];
    }
}
