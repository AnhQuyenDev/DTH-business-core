<?php

namespace App\Support\Ui\Labels;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\PositionAuthority;
use App\Enums\Crm\PositionGroup;
use App\Enums\UserRole;
use App\Models\Crm\Department;
use App\Models\Crm\Position;
use App\Models\Crm\StaffBusinessFunction;
use App\Models\Marketing\FormTemplate;
use App\Models\Marketing\LandingPageSubmission;
use App\Models\User;
use App\Support\Ui\BadgePalette;
use App\Support\Ui\SystemColorPalette;
use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

final class SystemLabelRegistry
{
    private const CACHE_PREFIX = 'ui.system-label-registry.v3.';

    private const CACHE_TTL_SECONDS = 45;

    /** @var array<string, array<string, int>> */
    private array $groupedCounts = [];

    /** @var array<string, int>|null */
    private ?array $landingPageDistributionCounts = null;

    /** @return Collection<int, SystemLabelDefinition> */
    public function all(): Collection
    {
        try {
            return Cache::remember(
                self::cacheKey(),
                now()->addSeconds(self::CACHE_TTL_SECONDS),
                fn (): Collection => $this->build(),
            );
        } catch (\Throwable) {
            return $this->build();
        }
    }

    public static function flushCache(): void
    {
        foreach (array_unique([app()->getLocale(), 'vi', 'en']) as $locale) {
            try {
                Cache::forget(self::cacheKey($locale));
            } catch (\Throwable) {
                // The label directory must remain usable when cache is offline.
            }
        }
    }

    /** @return Collection<int, SystemLabelDefinition> */
    private function build(): Collection
    {
        $this->groupedCounts = [];
        $this->landingPageDistributionCounts = null;

        return collect()
            ->concat($this->departments())
            ->concat($this->positionGroups())
            ->concat($this->positionAuthorities())
            ->concat($this->accessRoles())
            ->concat($this->businessFunctions())
            ->concat($this->audiences())
            ->concat($this->enumDefinitions())
            ->concat($this->valueDefinitions())
            ->sortBy(fn (SystemLabelDefinition $item): string => implode('|', [
                $item->moduleLabel,
                $item->groupLabel,
                $item->label,
            ]))
            ->values();
    }

    private static function cacheKey(?string $locale = null): string
    {
        return self::CACHE_PREFIX.($locale ?? app()->getLocale());
    }

    public function find(string $identifier): ?SystemLabelDefinition
    {
        return $this->all()->first(
            fn (SystemLabelDefinition $definition): bool => $definition->identifier === $identifier,
        );
    }

