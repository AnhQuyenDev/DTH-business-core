<?php

namespace Dth\HumanResource\Models;

use Dth\HumanResource\Enums\BusinessFunction;
use Dth\HumanResource\Enums\PositionAuthority;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeBusinessFunction extends Model
{
    protected $table = 'hr_employee_business_functions';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'function_key' => BusinessFunction::class,
            'authority_level' => PositionAuthority::class,
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (self $assignment): void {
            if ($assignment->is_primary && $assignment->is_active) {
                static::query()
                    ->where('employee_id', $assignment->employee_id)
                    ->whereKeyNot($assignment->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
