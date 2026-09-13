# DTH Marketing M-E -> M-G - Apply & UAT

Baseline expected: **Marketing M-B -> M-D Hotfix V3**.
Target: **PHP 8.2+ / Laravel 12 / Filament 4.13.1**.

The recommended delta archive contains only Marketing files changed/new after Hotfix V3. It does not contain `packages/dth/email` and does not replace Email Module code.

## Apply

1. Back up the database.
2. Extract the ZIP into the Laravel project root.
3. Run:

```bash
composer dump-autoload
php artisan migrate
php artisan optimize:clear
npm run build
composer show filament/filament
```

Expected Filament baseline: **v4.13.1**.

Package-standalone tests use Orchestra Testbench. If package dev dependencies are installed:

```bash
cd packages/dth/marketing
composer install
vendor/bin/phpunit
```

If the host project already provides Orchestra Testbench, the equivalent host test command is acceptable. Browser/UAT on the actual application remains mandatory.

---

# Gate 0 - No regression M-B -> M-D / Email

All items below must PASS before evaluating M-E:

- Marketing Campaign CRUD/lifecycle still behaves exactly as the accepted M-B baseline.
- Form Template imported HTML Preview still keeps the file's original CSS/layout/style.
- Active Form Template remains editable according to the accepted M-C behavior.
- Form Template delete remains **hard delete** and Published Landing usage guard still works.
- Landing Page imported HTML Preview still keeps the original document styles.
- Extracted Personal/Business Forms are still appended to the bottom of Landing Page and keep their own visual styling.
- Landing Page Publish/Unpublish/Archive/Preview/Public Link remains unchanged.
- Copy Link still shows the full URL and copies the complete URL.
- Existing M-B -> M-D tables still expose their accepted bulk actions.
- Email Dashboard, Email Campaign, Email Template, Sending Account/Domain, Delivery Log, suppression and open/click tracking show no regression.
- No Email Module source file is modified by this patch.

Critical V3 import/preview/lifecycle files and migrations `000001..000007` were checksum-verified unchanged in the build.

---

# Gate M-E - Public Submission Pipeline

## M-E.1 View tracking + attribution

- Open a **Published** Landing Page with e.g. `?utm_source=google&utm_medium=cpc&utm_campaign=test`.
- Page must render successfully with the same visual presentation as V3.
- `marketing_landing_page_views` must receive a row with Landing Page, Marketing Campaign, UTM, IP/referrer metadata as applicable.
- Draft/Archived Landing Page continues to return 404 publicly.

## M-E.2 Public form submission

- Public Personal and Business controls must be enabled; Preview controls remain disabled.
- Form POST goes to `/lp/{slug}/submit` and includes CSRF, `submission_type` and `_submission_token`.
- Dynamic required/email/select/checkbox validation must follow Form Template field configuration.
- Successful submission creates `marketing_landing_page_submissions` and ends at `processed` when installed providers complete normally.
- Form Template `redirect_url` works only after a processed submission; otherwise success goes to thank-you.
- Thank-you is reachable only while the Landing Page is Published.

## M-E.3 Duplicate/idempotency

- Double-submit the same token + same data: only **one** submission row exists.
- Reuse the same token with different data: backend rejects the request.
- Rapid same-payload submission without a token is protected by payload fingerprint window.
- Concurrent duplicate requests do not create duplicate rows.

## M-E.4 Failure / spam

- Populate the honeypot `_dth_website`: result becomes `spam` and CRM/Lead work is not performed.
- Force a provider exception: submission remains in DB as `failed` with `failure_reason`; public UI must not show a success confirmation.
- Admin Retry can reprocess Failed/Received rows.
- Admin Mark Spam works on one row and via bulk action.

## M-E.5 CRM capability boundary

With CRM adapters absent:

- submission still persists/processes without a fatal error;
- Contact/Lead reference fields remain N/A/null;
- no direct CRM model dependency exists.

With adapters enabled:

- normalized email/phone and mapped form answers reach `AudienceProvider` / `LeadProvider`;
- returned Contact/Lead references are stored;
- Contact action is Created/Updated/Skipped according to adapter response.

**M-E PASS requires every item above.**

---

# Gate M-F - Lists, Segments, Automation

## M-F.1 Marketing Lists

- Create/Edit/Delete Marketing List works.
- Add manual member with email/phone/contact reference works and member key stays stable.
- List member Subscribe/Unsubscribe/Delete row actions work.
- Member bulk Subscribe/Unsubscribe/Delete works.
- List bulk Activate/Archive/Delete works.

## M-F.2 Submission automation

Test Landing Page and Form Template automation independently and together:

- fixed tags are persisted on the submission;
- when AudienceProvider is available, tags are forwarded through the contract;
- `tag_from_value` on a Form field adds the actual submitted value as a tag;
- configured Marketing List is created/reused and member is upserted without duplication;
- auto segment creates/reuses one automatic Landing Page segment.

## M-F.3 Segments

Create and preview these supported rules:

- created_within_days
- created_between
- has_tag
- in_list
- customer_type
- service_interest
- source
- utm_source
- utm_campaign
- landing_page

`lead_status` must appear **only** when LeadProvider is available.

For each rule:

- Preview Count is correct.
- Preview Sample is consistent with Count.
- Multiple conditions are combined using AND.
- Unsupported rule/operator is rejected by backend and cannot be silently saved/ignored.
- Automatic segments cannot be manually edited.
- Segment bulk Activate/Archive/Delete works.

**M-F PASS requires every item above.**

---

# Gate M-G - UTM + Email Bridge

## M-G.1 UTM

- On Published Landing Page choose Generate UTM.
- `source` and `medium` are required.
- Campaign falls back to Marketing Campaign slug/name when omitted.
- Generated URL contains correct RFC3986 query values.
- A row is persisted in `marketing_landing_page_utm_urls` with Landing Page + Marketing Campaign relation.
- Open UTM Report after generating traffic/submissions: views/submissions/conversion groups must reflect captured UTM source/medium/campaign.
- Full multi-campaign analytics is **not** required here; that belongs to M-H.

## M-G.2 Email integration

- Marketing Campaign opens Email Campaign linkage UI without changing campaign lifecycle.
- If DTH Email exposes source linkage, Refresh from Email finds matching Email Campaigns.
- Manual Email Campaign reference can be added when automatic source linkage is not available.
- Open Email navigates to Email Module when bridge provides an admin URL.
- Link rows can be bulk-deleted only while the Marketing Campaign is non-terminal.
- Completed/Cancelled Marketing Campaign keeps historical scope protected.

## M-G.3 Mandatory Email regression

Re-test existing Email behavior:

- create/edit Email Campaign according to current Email rules;
- sending account/domain selection;
- queue/send path;
- suppression;
- delivery log;
- open/click tracking;
- Email analytics already accepted in the Email module.

Marketing M-G must **not** write Email SMTP, queue, suppression, delivery or tracking tables and must not modify Email package code.

**M-G PASS requires every item above.**

---

# Ready for M-H only when

Proceed to **M-H Marketing Analytics & Reporting** only after Gate 0 + M-E + M-F + M-G are all PASS in the real application environment.
