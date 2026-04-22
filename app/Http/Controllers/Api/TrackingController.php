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
            \Illuminate\Support\Facades\Log::info('GPS Tracking Request:', $request->all());

            $validated = $request->validate([
                'dispatch_id' => 'required|exists:dispatches,id',
                'lat' => 'required|numeric',
                'lng' => 'required|numeric',
                'speed' => 'nullable|numeric',
                'heading' => 'nullable|numeric',
            ]);

            $location = DispatchLocation::create($validated);

            // Disparar evento para tiempo real
            try {
                event(new \App\Events\LocationUpdated($location));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Real-time broadcast failed: ' . $e->getMessage());
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Location recorded',
                'location' => $location
            ], 201);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('GPS Store Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'error' => 'Internal Server Error',
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
