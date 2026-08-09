<?php

namespace App\Models\Marketing;

use App\Enums\Marketing\LandingPageStatus;
use App\Models\Crm\LandingPageForm;
use App\Models\Sales\Service;
use App\Models\Sales\ServicePackage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class LandingPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'landing_form_template_id',
        'name',
        'slug',
        'page_title',
        'headline',
        'subheadline',
        'content',
        'cta_text',
        'html_body',
        'css_body',
        'theme_tokens',
        'thumbnail_path',
        'tracking_source',
        'campaign_id',
        'auto_tag_names',
        'auto_list_names',
        'auto_create_tags',
        'auto_create_lists',
        'auto_create_segment',
        'status',
        'published_at',
        'created_by',
        'marketing_campaign_id',
        'service_id',
    ];

    protected function casts(): array
    {
        return [
            'auto_tag_names' => 'array',
            'auto_list_names' => 'array',
            'auto_create_tags' => 'boolean',
            'auto_create_lists' => 'boolean',
            'auto_create_segment' => 'boolean',
            'theme_tokens' => 'array',
            'status' => LandingPageStatus::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $page): void {
            if (blank($page->slug) && filled($page->name)) {
                $page->slug = Str::slug($page->name);
            }
        });

        static::deleting(function (self $page): void {
            $page->submissions()->delete();
        });
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'landing_form_template_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function views(): HasMany
    {
        return $this->hasMany(LandingPageView::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(LandingPageSubmission::class);
    }

    public function forms(): HasMany
    {
        return $this->hasMany(LandingPageForm::class);
    }

    public function personalForm(): HasOne
    {
        return $this->hasOne(LandingPageForm::class)->where('form_type', 'personal');
    }

    public function businessForm(): HasOne
    {
        return $this->hasOne(LandingPageForm::class)->where('form_type', 'business');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function utmUrls(): HasMany
    {
        return $this->hasMany(LandingPageUtmUrl::class);
    }

    public function defaultCampaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class, 'campaign_id');
    }

    public function marketingCampaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'marketing_campaign_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function servicePackages(): BelongsToMany
    {
        return $this->belongsToMany(
            ServicePackage::class,
            'landing_page_service_package'
        )->withTimestamps();
    }

    public function isPublished(): bool
    {
        return $this->status === LandingPageStatus::Published;
    }
}
