<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;

/** Catat created/updated/deleted ke activity_logs dengan nilai sebelum/sesudah (PRD 8.13). */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn ($model) => ActivityLog::record('created', $model, ['after' => $model->loggableAttributes($model->getAttributes())]));

        static::updated(function ($model) {
            $after = $model->loggableAttributes($model->getChanges());
            if ($after === []) {
                return;
            }
            $before = array_intersect_key($model->getOriginal(), $after);
            $action = array_key_exists('status', $after) ? 'status_changed' : 'updated';
            ActivityLog::record($action, $model, ['before' => $before, 'after' => $after]);
        });

        static::deleted(fn ($model) => ActivityLog::record('deleted', $model, ['before' => $model->loggableAttributes($model->getOriginal())]));
    }

    protected function loggableAttributes(array $attributes): array
    {
        // $logExcept di model: kolom rahasia yang tidak boleh masuk log (mis. password)
        return array_diff_key($attributes, array_flip(['created_at', 'updated_at', 'deleted_at', ...($this->logExcept ?? [])]));
    }
}
