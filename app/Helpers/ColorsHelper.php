<?php

namespace App\Helpers;

class ColorsHelper
{
    /**
     * Paleta de colores industriales para gráficas (Filament/ApexCharts)
     */
    public static function getIndustrialPalette(): array
    {
        return [
            '#3b82f6', // Azul (Producción)
            '#f97316', // Naranja (Ventas)
            '#eab308', // Amarillo (Intermedio)
            '#ef4444', // Rojo (Crítico)
            '#22c55e', // Verde (Óptimo)
            '#6366f1', // Indigo (Logística)
            '#ec4899', // Rosa (Otros)
            '#8b5cf6', // Violeta
        ];
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            'confirmed', 'success', 'completed' => '#22c55e',
            'pending', 'warning', 'in_progress' => '#eab308',
            'cancelled', 'danger', 'failed' => '#ef4444',
            default => '#64748b',
        };
    }
}
