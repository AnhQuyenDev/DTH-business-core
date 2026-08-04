<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VnBank extends Model
{
    protected $fillable = [
        'code',
        'bin',
        'short_name',
        'name',
        'swift_code',
        'logo',
    ];

    public $timestamps = true;
}
