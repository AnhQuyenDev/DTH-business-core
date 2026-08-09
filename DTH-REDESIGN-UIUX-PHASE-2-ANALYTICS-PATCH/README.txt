DTH BUSINESS CORE - REDESIGN UI/UX PHASE 2
Dashboard, Reports & Enterprise Analytics

Baseline expected:
- Redesign UI/UX Phase 1
- Phase 1 Hotfix 1
- Phase 1 Hotfix 2

Scope:
- Enterprise/Admin analytics dashboard
- Marketing campaign/social/email analytics
- Finance/revenue comparison and aging analytics
- Sales quotation-to-paid analytics (Opportunity intentionally excluded)
- Customer Service operations dashboard
- Workforce productivity/KPI support analytics
- Revenue Analysis redesign
- Email Campaign Report redesign
- CSV export and equal-period comparisons

No migration.
No npm build.
No queue worker required.
Golden Path business workflows are not changed.

APPLY (PowerShell):
  powershell -ExecutionPolicy Bypass -File .\APPLY-REDESIGN-UIUX-PHASE-2.ps1 -Target ".\marketing-email-laravel-v12"

After applying:
  Ctrl + F5 in the browser.

Documentation:
  docs/REDESIGN_UI_UX_PHASE_2_ANALYTICS_2026.md
