# CRM Agent Profile migration

Human Resource is the owner of employee identity, organization, employment status, and availability. CRM stores only module-specific assignment settings.

## Final ownership

- HR employee master: `hr_employees`
- CRM assignment profile: `crm_agent_profiles`
- Link: `crm_agent_profiles.employee_id -> hr_employees.id`

`crm_agent_profiles` contains only CRM-specific fields such as `assignment_enabled`, `lead_capacity`, `customer_capacity`, and `distribution_weight`.

## Upgrade migration

`2026_09_15_000002_rename_crm_staff_to_agent_profiles.php` renames the existing CRM profile table and assignment foreign-key columns in place. It does not copy or recreate the rows, so primary-key values remain unchanged.

Examples:

- `crm_staff` -> `crm_agent_profiles`
- `crm_leads.assigned_staff_id` -> `assigned_agent_profile_id`
- `crm_company_assignments.staff_id` -> `agent_profile_id`
- `crm_customers.converted_by_staff_id` -> `converted_by_agent_profile_id`

The migration is resumable for the normal partial-DDL case: when only the target table/column exists, that rename is skipped. If both the legacy and target table/column exist at the same time, the migration stops instead of guessing which data should win.

## Run

Back up the database first, then run:

```bash
php artisan optimize:clear
php artisan migrate
php artisan human-resource:health
php artisan crm:health
```

After migration, `crm:health` also verifies that the legacy `crm_staff` table no longer exists.
