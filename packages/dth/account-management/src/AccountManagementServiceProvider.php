<?php
namespace Dth\AccountManagement;

use Dth\AccountManagement\Console\Commands\AccountHealthCommand;
use Dth\AccountManagement\Console\Commands\SyncPermissionsCommand;
use Dth\AccountManagement\Models\AccountAuditLog;
use Dth\AccountManagement\Models\AccountUser;
use Dth\AccountManagement\Services\AccessControlService;
use Dth\AccountManagement\Services\AccountAnalyticsService;
use Dth\AccountManagement\Services\AccountSettingService;
use Dth\AccountManagement\Services\AuditLogger;
use Dth\AccountManagement\Services\EmployeeLinkService;
use Dth\AccountManagement\Services\InvitationService;
use Dth\AccountManagement\Services\PermissionRegistryService;
use Dth\AccountManagement\Services\SecurityService;
use Dth\AccountManagement\Support\AccountAuthorization;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

final class AccountManagementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/account-management.php', 'dth-account-management');
        $this->mergeConfigFrom(__DIR__.'/../config/translations.php', 'dth-account-management-translations');

        foreach ([
            AccountSettingService::class,
            AccessControlService::class,
            PermissionRegistryService::class,
            EmployeeLinkService::class,
            InvitationService::class,
            AuditLogger::class,
            SecurityService::class,
            AccountAnalyticsService::class,
            AccountAuthorization::class,
        ] as $service) {
            $this->app->singleton($service);
        }
    }

    public function boot(): void
    {
        if (! config('dth-account-management.enabled', true)) {
            return;
        }

        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'dth-account-management');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        $this->publishes([
            __DIR__.'/../config/account-management.php' => config_path('dth-account-management.php'),
        ], 'dth-account-management-config');

        $this->app->booted(function (): void {
            $gate = app(GateContract::class);
            $access = app(AccessControlService::class);
            app(PermissionRegistryService::class)->registerGates($gate, $access);

            // Email currently relies mainly on Filament/Laravel model abilities.
            // Map those checks to the account permission registry without changing
            // the Email package itself.
            $gate->before(function ($user, string $ability, array $arguments = []) use ($access) {
                $subject = $arguments[0] ?? null;
                $class = is_string($subject)
                    ? $subject
                    : (is_object($subject) ? $subject::class : '');

                if (str_starts_with($class, 'Dth\\Email\\Models\\')) {
                    $permission = in_array($ability, ['viewAny', 'view'], true)
                        ? 'email.view'
                        : 'email.manage';

                    return $access->allows($user, $permission);
                }

                return null;
            });
        });

        Event::listen(Login::class, fn (Login $event) => app(SecurityService::class)->onLogin($event));
        Event::listen(Failed::class, fn (Failed $event) => app(SecurityService::class)->onFailed($event));
        Event::listen(Logout::class, fn (Logout $event) => app(SecurityService::class)->onLogout($event));

        if (config('dth-account-management.features.audit', true)) {
            foreach (['created', 'updated', 'deleted'] as $verb) {
                Event::listen("eloquent.{$verb}: *", function (string $eventName, array $data) use ($verb): void {
                    $model = $data[0] ?? null;
                    if ($model instanceof Model && ! $model instanceof AccountAuditLog) {
                        app(AuditLogger::class)->modelEvent($verb, $model);
                    }
                });
            }
        }

        // Optional Human Resource lifecycle integration. No hard package dependency.
        Event::listen('eloquent.updated: Dth\\HumanResource\\Models\\Employee', function (string $eventName, array $data): void {
            if (! config('dth-account-management.human_resource.auto_disable_when_employee_inactive', true)) {
                return;
            }

            $employee = $data[0] ?? null;
            if (! $employee || ! $employee->user_id || ! $employee->wasChanged('employment_status')) {
                return;
            }

            $status = $employee->employment_status instanceof \BackedEnum
                ? $employee->employment_status->value
                : (string) $employee->employment_status;

            if ($status !== 'active' && Schema::hasTable('users')) {
                AccountUser::query()->whereKey($employee->user_id)->update(['account_status' => 'inactive']);
            }
        });

        if ($this->app->runningInConsole()) {
            $this->commands([AccountHealthCommand::class, SyncPermissionsCommand::class]);
        }
    }
}
