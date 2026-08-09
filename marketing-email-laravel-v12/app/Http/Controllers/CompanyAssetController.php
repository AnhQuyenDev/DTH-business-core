<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CompanyAssetController extends Controller
{
    public function logo(): BinaryFileResponse
    {
        $path = company_field('logo_path');

        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('public')->path($path),
            [
                'Cache-Control' => 'public, max-age=3600',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }
}
