<?php

namespace App\Models\Concerns;

use App\Services\AuditLogger;

trait Auditable
{
    /**
     * ESTA propiedad sí existe (no es columna). Evita que Eloquent la guarde en BD.
     */
    protected ?array $auditOldSnapshot = null;

    public function auditModule(): string
    {
        return property_exists($this, 'auditModule')
            ? (string) $this->auditModule
            : 'general';
    }

    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            AuditLogger::log(
                event: 'created',
                module: $model->auditModule(),
                model: $model,
                old: null,
                new: $model->auditSnapshot(),
                description: 'Registro creado'
            );
        });

        static::updating(function ($model) {
            // Guardar “antes” en propiedad NO persistente
            $model->auditOldSnapshot = $model->auditOriginalSnapshot();
        });

        static::updated(function ($model) {
            $old = $model->auditOldSnapshot ?? $model->auditOriginalSnapshot();
            $new = $model->auditSnapshot();

            AuditLogger::log(
                event: 'updated',
                module: $model->auditModule(),
                model: $model,
                old: $old,
                new: $new,
                description: 'Registro actualizado'
            );

            // limpiar
            $model->auditOldSnapshot = null;
        });

        static::deleted(function ($model) {
            AuditLogger::log(
                event: 'deleted',
                module: $model->auditModule(),
                model: $model,
                old: $model->auditSnapshot(),
                new: null,
                description: 'Registro eliminado'
            );
        });
    }

    public function auditSnapshot(): array
    {
        // Evitar guardar datos sensibles en la bitácora
        $data = $this->attributesToArray();
        unset($data['password'], $data['remember_token']);
        return $data;
    }

    public function auditOriginalSnapshot(): array
    {
        $data = $this->getOriginal();
        unset($data['password'], $data['remember_token']);
        return $data;
    }
}
