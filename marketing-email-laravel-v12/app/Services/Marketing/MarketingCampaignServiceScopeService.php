<?php

namespace App\Services\Marketing;

use App\Models\Marketing\LandingPage;
use App\Models\Marketing\MarketingCampaign;
use App\Models\Sales\Service;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class MarketingCampaignServiceScopeService
{
    /** @return array<int, int> */
    public function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->filter(fn (mixed $id): bool => filled($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /** @return array<int, string> */
    public function activeServiceOptions(): array
    {
        return Service::query()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** @return array<int, string> */
    public function serviceOptionsForCampaign(?int $campaignId): array
    {
        if ($campaignId === null) {
            return $this->activeServiceOptions();
        }

        return Service::query()
            ->where('status', 'active')
            ->whereHas(
                'marketingCampaigns',
                fn ($query) => $query->whereKey($campaignId)
            )
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    /** @return array<int, string> */
    public function compatibleLandingPageOptions(
        array $serviceIds,
        ?int $campaignId = null,
    ): array {
        $serviceIds = $this->normalizeIds($serviceIds);

        if ($serviceIds === []) {
            return [];
        }

        return LandingPage::query()
            ->where(function ($query) use ($serviceIds, $campaignId): void {
                // Trang chưa thuộc Campaign chỉ được đề xuất khi service nằm trong
                // scope đang chọn.
                $query->where(function ($candidate) use ($serviceIds): void {
                    $candidate
                        ->whereNull('marketing_campaign_id')
                        ->whereIn('service_id', $serviceIds);
                });

                // Khi sửa Campaign, luôn giữ các Landing Page hiện đang thuộc
                // Campaign trong options, kể cả khi người dùng vừa bỏ service.
                // Nhờ vậy state không bị mất âm thầm và validation sẽ buộc người
                // dùng chủ động gỡ/chuyển Landing Page trước khi thu hẹp scope.
                if ($campaignId !== null) {
                    $query->orWhere('marketing_campaign_id', $campaignId);
                }
            })
            ->with('service:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'service_id'])
            ->mapWithKeys(function (LandingPage $page): array {
                $serviceName = $page->service?->name ?? 'Chưa có dịch vụ';

                return [
                    $page->id => $page->name.' — '.$serviceName,
                ];
            })
            ->all();
    }

    public function assertLandingPageServiceAllowed(
        ?int $campaignId,
        ?int $serviceId,
    ): void {
        if ($campaignId === null) {
            return;
        }

        if ($serviceId === null) {
            throw ValidationException::withMessages([
                'service_id' => 'Landing Page thuộc chiến dịch quảng cáo phải chọn Dịch vụ chính.',
            ]);
        }

        $campaign = MarketingCampaign::query()
            ->with('services:id')
            ->find($campaignId);

        if ($campaign === null) {
            throw ValidationException::withMessages([
                'marketing_campaign_id' => 'Chiến dịch quảng cáo không tồn tại.',
            ]);
        }

        if ($campaign->services->isEmpty()) {
            throw ValidationException::withMessages([
                'marketing_campaign_id' => 'Chiến dịch chưa cấu hình Dịch vụ quảng bá. Hãy cấu hình chiến dịch trước khi gắn Landing Page.',
            ]);
        }

        if (! $campaign->services->contains('id', $serviceId)) {
            $serviceName = Service::query()->whereKey($serviceId)->value('name')
                ?? 'dịch vụ đã chọn';

            throw ValidationException::withMessages([
                'service_id' => "{$serviceName} không nằm trong phạm vi dịch vụ quảng bá của chiến dịch.",
            ]);
        }
    }

    /**
     * @param array<int, int|string> $serviceIds
     * @param array<int, int|string> $landingPageIds
     */
    public function assertCampaignConfiguration(
        array $serviceIds,
        array $landingPageIds = [],
        ?int $campaignId = null,
    ): void {
        $serviceIds = $this->normalizeIds($serviceIds);
        $landingPageIds = $this->normalizeIds($landingPageIds);

        if ($serviceIds === []) {
            throw ValidationException::withMessages([
                'service_ids' => 'Chiến dịch quảng cáo phải có ít nhất một Dịch vụ quảng bá.',
            ]);
        }

        $validServiceCount = Service::query()
            ->whereIn('id', $serviceIds)
            ->where('status', 'active')
            ->count();

        if ($validServiceCount !== count($serviceIds)) {
            throw ValidationException::withMessages([
                'service_ids' => 'Có dịch vụ không tồn tại hoặc không còn hoạt động.',
            ]);
        }


        if ($landingPageIds === []) {
            return;
        }

        $pages = LandingPage::query()
            ->whereIn('id', $landingPageIds)
            ->get(['id', 'name', 'service_id', 'marketing_campaign_id']);

        if ($pages->count() !== count($landingPageIds)) {
            throw ValidationException::withMessages([
                'landing_page_ids' => 'Có Landing Page không tồn tại.',
            ]);
        }

        $withoutService = $pages->filter(
            fn (LandingPage $page): bool => $page->service_id === null
        );

        if ($withoutService->isNotEmpty()) {
            throw ValidationException::withMessages([
                'landing_page_ids' => 'Landing Page phải có Dịch vụ chính trước khi gắn vào chiến dịch: '.$this->pageNames($withoutService).'.',
            ]);
        }

        $outsideScope = $pages->filter(
            fn (LandingPage $page): bool => ! in_array((int) $page->service_id, $serviceIds, true)
        );

        if ($outsideScope->isNotEmpty()) {
            throw ValidationException::withMessages([
                'landing_page_ids' => 'Có Landing Page quảng bá dịch vụ nằm ngoài phạm vi chiến dịch: '.$this->pageNames($outsideScope).'.',
            ]);
        }

        $ownedByAnotherCampaign = $pages->filter(
            fn (LandingPage $page): bool => $page->marketing_campaign_id !== null
                && (int) $page->marketing_campaign_id !== (int) $campaignId
        );

        if ($ownedByAnotherCampaign->isNotEmpty()) {
            throw ValidationException::withMessages([
                'landing_page_ids' => 'Có Landing Page đang thuộc chiến dịch quảng cáo khác: '.$this->pageNames($ownedByAnotherCampaign).'.',
            ]);
        }
    }

    /** @param Collection<int, LandingPage> $pages */
    private function pageNames(Collection $pages): string
    {
        return $pages->pluck('name')->filter()->implode(', ');
    }
}
