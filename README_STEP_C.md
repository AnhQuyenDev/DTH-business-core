# Step C - Global Email Dashboard

## Muc tieu

Step C bo sung Dashboard tong quan rieng cho Email Module tren nen Step B Email Analytics Data Layer. Dashboard chi doc du lieu, khong thay doi logic gui mail/campaign/tracking.

Nen tang muc tieu: Laravel 12 + Filament 4.13.1.

## Thanh phan da bo sung

### 1. Email Dashboard page

Navigation:

`Email -> Dashboard`

Route Filament:

`/admin/email-dashboard`

Dashboard dung native Filament Dashboard/Widget, khong nhung React va khong them frontend framework moi.

### 2. Global filters

Nut `Filters` o header mo slide-over va chi query lai dashboard khi bam Apply:

- Start date
- End date
- Sending account
- Campaign status
- Compare with previous period

Filter duoc persist theo session cua Filament. End date duoc validate khong nho hon Start date. Neu session cu/invalid, resolver fallback an toan ve 30 ngay mac dinh.

### 3. KPI cards

8 card:

- Campaigns
- Recipients
- Sent
- Open rate
- Click rate
- Click-to-open rate (CTOR)
- Unsubscribe rate
- Failure rate

Card co delta so voi ky truoc. Metric xau khi tang (unsubscribe/failure) duoc danh gia nguoc chieu de mau mo ta khong gay hieu nham.

### 4. Charts

- Email performance over time: Sent / Unique Opens / Unique Clicks
- Engagement funnel: Recipients -> Sent -> Opened -> Clicked
- Top campaigns: Open Rate vs Click Rate
- Top clicked links: Total Clicks vs Unique Clickers
- Sending account performance: Open / Click / Failure Rate

Cac chart xep hang (Top Campaigns / Top Links / Sending Account Performance) co empty-state ro rang khi filter khong co du lieu.

Neu range <= 2 ngay, trend tu dong dung bucket theo gio. Range dai hon dung bucket theo ngay.

### 5. Email infrastructure snapshot

Card co ban:

- Active sending accounts / total
- Verified sending domains / total
- Pending email jobs
- Failed queue jobs

Step C khong gia lap Scheduler/Worker heartbeat. Heartbeat that se duoc them o Step I.

### 6. i18n

Toan bo label/description cua Dashboard su dung Root i18n Foundation Step A.

- English: canonical default trong module
- Vietnamese: root `config/localization.php`

Khong dich raw DB status. Status raw van de danh cho logic va StatusColor.

### 7. Livewire stability

Tat lazy loading va polling tren dashboard widgets de tranh regression Livewire 419 da gap trong UAT truoc do. Du lieu chi reload khi page/filter Livewire re-render.

## File chinh

New:

- `packages/dth/email/src/Filament/Pages/EmailDashboard.php`
- `packages/dth/email/src/Services/EmailDashboardFilterResolver.php`
- `packages/dth/email/src/Filament/Widgets/Concerns/UsesEmailDashboardFilters.php`
- `packages/dth/email/src/Filament/Widgets/Dashboard/Concerns/FormatsDashboardMetrics.php`
- `packages/dth/email/src/Filament/Widgets/Dashboard/EmailOverviewStats.php`
- `packages/dth/email/src/Filament/Widgets/Dashboard/EmailPerformanceTrendChart.php`
- `packages/dth/email/src/Filament/Widgets/Dashboard/EmailEngagementFunnelChart.php`
- `packages/dth/email/src/Filament/Widgets/Dashboard/TopCampaignsChart.php`
- `packages/dth/email/src/Filament/Widgets/Dashboard/TopLinksChart.php`
- `packages/dth/email/src/Filament/Widgets/Dashboard/SendingAccountPerformanceChart.php`
- `packages/dth/email/src/Filament/Widgets/Dashboard/EmailInfrastructureOverview.php`
- `packages/dth/email/tests/Unit/EmailDashboardFilterResolverTest.php`

Edited:

- `packages/dth/email/src/Filament/EmailPlugin.php`
- `packages/dth/email/src/EmailServiceProvider.php`
- `config/localization.php`

## Apply

Giai nen ZIP Step C de len project da PASS Step B:

```bash
composer dump-autoload
php artisan optimize:clear
```

Step C khong co migration moi.

Neu UAT can so lieu cap nhat ngay:

```env
DTH_EMAIL_ANALYTICS_CACHE_TTL=0
```

sau do:

```bash
php artisan optimize:clear
```

## UAT

1. Vao `Email -> Dashboard`.
2. Xac nhan 8 KPI cards render, khong co Livewire 419.
3. Xac nhan cac chart render tren desktop va khong vo responsive khi sidebar collapse.
4. Bam Filters:
   - doi date range
   - filter theo Sending Account
   - filter theo Campaign Status
   - bat/tat Compare previous period
5. So lieu KPI/chart phai thay doi dong bo cung mot filter.
6. Chuyen EN <-> VI:
   - navigation Dashboard
   - title/subheading
   - filter labels
   - KPI labels
   - chart headings/descriptions/legends
   deu phai doi ngon ngu.
7. Khi Compare previous period tat, KPI delta hien `No comparison available` / `Chua co du lieu so sanh`.
8. SMTP khong duoc tu dong hien Delivery Rate 0% tren dashboard; metric delivery khong nam trong KPI Step C.
9. Email Infrastructure:
   - account/domain counts khop cac Resource
   - queue sync co the hien Pending = 0
   - Step C chua ket luan worker/scheduler healthy/offline.
10. Existing Send/Open/Click/Unsubscribe flow phai khong regression.

## Automated test

```bash
cd packages/dth/email
php vendor/bin/phpunit --filter=EmailDashboardFilterResolverTest
```

## Definition of Done Step C

- Dashboard xuat hien dau nhom Email.
- Global filters cap nhat dong bo tat ca widgets.
- KPI/delta dung Step B metric definitions.
- Trend/funnel/top campaigns/top links/account performance render dung.
- Infrastructure snapshot khong gia lap heartbeat.
- EN/VI PASS.
- Khong Livewire 419.
- Khong regression Email Campaign UAT.

Sau khi Step C PASS moi sang Step D - Campaign Report Page.
