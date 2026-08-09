<?php

namespace App\Models\Marketing;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Segment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'rules',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'rules' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $segment): void {
            if (blank($segment->slug) && filled($segment->name)) {
                $segment->slug = Str::slug($segment->name);
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
