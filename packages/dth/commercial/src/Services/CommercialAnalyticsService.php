<?php

namespace Dth\Commercial\Services;

use Dth\Commercial\Enums\OpportunityStage;
use Dth\Commercial\Models\Bundle;
use Dth\Commercial\Models\Opportunity;
use Dth\Commercial\Models\OpportunityItem;
use Dth\Commercial\Models\Product;
use Dth\Commercial\Models\Service;
use Dth\Commercial\Support\UiText;
use Illuminate\Support\Collection;

final class CommercialAnalyticsService
{
    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        $openStages = $this->openStages();

        $openOpportunities = Opportunity::query()
            ->whereIn('stage', $openStages)
            ->withCount('items')
            ->get([
                'id', 'opportunity_code', 'title', 'stage', 'estimated_value', 'currency',
                'probability', 'expected_close_date', 'service_name_snapshot',
                'company_name_snapshot', 'contact_name_snapshot', 'assigned_employee_name_snapshot',
                'created_at', 'updated_at',
            ]);

        $wonCount = Opportunity::query()->where('stage', OpportunityStage::Won->value)->count();
        $lostCount = Opportunity::query()->where('stage', OpportunityStage::Lost->value)->count();
        $decidedCount = $wonCount + $lostCount;
        $winRate = $decidedCount > 0 ? round(($wonCount / $decidedCount) * 100, 1) : 0.0;

        $closingThisMonth = $openOpportunities->filter(static fn (Opportunity $opportunity): bool =>
            $opportunity->expected_close_date?->isSameMonth(now()) ?? false
        );

        $pipelineValue = (float) $openOpportunities->sum('estimated_value');
        $weightedPipeline = (float) $openOpportunities->sum(static fn (Opportunity $opportunity): float =>
            ((float) ($opportunity->estimated_value ?? 0)) * (((int) $opportunity->probability) / 100)
        );

        return [
            'active_services' => Service::query()->where('status', 'active')->count(),
            'active_products' => Product::query()->where('status', 'active')->count(),
            'active_bundles' => Bundle::query()->where('status', 'active')->count(),
            // Compatibility key consumed by older dashboard/report templates.
            'active_packages' => Bundle::query()->where('status', 'active')->count(),
            'open_opportunities' => $openOpportunities->count(),
            'pipeline_value' => $pipelineValue,
            'weighted_pipeline_value' => $weightedPipeline,
            'win_rate' => $winRate,
            'closing_this_month_count' => $closingThisMonth->count(),
            'closing_this_month_value' => (float) $closingThisMonth->sum('estimated_value'),
            'stage_breakdown' => $this->stageBreakdown(),
            'monthly_pipeline' => $this->monthlyPipeline(),
            'top_services' => $this->topServices($openOpportunities),
            'recent_opportunities' => Opportunity::query()->withCount('items')->latest('updated_at')->limit(6)->get(),
        ];
    }

    /** @return array<int,string> */
    private function openStages(): array
    {
        return [
            OpportunityStage::Discovery->value,
            OpportunityStage::Qualified->value,
            OpportunityStage::Proposal->value,
            OpportunityStage::Negotiation->value,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function stageBreakdown(): array
    {
        $tones = [
            OpportunityStage::Discovery->value => 'sky',
            OpportunityStage::Qualified->value => 'cyan',
            OpportunityStage::Proposal->value => 'amber',
            OpportunityStage::Negotiation->value => 'orange',
            OpportunityStage::Won->value => 'green',
            OpportunityStage::Lost->value => 'rose',
        ];
        $stages = [
            OpportunityStage::Discovery,
            OpportunityStage::Qualified,
            OpportunityStage::Proposal,
            OpportunityStage::Negotiation,
            OpportunityStage::Won,
            OpportunityStage::Lost,
        ];

        $counts = Opportunity::query()
            ->whereIn('stage', array_map(static fn (OpportunityStage $stage): string => $stage->value, $stages))
            ->get(['stage', 'estimated_value'])
            ->groupBy(static fn (Opportunity $opportunity): string =>
                $opportunity->stage instanceof OpportunityStage ? $opportunity->stage->value : (string) $opportunity->stage
            );

        $total = max(1, $counts->flatten(1)->count());

        return array_map(function (OpportunityStage $stage) use ($counts, $tones, $total): array {
            /** @var Collection<int, Opportunity> $records */
            $records = $counts->get($stage->value, collect());
            return [
                'stage' => $stage->value,
                'label' => $stage->label(),
                'tone' => $tones[$stage->value] ?? 'slate',
                'count' => $records->count(),
                'percentage' => round(($records->count() / $total) * 100),
                'value' => (float) $records->sum('estimated_value'),
            ];
        }, $stages);
    }

    /** @return array<int, array{key:string,label:string,value:float}> */
    private function monthlyPipeline(): array
    {
        $months = collect(range(0, 5))->map(static fn (int $offset) => now()->startOfMonth()->addMonths($offset));
        $firstMonth = $months->first();
        $lastMonth = $months->last();

        $opportunities = Opportunity::query()
            ->whereIn('stage', $this->openStages())
            ->whereBetween('expected_close_date', [
                $firstMonth?->copy()->startOfMonth(),
                $lastMonth?->copy()->endOfMonth(),
            ])
            ->get(['expected_close_date', 'estimated_value']);

        return $months->map(function ($month) use ($opportunities): array {
            $value = $opportunities
                ->filter(static fn (Opportunity $opportunity): bool => $opportunity->expected_close_date?->format('Y-m') === $month->format('Y-m'))
                ->sum('estimated_value');
            return [
                'key' => $month->format('Y-m'),
                'label' => UiText::get('overview.month_short', 'M:month', ['month' => $month->format('m')]),
                'value' => (float) $value,
            ];
        })->all();
    }

    /** @param Collection<int,Opportunity> $openOpportunities @return array<int,array{name:string,value:float,count:int}> */
    private function topServices(Collection $openOpportunities): array
    {
        $ids = $openOpportunities->pluck('id')->all();
        $result = collect();

        if ($ids !== []) {
            OpportunityItem::query()
                ->whereIn('opportunity_id', $ids)
                ->whereNotNull('service_name_snapshot')
                ->get(['opportunity_id', 'service_name_snapshot', 'total'])
                ->groupBy('service_name_snapshot')
                ->each(function (Collection $records, string $name) use ($result): void {
                    $result->put($name, [
                        'name' => $name,
                        'value' => (float) $records->sum('total'),
                        'count' => $records->pluck('opportunity_id')->unique()->count(),
                    ]);
                });
        }

        // Preserve useful historical analytics for opportunities created before
        // line items existed. They only contribute when they still have no items.
        $openOpportunities
            ->filter(fn (Opportunity $opportunity): bool => (int) ($opportunity->items_count ?? 0) === 0 && filled($opportunity->service_name_snapshot))
            ->each(function (Opportunity $opportunity) use ($result): void {
                $name = (string) $opportunity->service_name_snapshot;
                $row = $result->get($name, ['name' => $name, 'value' => 0.0, 'count' => 0]);
                $row['value'] += (float) ($opportunity->estimated_value ?? 0);
                $row['count']++;
                $result->put($name, $row);
            });

        return $result->sortByDesc('value')->take(5)->values()->all();
    }
}
