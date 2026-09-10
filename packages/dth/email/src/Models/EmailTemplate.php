<?php

namespace Dth\Email\Models;

use Dth\Email\Enums\EmailTemplateStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class EmailTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'email_templates';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => EmailTemplateStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $template): void {
            if (blank($template->template_key)) {
                $base = Str::slug((string) $template->name, '.');
                $base = $base !== '' ? $base : 'template';
                $candidate = $base;
                $suffix = 2;

                while (self::query()->withTrashed()->where('template_key', $candidate)->exists()) {
                    $candidate = $base.'.'.$suffix;
                    $suffix++;
                }

                $template->template_key = $candidate;
            }
        });
    }

    public function setTemplateKeyAttribute(?string $value): void
    {
        if ($value === null || trim($value) === '') {
            $this->attributes['template_key'] = null;
            return;
        }

        $this->attributes['template_key'] = Str::of($value)
            ->lower()
            ->trim()
            ->replace(['/', '\\', ' '], '.')
            ->replaceMatches('/[^a-z0-9._-]+/', '-')
            ->replaceMatches('/[.]{2,}/', '.')
            ->trim('.-_')
            ->toString();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EmailTemplateCategory::class, 'category_id');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(EmailCampaign::class, 'email_template_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(EmailMessage::class, 'template_id');
    }
}
