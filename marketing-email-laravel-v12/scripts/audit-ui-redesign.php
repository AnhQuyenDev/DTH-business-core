<?php

declare(strict_types=1);

/**
 * Static audit for the DTH Filament UI/UX redesign.
 *
 * This script intentionally has no Laravel or Composer dependency. It can be
 * executed immediately after extracting the source archive:
 *
 *     php scripts/audit-ui-redesign.php
 */

$root = dirname(__DIR__);
$errors = [];
$metrics = [];

$addError = static function (string $message) use (&$errors): void {
    $errors[] = $message;
};

$relative = static function (string $path) use ($root): string {
    return ltrim(str_replace($root, '', $path), DIRECTORY_SEPARATOR);
};

$phpFiles = static function (string $directory): array {
    if (! is_dir($directory)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
        $directory,
        FilesystemIterator::SKIP_DOTS,
    ));

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
};

$flatten = static function (array $values, string $prefix = '') use (&$flatten): array {
    $result = [];

    foreach ($values as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

        if (is_array($value)) {
            $result += $flatten($value, $path);
        } else {
            $result[$path] = $value;
        }
    }

    return $result;
};

$loadLanguage = static function (string $locale) use ($root, $flatten, $addError): array {
    $jsonPath = $root.'/lang/'.$locale.'.json';
    $json = [];

    if (! is_file($jsonPath)) {
        $addError("Missing translation file: lang/{$locale}.json");
    } else {
        $decoded = json_decode((string) file_get_contents($jsonPath), true);
        if (! is_array($decoded) || json_last_error() !== JSON_ERROR_NONE) {
            $addError("Invalid JSON translation file: lang/{$locale}.json");
        } else {
            $json = $decoded;
        }
    }

    $php = [];
    $phpByFile = [];
    foreach (glob($root.'/lang/'.$locale.'/*.php') ?: [] as $path) {
        $name = pathinfo($path, PATHINFO_FILENAME);
        $values = require $path;

        if (! is_array($values)) {
            $addError("Translation file does not return an array: lang/{$locale}/{$name}.php");
            continue;
        }

        $flattened = $flatten($values, $name);
        $php += $flattened;
        $phpByFile[$name] = $flattened;
    }

    return [
        'json' => $json,
        'php' => $php,
        'php_by_file' => $phpByFile,
        'all' => $json + $php,
    ];
};

$languages = [
    'en' => $loadLanguage('en'),
    'vi' => $loadLanguage('vi'),
];

$compareKeySets = static function (array $left, array $right, string $context) use ($addError): void {
    $leftKeys = array_fill_keys(array_keys($left), true);
    $rightKeys = array_fill_keys(array_keys($right), true);

    $missingRight = array_keys(array_diff_key($leftKeys, $rightKeys));
    $missingLeft = array_keys(array_diff_key($rightKeys, $leftKeys));

    sort($missingRight);
    sort($missingLeft);

    if ($missingRight !== []) {
        $addError($context.' missing in Vietnamese: '.implode(', ', $missingRight));
    }

    if ($missingLeft !== []) {
        $addError($context.' missing in English: '.implode(', ', $missingLeft));
    }
};

$compareKeySets($languages['en']['json'], $languages['vi']['json'], 'JSON translation keys');

$translationFiles = array_unique(array_merge(
    array_keys($languages['en']['php_by_file']),
    array_keys($languages['vi']['php_by_file']),
));
sort($translationFiles);

foreach ($translationFiles as $file) {
    $compareKeySets(
        $languages['en']['php_by_file'][$file] ?? [],
        $languages['vi']['php_by_file'][$file] ?? [],
        "PHP translation keys ({$file}.php)",
    );
}

$metrics['json_translation_keys'] = count($languages['en']['json']);
$metrics['php_translation_keys'] = count($languages['en']['php']);

$scanFiles = array_merge(
    $phpFiles($root.'/app/Filament'),
    $phpFiles($root.'/resources/views/filament'),
);

