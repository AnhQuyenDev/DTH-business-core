<?php

namespace Dth\Commercial\Models;

use Dth\Commercial\Enums\OpportunityStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opportunity extends Model
{
    use SoftDeletes;

    protected $table = 'commercial_opportunities';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'stage' => OpportunityStage::class,
            'estimated_value' => 'decimal:2',
            'probability' => 'integer',
            'expected_close_date' => 'date',
            'won_at' => 'datetime',
            'lost_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $opportunity): void {
            $opportunity->currency = strtoupper(trim((string) ($opportunity->currency ?: 'VND')));
        });
    }

    /** Legacy primary-service snapshot retained for CRM handoff compatibility. */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OpportunityItem::class, 'opportunity_id')->orderBy('sort_order')->orderBy('id');
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(OpportunityInteraction::class, 'opportunity_id')->orderByDesc('interaction_at');
    }

    public function recalculateEstimatedValue(bool $zeroWhenEmpty = false): void
    {
        if (! $this->exists) {
            return;
        }

        $items = $this->items();
        if (! $items->exists() && ! $zeroWhenEmpty) {
            return;
        }

        $this->updateQuietly([
            'estimated_value' => round((float) $items->sum('total'), 2),
        ]);
    }

    public function isTerminal(): bool
    {
        $stage = $this->stage instanceof OpportunityStage ? $this->stage : OpportunityStage::from((string) $this->stage);
        return $stage->isTerminal();
    }
}
