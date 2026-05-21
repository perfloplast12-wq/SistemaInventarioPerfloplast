<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class GpsTrackingThrottle
{
    private const MIN_SECONDS = 25;

    private const MIN_METERS = 40;

    public function shouldPersist(string $key, float $lat, float $lng, bool $force = false): bool
    {
        if ($force) {
            return true;
        }

        $last = Cache::get($key);

        if (! is_array($last)) {
            return true;
        }

        $elapsed = time() - (int) ($last['at'] ?? 0);

        if ($elapsed >= self::MIN_SECONDS) {
            return true;
        }

        return $this->distanceMeters(
            (float) ($last['lat'] ?? 0),
            (float) ($last['lng'] ?? 0),
            $lat,
            $lng,
        ) >= self::MIN_METERS;
    }

    public function remember(string $key, float $lat, float $lng): void
    {
        Cache::put($key, [
            'lat' => $lat,
            'lng' => $lng,
            'at' => time(),
        ], now()->addMinutes(3));
    }

    public function cacheKey(string $type, int $id): string
    {
        return "gps_throttle:{$type}:{$id}";
    }

    private function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
