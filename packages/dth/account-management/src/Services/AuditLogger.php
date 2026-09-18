<?php
namespace Dth\AccountManagement\Services;

use Dth\AccountManagement\Models\AccountAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class AuditLogger
{
    private array $sensitive = ['password','remember_token','token','token_hash','payload','secret','api_key'];

    public function modelEvent(string $event, Model $model): void
    {
        if ($model instanceof AccountAuditLog || ! Schema::hasTable('account_audit_logs')) return;
        try {
            $new = $event === 'deleted' ? [] : $this->sanitize($event === 'updated' ? $model->getChanges() : $model->getAttributes());
            $old = $event === 'created' ? [] : $this->sanitize($event === 'updated' ? array_intersect_key($model->getOriginal(), $model->getChanges()) : $model->getOriginal());
            $this->write(
                module: $this->moduleFromModel($model),
                event: $event,
                subjectType: $model::class,
                subjectId: (string) $model->getKey(),
                description: class_basename($model).' '.$event,
                oldValues: $old,
                newValues: $new,
            );
        } catch (Throwable) {
            // Audit logging must never break the business transaction.
        }
    }

    public function write(string $module, string $event, ?string $subjectType = null, ?string $subjectId = null, ?string $description = null, array $oldValues = [], array $newValues = [], ?int $userId = null): void
    {
        if (! Schema::hasTable('account_audit_logs')) return;
        AccountAuditLog::query()->create([
            'user_id' => $userId ?? auth()->id(), 'module' => $module, 'event' => $event,
            'subject_type' => $subjectType, 'subject_id' => $subjectId, 'description' => $description,
            'old_values' => $oldValues ?: null, 'new_values' => $newValues ?: null,
            'ip_address' => request()?->ip(), 'user_agent' => substr((string) request()?->userAgent(), 0, 1000), 'created_at' => now(),
        ]);
    }

    private function sanitize(array $values): array
    {
        foreach ($this->sensitive as $key) unset($values[$key]);
        return $values;
    }

    private function moduleFromModel(Model $model): string
    {
        $class = $model::class;
        return match (true) {
            str_contains($class, '\\AccountManagement\\') => 'accounts',
            str_contains($class, '\\Commercial\\') => 'commercial',
            str_contains($class, '\\Marketing\\') => 'marketing',
            str_contains($class, '\\Crm\\') => 'crm',
            str_contains($class, '\\HumanResource\\') => 'human-resource',
            str_contains($class, '\\Email\\') => 'email',
            default => 'core',
        };
    }
}
