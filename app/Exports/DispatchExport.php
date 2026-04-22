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

class DispatchExport implements FromCollection, WithHeadings, WithMapping, WithStyles, WithDrawings, ShouldAutoSize
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
        $sheet->insertNewRowBefore(1, 5);
        
        $sheet->mergeCells("C2:G4");
        $sheet->setCellValue('C2', "REPORTE DE LOGÍSTICA");
        $sheet->getStyle('C2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 20, 'color' => ['rgb' => '10B981']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $headerRange = "A6:{$lastColumn}6";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '10B981'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension(6)->setRowHeight(25);

        for ($i = 7; $i <= ($lastRow + 5); $i++) {
            if ($i % 2 == 0) {
                $sheet->getStyle("A{$i}:{$lastColumn}{$i}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F9FAFB');
            }
        }
        return [];
    }
}
