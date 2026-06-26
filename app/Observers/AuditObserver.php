<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

/**
 * Records create / update / delete on audited models to activity_logs
 * (PROJECT_DOCUMENTATION §23 — Definition of Done). Only authenticated
 * (admin) actions are logged; seeders and console runs are skipped.
 */
class AuditObserver
{
    /** Never store these in the before/after payload. */
    private const REDACT = ['password', 'remember_token', 'created_at', 'updated_at'];

    public function created(Model $model): void
    {
        $this->log($model, 'created', ['after' => $this->sanitize($model->getAttributes())]);
    }

    public function updated(Model $model): void
    {
        $changed = $this->sanitize($model->getChanges());
        if ($changed === []) {
            return; // nothing meaningful changed (e.g. only a timestamp)
        }

        $this->log($model, 'updated', [
            'before' => $this->sanitize(array_intersect_key($model->getRawOriginal(), $model->getChanges())),
            'after' => $changed,
        ]);
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'deleted', ['before' => $this->sanitize($model->getAttributes())]);
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function log(Model $model, string $event, array $properties): void
    {
        if (! auth()->check()) {
            return;
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'subject_type' => $model->getMorphClass(),
            'subject_id' => $model->getKey(),
            'description' => $this->describe($model, $event),
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 255),
        ]);
    }

    private function describe(Model $model, string $event): string
    {
        $label = str(class_basename($model))->headline()->toString();
        $name = $this->subjectName($model);

        return ucfirst($event) . ' ' . $label . ($name !== null ? " “{$name}”" : ' #' . $model->getKey());
    }

    private function subjectName(Model $model): ?string
    {
        foreach (['name', 'title', 'code', 'order_number', 'quotation_number', 'reference', 'sku', 'email'] as $attribute) {
            $value = $model->getAttribute($attribute);
            if (! empty($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function sanitize(array $attributes): array
    {
        return collect($attributes)
            ->except(self::REDACT)
            ->map(fn ($value) => is_string($value) && strlen($value) > 500 ? substr($value, 0, 500) . '…' : $value)
            ->all();
    }
}
