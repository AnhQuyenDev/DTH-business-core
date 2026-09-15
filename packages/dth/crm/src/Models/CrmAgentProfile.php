<?php

namespace Dth\Crm\Models;

use Dth\HumanResource\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmAgentProfile extends Model
{
    use SoftDeletes;

    /**
     * CRM owns only assignment settings here. Employee identity remains owned by Human Resource.
     */
    protected $table = 'crm_agent_profiles';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'assignment_enabled' => 'boolean',
            'lead_capacity' => 'integer',
            'customer_capacity' => 'integer',
            'distribution_weight' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'assigned_agent_profile_id');
    }

    public function customerAssignments(): HasMany
    {
        return $this->hasMany(CustomerAssignment::class, 'agent_profile_id');
    }

    public function companyAssignments(): HasMany
    {
        return $this->hasMany(CompanyAssignment::class, 'agent_profile_id');
    }

    public function scopeAssignmentEnabled(Builder $query): Builder
    {
        return $query
            ->where('assignment_enabled', true)
            ->whereHas('employee', fn (Builder $employee): Builder => $employee->active());
    }

    public function scopeForUser(Builder $query, ?int $userId): Builder
    {
        if ($userId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'employee',
            fn (Builder $employee): Builder => $employee->where('user_id', $userId),
        );
    }

    public static function currentForUser(?int $userId): ?self
    {
        return static::query()->forUser($userId)->first();
    }

    /** @return array<int|string, string> */
    public static function options(bool $assignmentEnabledOnly = false): array
    {
        $query = static::query()->with('employee:id,employee_code,full_name,employment_status');

        if ($assignmentEnabledOnly) {
            $query->assignmentEnabled();
        }

        return $query
            ->get()
            ->filter(fn (self $profile): bool => $profile->employee !== null)
            ->sortBy(fn (self $profile): string => mb_strtolower((string) $profile->employee?->full_name))
            ->mapWithKeys(fn (self $profile): array => [$profile->id => $profile->displayLabel()])
            ->all();
    }

    public function displayLabel(): string
    {
        $this->loadMissing('employee');

        return $this->employee?->displayLabel() ?? 'CRM #'.$this->getKey();
    }

    public function userId(): ?int
    {
        $this->loadMissing('employee');

        return $this->employee?->user_id;
    }

    public function isAvailableForNewWork(): bool
    {
        if (! $this->assignment_enabled) {
            return false;
        }

        $this->loadMissing('employee');

        return $this->employee?->isAvailableForNewWork() ?? false;
    }
}
