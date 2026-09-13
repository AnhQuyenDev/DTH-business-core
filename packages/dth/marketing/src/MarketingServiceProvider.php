<?php

namespace Dth\Marketing;

use Dth\Marketing\Console\Commands\MarketingAuditPruneCommand;
use Dth\Marketing\Console\Commands\MarketingFinalCheckCommand;
use Dth\Marketing\Console\Commands\MarketingHealthCommand;
use Dth\Marketing\Console\Commands\RepairSemanticMappingsCommand;
use Dth\Marketing\Contracts\AudienceProvider;
use Dth\Marketing\Contracts\CatalogProvider;
use Dth\Marketing\Contracts\EmailMarketingBridge;
use Dth\Marketing\Contracts\LeadProvider;
use Dth\Marketing\Contracts\RevenueProvider;
use Dth\Marketing\Integrations\Crm\NullAudienceProvider;
use Dth\Marketing\Integrations\Crm\NullLeadProvider;
use Dth\Marketing\Integrations\Email\DthEmailMarketingBridge;
use Dth\Marketing\Integrations\Finance\NullRevenueProvider;
use Dth\Marketing\Integrations\Sales\NullCatalogProvider;
use Dth\Marketing\Models\ContactList;
use Dth\Marketing\Models\ContactListMember;
use Dth\Marketing\Models\FormTemplate;
use Dth\Marketing\Models\LandingPage;
use Dth\Marketing\Models\LandingPageSubmission;
use Dth\Marketing\Models\MarketingCampaign;
use Dth\Marketing\Models\MarketingCampaignEmailLink;
use Dth\Marketing\Models\Segment;
use Dth\Marketing\Policies\MarketingResourcePolicy;
use Dth\Marketing\Services\AudienceAutomationService;
use Dth\Marketing\Services\CampaignServiceScopeService;
use Dth\Marketing\Services\EmailMarketingLinkService;
use Dth\Marketing\Services\FormMappingRegistry;
use Dth\Marketing\Services\FormTemplateHtmlImportService;
use Dth\Marketing\Services\FormTemplateLifecycleService;
use Dth\Marketing\Services\FormTemplatePreviewService;
use Dth\Marketing\Services\HtmlFormParser;
use Dth\Marketing\Services\LandingPageCatalogService;
use Dth\Marketing\Services\LandingPageHtmlImportService;
use Dth\Marketing\Services\LandingPageLifecycleService;
use Dth\Marketing\Services\LandingPageRenderService;
use Dth\Marketing\Services\LandingPageSubmissionService;
use Dth\Marketing\Services\LandingPageThemeService;
use Dth\Marketing\Services\LandingPageTrackingService;
use Dth\Marketing\Services\LandingPageUtmBuilder;
use Dth\Marketing\Services\LandingPageUtmService;
use Dth\Marketing\Services\MarketingAnalyticsService;
use Dth\Marketing\Services\MarketingAuditTrailService;
use Dth\Marketing\Services\MarketingCampaignLifecycleService;
use Dth\Marketing\Services\MarketingInsightService;
use Dth\Marketing\Services\MarketingReportExportService;
use Dth\Marketing\Services\SegmentQueryService;
use Dth\Marketing\Services\SegmentRuleRegistry;
use Dth\Marketing\Services\SemanticFieldResolver;
use Dth\Marketing\Services\SemanticMappingRepairService;
use Dth\Marketing\Services\SubmissionSemanticNormalizer;
use Dth\Marketing\Services\UtmReportService;
use Dth\Marketing\Support\IntegrationHealthService;
use Dth\Marketing\Support\MarketingAuthorizationService;
use Dth\Marketing\Support\SimplePdfWriter;
use Dth\Marketing\Support\SimpleXlsxWriter;
use Dth\Marketing\Support\SimpleZipArchive;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class MarketingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/marketing.php', 'dth-marketing');

        $this->bindIntegration(AudienceProvider::class, 'audience_provider', NullAudienceProvider::class);
        $this->bindIntegration(LeadProvider::class, 'lead_provider', NullLeadProvider::class);
        $this->bindIntegration(CatalogProvider::class, 'catalog_provider', NullCatalogProvider::class);
        $this->bindIntegration(RevenueProvider::class, 'revenue_provider', NullRevenueProvider::class);
        $this->bindIntegration(EmailMarketingBridge::class, 'email_marketing_bridge', DthEmailMarketingBridge::class);

        foreach ([
            IntegrationHealthService::class,
            MarketingAuthorizationService::class,
            CampaignServiceScopeService::class,
            MarketingCampaignLifecycleService::class,
            FormMappingRegistry::class,
            SemanticFieldResolver::class,
            SubmissionSemanticNormalizer::class,
            SemanticMappingRepairService::class,
            HtmlFormParser::class,
            FormTemplateHtmlImportService::class,
            FormTemplateLifecycleService::class,
            FormTemplatePreviewService::class,
            LandingPageCatalogService::class,
            LandingPageHtmlImportService::class,
            LandingPageLifecycleService::class,
            LandingPageThemeService::class,
            LandingPageRenderService::class,
            LandingPageUtmBuilder::class,
            LandingPageTrackingService::class,
            AudienceAutomationService::class,
            LandingPageSubmissionService::class,
            SegmentRuleRegistry::class,
            SegmentQueryService::class,
            LandingPageUtmService::class,
            UtmReportService::class,
            EmailMarketingLinkService::class,
            MarketingInsightService::class,
            MarketingAnalyticsService::class,
            MarketingAuditTrailService::class,
            SimpleZipArchive::class,
            SimpleXlsxWriter::class,
            SimplePdfWriter::class,
            MarketingReportExportService::class,
        ] as $service) {
            $this->app->singleton($service);
        }
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'dth-marketing');

        $this->publishes([
            __DIR__.'/../config/marketing.php' => config_path('dth-marketing.php'),
        ], 'dth-marketing-config');

        $this->registerRateLimiters();
        $this->registerPolicies();

        if ($this->app->runningInConsole()) {
            $this->commands([
                RepairSemanticMappingsCommand::class,
                MarketingHealthCommand::class,
                MarketingAuditPruneCommand::class,
                MarketingFinalCheckCommand::class,
            ]);
        }
    }

    /** @param class-string $contract @param class-string $fallback */
    private function bindIntegration(string $contract, string $configKey, string $fallback): void
    {
        if ($this->app->bound($contract)) {
            return;
        }

        $configured = config('dth-marketing.integrations.'.$configKey);
        if ($configured === null || $configured === '') {
            $this->app->singleton($contract, $fallback);

            return;
        }

        if (! is_string($configured) || ! class_exists($configured)) {
            throw new \InvalidArgumentException("Invalid Marketing integration adapter configured for {$configKey}.");
        }
        if (! is_a($configured, $contract, true)) {
            throw new \InvalidArgumentException("Marketing integration adapter {$configured} must implement {$contract}.");
        }

        $this->app->singleton($contract, $configured);
    }

    private function registerRateLimiters(): void
    {
        RateLimiter::for('dth-marketing-submissions', function (Request $request): array {
            $slug = (string) ($request->route('slug') ?? 'landing');
            $ip = (string) ($request->ip() ?? 'unknown');
            $key = hash('sha256', $slug.'|'.$ip);
            $perMinute = max(1, (int) config('dth-marketing.security.submission_rate_limit_per_minute', 10));
            $perHour = max($perMinute, (int) config('dth-marketing.security.submission_rate_limit_per_hour', 60));

            return [
                Limit::perMinute($perMinute)->by('m:'.$key),
                Limit::perHour($perHour)->by('h:'.$key),
            ];
        });
    }

    private function registerPolicies(): void
    {
        $gate = app(GateContract::class);
        foreach ([
            MarketingCampaign::class,
            LandingPage::class,
            FormTemplate::class,
            LandingPageSubmission::class,
            ContactList::class,
            ContactListMember::class,
            Segment::class,
            MarketingCampaignEmailLink::class,
        ] as $model) {
            if ($gate->getPolicyFor($model) === null) {
                $gate->policy($model, MarketingResourcePolicy::class);
            }
        }
    }
}
