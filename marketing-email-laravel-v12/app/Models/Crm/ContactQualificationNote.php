<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactQualificationNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_qualification_id',
        'staff_id',
        'note_type',
        'content',
        'outcome',
        'contacted_at',
        'next_follow_up_at',
    ];

    protected function casts(): array
    {
        return [
            'contacted_at' => 'datetime',
            'next_follow_up_at' => 'datetime',
        ];
    }

    public function qualification(): BelongsTo
    {
        return $this->belongsTo(ContactQualification::class, 'contact_qualification_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
