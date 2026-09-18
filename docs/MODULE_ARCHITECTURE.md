# DTH module architecture

The host application no longer imports DTH Filament plugins directly. Installed modules declare their UI and integration metadata in `composer.json > extra.dth-module`; `App\Support\Modules\DthModuleRegistry` discovers those manifests and registers only available/enabled plugins and module-owned auth middleware.

## Dependency types

- `required`: the module is not a valid installation without the dependency. Composer should enforce this in `require`.
- `optional`: the module can run without the other package.
- `integrations`: functionality that activates only when both packages are installed/enabled; contracts/null adapters should be used where applicable.

Current matrix:

| Module | Required | Optional / integration |
| --- | --- | --- |
| Email | none | Marketing |
| Marketing | none | CRM, Commercial, Email |
| Commercial | none | CRM, Marketing, Email |
| CRM | Human Resource | Marketing, Commercial |
| Human Resource | none | CRM, Account Management |
| Account Management | none | Human Resource |

## Filament registration

A module manifest may declare:

```json
{
  "extra": {
    "dth-module": {
      "id": "example",
      "order": 100,
      "enabled_config": "dth-example.enabled",
      "filament": {
        "plugin": "Dth\\Example\\Filament\\ExamplePlugin",
        "auth_middleware": []
      }
    }
  }
}
```

No change to `AdminPanelProvider` is required when a future DTH module follows this manifest contract.

## Marketing + CRM

Marketing owns the `AudienceProvider` and `LeadProvider` contracts and registers null adapters by default. When CRM and Marketing are both enabled, `CrmServiceProvider::boot()` replaces only those null adapters with:

- `DthMarketingAudienceProvider`
- `DthMarketingLeadProvider`

The rebinding happens in `boot()` because all service providers have completed `register()` by then, making provider registration order irrelevant. Explicit non-null custom adapters are not overwritten.


## Package-owned migrations and host seeders

Module schema belongs to the package that owns the module. The host `database/migrations` directory must not duplicate package migrations; otherwise removing a package would still create its tables on a fresh install.

Host seeders must also tolerate optional modules. `SuperAdminSeeder` always creates the core user, but only assigns the Account Management `super-admin` role when the account tables exist.
