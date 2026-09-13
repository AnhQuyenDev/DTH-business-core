<?php

namespace Dth\Marketing\Models;

use Dth\Marketing\Enums\FormFieldType;
use Dth\Marketing\Enums\SemanticFieldRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormField extends Model
{
    protected $table = 'marketing_form_fields';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'field_type' => FormFieldType::class,
            'options' => 'array',
            'is_required' => 'boolean',
            'tag_from_value' => 'boolean',
            'semantic_role' => SemanticFieldRole::class,
            'semantic_confidence' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function formTemplate(): BelongsTo
    {
        return $this->belongsTo(FormTemplate::class, 'form_template_id');
    }
}
