<?php

namespace App\Models\Marketing;

use App\Enums\Marketing\FormFieldType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    protected $table = 'form_fields';

    protected $fillable = [
        'landing_form_template_id',
        'label',
        'field_key',
        'field_type',
        'placeholder',
        'options',
        'default_value',
        'is_required',
        'contact_mapping',
        'validation_rules',
        'tag_from_value',
        'sort_order',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'options'     => 'array',
            'is_required' => 'boolean',
            'tag_from_value' => 'boolean',
            'field_type'  => FormFieldType::class,
        ];
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'landing_form_template_id');
    }
}
