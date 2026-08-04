<?php

namespace App\Models\Marketing;

use App\Models\User;
use App\Services\Marketing\AuditLogService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'category',
        'subject',
        'preheader',
        'html_body',
        'text_body',
        'status',
        'created_by',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    protected static function booted(): void
    {
        static::created(function (self $template): void {
            app(AuditLogService::class)->log('template.created', $template, [], $template->attributesToArray());
        });

        static::updated(function (self $template): void {
            app(AuditLogService::class)->log('template.updated', $template, $template->getOriginal(), $template->getChanges());
        });

        static::deleted(function (self $template): void {
            app(AuditLogService::class)->log('template.deleted', $template, $template->getOriginal(), []);
        });
    }
}