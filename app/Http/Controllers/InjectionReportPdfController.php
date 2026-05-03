<?php

namespace App\Http\Controllers;

use App\Models\InjectionReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class InjectionReportPdfController extends Controller
{
    public function download(InjectionReport $report)
    {
        $report->load('items');

        $pdf = Pdf::loadView('pdf.injection-report', ['report' => $report])
            ->setPaper('letter', 'portrait'); 

        return $pdf->stream('reporte-actividad-' . $report->id . '.pdf');
    }
}
