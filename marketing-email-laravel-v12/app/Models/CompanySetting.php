<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $fillable = [
        'company_name',
        'tax_code',
        'address',
        'phone',
        'email',
        'website',
        'logo_path',
        'vietqr_client_id',
        'vietqr_api_key',
    ];

    public static function firstOrCreateDefault(): self
    {
        return static::query()->firstOrCreate(['id' => 1], [
            'company_name' => env('APP_NAME', 'Laravel'),
        ]);
    }
}
