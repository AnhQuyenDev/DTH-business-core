<?php

namespace App\Http\Middleware;

use App\Support\Localization\LocaleManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyLocale
{
    public function __construct(private readonly LocaleManager $locales) {}

    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->locales->resolve($request));

        return $next($request);
    }
}
