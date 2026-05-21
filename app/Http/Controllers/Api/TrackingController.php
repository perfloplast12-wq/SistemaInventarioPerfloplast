<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dispatch;
use App\Models\DispatchLocation;
use App\Services\DispatchMapDataService;
use App\Services\GpsTrackingThrottle;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function __construct(
        protected GpsTrackingThrottle $gpsThrottle,
        protected DispatchMapDataService $dispatchMapData,
    ) {}

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

            $isOffline = $request->input('status') === 'offline';
            $lat = (float) $validated['lat'];
            $lng = (float) $validated['lng'];

            if ($request->filled('dispatch_id')) {
                if ($isOffline) {
                    // Señal de desconexión: guardar en DB usando la última posición pero con speed = -1
                    $lastLoc = DispatchLocation::where('dispatch_id', $validated['dispatch_id'])
                        ->latest('id')
                        ->first();
                    
                    if ($lastLoc) {
                        $location = DispatchLocation::create([
                            'dispatch_id' => $validated['dispatch_id'],
                            'lat' => $lastLoc->lat,
                            'lng' => $lastLoc->lng,
                            'speed' => -1, // Marca especial de OFFLINE
                            'heading' => 0,
                            'created_at' => now(),
                        ]);
                        event(new \App\Events\LocationUpdated($location, true));
                        $this->dispatchMapData->forgetForUser($user);
                    }
                } else {
                    $throttleKey = $this->gpsThrottle->cacheKey('dispatch', (int) $validated['dispatch_id']);

                    if (! $this->gpsThrottle->shouldPersist($throttleKey, $lat, $lng)) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Location throttled',
                        ]);
                    }

                    $location = DispatchLocation::create([
                        'dispatch_id' => $validated['dispatch_id'],
                        'lat' => $lat,
                        'lng' => $lng,
                        'speed' => $validated['speed'] ?? null,
                        'heading' => $validated['heading'] ?? null,
                        'created_at' => now(),
                    ]);
                    $this->gpsThrottle->remember($throttleKey, $lat, $lng);
                    $this->dispatchMapData->forgetForUser($user);

                    try {
                        event(new \App\Events\LocationUpdated($location));
                    } catch (\Exception $e) {}
                }
            } elseif ($user) {
                // Rastreo general de usuario (Vendedores)
                if ($isOffline) {
                    // Señal de desconexión: marcar última ubicación con accuracy = -1
                    $lastLoc = \App\Models\UserLocation::where('user_id', $user->id)
                        ->latest('id')
                        ->first();
                    if ($lastLoc) {
                        // Guardar señal de desconexión con las mismas coordenadas pero accuracy -1
                        \App\Models\UserLocation::create([
                            'user_id' => $user->id,
                            'lat' => $lastLoc->lat,
                            'lng' => $lastLoc->lng,
                            'speed' => 0,
                            'heading' => 0,
                            'accuracy' => -1, // Marcador especial: usuario desconectado
                            'created_at' => now(),
                        ]);
                        $this->dispatchMapData->forgetForUser($user);
                    }
                } else {
                    $throttleKey = $this->gpsThrottle->cacheKey('user', $user->id);

                    if (! $this->gpsThrottle->shouldPersist($throttleKey, $lat, $lng)) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Location throttled',
                        ]);
                    }

                    \App\Models\UserLocation::create([
                        'user_id' => $user->id,
                        'lat' => $lat,
                        'lng' => $lng,
                        'speed' => $validated['speed'],
                        'heading' => $validated['heading'],
                        'accuracy' => $validated['accuracy'] ?? null,
                        'created_at' => now(),
                    ]);
                    $this->gpsThrottle->remember($throttleKey, $lat, $lng);
                    $this->dispatchMapData->forgetForUser($user);
                }
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
