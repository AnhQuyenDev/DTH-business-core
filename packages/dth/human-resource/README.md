# DTH Human Resource

`dth/human-resource` owns the employee master and organization structure shared by the business modules.

## Ownership boundary

Human Resource owns:

- employee identity and employment status;
- departments and global job titles;
- work / leave / remote availability;
- business-function assignments and authority metadata.

CRM must not duplicate those fields. CRM keeps only the module-specific assignment profile in `crm_agent_profiles`:

- `employee_id`;
- `assignment_enabled`;
- `lead_capacity`;
- `customer_capacity`;
- `distribution_weight`.

Email and Marketing are not modified by this extraction. Future integrations should reference `hr_employees` instead of creating another employee master.

## Upgrade paths

Two migration paths are supported:

1. **Monolithic flow -> modular source**: if legacy `staff`, `departments`, `positions`, `staff_availabilities`, or `staff_business_functions` tables exist, `2026_09_12_000002_import_legacy_human_resource_data.php` imports them into HR without deleting the legacy tables.
2. **Current modular CRM -> HR split**: `2026_09_15_000001_extract_crm_staff_to_human_resource.php` moves identity and availability data out of the legacy `crm_staff` table. `2026_09_15_000002_rename_crm_staff_to_agent_profiles.php` then renames that table to `crm_agent_profiles` and renames CRM assignment foreign-key columns in place. Primary-key values are preserved, so existing Lead/Company/Customer assignments keep the same owners.

Back up the database before running the extraction migration on an existing installation.

## Commands

```bash
php artisan human-resource:health
php artisan crm:health
```

## Integration rule

`hr_employees` is the employee master. Other modules may maintain module-specific profiles that point to an employee, but employee identity, organization, employment status, and availability must not be copied into those profiles.

Business functions are capability metadata. Authentication/RBAC remains a Core/Security concern; HR does not write application roles into the `users` table.
