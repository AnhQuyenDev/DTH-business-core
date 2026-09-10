<?php

namespace Dth\Email\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EmailTemplateCategory extends Model
{
    protected $table = 'email_template_categories';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $category): void {
            if (blank($category->slug)) {
                $base = Str::slug((string) $category->name);
                $base = $base !== '' ? $base : 'category';
                $candidate = $base;
                $suffix = 2;

                while (self::query()->where('slug', $candidate)->exists()) {
                    $candidate = $base.'-'.$suffix;
                    $suffix++;
                }

                $category->slug = $candidate;
            }
        });
    }

    public function setSlugAttribute(?string $value): void
    {
        $this->attributes['slug'] = blank($value) ? null : Str::slug((string) $value);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(EmailTemplate::class, 'category_id');
    }
}
