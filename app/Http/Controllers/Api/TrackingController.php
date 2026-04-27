<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dispatch;
use App\Models\DispatchLocation;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            
            $validated = $request->validate([
                'dispatch_id' => 'nullable|exists:dispatches,id',
                'lat' => 'required|numeric',
                'lng' => 'required|numeric',
                'speed' => 'nullable|numeric',
                'heading' => 'nullable|numeric',
                'accuracy' => 'nullable|numeric',
            ]);

            if ($request->filled('dispatch_id')) {
                $location = DispatchLocation::create($validated);
                
                // Disparar evento para tiempo real de despacho
                try {
                    event(new \App\Events\LocationUpdated($location));
                } catch (\Exception $e) {}
            } else if ($user) {
                // Rastreo general de usuario (Vendedores)
                $location = \App\Models\UserLocation::create([
                    'user_id' => $user->id,
                    'lat' => $validated['lat'],
                    'lng' => $validated['lng'],
                    'speed' => $validated['speed'],
                    'heading' => $validated['heading'],
                    'accuracy' => $validated['accuracy'] ?? null,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Location recorded'
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('GPS Store Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Dispatch $dispatch)
    {
        return response()->json([
            'dispatch_id' => $dispatch->id,
            'status' => $dispatch->status,
            'locations' => $dispatch->locations()->latest()->get()
        ]);
    }
}
