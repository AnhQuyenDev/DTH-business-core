<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Toggle;

/**
 * Filament-native boolean toggle with explicit on/off state labels.
 *
 * The component keeps the original boolean field behavior intact. The labels
 * are rendered by the shared UI layer so users never have to infer a toggle's
 * current meaning from color alone. The UI layer also suppresses an identical
 * native label, preventing duplicate state text without hiding contextual labels.
 */
class StateToggle extends Toggle
{
    public function stateLabels(string $onLabel, string $offLabel): static
    {
        return $this->extraAttributes([
            'class' => 'dth-state-toggle-source',
            'data-dth-on-label' => $onLabel,
            'data-dth-off-label' => $offLabel,
        ]);
    }
}
