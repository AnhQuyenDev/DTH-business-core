<?php

namespace Dth\Marketing\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

final class MarketingFinalCheckCommand extends Command
{
    protected $signature = 'marketing:final-check';
    protected $description = 'Run final non-destructive Marketing module production-readiness checks.';

    public function handle(): int
    {
        $checks = [];
        $requiredTables = [
            'marketing_campaigns',
            'marketing_form_templates',
            'marketing_form_fields',
            'marketing_landing_pages',
            'marketing_landing_page_views',
            'marketing_landing_page_submissions',
            'marketing_contact_lists',
            'marketing_contact_list_members',
            'marketing_segments',
            'marketing_landing_page_utm_urls',
            'marketing_campaign_email_links',
            'marketing_audit_logs',
        ];

        foreach ($requiredTables as $table) {
            $checks[] = ['Schema: '.$table, Schema::hasTable($table) ? 'PASS' : 'FAIL'];
        }

        foreach ([
            'marketing.landing-pages.public.show',
            'marketing.landing-pages.public.submit',
            'dth.marketing.reports.dashboard',
            'dth.marketing.reports.campaign',
        ] as $routeName) {
            $checks[] = ['Route: '.$routeName, Route::has($routeName) ? 'PASS' : 'FAIL'];
        }

        $checks[] = ['Column: marketing_landing_page_views.source', Schema::hasColumn('marketing_landing_page_views', 'source') ? 'PASS' : 'FAIL'];
        $checks[] = ['Column: marketing_landing_page_submissions.normalized_data', Schema::hasColumn('marketing_landing_page_submissions', 'normalized_data') ? 'PASS' : 'FAIL'];
        $checks[] = ['Feature: analytics', config('dth-marketing.features.analytics', false) ? 'PASS' : 'FAIL'];
        $checks[] = ['Rate limit / minute', ((int) config('dth-marketing.security.submission_rate_limit_per_minute', 0)) > 0 ? 'PASS' : 'FAIL'];
        $checks[] = ['Rate limit / hour', ((int) config('dth-marketing.security.submission_rate_limit_per_hour', 0)) > 0 ? 'PASS' : 'FAIL'];
        $checks[] = ['Duplicate window', ((int) config('dth-marketing.security.duplicate_window_seconds', 0)) > 0 ? 'PASS' : 'FAIL'];
        $checks[] = ['Payload ceiling', ((int) config('dth-marketing.security.max_submission_payload_bytes', 0)) >= 1024 ? 'PASS' : 'FAIL'];
        $checks[] = ['Authorization mode', in_array((string) config('dth-marketing.authorization.mode', 'auto'), ['auto', 'strict', 'off'], true) ? 'PASS' : 'FAIL'];
        $checks[] = ['Audit enabled', config('dth-marketing.audit.enabled', true) ? 'PASS' : 'WARN'];

        $this->table(['Check', 'Result'], $checks);
        $failed = collect($checks)->contains(fn (array $row): bool => $row[1] === 'FAIL');

        if ($failed) {
            $this->error('Marketing final check found blocking failures.');

            return self::FAILURE;
        }

        $this->info('Marketing structural final check PASS. Continue with browser UAT and Email regression tests.');

        return self::SUCCESS;
    }
}
