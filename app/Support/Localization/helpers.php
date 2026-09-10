<?php

use App\Support\Localization\UiTranslator;

if (! function_exists('ui_t')) {
    function ui_t(
        string $key,
        ?string $default = null,
        array $replace = [],
        ?string $module = null,
        ?string $context = null,
    ): string {
        return app(UiTranslator::class)->translate(
            key: $key,
            default: $default,
            replace: $replace,
            module: $module,
            context: $context,
        );
    }
}

if (! function_exists('ui_status')) {
    function ui_status(mixed $state): string
    {
        return app(UiTranslator::class)->status($state);
    }
}