$literalTranslationKeys = [];
$dynamicTranslationPrefixes = [];
$helperTextCount = 0;
$rawToggleCount = 0;
$stateToggleCount = 0;
$stateLabelCount = 0;
$sectionCount = 0;
$sectionWithIconCount = 0;
$actionCount = 0;
$actionWithIconCount = 0;
$bladeButtonCount = 0;
$bladeButtonWithIconCount = 0;

$actionClasses = [
    'Action',
    'AttachAction',
    'AssociateAction',
    'CreateAction',
    'DeleteAction',
    'DeleteBulkAction',
    'DetachAction',
    'DissociateAction',
    'EditAction',
    'ForceDeleteAction',
    'ForceDeleteBulkAction',
    'QuickViewAction',
    'ReleaseCustomerAction',
    'RestoreAction',
    'RestoreBulkAction',
    'ViewAction',
];
$actionPattern = '/(?<![A-Za-z0-9_])(?:Actions\\\\|Tables\\\\Actions\\\\)?('.implode('|', array_map('preg_quote', $actionClasses)).')::make\s*\(/';

foreach ($scanFiles as $path) {
    $contents = (string) file_get_contents($path);
    $name = $relative($path);

    $helperTextCount += substr_count($contents, '->helperText(');
    $rawToggleCount += preg_match_all('/(?<!State)Toggle::make\s*\(/', $contents);
    $stateToggleCount += preg_match_all('/StateToggle::make\s*\(/', $contents);
    $stateLabelCount += substr_count($contents, '->stateLabels(');

    if (preg_match_all('/(?:__|trans|trans_choice)\(\s*([\'\"])([^\'\"]+)\1\s*\./', $contents, $prefixMatches, PREG_SET_ORDER)) {
        foreach ($prefixMatches as $match) {
            $prefix = preg_replace('/\{.*$/', '', $match[2]) ?? $match[2];
            if ($prefix !== '' && ! str_contains($prefix, '::')) {
                $dynamicTranslationPrefixes[$prefix][$name] = true;
            }
        }
    }

    if (preg_match_all('/(?:__|trans|trans_choice)\(\s*"([^"$]*)(?:\{?\$)/', $contents, $prefixMatches, PREG_SET_ORDER)) {
        foreach ($prefixMatches as $match) {
            $prefix = preg_replace('/\{.*$/', '', $match[1]) ?? $match[1];
            if ($prefix !== '' && ! str_contains($prefix, '::')) {
                $dynamicTranslationPrefixes[$prefix][$name] = true;
            }
        }
    }

    if (preg_match_all('/(?:__|trans|trans_choice)\(\s*([\'\"])([^\'\"]+)\1/', $contents, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $key = $match[2];
            if (
                $key === ''
                || str_contains($key, '$')
                || str_contains($key, '{')
                || str_contains($key, '::')
                || str_ends_with($key, '.')
                || str_ends_with($key, '_')
            ) {
                continue;
            }
            $literalTranslationKeys[$key][$name] = true;
        }
    }

    $offset = 0;
    while (($position = strpos($contents, 'Section::make(', $offset)) !== false) {
        $sectionCount++;
        $nextSection = strpos($contents, 'Section::make(', $position + 1);
        $schema = strpos($contents, '->schema(', $position);
        $boundaryCandidates = array_filter([$nextSection, $schema], static fn ($value): bool => $value !== false);
        $boundary = $boundaryCandidates === [] ? min(strlen($contents), $position + 1200) : min($boundaryCandidates);
        $segment = substr($contents, $position, $boundary - $position);

        if (str_contains($segment, '->icon(')) {
            $sectionWithIconCount++;
        } else {
            $line = substr_count(substr($contents, 0, $position), "\n") + 1;
            $addError("Section without icon: {$name}:{$line}");
        }

        $offset = $position + 1;
    }

    if (preg_match_all($actionPattern, $contents, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[0] as $match) {
            [$token, $position] = $match;
            $actionCount++;
            $segment = substr($contents, $position, 420);

            if (preg_match('/->icon(?:Button)?\s*\(/', $segment)) {
                $actionWithIconCount++;
            } else {
                $line = substr_count(substr($contents, 0, $position), "\n") + 1;
                $addError("Action without icon: {$name}:{$line} ({$token})");
            }
        }
    }
}

