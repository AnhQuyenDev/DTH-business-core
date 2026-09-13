# Apply DTH CRM to the current project

The ZIP is rooted at the project root and only adds `packages/dth/crm`; it does not overwrite Email or Marketing.

## 1. Extract ZIP into project root
Expected path: `packages/dth/crm/composer.json`.

## 2. Register Composer package
```bash
composer require dth/crm:@dev --no-update
composer update dth/crm --no-interaction
```

## 3. Register Filament plugin
In `app/Providers/Filament/AdminPanelProvider.php`, keep the existing Email and Marketing plugins unchanged and add:

```php
use Dth\Crm\Filament\CrmPlugin;
```

Then append to the existing Panel chain:

```php
->plugin(
    CrmPlugin::make()
)
```

Do not remove or reorder stable Email/Marketing plugins unless your host application requires a specific navigation order.

## 4. Database/cache
```bash
php artisan migrate
php artisan optimize:clear
composer show filament/filament
php artisan crm:health
```

Filament must remain `v4.13.1` in the current project baseline.

## 5. Marketing integration
No Marketing source file must be edited. CRM registers implementations of Marketing `AudienceProvider` and `LeadProvider` when `dth/marketing` is installed. Re-run a published Landing Page submission and verify CRM Contact + Lead are created.

## 6. Tests
```bash
php artisan test packages/dth/crm/tests
php artisan test packages/dth/marketing/tests
# run the existing Email regression suite as well
```

See `README_CRM_FINAL_UAT.md` for the blocking PASS gate.
