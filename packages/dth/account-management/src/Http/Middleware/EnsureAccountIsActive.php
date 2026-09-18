<?php
namespace Dth\AccountManagement\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('dth-account-management.enabled', true)) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user || ! Schema::hasColumn($user->getTable(), 'account_status')) return $next($request);

        $record = DB::table($user->getTable())->where($user->getAuthIdentifierName(), $user->getAuthIdentifier())->first(['account_status','locked_until']);
        $locked = $record?->locked_until && now()->lt($record->locked_until);
        if (! $record || (string) $record->account_status !== 'active' || $locked) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect('/admin/login')->withErrors(['email' => 'Tài khoản đang bị vô hiệu hóa hoặc tạm khóa.']);
        }
        return $next($request);
    }
}
