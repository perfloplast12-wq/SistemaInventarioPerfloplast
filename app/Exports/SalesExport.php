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
        $drawing->setDescription('Perfloplast Logo');
        $drawing->setPath(public_path('images/logo-perfloplast-premium.png'));
        $drawing->setHeight(60);
        $drawing->setCoordinates('A1');

        return $drawing;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();

        // Insertar espacio para el logo y título
        $sheet->insertNewRowBefore(1, 5);
        
        // Título del Reporte
        $sheet->mergeCells("B2:{$lastColumn}4");
        $sheet->setCellValue('B2', "PERFLO-PLAST: REPORTE DE VENTAS");
        $sheet->getStyle('B2')->getFont()->setSize(18)->setBold(true)->getColor()->setRGB('10B981');
        $sheet->getStyle('B2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Estilo para el encabezado de la tabla (ahora en fila 6)
        $headerRange = "A6:{$lastColumn}6";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '10B981'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']],
            ],
        ]);

        // Estilo de datos y cebra
        $dataRange = "A7:{$lastColumn}" . ($lastRow + 5);
        $sheet->getStyle($dataRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        
        for ($i = 7; $i <= ($lastRow + 5); $i++) {
            if ($i % 2 == 0) {
                $sheet->getStyle("A{$i}:{$lastColumn}{$i}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F9FAFB');
            }
            // Bordes suaves
            $sheet->getStyle("A{$i}:{$lastColumn}{$i}")->getBorders()->getBottom()
                ->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('E5E7EB');
        }

        return [];
    }
}
