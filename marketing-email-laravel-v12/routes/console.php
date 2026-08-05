<?php

use App\Jobs\Marketing\ProcessScheduledCampaignsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    dispatch(new ProcessScheduledCampaignsJob)->onQueue('marketing');
})->everyMinute();

Schedule::command('sales:process-reminders')->everyMinute()->withoutOverlapping();

Artisan::command('i18n:audit-hardcoded {--path=* : Relative paths to scan} {--fail : Exit with non-zero code when findings exist}', function () {
    $root = base_path();
    $paths = $this->option('path');

    if (empty($paths)) {
        $paths = [
            'app/Filament',
            'app/Http/Controllers',
            'app/Services',
            'resources/views',
        ];
    }

    $phpPatterns = [
        '/->(?:label|title|modalHeading|modalDescription|copyMessage|helperText|placeholder|addActionLabel)\(\s*[\"\'](?!\s*__\(|\s*trans\()/',
        '/Notification::make\(\).*->(?:title|body)\(\s*[\"\'](?!\s*__\(|\s*trans\()/s',
    ];

    $bladePatterns = [
        '/>\s*[A-Za-zÀ-ỹ][^<]*\s*</u',
    ];

    $findings = [];

    foreach ($paths as $path) {
        $absolute = $root.DIRECTORY_SEPARATOR.str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        if (! File::exists($absolute)) {
            $this->warn('Path not found: '.$path);

            continue;
        }

        $files = File::isFile($absolute)
            ? [$absolute]
            : File::allFiles($absolute);

        foreach ($files as $file) {
            $filePath = is_string($file) ? $file : $file->getPathname();
            if (! str_ends_with($filePath, '.php') && ! str_ends_with($filePath, '.blade.php')) {
                continue;
            }

            $content = File::get($filePath);
            $lines = preg_split('/\r\n|\n|\r/', $content) ?: [];

            foreach ($lines as $index => $line) {
                $lineNumber = $index + 1;

                if (str_contains($line, '__(') || str_contains($line, 'trans(')) {
                    continue;
                }

                if (str_ends_with($filePath, '.blade.php')) {
                    foreach ($bladePatterns as $pattern) {
                        if (preg_match($pattern, $line)) {
                            $findings[] = [
                                'file' => str_replace($root.DIRECTORY_SEPARATOR, '', $filePath),
                                'line' => $lineNumber,
                                'text' => trim($line),
                            ];
                            break;
                        }
                    }

                    continue;
                }

                foreach ($phpPatterns as $pattern) {
                    if (preg_match($pattern, $line)) {
                        $findings[] = [
                            'file' => str_replace($root.DIRECTORY_SEPARATOR, '', $filePath),
                            'line' => $lineNumber,
                            'text' => trim($line),
                        ];
                        break;
                    }
                }
            }
        }
    }

    if (empty($findings)) {
        $this->info('No obvious hardcoded user-facing strings found in scanned paths.');

        return 0;
    }

    $this->warn('Potential hardcoded user-facing strings: '.count($findings));
    foreach ($findings as $finding) {
        $this->line($finding['file'].':'.$finding['line'].'  '.$finding['text']);
    }

    return $this->option('fail') ? 1 : 0;
})->purpose('Audit potential hardcoded user-facing strings for i18n cleanup');
