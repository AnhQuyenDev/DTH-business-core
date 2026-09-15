<?php

namespace Dth\HumanResource\Models;

use Dth\HumanResource\Enums\BusinessFunction;
use Dth\HumanResource\Enums\PositionAuthority;
use Dth\HumanResource\Enums\PositionGroup;
use Dth\HumanResource\Support\CodeGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    protected $table = 'hr_positions';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'group_key' => PositionGroup::class,
            'authority_level' => PositionAuthority::class,
            'function_key' => BusinessFunction::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $position): void {
            if (blank($position->code)) {
                $position->code = app(CodeGenerator::class)->next(
                    self::class,
                    'code',
                    (string) config('dth-human-resource.position_code_prefix', 'JOB'),
                );
            }
        });

        static::saving(function (self $position): void {
            $position->title = trim((string) $position->title);
        });
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'position_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function groupedOptions(): array
    {
        $groups = [];
        foreach (static::query()->active()->orderBy('sort_order')->orderBy('title')->get() as $position) {
            $function = $position->function_key instanceof BusinessFunction ? $position->function_key : null;
            $groupLabel = $function?->label() ?? \Dth\HumanResource\Support\UiText::get('positions.all_functions', 'All business functions');
            $groups[$groupLabel][$position->id] = $position->title;
        }

        return $groups;
    }
}
