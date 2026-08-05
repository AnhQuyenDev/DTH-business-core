<?php

namespace App\Models\Marketing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SendingDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain',
        'status',
        'spf_status',
        'dkim_status',
        'dmarc_status',
        'notes',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }
}
