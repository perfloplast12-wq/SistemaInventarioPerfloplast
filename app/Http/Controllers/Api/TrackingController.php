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
        \Illuminate\Support\Facades\Log::info('GPS Tracking Request:', $request->all());

        $validated = $request->validate([
            'dispatch_id' => 'required|exists:dispatches,id',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'speed' => 'nullable|numeric',
            'heading' => 'nullable|numeric',
        ]);

        $dispatch = Dispatch::findOrFail($validated['dispatch_id']);

        if ($dispatch->status !== 'in_progress') {
            return response()->json(['message' => 'Dispatch not in progress'], 403);
        }

        $location = DispatchLocation::create($validated);

        // Disparar evento para tiempo real
        event(new \App\Events\LocationUpdated($location));

        return response()->json([
            'message' => 'Location recorded',
            'location' => $location
        ], 201);
    }

    public function show(Dispatch $dispatch)
    {
        $locations = $dispatch->locations()
            ->orderBy('created_at', 'asc')
            ->select(['lat', 'lng', 'created_at'])
            ->get();

        return response()->json($locations);
    }
}
