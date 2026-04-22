<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class SalesExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithDrawings, ShouldAutoSize
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

    public function headings(): array
    {
        return [
            'Nro. Venta',
            'Fecha',
            'Cliente',
            'Descuento',
            'Total (Q)',
            'Pagado',
            'Saldo',
            'Estado',
            'Creado por'
        ];
    }

    public function map($sale): array
    {
        return [
            $sale->sale_number,
            $sale->sale_date->format('d/m/Y H:i'),
            $sale->customer_name,
            number_format($sale->discount_amount, 2),
            number_format($sale->total, 2),
            number_format($sale->total_paid, 2),
            number_format($sale->balance, 2),
            strtoupper($sale->status),
            $sale->creator?->name ?? 'Sistema',
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

        // 1. Crear espacio para cabecera (6 filas para seguridad)
        $sheet->insertNewRowBefore(1, 6);
        
        // 2. Dar altura a las filas de la cabecera para que el logo quepa bien
        for ($i = 1; $i <= 6; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(18);
        }

        // 3. Título Centrado (Fila 2 a 4)
        $sheet->mergeCells("C2:G4");
        $sheet->setCellValue('C2', "REPORTE DE VENTAS");
        $sheet->getStyle('C2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 20,
                'color' => ['rgb' => '10B981'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // 4. Estilo del Encabezado de Tabla (Fila 7)
        $headerRange = "A7:{$lastColumn}7";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '10B981'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);
        $sheet->getRowDimension(7)->setRowHeight(25);

        // 5. Filas de Datos y Cebreado
        for ($i = 8; $i <= ($lastRow + 6); $i++) {
            if ($i % 2 == 0) {
                $sheet->getStyle("A{$i}:{$lastColumn}{$i}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F9FAFB');
            }
            $sheet->getRowDimension($i)->setRowHeight(18);
        }

        return [];
    }
}
