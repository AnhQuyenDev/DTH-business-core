<?php

namespace Tests\Feature\Smoke;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class V1RouteContractTest extends TestCase
{
    public function test_public_landing_page_and_quotation_routes_exist(): void
    {
        foreach ([
            'sales.quotation.public.show',
            'sales.quotation.public.accept',
            'sales.quotation.public.reject',
            'sales.quotation.public.notify-payment',
            'sales.quotation.public.csrf-token',
            'marketing.landing-pages.public.show',
            'marketing.landing-pages.public.submit',
            'marketing.landing-pages.public.thank-you',
        ] as $routeName) {
            $this->assertTrue(Route::has($routeName), "Missing route [{$routeName}]");
        }
    }

    public function test_language_switch_route_exists(): void
    {
        $this->assertTrue(Route::has('language.switch'));
    }
}
