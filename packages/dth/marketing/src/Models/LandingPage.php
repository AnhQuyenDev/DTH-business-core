<?php

namespace Dth\Marketing\Models;

use Dth\Marketing\Enums\LandingPageStatus;
use Dth\Marketing\Services\LandingPageCatalogService;
use Dth\Marketing\Services\LandingPageHtmlImportService;
use Dth\Marketing\Services\LandingPageLifecycleService;
use Dth\Marketing\Support\UniqueSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LandingPage extends Model
{
    protected $table = 'marketing_landing_pages';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => LandingPageStatus::class,
            'theme_tokens' => 'array',
            'package_references' => 'array',
            'catalog_snapshot' => 'array',
            'auto_tag_names' => 'array',
            'auto_list_names' => 'array',
            'auto_create_tags' => 'boolean',
            'auto_create_lists' => 'boolean',
            'auto_create_segment' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $page): void {
            $page->slug = UniqueSlug::forModel($page, (string) $page->name, $page->slug);
            $requestedStatus = $page->status instanceof \BackedEnum
                ? (string) $page->status->value
                : (string) ($page->status ?? LandingPageStatus::Draft->value);

            if ($requestedStatus !== LandingPageStatus::Draft->value) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'status' => 'New Landing Pages must start in Draft status.',
                ]);
            }

            $page->status = LandingPageStatus::Draft;
            $page->theme_tokens = array_replace(self::defaultTheme(), (array) ($page->theme_tokens ?? []));

            if (filled($page->html_body)) {
                $page->html_body = app(LandingPageHtmlImportService::class)->sanitize((string) $page->html_body);
            }

            if (filled($page->service_reference)) {
                $catalog = app(LandingPageCatalogService::class);
                $campaign = $page->marketing_campaign_id
                    ? MarketingCampaign::query()->find($page->marketing_campaign_id)
                    : null;
                $catalog->assertSelectionValid($campaign, $page->service_reference, (array) $page->package_references);
                $page->catalog_snapshot = $catalog->snapshot($page->service_reference, (array) $page->package_references);
            }
        });

        static::updating(function (self $page): void {
            $lifecycle = app(LandingPageLifecycleService::class);
            $originalStatus = (string) ($page->getRawOriginal('status') ?: LandingPageStatus::Draft->value);
            $nextStatus = $page->status instanceof \BackedEnum
                ? (string) $page->status->value
                : (string) $page->status;

            if ($page->isDirty('status')) {
                $lifecycle->assertTransition($originalStatus, $nextStatus);

                if ($nextStatus === LandingPageStatus::Published->value) {
                    // The caller may have changed campaign/form foreign keys in the
                    // same save. Never validate a relationship cached with old keys.
                    $page->unsetRelations();
                    $lifecycle->assertPublishReady($page);
                }
            }

            $lifecycle->assertChangesAllowed($originalStatus, array_keys($page->getDirty()));

            if ($page->isDirty('html_body') && filled($page->html_body)) {
                $page->html_body = app(LandingPageHtmlImportService::class)->sanitize((string) $page->html_body);
            }

            if ($page->isDirty('slug') || trim((string) $page->slug) === '') {
                $page->slug = UniqueSlug::forModel($page, (string) $page->name, $page->slug);
            }

            if (
                $page->isDirty('marketing_campaign_id')
                || $page->isDirty('service_reference')
                || $page->isDirty('package_references')
            ) {
                $catalog = app(LandingPageCatalogService::class);
                $campaign = $page->marketing_campaign_id
                    ? MarketingCampaign::query()->find($page->marketing_campaign_id)
                    : null;
                $catalog->assertSelectionValid($campaign, $page->service_reference, (array) $page->package_references);
                $page->catalog_snapshot = $catalog->snapshot($page->service_reference, (array) $page->package_references);
            }
        });
    }

    /** @return array<string, string> */
    public static function defaultTheme(): array
    {
        return [
            'primary' => '#2563eb',
            'primary_hover' => '#1d4ed8',
            'background' => '#f8fafc',
            'surface' => '#ffffff',
            'text' => '#0f172a',
            'muted_text' => '#64748b',
            'border' => '#cbd5e1',
            'danger' => '#dc2626',
            'radius' => '12px',
        ];
    }

    public function marketingCampaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'marketing_campaign_id');
    }

    public function personalFormTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'personal_form_template_id');
    }

    public function businessFormTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'business_form_template_id');
    }


    public function views(): HasMany
    {
        return $this->hasMany(LandingPageView::class, 'landing_page_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(LandingPageSubmission::class, 'landing_page_id');
    }

    public function utmUrls(): HasMany
    {
        return $this->hasMany(LandingPageUtmUrl::class, 'landing_page_id');
    }

    public function isPublished(): bool
    {
        return $this->status === LandingPageStatus::Published;
    }

    public function publicUrl(): string
    {
        return route('marketing.landing-pages.public.show', ['slug' => $this->slug]);
    }
}
