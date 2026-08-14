<?php

namespace App\Services\System;

use App\Models\Crm\Department;
use App\Models\System\UiBadgeStyle;
use App\Services\Marketing\AuditLogService;
use App\Support\Ui\Labels\SystemLabelDefinition;
use App\Support\Ui\SystemColorPalette;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class SystemLabelColorService
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {}

    public function change(
        SystemLabelDefinition $definition,
        string $color,
        bool $acknowledged,
        ?string $reason = null,
    ): bool {
        $reason = filled($reason) ? trim((string) $reason) : null;

        if (! SystemColorPalette::isSupported($color)) {
            throw ValidationException::withMessages([
                'selectedColor' => __('configuration.system_labels.validation.invalid_color'),
            ]);
        }

        $color = SystemColorPalette::normalize($color);

        $this->guardBusinessChange($definition, $acknowledged, $reason);

        if ($color === SystemColorPalette::normalize($definition->currentColor)) {
            return false;
        }

        if ($color === SystemColorPalette::normalize($definition->defaultColor)) {
            return $this->reset($definition, $acknowledged, $reason);
        }

        return DB::transaction(function () use ($definition, $color, $reason): bool {
            $oldColor = SystemColorPalette::normalize($definition->currentColor);

            if ($definition->storage === 'department') {
                $department = Department::query()->findOrFail($definition->sourceId);
                $department->update(['color' => $color]);
                $auditable = $department;
            } else {
                $style = UiBadgeStyle::query()->updateOrCreate(
                    [
                        'category' => $definition->styleCategory,
                        'key' => $definition->key,
                    ],
                    ['color' => $color],
                );
                $auditable = $style;
            }

            $this->auditLog->log(
                'system_label.color_changed',
                $auditable,
                $this->auditValues($definition, $oldColor),
                $this->auditValues($definition, $color, $reason),
            );

            return true;
        });
    }

    public function reset(
        SystemLabelDefinition $definition,
        bool $acknowledged,
        ?string $reason = null,
    ): bool {
        $reason = filled($reason) ? trim((string) $reason) : null;
        $this->guardBusinessChange($definition, $acknowledged, $reason);

        if (! $definition->isCustomized()) {
            return false;
        }

        return DB::transaction(function () use ($definition, $reason): bool {
            $oldColor = SystemColorPalette::normalize($definition->currentColor);
            $defaultColor = SystemColorPalette::normalize($definition->defaultColor);

            if ($definition->storage === 'department') {
                $department = Department::query()->findOrFail($definition->sourceId);
                $department->update(['color' => $defaultColor]);
                $auditable = $department;
            } else {
                $style = UiBadgeStyle::query()
                    ->where('category', $definition->styleCategory)
                    ->where('key', $definition->key)
                    ->first();

                $auditable = $style;
                $style?->delete();
            }

            $this->auditLog->log(
                'system_label.color_reset',
                $auditable,
                $this->auditValues($definition, $oldColor),
                $this->auditValues($definition, $defaultColor, $reason),
            );

            return true;
        });
    }

    private function guardBusinessChange(
        SystemLabelDefinition $definition,
        bool $acknowledged,
        ?string $reason,
    ): void {
        if ($definition->isBusinessSensitive() && ! $acknowledged) {
            throw ValidationException::withMessages([
                'acknowledged' => __('configuration.system_labels.validation.acknowledgement_required'),
            ]);
        }

        if ($definition->requiresReason() && blank($reason)) {
            throw ValidationException::withMessages([
                'changeReason' => __('configuration.system_labels.validation.reason_required'),
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function auditValues(
        SystemLabelDefinition $definition,
        string $color,
        ?string $reason = null,
    ): array {
        return [
            'label_identifier' => $definition->identifier,
            'label_group' => $definition->group,
            'label_key' => $definition->key,
            'label' => $definition->label,
            'color' => $color,
            'impact' => $definition->impact,
            'usage_count' => $definition->usageCount,
            'reason' => $reason,
        ];
    }
}
