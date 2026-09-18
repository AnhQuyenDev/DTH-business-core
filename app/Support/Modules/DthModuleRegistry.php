<?php

namespace App\Support\Modules;

use Composer\InstalledVersions;
use Filament\Contracts\Plugin;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

final class DthModuleRegistry
{
    /** @var array<string, array<string, mixed>>|null */
    private ?array $definitions = null;

    public function __construct(
        private readonly Container $container,
    ) {}

    /**
     * Discover installed DTH modules from package composer manifests.
     *
     * Local path repositories are scanned first so development installs work
     * before Composer metadata has been regenerated. Installed Composer
     * packages are then merged in, which also supports vendor-based installs.
     *
     * @return array<string, array<string, mixed>> keyed by Composer package name
     */
    public function definitions(): array
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }

        $definitions = [];

        foreach ($this->localComposerFiles() as $composerFile) {
            $this->addDefinition($definitions, $composerFile);
        }

        if (class_exists(InstalledVersions::class)) {
            foreach (InstalledVersions::getInstalledPackages() as $packageName) {
                if (! str_starts_with($packageName, 'dth/')) {
                    continue;
                }

                $installPath = InstalledVersions::getInstallPath($packageName);
                if (! is_string($installPath) || $installPath === '') {
                    continue;
                }

                $composerFile = rtrim($installPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'composer.json';
                if (is_file($composerFile)) {
                    $this->addDefinition($definitions, $composerFile);
                }
            }
        }

        uasort($definitions, static function (array $left, array $right): int {
            return ((int) ($left['order'] ?? 1000)) <=> ((int) ($right['order'] ?? 1000));
        });

        return $this->definitions = $definitions;
    }

    /** @return array<int, array<string, mixed>> */
    public function activeDefinitions(): array
    {
        return array_values(array_filter(
            $this->definitions(),
            fn (array $definition): bool => $this->isEnabled($definition),
        ));
    }

    /** @return list<class-string> */
    public function filamentPluginClasses(): array
    {
        $classes = [];

        foreach ($this->activeDefinitions() as $definition) {
            $pluginClass = data_get($definition, 'filament.plugin');
            if (! is_string($pluginClass) || $pluginClass === '' || ! class_exists($pluginClass)) {
                continue;
            }

            if (! is_a($pluginClass, Plugin::class, true)) {
                throw new RuntimeException("DTH module plugin [{$pluginClass}] must implement ".Plugin::class.'.');
            }

            $classes[] = $pluginClass;
        }

        return array_values(array_unique($classes));
    }

    /** @return list<Plugin> */
    public function filamentPlugins(): array
    {
        return array_map(
            fn (string $pluginClass): Plugin => $this->container->make($pluginClass),
            $this->filamentPluginClasses(),
        );
    }

    /** @return list<class-string> */
    public function authMiddlewareClasses(): array
    {
        $middleware = [];

        foreach ($this->activeDefinitions() as $definition) {
            foreach ((array) data_get($definition, 'filament.auth_middleware', []) as $middlewareClass) {
                if (is_string($middlewareClass) && $middlewareClass !== '' && class_exists($middlewareClass)) {
                    $middleware[] = $middlewareClass;
                }
            }
        }

        return array_values(array_unique($middleware));
    }

    /** @param array<string, mixed> $definition */
    private function isEnabled(array $definition): bool
    {
        $configKey = $definition['enabled_config'] ?? null;

        if (! is_string($configKey) || $configKey === '') {
            return true;
        }

        return (bool) config($configKey, true);
    }

    /** @return list<string> */
    private function localComposerFiles(): array
    {
        $files = glob(base_path('packages/dth/*/composer.json')) ?: [];
        sort($files);

        return array_values($files);
    }

    /**
     * @param array<string, array<string, mixed>> $definitions
     */
    private function addDefinition(array &$definitions, string $composerFile): void
    {
        $json = file_get_contents($composerFile);
        if (! is_string($json) || $json === '') {
            return;
        }

        $composer = json_decode($json, true);
        if (! is_array($composer)) {
            return;
        }

        $packageName = $composer['name'] ?? null;
        $manifest = data_get($composer, 'extra.dth-module');

        if (! is_string($packageName) || ! str_starts_with($packageName, 'dth/') || ! is_array($manifest)) {
            return;
        }

        $manifest['package'] = $packageName;
        $manifest['path'] = dirname($composerFile);

        // Prefer the local path-repository manifest when both local and vendor
        // metadata exist. This makes development changes visible immediately.
        if (! isset($definitions[$packageName]) || str_contains($composerFile, DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'dth'.DIRECTORY_SEPARATOR)) {
            $definitions[$packageName] = $manifest;
        }
    }
}
