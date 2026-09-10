# Step B - Email Analytics Data Layer

## Muc tieu

Step B tao nguon so lieu thong nhat cho cac buoc tiep theo: Global Email Dashboard, Campaign Report, Export, Statistical Insight va Local AI. Buoc nay chua them Dashboard UI moi.

Nen tang muc tieu: Laravel 12 + Filament 4.13.1. Step B duoc xay tren Step A (Root i18n Foundation) da PASS.

## Thanh phan da bo sung

### 1. AnalyticsRange / EmailAnalyticsFilters

- Khoang thoi gian dung chung.
- Tu dong tao ky so sanh lien truoc co do dai bang ky hien tai.
- Filter theo Sending Account, Campaign, Message Status va Campaign Status.
- Gioi han range de tranh query dashboard qua lon.
- Ho tro granularity Day va Hour cho trend.

### 2. EmailAnalyticsService

API du lieu trung tam:

- `overview()`
- `trend()`
- `engagementFunnel()`
- `campaignPerformance()`
- `topCampaigns()`
- `topLinks()`
- `sendingAccountPerformance()`
- `systemHealth()`

`overview()` dung send-cohort theo `email_campaigns.started_at` de ty le Open/Click/Unsubscribe co denominator nhat quan. `trend()` dung thoi diem activity thuc te (`sent_at`, `occurred_at`, `failed_at`) de ve bieu do theo thoi gian.

### 3. Metric definitions

- Campaigns
- Recipients
- Messages
- Sent
- Unique Opens
- Total Opens
- Unique Clicks
- Total Clicks
- Open Rate
- Click Rate (CTR)
- Click-to-Open Rate (CTOR)
- Unsubscribe Rate
- Failure Rate
- Delivered / Delivery Rate
- Bounced / Bounce Rate
- Complained / Complaint Rate

Unsubscribe cua campaign duoc lay tu `email_events` de giu dung lich su, khong bi xoa nguoc khi recipient duoc Resubscribe ve sau.

### 4. TransportCapabilities

SMTP hien tai duoc khai bao:

- Sent: supported
- Open: supported (tracking pixel)
- Click: supported
- Unsubscribe: supported
- Delivery confirmation: unsupported
- Bounce event: unsupported
- Complaint event: unsupported

Vi vay `deliveryRate`, `bounceRate`, `complaintRate` la `null` / N/A thay vi 0%. Campaign Analytics Widget hien tai cung da duoc sua de khong hien Delivery Rate = 0% sai nghiep vu.

Future provider co the khai bao capability rieng trong `config/email.php` ma khong can viet lai dashboard.

### 5. Comparison period

`overview()` tra ve:

- current snapshot
- previous snapshot
- metric deltas
- direction: up / down / flat / unavailable

Day la nen cho KPI comparison va Statistical Insight o cac buoc sau.

### 6. Cache

Analytics query duoc cache ngan han. Mac dinh 120 giay:

```env
DTH_EMAIL_ANALYTICS_CACHE_TTL=120
```

Dat `0` khi debug/UAT neu can thay thay doi ngay lap tuc.

### 7. Database indexes

Migration moi:

`packages/dth/email/database/migrations/2026_09_10_000016_add_email_analytics_indexes.php`

Bo sung composite index cho:

- campaign started/status/account
- recipient campaign/status/sent/open/click
- message account/status/sent/failed
- event type/occurred_at
- tracked link message/last_clicked_at
- suppression reason/released_at

Step B chua tao bang aggregate daily. Chi khi volume sau nay lam direct aggregate khong dap ung SLA moi can `email_analytics_daily`.

### 8. System health contract

`systemHealth()` hien co snapshot co ban cho account/domain/jobs.

Scheduler/Queue worker heartbeat hien de `unknown` (hoac `not_required` neu queue=sync). Step I moi bo sung heartbeat thuc va canh bao Healthy/Offline. Cach nay tranh gia lap health khi chua co bang chung runtime.

### 9. Read-only sanity command

Sau khi apply co the kiem tra tren database that:

```bash
php artisan email:analytics:check --days=30
```

Filter tuy chon:

```bash
php artisan email:analytics:check --days=30 --account=1
php artisan email:analytics:check --days=30 --campaign=10
php artisan email:analytics:check --days=7 --no-compare
```

Command chi doc du lieu, khong sua campaign/message/event.

## Apply

Giai nen ZIP Step B de len root project da apply Step A, sau do:

```bash
composer dump-autoload
php artisan migrate
php artisan optimize:clear
```

Neu dang UAT du lieu realtime, co the them vao `.env`:

```env
DTH_EMAIL_ANALYTICS_CACHE_TTL=0
```

roi:

```bash
php artisan optimize:clear
```

## Kiem thu

### Automated tests

```bash
cd packages/dth/email
php vendor/bin/phpunit
```

Test moi:

- `tests/Unit/AnalyticsRangeTest.php`
- `tests/Unit/TransportCapabilityServiceTest.php`
- `tests/Feature/EmailAnalyticsServiceTest.php`
- SchemaTest duoc bo sung check analytics indexes.

### UAT tren database that

1. Chay:

```bash
php artisan email:analytics:check --days=30
```

2. Kiem tra:
   - Campaigns / Recipients / Sent khop du lieu trong 30 ngay.
   - Unique Opens <= Sent trong mot cohort thong thuong.
   - Total Opens co the lon hon Unique Opens.
   - Unique Clicks <= Unique Opens thong thuong.
   - Total Clicks co the lon hon Unique Clicks.
   - Delivery Rate hien `N/A` khi chi dung SMTP.
   - Previous period xuat hien neu khong dung `--no-compare`.

3. Vao View Campaign cu:
   - Card Delivered phai hien `N/A` thay vi `0` neu Sending Account la SMTP.
   - Open/Click/Unsubscribe hien nhu truoc.
   - Mau badge/status khong thay doi.

4. Chay voi campaign cu the:

```bash
php artisan email:analytics:check --days=30 --campaign=<ID>
```

5. Neu can xem metric ngay sau khi Open/Click/Unsubscribe, dat cache TTL = 0 trong UAT.

## Definition of Done Step B

- Migration analytics index chay thanh cong.
- `email:analytics:check` chay khong loi tren DB that.
- Overview/current/previous/delta tra so lieu hop ly.
- Trend khong bi thieu bucket ngay khong co activity.
- Unique vs Total Open/Click duoc tach ro.
- CTOR tinh dung.
- SMTP Delivery/Bounce/Complaint khong bi hien nhu metric 0% that.
- Existing Campaign View khong regression Open/Click/Unsubscribe.
- Automated tests PASS trong moi truong project co PHP extensions day du.

Sau khi Step B PASS moi tien hanh Step C - Global Email Dashboard.
