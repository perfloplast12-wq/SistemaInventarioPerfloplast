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
        $drawing->setHeight(55);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(5);
        $drawing->setOffsetY(5);

        return $drawing;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        // 1. Crear espacio para cabecera (5 filas)
        $sheet->insertNewRowBefore(1, 5);
        
        // 2. Título Centrado (Fila 2 a 4)
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

        // 3. Estilo del Encabezado de Tabla (Fila 6)
        $headerRange = "A6:{$lastColumn}6";
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
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']],
            ],
        ]);
        $sheet->getRowDimension(6)->setRowHeight(25);

        // 4. Filas de Datos y Cebreado
        for ($i = 7; $i <= ($lastRow + 5); $i++) {
            if ($i % 2 == 0) {
                $sheet->getStyle("A{$i}:{$lastColumn}{$i}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F9FAFB');
            }
            $sheet->getRowDimension($i)->setRowHeight(20);
        }

        return [];
    }
}
