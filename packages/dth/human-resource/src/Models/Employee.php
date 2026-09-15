<?php

namespace Dth\HumanResource\Models;

use Dth\HumanResource\Enums\BusinessFunction;
use Dth\HumanResource\Enums\EmploymentStatus;
use Dth\HumanResource\Enums\PositionAuthority;
use Dth\HumanResource\Support\CodeGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $table = 'hr_employees';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'employment_status' => EmploymentStatus::class,
            'started_at' => 'date',
            'ended_at' => 'date',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $employee): void {
            if (blank($employee->employee_code)) {
                $employee->employee_code = app(CodeGenerator::class)->next(
                    self::class,
                    'employee_code',
                    (string) config('dth-human-resource.employee_code_prefix', 'EMP'),
                );
            }
        });

        static::saving(function (self $employee): void {
            $employee->full_name = trim((string) $employee->full_name);
            $employee->email = self::normalizeEmail($employee->email);
            $employee->phone = self::normalizePhone($employee->phone);
        });
    }

    public static function normalizeEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return $email === '' ? null : $email;
    }

    public static function normalizePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }

        $international = str_starts_with($phone, '+');
        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        return $digits === '' ? null : ($international ? '+' : '').$digits;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo((string) config('auth.providers.users.model', \App\Models\User::class), 'user_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(EmployeeAvailability::class, 'employee_id');
    }

    public function businessFunctions(): HasMany
    {
        return $this->hasMany(EmployeeBusinessFunction::class, 'employee_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('employment_status', EmploymentStatus::Active->value);
    }

    public function scopeWithBusinessFunction(Builder $query, BusinessFunction|string $function): Builder
    {
        $resolved = $function instanceof BusinessFunction
            ? $function
            : BusinessFunction::tryFrom((string) $function);

        if ($resolved === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('businessFunctions', fn (Builder $assignment): Builder => $assignment
            ->where('function_key', $resolved->value)
            ->where('is_active', true));
    }

    public function primaryBusinessFunction(): ?BusinessFunction
    {
        $this->loadMissing('businessFunctions');

        $assignment = $this->businessFunctions
            ->where('is_active', true)
            ->sortBy([
                ['is_primary', 'desc'],
                ['id', 'asc'],
            ])
            ->first();

        if ($assignment?->function_key instanceof BusinessFunction) {
            return $assignment->function_key;
        }

        if ($assignment?->function_key) {
            return BusinessFunction::tryFrom((string) $assignment->function_key);
        }

        // Department.function_key is organizational metadata only. It is used
        // as a migration-era fallback when an employee has not yet received an
        // explicit business-function assignment.
        $this->loadMissing('department');

        return $this->department?->function_key instanceof BusinessFunction
            ? $this->department->function_key
            : BusinessFunction::tryFrom((string) $this->department?->function_key);
    }

    public function hasBusinessFunction(BusinessFunction|string $function): bool
    {
        $resolved = $function instanceof BusinessFunction
            ? $function
            : BusinessFunction::tryFrom((string) $function);

        if ($resolved === null) {
            return false;
        }

        $this->loadMissing('businessFunctions');

        if ($this->businessFunctions->isNotEmpty()) {
            return $this->businessFunctions->contains(
                fn (EmployeeBusinessFunction $assignment): bool => $assignment->is_active
                    && $assignment->function_key === $resolved,
            );
        }

        return $this->primaryBusinessFunction() === $resolved;
    }

    public function businessAuthorityFor(BusinessFunction|string $function): ?PositionAuthority
    {
        $resolved = $function instanceof BusinessFunction
            ? $function
            : BusinessFunction::tryFrom((string) $function);

        if ($resolved === null) {
            return null;
        }

        $this->loadMissing('businessFunctions');
        $assignment = $this->businessFunctions->first(
            fn (EmployeeBusinessFunction $item): bool => $item->is_active
                && $item->function_key === $resolved,
        );

        if ($assignment?->authority_level instanceof PositionAuthority) {
            return $assignment->authority_level;
        }

        if ($assignment?->authority_level) {
            return PositionAuthority::tryFrom((string) $assignment->authority_level);
        }

        if ($this->businessFunctions->isNotEmpty()) {
            return null;
        }

        $this->loadMissing('position');

        return $this->position?->authority_level instanceof PositionAuthority
            ? $this->position->authority_level
            : PositionAuthority::tryFrom((string) $this->position?->authority_level);
    }

    public function hasBusinessManagerAuthority(BusinessFunction|string $function): bool
    {
        return $this->businessAuthorityFor($function)?->isManager() ?? false;
    }

    public function isAvailableForNewWork(?\DateTimeInterface $at = null): bool
    {
        if ($this->employment_status !== EmploymentStatus::Active) {
            return false;
        }

        $at ??= now();

        $availability = $this->relationLoaded('availabilities')
            ? $this->availabilities
                ->filter(fn (EmployeeAvailability $item): bool => $item->starts_at <= $at && $item->ends_at >= $at)
                ->sortByDesc('starts_at')
                ->first()
            : $this->availabilities()
                ->where('starts_at', '<=', $at)
                ->where('ends_at', '>=', $at)
                ->orderByDesc('starts_at')
                ->first();

        return $availability?->can_receive_new_work ?? true;
    }

    public function displayLabel(): string
    {
        return $this->employee_code.' · '.$this->full_name;
    }
}
