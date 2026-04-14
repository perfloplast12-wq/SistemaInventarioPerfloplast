<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    public static function log(
        string $event,
        string $module,
        ?Model $model = null,
        ?array $old = null,
        ?array $new = null,
        ?string $description = null
    ): void {
        $request = app(Request::class);

        $changes = self::diff($old, $new);

        AuditLog::create([
            'user_id'        => auth()->id(),
            'event'          => $event,
            'module'         => $module,
            'auditable_type' => $model ? get_class($model) : null,
            'auditable_id'   => $model?->getKey(),
            'old_values'     => $old,
            'new_values'     => $new,
            'changes'        => $changes,
            'ip_address'     => $request?->ip(),
            'user_agent'     => substr((string) $request?->userAgent(), 0, 4000),
            'url'            => $request?->fullUrl(),
            'method'         => $request?->method(),
            'description'    => $description,
        ]);
    }

    /**
     * Devuelve SOLO lo que cambió (clave => [antes, después])
     */
    private static function diff(?array $old, ?array $new): ?array
    {
        if (!$old && !$new) return null;

        $old = $old ?? [];
        $new = $new ?? [];

        $keys = array_unique(array_merge(array_keys($old), array_keys($new)));
        $out = [];

        foreach ($keys as $key) {
            $before = $old[$key] ?? null;
            $after  = $new[$key] ?? null;

            // Si ambos son arrays, comparamos JSON (simple y robusto)
            $beforeNorm = is_array($before) ? json_encode($before) : $before;
            $afterNorm  = is_array($after)  ? json_encode($after)  : $after;

            if ($beforeNorm !== $afterNorm) {
                $out[$key] = ['before' => $before, 'after' => $after];
            }
        }

        return empty($out) ? null : $out;
    }
}
