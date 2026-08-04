<?php

namespace App\Models\Crm;

use App\Models\Marketing\FormTemplate;
use App\Models\Marketing\LandingPage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LandingPageForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'landing_page_id',
        'form_template_id',
        'form_type',
        'display_mode',
        'position_key',
        'is_default',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }
}
