<?php

use App\Models\CompanySetting;

if (! function_exists('company_name')) {
    function company_name(): string
    {
        static $name = null;

        if ($name === null) {
            $name = CompanySetting::firstOrCreateDefault()->company_name ?: config('app.name');
        }

        return $name;
    }
}
