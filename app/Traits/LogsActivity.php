<?php

namespace App\Traits;

use App\Models\UserActivity;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function ($model) {
            $model->saveActivityLog('created', "Created " . class_basename($model) . " with ID " . $model->id, $model->getAttributes());
        });

        static::updated(function ($model) {
            $changes = [
                'before' => array_intersect_key($model->getOriginal(), $model->getChanges()),
                'after' => $model->getChanges(),
            ];
            $model->saveActivityLog('updated', "Updated " . class_basename($model) . " with ID " . $model->id, $changes);
        });

        static::deleted(function ($model) {
            $model->saveActivityLog('deleted', "Deleted " . class_basename($model) . " with ID " . $model->id, $model->getAttributes());
        });
    }

    protected function saveActivityLog(string $type, string $description, array $properties = [])
    {
        if (Auth::check()) {
            Auth::user()->logActivity($type, $description, array_merge([
                'model' => class_basename($this),
                'id' => $this->id,
            ], $properties));
        }
    }
}
