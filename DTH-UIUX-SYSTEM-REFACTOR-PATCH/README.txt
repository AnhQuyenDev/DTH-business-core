DTH BUSINESS CORE - SYSTEM UI/UX REFACTOR
=========================================

PATCH SCOPE
-----------
- Navigation regrouped into Email, Marketing, CRM, Sales, Finance,
  Customer Care, Configuration and System.
- Filament design system normalized without changing the default canvas/background.
- Vietnamese/English labels, helpers, notifications and core dashboards standardized.
- Status / Role / Department Function badge colors centralized and configurable.
- Date / datetime display standardized.
- Major forms reorganized around user decisions (Staff, User, Email Template,
  Marketing Campaign and existing business forms).
- Sales Overview and Revenue Report redesigned for comparison and drill-down.
- Role-based landing page after login.
- Golden Path state machines and backend business rules are not intentionally changed.

FAST APPLY
----------
1. Commit/backup current code and back up the database.
2. Extract this ZIP.
3. Open PowerShell in the extracted patch directory.
4. Run:

powershell -ExecutionPolicy Bypass -File .\APPLY-UIUX-REFACTOR.ps1 -Target "D:\path\to\DTH-business-core\marketing-email-laravel-v12"

The script automatically:
- backs up overwritten files;
- copies the patch;
- runs php artisan migrate --force;
- runs php artisan optimize:clear.

It does NOT overwrite .env.
It does NOT need npm build.
It does NOT need queue:work for this refactor.

NEW ADMIN UI CONFIG
-------------------
Configuration -> Badge Styles

Admin can override Filament semantic colors for:
- statuses;
- system roles;
- department functions.

Department records keep their existing per-department color setting.

DOCUMENTATION
-------------
See:
docs/UI_UX_SYSTEM_REFACTOR_2026.md

ROLLBACK
--------
The apply script creates:
_uiux_refactor_backup_YYYYMMDD_HHMMSS
inside the Laravel application root.