foreach ($phpFiles($root.'/resources/views/filament') as $path) {
    $contents = (string) file_get_contents($path);
    $name = $relative($path);

    if (preg_match_all('/<x-filament::button\b(.*?)>/s', $contents, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[1] as $match) {
            [$attributes, $position] = $match;
            $bladeButtonCount++;

            if (preg_match('/\bicon\s*=/', $attributes)) {
                $bladeButtonWithIconCount++;
            } else {
                $line = substr_count(substr($contents, 0, $position), "\n") + 1;
                $addError("Blade Filament button without icon: {$name}:{$line}");
            }
        }
    }

    if (preg_match_all('/<button\b(.*?)>(.*?)<\/button>/s', $contents, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)) {
        foreach ($matches as $match) {
            [$full, $position] = $match[0];
            $body = $match[2][0];
            $bladeButtonCount++;

            if (str_contains($body, '@svg(') || str_contains($body, '<svg')) {
                $bladeButtonWithIconCount++;
            } else {
                $line = substr_count(substr($contents, 0, $position), "\n") + 1;
                $addError("Plain Blade button without icon: {$name}:{$line}");
            }
        }
    }
}

if ($helperTextCount > 0) {
    $addError("Found {$helperTextCount} helperText() calls; use hintIcon() tooltips instead.");
}

if ($rawToggleCount > 0) {
    $addError("Found {$rawToggleCount} raw Toggle::make() calls; use StateToggle with explicit state labels.");
}

if ($stateToggleCount !== $stateLabelCount) {
    $addError("StateToggle/stateLabels mismatch: {$stateToggleCount} toggles and {$stateLabelCount} label definitions.");
}

foreach ($literalTranslationKeys as $key => $locations) {
    foreach (['en', 'vi'] as $locale) {
        if (! array_key_exists($key, $languages[$locale]['all'])) {
            $addError(sprintf(
                'Missing %s translation key "%s" used in %s',
                strtoupper($locale),
                $key,
                implode(', ', array_keys($locations)),
            ));
        }
    }
}

foreach ($dynamicTranslationPrefixes as $prefix => $locations) {
    foreach (['en', 'vi'] as $locale) {
        $found = false;
        foreach (array_keys($languages[$locale]['all']) as $translationKey) {
            if (str_starts_with($translationKey, $prefix)) {
                $found = true;
                break;
            }
        }

        if (! $found) {
            $addError(sprintf(
                'Missing %s translation family "%s*" used in %s',
                strtoupper($locale),
                $prefix,
                implode(', ', array_keys($locations)),
            ));
        }
    }
}

$metrics['literal_translation_keys_used'] = count($literalTranslationKeys);
$metrics['dynamic_translation_families'] = count($dynamicTranslationPrefixes);
$metrics['state_toggles'] = $stateToggleCount;
$metrics['helper_text_calls'] = $helperTextCount;
$metrics['sections_with_icons'] = "{$sectionWithIconCount}/{$sectionCount}";
$metrics['actions_with_icons'] = "{$actionWithIconCount}/{$actionCount}";
$metrics['blade_buttons_with_icons'] = "{$bladeButtonWithIconCount}/{$bladeButtonCount}";

// Navigation items that can appear in the sidebar must expose a corresponding icon.
$navigationCandidates = array_merge(
    $phpFiles($root.'/app/Filament/Pages'),
    $phpFiles($root.'/app/Filament/Resources'),
);
$navigationCount = 0;

