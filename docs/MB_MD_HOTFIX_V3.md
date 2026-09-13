# Marketing M-B to M-D Hotfix V3

This hotfix is based on the latest `marketing.zip` baseline supplied for UAT and re-checks the legacy Marketing implementation for the M-B to M-D behavior.

## Scope

- M-B Marketing Campaign: no business-flow changes in this hotfix.
- M-C Form Template: hard delete, visual HTML import preservation, isolated full-document preview, Active editing behavior.
- M-D Landing Page: form extraction with presentation context, append attached forms at the end of the page, preserve form presentation assets, improved Copy Link modal.
- M-E public submission remains out of scope. Preview/public forms stay disabled until the M-E submission pipeline is enabled.
- No Email package files are included or changed.

## Root causes fixed

1. Form Template used Laravel SoftDeletes. Old deleted rows remained in the database and could reappear when query/model behavior changed.
2. HTML import reduced a form to a generic field shell. Original wrappers, layout classes, input classes and presentation resources were therefore lost.
3. Preview was rendered inside the Filament modal. Imported page CSS/Tailwind did not have a reliable isolated document context and could conflict with Filament CSS.
4. Landing import removed only the form or disconnected it from the surrounding visual container. Landing rendering then used generic wrappers/two-column placement, causing style loss.
5. Copy Link relied on a small modal layout and did not provide a robust full-width, wrapping URL surface.

## New behavior

### Hard delete

Migration `2026_09_12_000007_make_form_template_deletes_hard.php`:

- Permanently removes rows that were already soft-deleted.
- Drops `marketing_form_templates.deleted_at`.
- FormTemplate no longer uses `SoftDeletes`.
- Existing guard remains: a Form Template referenced by a Published Landing Page cannot be deleted until the page is unpublished/reassigned.

### Form Template HTML import

Imported HTML now keeps the original visual hierarchy. Supported field controls are replaced in place by stable control tokens while these presentation details are retained:

- enclosing div/section/card/grid markup;
- form and field CSS classes;
- safe inline styles;
- style blocks;
- stylesheet links;
- trusted presentation scripts such as Tailwind CDN/config;
- select placeholder text;
- safe control attributes.

Preview now opens as a full HTML document in a new tab. This prevents Filament admin CSS from becoming the rendering environment for imported form CSS.

For Active Form Templates, content/fields remain editable. Audience type is intentionally locked after activation because it defines whether a template can occupy the Personal or Business slot. Archived templates remain read-only.

### Landing Page form extraction and rendering

When an imported Landing Page contains a form:

1. The nearest meaningful visual form container is captured along with presentation assets.
2. A Draft Form Template is created from that captured source and fields are auto-mapped.
3. The captured form container is removed from Landing Page HTML.
4. The first Personal/Business Form Template is attached to the matching Landing Page slot.
5. Rendering appends selected Form Templates at the end of the Landing Page, before `</body>` for full HTML documents.
6. Forms are stacked vertically at full width instead of being forced into a generic side-by-side grid.

The form remains Draft after extraction and must be reviewed/activated before the Landing Page can be published.

### Copy Link

The modal is widened and the full URL is shown in a wrapping monospace block with a dedicated Copy button and copied feedback.

## Important data note

A Form Template imported by the previous broken importer may already have lost its original wrapper/CSS information before it was stored. Code cannot reconstruct markup that is no longer in the database. After applying this hotfix, re-import the original HTML for affected Form Templates (or re-import the Landing Page source) to get the full visual result.

## Apply

If replacing the package with the full archive, back up `packages/dth/marketing`, then replace its contents with this package.

Run from the application root:

```bash
composer dump-autoload
php artisan migrate
php artisan optimize:clear
npm run build
composer show filament/filament
```

Expected Filament baseline: 4.13.1.

## UAT gate before M-E

1. Delete a Draft/Active Form Template that is not used by a Published Landing Page. Confirm its row is physically absent from `marketing_form_templates` and its `marketing_form_fields` rows are cascaded.
2. Confirm a Form Template used by a Published Landing Page cannot be deleted.
3. Re-import the original styled Personal form HTML. Preview must open in a new tab and visually retain its original card, spacing, grid, input/select and submit-button styles.
4. Activate the imported Form Template. Edit label, placeholder, required, options, mapping or submit text, save and reload. Changes must persist and Preview must reflect the updated controls while retaining the imported visual layout.
5. Confirm audience type cannot change after activation, while the other editable fields still can. Archived remains read-only.
6. Import a Landing Page HTML file that contains a styled form. Confirm the old form visual section is removed from the Landing HTML and a Draft Form Template is created/attached.
7. Activate the generated Form Template and Preview the Landing Page. The form must render at the end of the page with its preserved presentation instead of the previous plain two-column rendering.
8. If Personal and Business forms are attached, verify they are stacked vertically and each preserves its own presentation.
9. Publish the Landing Page and verify `/lp/{slug}` renders only when Published. M-D forms remain non-submitting until M-E.
10. Open Copy Link and verify the complete URL is visible/wrapped and the Copy button copies the exact URL.
11. Run the Marketing test suite and the existing Email regression/smoke suite in the real project runtime.
12. Re-check Email Dashboard, Campaign, Template, Sending Account/Domain, Delivery Log, suppression and open/click tracking. This hotfix contains no Email files.

## Environment limitation

The build sandbox does not contain the host Laravel vendor tree and the PHP CLI is missing extensions required for the real project test suite, so full PHPUnit/HTTP/Filament integration tests must be executed in the application environment before M-E starts.
