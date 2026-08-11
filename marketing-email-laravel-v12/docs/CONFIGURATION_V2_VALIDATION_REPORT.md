# Configuration V2 - Validation Report

Baseline: 2026-08-11

## Scope

Configuration V2 only refactors the Configuration surface and shared UI/i18n primitives required by that surface. Marketing, Sales, Finance and Customer Care workflow classes are not modified.

## Static gates completed

- PHP syntax: **850 / 850 PHP files PASS** across `app`, `database`, `tests`, `lang`, `resources`, `routes`, `config`.
- Configuration i18n parity: **VI 300 keys / EN 300 keys**, missing in either locale: **0**.
- Literal/dynamic-family `configuration.*` reference scan: **205 references**, unresolved families/keys: **0**.
- Shared configurable color palette: **22 unique colors**.
- Legacy department-scoped Job Title Livewire surface: **removed**.
- Global Job Title form: **no `department_id` input**.
- Business status colors: excluded from user-editable badge categories; semantic status mapping remains centralized.
- Staff Business Function form prevents duplicate function selection and keeps a single primary function in the same repeater interaction.
- Manual DOCX: rendered to **16 pages** and visually inspected page-by-page; no clipping/overlap detected.

## Migration behavior reviewed

`2026_08_11_200000_globalize_job_titles_for_configuration_v2.php`:

1. adds stable `code`, `group_key`, optional `function_key`;
2. normalizes and merges duplicate legacy Job Titles globally;
3. repoints Staff records to the retained canonical Job Title;
4. clears legacy `department_id` on Job Titles;
5. adds short, MySQL-safe unique index names for global `title` and `code`.

The `down()` migration removes V2 fields/indexes but intentionally cannot recreate historical department-specific duplicate Job Title rows that were merged. Back up the database before production migration.

## Runtime gate still required on the developer machine

The packaging container does not expose the PHP extensions required by this Laravel test environment (`mbstring`, DOM/XML, XMLWriter, SQLite/PDO SQLite), so a truthful full PHPUnit runtime PASS cannot be claimed here.

Run on the project's normal Windows/PHP environment after applying the patch:

```bash
php artisan optimize:clear
php artisan migrate
php artisan test tests/Feature/Configuration
php artisan test tests/Feature/Ui/DepartmentUiHardeningTest.php
php artisan test tests/Feature/Ui/UiTranslationCoverageTest.php
php artisan test
```

Then perform browser UAT using the Configuration Module User Manual checklist.

**Release rule:** Configuration V2 is a release candidate until migration + full regression + browser UAT are green on the target environment.