    /** @return Collection<int, SystemLabelDefinition> */
    private function departments(): Collection
    {
        if (! $this->hasTable('departments')) {
            return collect();
        }

        return Department::query()
            ->withCount(['staff', 'sendingAccounts'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(fn (Department $department): SystemLabelDefinition => new SystemLabelDefinition(
                identifier: 'organization.department.'.$department->getKey(),
                module: 'organization',
                moduleLabel: $this->moduleLabel('organization'),
                group: 'organization.department',
                groupLabel: $this->groupLabel('department'),
                key: (string) $department->getKey(),
                label: $department->name,
                type: 'identity',
                impact: 'business',
                defaultColor: $department->resolvedDefaultColor(),
                currentColor: SystemColorPalette::normalize($department->color),
                usageLocations: $this->usageLabels(['department_list', 'staff', 'accounts']),
                usageCount: 1 + (int) $department->staff_count + (int) $department->sending_accounts_count,
                storage: 'department',
                sourceId: $department->getKey(),
            ));
    }

    /** @return Collection<int, SystemLabelDefinition> */
    private function positionGroups(): Collection
    {
        $usageCounts = $this->groupedSourceCounts([[Position::class, 'group_key']]);

        return collect(PositionGroup::cases())
            ->map(fn (PositionGroup $group): SystemLabelDefinition => new SystemLabelDefinition(
                identifier: 'organization.position_group.'.$group->value,
                module: 'organization',
                moduleLabel: $this->moduleLabel('organization'),
                group: 'organization.position_group',
                groupLabel: $this->groupLabel('position_group'),
                key: $group->value,
                label: $group->label(),
                type: 'identity',
                impact: 'business',
                defaultColor: $group->defaultColor(),
                currentColor: $group->color(),
                usageLocations: $this->usageLabels(['position_list']),
                usageCount: $usageCounts[$group->value] ?? 0,
                styleCategory: 'organization.position_group',
            ));
    }

    /** @return Collection<int, SystemLabelDefinition> */
    private function positionAuthorities(): Collection
    {
        $usageCounts = $this->groupedSourceCounts([
            [Position::class, 'authority_level'],
            [StaffBusinessFunction::class, 'authority_level'],
        ]);

        return collect(PositionAuthority::cases())
            ->map(fn (PositionAuthority $authority): SystemLabelDefinition => new SystemLabelDefinition(
                identifier: 'organization.position_authority.'.$authority->value,
                module: 'organization',
                moduleLabel: $this->moduleLabel('organization'),
                group: 'organization.position_authority',
                groupLabel: $this->groupLabel('position_authority'),
                key: $authority->value,
                label: $authority->label(),
                type: 'identity',
                impact: 'business',
                defaultColor: $authority->defaultColor(),
                currentColor: $authority->color(),
                usageLocations: $this->usageLabels(['position_list', 'staff_functions']),
                usageCount: $usageCounts[$authority->value] ?? 0,
                styleCategory: 'organization.position_authority',
            ));
    }

    /** @return Collection<int, SystemLabelDefinition> */
    private function accessRoles(): Collection
    {
        $usageCounts = $this->groupedSourceCounts([[User::class, 'role']]);

        return collect(UserRole::assignableCases())
            ->map(fn (UserRole $role): SystemLabelDefinition => new SystemLabelDefinition(
                identifier: 'system.access_role.'.$role->value,
                module: 'system',
                moduleLabel: $this->moduleLabel('system'),
                group: 'system.access_role',
                groupLabel: $this->groupLabel('access_role'),
                key: $role->value,
                label: $role->label(),
                type: 'identity',
                impact: 'critical',
                defaultColor: $role->defaultColor(),
                currentColor: $role->color(),
                usageLocations: $this->usageLabels(['user_accounts', 'role_matrix']),
                usageCount: $usageCounts[$role->value] ?? 0,
                styleCategory: 'role',
            ));
    }

    /** @return Collection<int, SystemLabelDefinition> */
    private function businessFunctions(): Collection
    {
        $usageCounts = $this->groupedSourceCounts([
            [StaffBusinessFunction::class, 'function_key'],
            [Position::class, 'function_key'],
        ]);

        return collect(DepartmentFunction::cases())
            ->map(fn (DepartmentFunction $function): SystemLabelDefinition => new SystemLabelDefinition(
                identifier: 'staff.business_function.'.$function->value,
                module: 'organization',
                moduleLabel: $this->moduleLabel('organization'),
                group: 'staff.business_function',
                groupLabel: $this->groupLabel('business_function'),
                key: $function->value,
                label: $function->label(),
                type: 'identity',
                impact: 'business',
                defaultColor: $function->defaultColor(),
                currentColor: $function->color(),
                usageLocations: $this->usageLabels(['staff', 'staff_functions', 'work_distribution']),
                usageCount: $usageCounts[$function->value] ?? 0,
                styleCategory: 'department_function',
            ));
    }

    /** @return Collection<int, SystemLabelDefinition> */
    private function audiences(): Collection
    {
        $items = [
            'personal' => ['label' => __('uiux.audience.personal'), 'default' => 'info'],
            'business' => ['label' => __('uiux.audience.business'), 'default' => 'warning'],
            'generic' => ['label' => __('uiux.audience.generic'), 'default' => 'gray'],
        ];
        $directCounts = $this->groupedSourceCounts([
            [FormTemplate::class, 'audience_type'],
            [LandingPageSubmission::class, 'submission_type'],
            [\App\Models\Crm\Customer::class, 'customer_type'],
        ]);
        $salesCounts = $this->groupedSourceCounts([
            [\App\Models\Sales\ServicePackage::class, 'audience_type'],
            [\App\Models\Sales\PriceBook::class, 'audience_type'],
        ]);

        return collect($items)
            ->map(fn (array $item, string $key): SystemLabelDefinition => new SystemLabelDefinition(
                identifier: 'system.audience.'.$key,
                module: 'system',
                moduleLabel: $this->moduleLabel('system'),
                group: 'system.audience',
                groupLabel: $this->groupLabel('audience'),
                key: $key,
                label: $item['label'],
                type: 'identity',
                impact: 'business',
                defaultColor: $item['default'],
                currentColor: BadgePalette::audience($key, $item['default']),
                usageLocations: $this->usageLabels(['form_templates', 'landing_page_submissions', 'customers']),
                usageCount: ($directCounts[$key] ?? 0)
                    + ($salesCounts[$key === 'generic' ? 'both' : $key] ?? 0),
                styleCategory: 'audience',
            ))
            ->values();
    }

    /** @return Collection<int, SystemLabelDefinition> */
    private function enumDefinitions(): Collection
    {
        return collect(SystemLabelCatalog::enumGroups())
            ->flatMap(function (array $metadata, string $enumClass): Collection {
                if (! enum_exists($enumClass) || ! method_exists($enumClass, 'cases')) {
                    return collect();
                }

                $usageCounts = $this->groupedSourceCounts($metadata['count_sources'] ?? []);

                return collect($enumClass::cases())
                    ->map(function (BackedEnum $case) use ($metadata, $usageCounts): SystemLabelDefinition {
                        $defaultColor = BadgePalette::withoutOverrides(
                            fn (): string => method_exists($case, 'color') ? $case->color() : 'gray',
                        );
                        $currentColor = method_exists($case, 'color') ? $case->color() : $defaultColor;
                        $groupKey = (string) $metadata['group_key'];
                        $category = (string) $metadata['category'];

                        return new SystemLabelDefinition(
                            identifier: $category.'.'.$case->value,
                            module: (string) $metadata['module'],
                            moduleLabel: $this->moduleLabel((string) $metadata['module']),
                            group: $category,
                            groupLabel: $this->groupLabel($groupKey),
                            key: (string) $case->value,
                            label: method_exists($case, 'label') ? $case->label() : BadgePalette::statusLabel((string) $case->value),
                            type: (string) $metadata['type'],
                            impact: (string) $metadata['impact'],
                            defaultColor: SystemColorPalette::normalize($defaultColor),
                            currentColor: SystemColorPalette::normalize($currentColor, $defaultColor),
                            usageLocations: $this->usageLabels($metadata['usages'] ?? []),
                            usageCount: $usageCounts[(string) $case->value] ?? 0,
                            styleCategory: $category,
                        );
                    });
            })
            ->values();
    }

    /** @return Collection<int, SystemLabelDefinition> */
    private function valueDefinitions(): Collection
    {
        return collect(SystemLabelCatalog::valueGroups())
            ->flatMap(function (array $metadata, string $category): Collection {
                $usageCounts = $category === 'marketing.landing_page_distribution_state'
                    ? $this->landingPageDistributionCounts()
                    : $this->groupedSourceCounts($metadata['count_sources'] ?? []);

                return collect($metadata['values'] ?? [])
                    ->map(function (array $value, string $key) use ($metadata, $category, $usageCounts): SystemLabelDefinition {
                        $labelKey = (string) ($value['label_key'] ?? '');
                        $label = $labelKey !== '' ? __($labelKey) : $key;

                        if ($label === $labelKey) {
                            $label = BadgePalette::statusLabel($key);
                        }

                        $defaultColor = SystemColorPalette::normalize((string) ($value['color'] ?? 'gray'));

                        return new SystemLabelDefinition(
                            identifier: $category.'.'.$key,
                            module: (string) $metadata['module'],
                            moduleLabel: $this->moduleLabel((string) $metadata['module']),
                            group: $category,
                            groupLabel: $this->groupLabel((string) $metadata['group_key']),
                            key: $key,
                            label: $label,
                            type: (string) $metadata['type'],
                            impact: (string) $metadata['impact'],
                            defaultColor: $defaultColor,
                            currentColor: BadgePalette::managed($category, $key, $defaultColor),
                            usageLocations: $this->usageLabels($metadata['usages'] ?? []),
                            usageCount: $usageCounts[$key] ?? 0,
                            styleCategory: $category,
                        );
                    });
            })
            ->values();
    }

    /** @return array<string, int> */
    private function landingPageDistributionCounts(): array
    {
        if ($this->landingPageDistributionCounts !== null) {
            return $this->landingPageDistributionCounts;
        }

        try {
            if (! $this->hasTable((new LandingPageSubmission)->getTable())) {
                return $this->landingPageDistributionCounts = [];
            }

            $total = LandingPageSubmission::query()->count();
            $assigned = LandingPageSubmission::query()
                ->whereHas(
                    'lead.qualification',
                    fn ($qualification) => $qualification->whereNotNull('assigned_staff_id'),
                )
                ->count();

            return $this->landingPageDistributionCounts = [
                'assigned' => $assigned,
                'unassigned' => max(0, $total - $assigned),
            ];
        } catch (\Throwable) {
            return $this->landingPageDistributionCounts = [];
        }
    }

    /**
     * Executes one GROUP BY query per model/column and reuses the resulting map
     * for every value in that label group. This removes the previous N+1 count.
     *
     * @param  array<int, array{0: class-string<Model>, 1: string}>  $sources
     * @return array<string, int>
     */
    private function groupedSourceCounts(array $sources): array
    {
        $totals = [];

        foreach ($sources as [$modelClass, $column]) {
            foreach ($this->groupedCount($modelClass, $column) as $value => $count) {
                $totals[$value] = ($totals[$value] ?? 0) + $count;
            }
        }

        return $totals;
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @return array<string, int>
     */
    private function groupedCount(string $modelClass, string $column): array
    {
        $cacheKey = $modelClass.'|'.$column;

        if (array_key_exists($cacheKey, $this->groupedCounts)) {
            return $this->groupedCounts[$cacheKey];
        }

        try {
            $model = new $modelClass;

            if (! $this->hasTable($model->getTable())) {
                return $this->groupedCounts[$cacheKey] = [];
            }

            $counts = $modelClass::query()
                ->select($column)
                ->selectRaw('COUNT(*) as aggregate')
                ->whereNotNull($column)
                ->groupBy($column)
                ->pluck('aggregate', $column)
                ->mapWithKeys(fn ($count, $value): array => [(string) $value => (int) $count])
                ->all();

            return $this->groupedCounts[$cacheKey] = $counts;
        } catch (\Throwable) {
            return $this->groupedCounts[$cacheKey] = [];
        }
    }

    /** @param array<int, string> $keys
     *  @return array<int, string>
     */
    private function usageLabels(array $keys): array
    {
        return collect($keys)
            ->map(fn (string $key): string => __('configuration.system_labels.usages.'.$key))
            ->values()
            ->all();
    }

    private function moduleLabel(string $key): string
    {
        return __('configuration.system_labels.modules.'.$key);
    }

    private function groupLabel(string $key): string
    {
        return __('configuration.system_labels.groups.'.$key);
    }

    private function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
