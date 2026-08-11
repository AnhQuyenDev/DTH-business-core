<?php

namespace App\Models\Crm;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\PositionAuthority;
use App\Enums\Crm\PositionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Position extends Model
{
    use HasFactory;

    protected $table = 'positions';

    protected $fillable = [
        'code',
        'title',
        'group_key',
        'authority_level',
        'function_key',
        // Kept during the V2 transition for compatibility with historical data.
        // New UI never assigns a Job Title to a Department.
        'department_id',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'group_key' => PositionGroup::class,
            'authority_level' => PositionAuthority::class,
            'function_key' => DepartmentFunction::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Legacy-only relation retained so historical imports / old records do not
     * break during the transition. Job Titles are global master data in V2.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function grantsDepartmentManagerAuthority(): bool
    {
        return $this->authority_level?->isDepartmentManager() ?? false;
    }

    public function supportsBusinessFunction(DepartmentFunction|string|null $function): bool
    {
        if ($this->function_key === null) {
            return true;
        }

        $resolved = $function instanceof DepartmentFunction
            ? $function
            : DepartmentFunction::tryFrom((string) $function);

        return $resolved !== null && $this->function_key === $resolved;
    }

    /** @return array<string, array<int|string, string>> */
    public static function groupedOptions(): array
    {
        $groups = [];

        foreach (static::query()->active()->orderBy('sort_order')->orderBy('title')->get() as $position) {
            $function = $position->function_key instanceof DepartmentFunction
                ? $position->function_key
                : DepartmentFunction::tryFrom((string) $position->function_key);

            $groupLabel = $function?->label() ?? __('configuration.position.function_all');
            $group = $position->group_key instanceof PositionGroup
                ? $position->group_key
                : PositionGroup::tryFrom((string) $position->group_key);

            $label = $position->title;
            if ($group) {
                $label .= ' · '.$group->label();
            }

            $groups[$groupLabel][$position->id] = $label;
        }

        return $groups;
    }

    /**
     * Suggestions are intentionally generic. Department names never belong in
     * the title because the same Job Title can be reused across the company.
     *
     * @return array<int, string>
     */
    public static function titleSuggestions(): array
    {
        return [
            __('configuration.position.suggestions.director'),
            __('configuration.position.suggestions.deputy_director'),
            __('configuration.position.suggestions.department_head'),
            __('configuration.position.suggestions.deputy_head'),
            __('configuration.position.suggestions.team_lead'),
            __('configuration.position.suggestions.senior_specialist'),
            __('configuration.position.suggestions.specialist'),
            __('configuration.position.suggestions.staff'),
            __('configuration.position.suggestions.intern'),
            __('configuration.position.suggestions.collaborator'),
        ];
    }

    public static function suggestedCode(?string $title): ?string
    {
        $value = Str::of((string) $title)->ascii()->slug('_')->lower()->toString();

        return filled($value) ? $value : null;
    }
}
