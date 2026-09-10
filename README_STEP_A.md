# Step A - Root i18n Foundation (Filament 4.13.1)

This patch is the first implementation step from the post-UAT Email Module upgrade roadmap.
It contains only new/edited files relative to the full source archive supplied for this step.

## Goal

Create a root-level English/Vietnamese UI translation foundation that can be reused by future modules, then migrate the current Email Module UI onto that foundation without changing raw business statuses or badge-color semantics.

## Included

- Root `LocaleManager` and locale middleware.
- Root `UiTranslator` + global `ui_t()` / `ui_status()` helpers.
- Central `config/localization.php` catalog.
- `system_translations` DB table for runtime overrides and missing-key registry.
- `users.preferred_locale` persistence.
- English / Vietnamese switcher in the Filament user menu.
- Email Module resources/pages/widgets/relation managers migrated to root UI keys.
- Status labels translated while badge colors continue to use raw status values through `StatusColor`.
- Explicit `StatusColor` mappings for `verified`, `disabled`, and `error`.
- Feature tests for translation resolution, locale persistence, missing-key registration, and DB override priority.

## Apply

Extract this archive over the Laravel project root, preserving folders.

Then run:

```bash
composer dump-autoload
php artisan migrate
php artisan optimize:clear
```

Do not copy a `.env` file from this patch. The only environment option added to `.env.example` is:

```env
UI_TRANSLATION_CAPTURE_MISSING=true
```

Your existing `APP_LOCALE` and `APP_FALLBACK_LOCALE` continue to control the defaults.

## UAT checklist

1. Log in to `/admin`.
2. Open the user menu and choose `Tiếng Việt`.
3. Verify the Email navigation and all Email screens switch to Vietnamese:
   - Sending Domains
   - Sending Accounts
   - Template Categories
   - Templates
   - Campaigns
   - Delivery Log
   - Suppressions
4. Check forms, tables, filters, actions, modal labels, campaign analytics cards, recipients, and delivery detail labels.
5. Verify status text changes language but status values stored in the database do not change.
6. Verify badge colors remain unchanged after switching language.
7. Refresh / log out / log in: the selected language should persist for the user.
8. Switch back to `English` and verify the UI returns to English.

## Database checks

After choosing Vietnamese for a user:

```sql
SELECT id, email, preferred_locale
FROM users
WHERE id = <YOUR_USER_ID>;
```

Expected `preferred_locale = 'vi'`.

The new table is:

```text
system_translations
```

It stores manual DB overrides and future missing translation keys. It does not change the raw status values used by business logic.

## Tests

On the real project environment:

```bash
php artisan test --filter=LocalizationFoundationTest
```

You can also run the existing Email package test suite after applying the patch.

## Validation performed before handoff

- PHP syntax lint passed across root app/config/migrations/routes and Email package PHP/Blade files.
- Full PHPUnit execution was not possible in the build sandbox because the full root Composer vendor directory / required test extensions were unavailable there. Run the command above in your project environment.

## Stop point

Do not continue to Step B yet. After this patch passes your UAT, the next roadmap step is **B - Email Analytics Data Layer**.
