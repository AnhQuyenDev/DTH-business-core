<?php

namespace App\Support\Ui\Labels;

use App\Support\Ui\SystemColorPalette;

final readonly class SystemLabelDefinition
{
    /**
     * @param  array<int, string>  $usageLocations
     */
    public function __construct(
        public string $identifier,
        public string $module,
        public string $moduleLabel,
        public string $group,
        public string $groupLabel,
        public string $key,
        public string $label,
        public string $type,
        public string $impact,
        public string $defaultColor,
        public string $currentColor,
        public array $usageLocations = [],
        public int $usageCount = 0,
        public string $storage = 'override',
        public int|string|null $sourceId = null,
        public ?string $styleCategory = null,
    ) {}

    public function isCustomized(): bool
    {
        return SystemColorPalette::normalize($this->currentColor)
            !== SystemColorPalette::normalize($this->defaultColor);
    }

    public function isBusinessSensitive(): bool
    {
        return in_array($this->impact, ['business', 'critical'], true);
    }

    public function requiresReason(): bool
    {
        return $this->impact === 'critical';
    }

    public function currentHex(): string
    {
        return SystemColorPalette::hex($this->currentColor);
    }

    public function defaultHex(): string
    {
        return SystemColorPalette::hex($this->defaultColor);
    }
}
