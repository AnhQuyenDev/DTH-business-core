<?php

namespace Dth\Marketing\Models;

use Dth\Marketing\Enums\FormAudienceType;
use Dth\Marketing\Enums\FormTemplateStatus;
use Dth\Marketing\Enums\LandingPageStatus;
use Dth\Marketing\Services\FormTemplateLifecycleService;
use Dth\Marketing\Services\HtmlFormParser;
use Dth\Marketing\Support\UniqueSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class FormTemplate extends Model
{
    protected $table = 'marketing_form_templates';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'audience_type' => FormAudienceType::class,
            'status' => FormTemplateStatus::class,
            'version' => 'integer',
            'schema' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $template): void {
            $template->slug = UniqueSlug::forModel(
                $template,
                (string) $template->name,
                $template->slug,
            );
            $requestedStatus = $template->status instanceof \BackedEnum
                ? (string) $template->status->value
                : (string) ($template->status ?? FormTemplateStatus::Draft->value);

            if ($requestedStatus !== FormTemplateStatus::Draft->value) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'status' => 'New Form Templates must start in Draft status.',
                ]);
            }

            $template->status = FormTemplateStatus::Draft;
            $template->version = max(1, (int) ($template->version ?: 1));

            if (filled($template->html_body)) {
                $template->html_body = app(HtmlFormParser::class)->sanitizeTemplateHtml((string) $template->html_body);
            }
        });

        static::deleting(function (self $template): void {
            if ($template->isUsedByPublishedLandingPage()) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'form_template' => 'Unpublish or reassign Landing Pages before deleting a Form Template that is currently in public use.',
                ]);
            }
        });

        static::updating(function (self $template): void {
            $lifecycle = app(FormTemplateLifecycleService::class);
            $originalStatus = (string) ($template->getRawOriginal('status') ?: FormTemplateStatus::Draft->value);
            $nextStatus = $template->status instanceof \BackedEnum
                ? (string) $template->status->value
                : (string) $template->status;

            if ($template->isDirty('status')) {
                $lifecycle->assertTransition($originalStatus, $nextStatus);

                if ($nextStatus === FormTemplateStatus::Active->value) {
                    $lifecycle->assertActivationReady($template);
                }
            }

            $lifecycle->assertChangesAllowed($originalStatus, array_keys($template->getDirty()));

            if ($template->isDirty('html_body') && filled($template->html_body)) {
                $template->html_body = app(HtmlFormParser::class)->sanitizeTemplateHtml((string) $template->html_body);
            }

            if ($template->isDirty('slug') || trim((string) $template->slug) === '') {
                $template->slug = UniqueSlug::forModel(
                    $template,
                    (string) $template->name,
                    $template->slug,
                );
            }
        });
    }

    public function isUsedByPublishedLandingPage(): bool
    {
        if (! Schema::hasTable('marketing_landing_pages')) {
            return false;
        }

        return LandingPage::query()
            ->where('status', LandingPageStatus::Published->value)
            ->where(function ($query): void {
                $query->where('personal_form_template_id', $this->getKey())
                    ->orWhere('business_form_template_id', $this->getKey());
            })
            ->exists();
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class, 'form_template_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
