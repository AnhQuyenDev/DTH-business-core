# DTH Business Core - Marketing M-E -> M-G

Target: PHP 8.2+ / Laravel 12 / Filament 4.13.1
Baseline: Marketing M-B -> M-D Hotfix V3

## Included

- **M-E Public Submission Pipeline**: view tracking, UTM capture, public POST, dynamic validation, UUID idempotency token, SHA-256 payload fingerprint, cache lock, transaction, Received/Processed/Failed/Spam state, CRM/Lead contracts, admin retry/spam bulk actions.
- **M-F Audience Lists + Segments**: Marketing Lists/members, dynamic SegmentRuleRegistry, preview count/sample, capability-aware rules, fixed tags/lists, `tag_from_value`, automatic Landing Page segment, bulk actions.
- **M-G UTM + Email Integration**: persisted UTM links/report and Marketing Campaign <-> Email Campaign bridge/navigation.

## Intentionally not changed

The accepted V3 implementations for Form/Landing HTML import, Form visual preview, Landing style preservation, form append-at-end behavior, hard delete, Copy Link, Campaign lifecycle, Form lifecycle, Landing lifecycle and Catalog validation remain intact. Critical files were checksum-compared against Hotfix V3.

Email Module source is not included in this patch. M-G is a Marketing-side bridge only and does not mutate SMTP, queue, suppression, Delivery Log, open/click tracking or Email Campaign sending behavior.

## Apply

Use the **delta archive** when the current project is exactly on Marketing Hotfix V3. Extract it into the Laravel project root, then run:

```bash
composer dump-autoload
php artisan migrate
php artisan optimize:clear
npm run build
composer show filament/filament
```

Filament must remain **v4.13.1**.

See `packages/dth/marketing/docs/README_MARKETING_ME_MG_UAT.md` for the mandatory PASS gates before M-H.
