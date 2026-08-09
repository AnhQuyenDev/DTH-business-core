DTH UI/UX ROUND 2 - ENTERPRISE PATCH
====================================

Target base:
- Apply after the previous full-system UI/UX refactor.
- The target folder must be the Laravel app folder containing artisan.

Quick apply (PowerShell):

powershell -ExecutionPolicy Bypass -File .\DTH-UIUX-ROUND-2-ENTERPRISE-PATCH\APPLY-UIUX-ROUND-2.ps1 -Target ".\marketing-email-laravel-v12"

The script:
1. Backs up every existing file that will be overwritten.
2. Copies only the Round 2 changed/new files.
3. Runs php artisan optimize:clear.

It does NOT:
- overwrite .env
- run migrations
- require queue:work
- require npm install/build

Main documentation after apply:
docs/UI_UX_SYSTEM_REFACTOR_ROUND_2_2026.md
