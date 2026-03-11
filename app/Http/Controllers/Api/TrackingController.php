<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Dispatch;
use App\Models\DispatchLocation;

class TrackingController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'dispatch_id' => 'required|exists:dispatches,id',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'speed' => 'nullable|numeric',
            'heading' => 'nullable|numeric',
        ]);

        $dispatch = Dispatch::findOrFail($validated['dispatch_id']);

        // Solo permitir rastreo si el despacho está en proceso
        if ($dispatch->status !== 'in_progress') {
            return response()->json(['message' => 'Dispatch not in progress'], 403);
        }

        $location = DispatchLocation::create($validated);

        return response()->json([
            'message' => 'Location recorded',
            'location' => $location
        ], 201);
    }
}
