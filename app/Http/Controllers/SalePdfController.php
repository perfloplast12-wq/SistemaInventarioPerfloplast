<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class SalePdfController extends Controller
{
    public function download(Sale $sale)
    {
        $sale->load(['items.product', 'payments', 'creator']);
        
        $pdf = Pdf::loadView('reports.sale-invoice', [
            'sale' => $sale,
        ]);

        return $pdf->download("Venta_{$sale->sale_number}.pdf");
    }
}
