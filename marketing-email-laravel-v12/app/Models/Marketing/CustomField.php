<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CustomField extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'key',
        'type',
        'options',
        'is_required',
        'is_filterable',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'options' => 'array',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $customField): void {
            if (blank($customField->key) && filled($customField->name)) {
                $customField->key = Str::slug($customField->name, '_');
            }
        });
    }

    public function values(): HasMany
    {
        return $this->hasMany(ContactCustomFieldValue::class);
    }
}
