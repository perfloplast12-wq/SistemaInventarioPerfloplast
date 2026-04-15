<?php

namespace App\Filament\Concerns;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\UniqueConstraintViolationException;

/**
 * Trait to handle soft-deleted records when creating new records.
 * 
 * When a model uses SoftDeletes and has unique constraints,
 * soft-deleted records can block the creation of new records
 * with the same unique field values. This trait:
 * 
 * 1. Checks for soft-deleted records with matching unique fields
 * 2. Restores them if found (instead of creating duplicates)
 * 3. Catches UniqueConstraintViolationException as a safety net
 */
trait HandlesSoftDeletedDuplicates
{
    /**
     * Override to specify which fields should be checked for soft-deleted duplicates.
     * Return an array of field names that have unique constraints.
     * Example: return ['name'] or ['code', 'sku']
     */
    protected function getUniqueFieldsForRestore(): array
    {
        return [];
    }

    protected function handleRecordCreation(array $data): Model
    {
        $modelClass = static::getModel();

        // Check if model uses SoftDeletes and we have fields to check
        $uniqueFields = $this->getUniqueFieldsForRestore();
        
        if (!empty($uniqueFields) && in_array(SoftDeletes::class, class_uses_recursive($modelClass))) {
            foreach ($uniqueFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    continue;
                }

                $trashed = $modelClass::onlyTrashed()
                    ->where($field, $data[$field])
                    ->first();

                if ($trashed) {
                    $trashed->restore();
                    $trashed->update($data);

                    Notification::make()
                        ->title('Registro restaurado')
                        ->body("Se encontró un registro eliminado con el mismo valor y fue restaurado.")
                        ->info()
                        ->send();

                    return $trashed;
                }
            }
        }

        // Normal create with safety catch
        try {
            return $modelClass::create($data);
        } catch (UniqueConstraintViolationException $e) {
            Notification::make()
                ->title('Error')
                ->body('Ya existe un registro con estos datos. Intenta con valores diferentes.')
                ->danger()
                ->send();

            $this->halt();
            return new $modelClass;
        }
    }
}
