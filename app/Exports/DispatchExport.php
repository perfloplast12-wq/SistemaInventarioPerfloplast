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
        $drawing->setHeight(60);
        $drawing->setCoordinates('A1');
        return $drawing;
    }

    public function styles(Worksheet $sheet)
    {
        $lastColumn = $sheet->getHighestColumn();
        $lastRow = $sheet->getHighestRow();
        $sheet->insertNewRowBefore(1, 5);
        
        $sheet->mergeCells("B2:{$lastColumn}4");
        $sheet->setCellValue('B2', "PERFLO-PLAST: REPORTE DE LOGÍSTICA Y DESPACHOS");
        $sheet->getStyle('B2')->getFont()->setSize(18)->setBold(true)->getColor()->setRGB('10B981');
        $sheet->getStyle('B2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        $headerRange = "A6:{$lastColumn}6";
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '10B981'],
            ],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

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
