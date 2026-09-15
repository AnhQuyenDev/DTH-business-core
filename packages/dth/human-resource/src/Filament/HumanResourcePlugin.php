<?php

namespace Dth\HumanResource\Filament;

use Dth\HumanResource\Filament\Pages\HumanResourceOverview;
use Dth\HumanResource\Filament\Resources\DepartmentResource;
use Dth\HumanResource\Filament\Resources\EmployeeResource;
use Dth\HumanResource\Filament\Resources\PositionResource;
use Filament\Contracts\Plugin;
use Filament\Panel;

final class HumanResourcePlugin implements Plugin
{
    public static function make(): static
    {
        return app(static::class);
    }

    public function getId(): string
    {
        return 'dth-human-resource';
    }

    public function register(Panel $panel): void
    {
        if (! config('dth-human-resource.enabled', true)) {
            return;
        }

        $resources = [];
        if (config('dth-human-resource.features.employees', true)) {
            $resources[] = EmployeeResource::class;
        }
        if (config('dth-human-resource.features.departments', true)) {
            $resources[] = DepartmentResource::class;
        }
        if (config('dth-human-resource.features.positions', true)) {
            $resources[] = PositionResource::class;
        }

        $pages = config('dth-human-resource.features.analytics', true)
            ? [HumanResourceOverview::class]
            : [];

        $panel
            ->pages($pages)
            ->resources($resources);
    }

    public function boot(Panel $panel): void
    {
        // Reserved for Human Resource-only Filament hooks.
    }
}
