<?php

namespace App\Listeners;

use App\Models\Marketing\AuditLog;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        $cacheKey = 'login_logged_'.$event->user->id;
        if (Cache::has($cacheKey)) {
            return;
        }
        Cache::put($cacheKey, true, 60);

        AuditLog::query()->create([
            'user_id' => $event->user->id,
            'action' => 'login',
            'auditable_type' => $event->user::class,
            'auditable_id' => $event->user->getKey(),
            'ip_address' => Request::ip(),
            'user_agent' => substr((string) Request::userAgent(), 0, 1000),
            'created_at' => now(),
        ]);
    }
}
