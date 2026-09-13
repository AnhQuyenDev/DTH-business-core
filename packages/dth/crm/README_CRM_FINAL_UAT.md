# CRM Final UAT Gate

1. **Install / regression** — Filament remains v4.13.1. Email and Marketing regression suites pass. CRM ZIP contains no Email/Marketing implementation files.
2. **Contacts** — create/update Personal and Business contacts; normalized email/phone dedupe; arbitrary Marketing semantic keys still resolve before CRM boundary; tags merge without duplication.
3. **Companies** — exact tax code match; business email-domain match; normalized-name candidate; conflicting tax codes never auto-merge; Company↔Contact primary link persists.
4. **Lead intake** — same submission reference is idempotent; recent same contact/service is marked duplicate; source, attribution, form answers and service context remain snapshot data.
5. **Distribution** — only active/available staff under capacity receive leads; least-loaded distribution works; accept/reject produces history; rejected Lead returns to New.
6. **Qualification** — invalid transitions are backend-blocked; Follow-up requires next date; Qualified requires service interest; terminal statuses cannot reopen without explicit business rule.
7. **Sales boundary** — when Sales adapter absent, Qualified Lead remains valid without crashing; when available, handoff returns external opportunity reference and marks lead converted-to-opportunity.
8. **Customer** — convert Lead idempotently; personal/business identity preserved; owner assignment created; interaction timeline works; customer distribution batch completes with item history.
9. **Filament** — CRM Overview + 8 resources render; CRUD/search/filter where applicable; bulk delete checks individual policy records; no Filament v3 action namespaces.
10. **Security** — auto authorization preserves compatibility; strict mode honors `crm.view`, `crm.manage`, `crm.distribute`, `crm.qualify`, `crm.convert`, `crm.view-reports` if gates are defined.
11. **Database** — all `crm_*` migrations run cleanly on a fresh DB and rollback cleanly; FK/index constraints are valid.
12. **Commands** — `php artisan crm:health` returns PASS for required tables.
13. **Automated** — run `php artisan test packages/dth/crm/tests` plus Email and Marketing regression suites. All must PASS before CRM is accepted.