foreach ($navigationCandidates as $path) {
    $contents = (string) file_get_contents($path);

    if (
        ! preg_match('/class\s+\w+\s+extends\s+(?:Resource|Page)\b/', $contents)
        || str_contains($contents, 'protected static bool $shouldRegisterNavigation = false')
        || str_contains($contents, 'public static function shouldRegisterNavigation(): bool'.PHP_EOL.'    {'.PHP_EOL.'        return false;')
    ) {
        continue;
    }

    // Pages without a group/label/sort declaration are utility pages and not sidebar entries.
    if (
        str_contains($contents, 'extends Page')
        && ! str_contains($contents, 'getNavigationGroup')
        && ! str_contains($contents, '$navigationGroup')
        && ! str_contains($contents, '$navigationSort')
    ) {
        continue;
    }

    $navigationCount++;
    if (
        ! preg_match('/\$navigationIcon\s*=\s*[\'\"][^\'\"]+[\'\"]/', $contents)
        && ! str_contains($contents, 'getNavigationIcon(')
    ) {
        $addError('Navigation item without icon: '.$relative($path));
    }
}

$metrics['navigation_items_with_icons'] = $navigationCount;

$uiSystemPath = $root.'/resources/views/filament/ui-system.blade.php';
$uiSystemV3Path = $root.'/resources/views/filament/ui-system-v3.blade.php';
$uiSystemV4Path = $root.'/resources/views/filament/ui-system-v4-fixes.blade.php';

if (! is_file($uiSystemV3Path)) {
    $addError('Missing resources/views/filament/ui-system-v3.blade.php');
} else {
    $uiSystemV3 = (string) file_get_contents($uiSystemV3Path);
    foreach (['data-dth-module', 'dth-state-toggle__label', 'dth-helper-tooltip__trigger', 'dth-page-form-actions'] as $marker) {
        if (! str_contains($uiSystemV3, $marker)) {
            $addError("UI V3 layer is missing marker: {$marker}");
        }
    }
}

if (! is_file($uiSystemV4Path)) {
    $addError('Missing resources/views/filament/ui-system-v4-fixes.blade.php');
} else {
    $uiSystemV4 = (string) file_get_contents($uiSystemV4Path);
    foreach ([
        'dth-state-toggle__native-label--sr-only',
        'SIDEBAR_SCROLL_KEY',
        'fi-ta-table > tbody > .fi-ta-row:nth-child(even)',
        'dth-import-html-action',
        'fi-modal-window',
        'dth-page-form-actions',
    ] as $marker) {
        if (! str_contains($uiSystemV4, $marker)) {
            $addError("UI V4 hardening layer is missing marker: {$marker}");
        }
    }
}

$uiSystemContents = is_file($uiSystemPath) ? (string) file_get_contents($uiSystemPath) : '';
if (! str_contains($uiSystemContents, "@include('filament.ui-system-v3')")) {
    $addError('The shared Filament UI system does not include ui-system-v3.');
}
if (! str_contains($uiSystemContents, "@include('filament.ui-system-v4-fixes')")) {
    $addError('The shared Filament UI system does not include ui-system-v4-fixes.');
}

$importActionFiles = [
    $root.'/app/Filament/Resources/EmailTemplateResource/Pages/ListEmailTemplates.php',
    $root.'/app/Filament/Resources/FormTemplateResource/Pages/ListFormTemplates.php',
    $root.'/app/Filament/Resources/LandingPageResource/Pages/ListLandingPages.php',
];
foreach ($importActionFiles as $importActionFile) {
    if (! is_file($importActionFile) || ! str_contains((string) file_get_contents($importActionFile), 'dth-import-html-action')) {
        $addError('Import HTML action is missing its visible bordered-button class: '.$relative($importActionFile));
    }
}
$metrics['ui_v4_regression_guards'] = 'passed';

fwrite(STDOUT, "DTH Filament UI/UX redesign audit\n");
fwrite(STDOUT, str_repeat('=', 38)."\n");
foreach ($metrics as $label => $value) {
    fwrite(STDOUT, sprintf("%-34s %s\n", str_replace('_', ' ', ucfirst($label)).':', (string) $value));
}

if ($errors !== []) {
    fwrite(STDOUT, "\nFAILED (".count($errors)." issue(s))\n");
    foreach ($errors as $index => $error) {
        fwrite(STDOUT, sprintf("%3d. %s\n", $index + 1, $error));
    }
    exit(1);
}

fwrite(STDOUT, "\nPASSED — UI conventions and bilingual key parity are complete.\n");
