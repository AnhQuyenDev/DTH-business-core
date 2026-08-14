<?php

namespace App\Filament\Pages;

use App\Services\System\SystemLabelColorService;
use App\Support\Ui\Labels\SystemLabelDefinition;
use App\Support\Ui\Labels\SystemLabelRegistry;
use App\Support\Ui\SystemColorPalette;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SystemLabelManagementPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?int $navigationSort = 41;

    protected static ?string $slug = 'configuration/system-labels';

    protected static string $view = 'filament.pages.system-label-management-page';

    public string $search = '';

    public string $moduleFilter = '';

    public string $groupFilter = '';

    /** Hex without the leading #. */
    public string $colorFilter = '';

    public ?string $editingIdentifier = null;

    public string $editingMode = 'change';

    public ?string $selectedColor = null;

    public bool $acknowledged = false;

    public ?string $changeReason = null;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('configuration.system_labels.navigation');
    }

    public function getTitle(): string
    {
        return __('configuration.system_labels.title');
    }

    public function getSubheading(): ?string
    {
        return __('configuration.system_labels.subheading');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('system.manage-company-settings') ?? false;
    }

    public function selectColorFilter(string $hex): void
    {
        $hex = Str::lower(ltrim($hex, '#'));
        $this->colorFilter = $this->colorFilter === $hex ? '' : $hex;
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->moduleFilter = '';
        $this->groupFilter = '';
        $this->colorFilter = '';
    }

    public function openColorEditor(string $identifier, string $mode = 'change'): void
    {
        $this->authorizeColorManagement();
        $definition = app(SystemLabelRegistry::class)->find($identifier);

        if (! $definition) {
            Notification::make()
                ->danger()
                ->title(__('configuration.system_labels.notifications.not_found'))
                ->send();

            return;
        }

        $this->resetValidation();
        $this->editingIdentifier = $identifier;
        $this->editingMode = $mode === 'reset' ? 'reset' : 'change';
        $this->selectedColor = $this->editingMode === 'reset'
            ? $definition->defaultColor
            : $definition->currentColor;
        $this->acknowledged = false;
        $this->changeReason = null;

        $this->dispatch('open-modal', id: 'system-label-color-editor');
    }

    public function selectEditingColor(string $color): void
    {
        if (SystemColorPalette::isSupported($color)) {
            $this->selectedColor = $color;
        }
    }

    public function saveLabelColor(SystemLabelColorService $service): void
    {
        $this->authorizeColorManagement();
        $definition = $this->editingDefinition();

        if (! $definition) {
            Notification::make()
                ->danger()
                ->title(__('configuration.system_labels.notifications.not_found'))
                ->send();

            return;
        }

        $validated = Validator::make(
            [
                'selectedColor' => $this->selectedColor,
                'acknowledged' => $this->acknowledged,
                'changeReason' => $this->changeReason,
            ],
            [
                'selectedColor' => ['required', Rule::in(SystemColorPalette::keys())],
                'acknowledged' => $definition->isBusinessSensitive() ? ['accepted'] : ['nullable'],
                'changeReason' => $definition->requiresReason()
                    ? ['required', 'string', 'max:500']
                    : ['nullable', 'string', 'max:500'],
            ],
            [],
            [
                'selectedColor' => __('configuration.system_labels.current_color'),
                'acknowledged' => __('configuration.system_labels.acknowledgement'),
                'changeReason' => __('configuration.system_labels.change_reason'),
            ],
        )->validate();

        $changed = $this->editingMode === 'reset'
            ? $service->reset(
                $definition,
                (bool) $validated['acknowledged'],
                $validated['changeReason'] ?? null,
            )
            : $service->change(
                $definition,
                (string) $validated['selectedColor'],
                (bool) $validated['acknowledged'],
                $validated['changeReason'] ?? null,
            );

        Notification::make()
            ->color($changed ? 'success' : 'gray')
            ->icon($changed ? 'heroicon-o-check-circle' : 'heroicon-o-information-circle')
            ->title($changed
                ? __('configuration.system_labels.notifications.saved')
                : __('configuration.system_labels.notifications.no_change'))
            ->send();

        $this->dispatch('close-modal', id: 'system-label-color-editor');
        $this->editingIdentifier = null;
        $this->selectedColor = null;
        $this->acknowledged = false;
        $this->changeReason = null;
    }

    protected function getViewData(): array
    {
        $allLabels = app(SystemLabelRegistry::class)->all();
        $showResults = $this->hasActiveFilters();
        $labels = $showResults ? $this->applyFilters($allLabels) : collect();
        $editingDefinition = $this->editingIdentifier
            ? $allLabels->first(
                fn (SystemLabelDefinition $definition): bool => $definition->identifier === $this->editingIdentifier,
            )
            : null;

        $paletteUsage = $allLabels
            ->groupBy(fn (SystemLabelDefinition $definition): string => ltrim(Str::lower($definition->currentHex()), '#'))
            ->map(function (Collection $items, string $hex): array {
                return [
                    'hex_key' => $hex,
                    'hex' => '#'.$hex,
                    'definitions' => $items->count(),
                    'records' => $items->sum('usageCount'),
                    'labels' => $items->pluck('label')->take(6)->implode(', '),
                ];
            })
            ->sortByDesc('definitions')
            ->values();

        return [
            'labels' => $labels,
            'showResults' => $showResults,
            'totalLabels' => $allLabels->count(),
            'customizedCount' => $allLabels->filter->isCustomized()->count(),
            'paletteUsage' => $paletteUsage,
            'moduleOptions' => $allLabels->pluck('moduleLabel', 'module')->sort()->all(),
            'groupOptions' => $allLabels->pluck('groupLabel', 'group')->sort()->all(),
            'colorOptions' => SystemColorPalette::options(),
            'colorHex' => SystemColorPalette::hexMap(),
            'editingDefinition' => $editingDefinition,
        ];
    }

    /** @param Collection<int, SystemLabelDefinition> $labels
     *  @return Collection<int, SystemLabelDefinition>
     */
    private function applyFilters(Collection $labels): Collection
    {
        $search = Str::lower(trim($this->search));

        return $labels
            ->when($search !== '', fn (Collection $items): Collection => $items->filter(
                fn (SystemLabelDefinition $definition): bool => Str::contains(
                    Str::lower(implode(' ', [
                        $definition->label,
                        $definition->groupLabel,
                        $definition->moduleLabel,
                        $definition->identifier,
                    ])),
                    $search,
                ),
            ))
            ->when($this->moduleFilter !== '', fn (Collection $items): Collection => $items->where('module', $this->moduleFilter))
            ->when($this->groupFilter !== '', fn (Collection $items): Collection => $items->where('group', $this->groupFilter))
            ->when($this->colorFilter !== '', fn (Collection $items): Collection => $items->filter(
                fn (SystemLabelDefinition $definition): bool => ltrim(Str::lower($definition->currentHex()), '#') === $this->colorFilter,
            ))
            ->values();
    }

    private function hasActiveFilters(): bool
    {
        return trim($this->search) !== ''
            || $this->moduleFilter !== ''
            || $this->groupFilter !== ''
            || $this->colorFilter !== '';
    }

    private function editingDefinition(): ?SystemLabelDefinition
    {
        if (! $this->editingIdentifier) {
            return null;
        }

        return app(SystemLabelRegistry::class)->find($this->editingIdentifier);
    }

    private function authorizeColorManagement(): void
    {
        abort_unless(static::canAccess(), 403);
    }
}
