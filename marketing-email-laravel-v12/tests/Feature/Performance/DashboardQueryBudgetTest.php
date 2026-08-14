<?php

namespace Tests\Feature\Performance;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\PositionAuthority;
use App\Models\Marketing\MarketingCampaign;
use App\Services\Dashboard\EnterpriseAnalyticsService;
use App\Services\Dashboard\WorkforceAnalyticsService;
use App\Services\Security\RbacSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\MakesV1Actors;
use Tests\TestCase;

class DashboardQueryBudgetTest extends TestCase
{
    use MakesV1Actors;
    use RefreshDatabase;

    public function test_rbac_readiness_is_evaluated_once_for_repeated_permission_checks(): void
    {
        app(RbacSyncService::class)->syncDefinitions();
        app()->forgetInstance(RbacSyncService::class);

        DB::enableQueryLog();
        $service = app(RbacSyncService::class);

        foreach (range(1, 25) as $iteration) {
            $this->assertTrue($service->ready());
        }

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertGreaterThan(0, $queryCount);
        $this->assertLessThanOrEqual(6, $queryCount);
    }

    public function test_workforce_query_count_does_not_increase_with_staff_count(): void
    {
        $viewer = $this->makeV1SuperAdmin('Performance Super Admin');

        foreach ($this->businessFunctions() as [$function, $authority]) {
            $this->makeV1BusinessActor('Baseline '.$function->value, [[$function, $authority, true]]);
        }

        $baseline = $this->workforceQueryCount($viewer);

        foreach (range(1, 2) as $round) {
            foreach ($this->businessFunctions() as [$function, $authority]) {
                $this->makeV1BusinessActor("Scale {$round} {$function->value}", [[$function, $authority, true]]);
            }
        }

        $scaled = $this->workforceQueryCount($viewer);

        $this->assertLessThanOrEqual($baseline + 1, $scaled);
        $this->assertLessThanOrEqual(30, $scaled);
    }

    public function test_executive_dashboard_query_count_does_not_increase_with_campaign_count(): void
    {
        $viewer = $this->makeV1SuperAdmin('Campaign Performance Super Admin');
        $this->makeCampaign($viewer->id, 1);

        $baseline = $this->enterpriseQueryCount($viewer);

        foreach (range(2, 20) as $sequence) {
            $this->makeCampaign($viewer->id, $sequence);
        }

        $scaled = $this->enterpriseQueryCount($viewer);

        $this->assertLessThanOrEqual($baseline + 1, $scaled);
        $this->assertLessThanOrEqual(35, $scaled);
    }

    private function workforceQueryCount($viewer): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        app(WorkforceAnalyticsService::class)->report($viewer, '30d');

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    private function enterpriseQueryCount($viewer): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        app(EnterpriseAnalyticsService::class)->overview($viewer, '30d');

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    private function makeCampaign(int $userId, int $sequence): void
    {
        MarketingCampaign::query()->create([
            'name' => "Performance Campaign {$sequence}",
            'status' => 'active',
            'start_date' => today(),
            'created_by' => $userId,
        ]);
    }

    /** @return array<int, array{DepartmentFunction, PositionAuthority}> */
    private function businessFunctions(): array
    {
        return [
            [DepartmentFunction::Marketing, PositionAuthority::Member],
            [DepartmentFunction::Sales, PositionAuthority::Member],
            [DepartmentFunction::CustomerService, PositionAuthority::Member],
            [DepartmentFunction::Finance, PositionAuthority::Member],
        ];
    }
}
