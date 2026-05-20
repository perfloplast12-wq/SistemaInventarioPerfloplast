<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class DispatchExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithDrawings, ShouldAutoSize, WithCustomStartCell
{
    protected $collection;

    public function __construct($collection)
    {
        $this->collection = $collection;
    }

    public function collection()
    {
        return $this->collection;
    }

    public function startCell(): string
    {
        return 'A7';
    }

    public function headings(): array
    {
        return [
            'Nro. Despacho',
            'Fecha',
            'Piloto',
            'Camión / Placa',
            'Destino',
            'Cant. Productos',
            'Estado'
        ];
    }

    public function map($dispatch): array
    {
        return [
            $dispatch->dispatch_number,
            $dispatch->dispatch_date->format('d/m/Y H:i'),
            $dispatch->driver?->name ?? 'N/A',
            $dispatch->truck?->plate_number ?? 'N/A',
            $dispatch->destination_warehouse?->name ?? 'N/A',
            $dispatch->items_count ?? $dispatch->items()->count(),
            strtoupper($dispatch->status),
        ];
    }

    public function drawings()
    {
        $drawing = new Drawing();
        $drawing->setName('Logo');
        $drawing->setPath(public_path('images/logo-perfloplast-premium.png'));
        $drawing->setHeight(50);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(10);
        $drawing->setOffsetY(5);
        return $drawing;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        // 1. Dar altura a las filas de la cabecera para que el logo quepa bien
        for ($i = 1; $i <= 6; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(18);
        }

        // 2. Título Centrado (Fila 2 a 4)
        $sheet->mergeCells("C2:G4");
        $sheet->setCellValue('C2', "REPORTE DE LOGÍSTICA");
        $sheet->getStyle('C2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 20, 'color' => ['rgb' => '10B981']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // 3. Estilo del Encabezado de Tabla (Fila 7)
        $headerRange = "A7:{$lastColumn}7";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '10B981'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(7)->setRowHeight(25);

        // 4. Filas de Datos y Cebreado
        for ($i = 8; $i <= $lastRow; $i++) {
            if ($i % 2 == 0) {
                $sheet->getStyle("A{$i}:{$lastColumn}{$i}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F9FAFB');
            }
        }
        return [];
    }
}
