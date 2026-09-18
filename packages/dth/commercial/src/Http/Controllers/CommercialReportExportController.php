<?php

namespace Dth\Commercial\Http\Controllers;

use Dth\Commercial\Services\CommercialReportExportService;
use Dth\Commercial\Support\CommercialAuthorization;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class CommercialReportExportController
{
    public function __invoke(
        Request $request,
        string $format,
        CommercialReportExportService $exports,
        CommercialAuthorization $authorization,
    ): Response {
        abort_unless($authorization->allows('view', $request->user()), 403);
        abort_unless($authorization->allows('reports', $request->user()), 403);
        abort_unless($authorization->allows('export', $request->user()), 403);
        abort_unless(in_array(strtolower($format), ['pdf', 'xlsx', 'csv'], true), 404);

        $file = $exports->dashboard($format);
        $filename = 'commercial-report-'.now()->format('Ymd-His').'.'.$file['extension'];

        return response($file['content'], 200, [
            'Content-Type' => $file['mime'],
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => (string) strlen($file['content']),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
