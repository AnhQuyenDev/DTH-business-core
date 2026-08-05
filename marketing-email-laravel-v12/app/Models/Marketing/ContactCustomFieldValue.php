<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactCustomFieldValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'custom_field_id',
        'value_text',
        'value_number',
        'value_date',
        'value_boolean',
        'value_json',
    ];

    protected function casts(): array
    {
        return [
            'value_number' => 'decimal:2',
            'value_date' => 'date',
            'value_boolean' => 'boolean',
            'value_json' => 'array',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function customField(): BelongsTo
    {
        return $this->belongsTo(CustomField::class);
    }
}
