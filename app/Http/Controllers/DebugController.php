<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Filament\Resources\InjectionReportResource;
use Filament\Forms;

class DebugController extends Controller
{
    public function debugForm()
    {
        try {
            $resource = new InjectionReportResource();
            // Try to instantiate the form as Filament does
            $form = InjectionReportResource::form(Forms\Form::make(app(\Livewire\Component::class)));
            return response()->json(['status' => 'success', 'message' => 'Form parses fine']);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
