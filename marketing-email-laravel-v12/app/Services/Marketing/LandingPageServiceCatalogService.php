<?php

namespace App\Services\Marketing;

use App\Enums\Sales\PackageStatus;
use App\Enums\Sales\ServiceStatus;
use App\Models\Marketing\LandingPage;
use App\Models\Sales\ServicePackage;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class LandingPageServiceCatalogService
{
    /**
     * Các lựa chọn Dịch vụ quan tâm được lấy từ ngữ cảnh của Landing Page,
     * không lấy từ Form Template. Nhờ đó cùng một Form Template có thể tái sử
     * dụng cho Hosting, VPS, Email doanh nghiệp... mà không hard-code option.
     *
     * @return array<string, string> [technical_value => display_label]
     */
    public function options(
        LandingPage $landingPage,
        ?string $audienceType = null,
    ): array {
        $landingPage->loadMissing('service');
        $service = $landingPage->service;

        if (
            $service === null
            || $service->status !== ServiceStatus::Active
        ) {
            return [];
        }

        $packages = $this->packagesForLandingPage(
            $landingPage,
            $audienceType,
        );

        if ($packages->isEmpty()) {
            $hasActivePackages = ServicePackage::query()
                ->where('service_id', $service->id)
                ->where('status', PackageStatus::Active->value)
                ->exists();

            if ($hasActivePackages) {
                return [];
            }

            return [
                (string) $service->service_code => (string) $service->name,
            ];
        }

        return $packages
            ->mapWithKeys(fn (ServicePackage $package): array => [
                (string) $package->package_code => $this->packageLabel(
                    (string) $service->name,
                    (string) $package->name,
                ),
            ])
            ->all();
    }

    /**
     * Snapshot ngữ cảnh catalog tại thời điểm khách gửi form.
     * Không phụ thuộc vào việc tên Service/Package có bị đổi về sau.
     *
     * @return array<string, mixed>
     */
    public function selectionContext(
        LandingPage $landingPage,
        ?string $selectedValue,
        ?string $audienceType = null,
    ): array {
        if (blank($selectedValue)) {
            return [];
        }

        $landingPage->loadMissing('service');
        $service = $landingPage->service;

        if ($service === null) {
            return [];
        }

        $packages = $this->packagesForLandingPage(
            $landingPage,
            $audienceType,
        );
        $package = $packages->firstWhere(
            'package_code',
            (string) $selectedValue,
        );

        if ($package instanceof ServicePackage) {
            return [
                'service_id' => $service->id,
                'service_code' => $service->service_code,
                'service_name' => $service->name,
                'service_package_id' => $package->id,
                'service_package_code' => $package->package_code,
                'service_package_name' => $package->name,
                'display_label' => $this->packageLabel(
                    (string) $service->name,
                    (string) $package->name,
                ),
            ];
        }

        if ((string) $service->service_code === (string) $selectedValue) {
            return [
                'service_id' => $service->id,
                'service_code' => $service->service_code,
                'service_name' => $service->name,
                'service_package_id' => null,
                'service_package_code' => null,
                'service_package_name' => null,
                'display_label' => $service->name,
            ];
        }

        return [];
    }

    /** @return Collection<int, ServicePackage> */
    private function packagesForLandingPage(
        LandingPage $landingPage,
        ?string $audienceType,
    ): Collection {
        $landingPage->loadMissing('servicePackages');
        $audienceType = in_array(
            $audienceType,
            ['personal', 'business'],
            true,
        ) ? $audienceType : null;

        $hasExplicitSelection = $landingPage->servicePackages->isNotEmpty();
        $selectedPackages = $landingPage->servicePackages
            ->filter(fn (ServicePackage $package): bool =>
                $this->packageIsAvailable(
                    $package,
                    (int) $landingPage->service_id,
                    $audienceType,
                )
            )
            ->sortBy(fn (ServicePackage $package): array => [
                (int) $package->sort_order,
                (int) $package->id,
            ])
            ->values();

        if ($hasExplicitSelection) {
            return $selectedPackages;
        }

        return ServicePackage::query()
            ->where('service_id', $landingPage->service_id)
            ->where('status', PackageStatus::Active->value)
            ->when(
                filled($audienceType),
                fn ($query) => $query->whereIn('audience_type', [
                    'both',
                    $audienceType,
                ])
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    private function packageIsAvailable(
        ServicePackage $package,
        int $serviceId,
        ?string $audienceType,
    ): bool {
        $status = $package->status instanceof PackageStatus
            ? $package->status->value
            : (string) $package->status;
        $audience = $package->audience_type instanceof \BackedEnum
            ? $package->audience_type->value
            : (string) $package->audience_type;

        return (int) $package->service_id === $serviceId
            && $status === PackageStatus::Active->value
            && (
                blank($audienceType)
                || $audience === 'both'
                || $audience === $audienceType
            );
    }

    private function packageLabel(
        string $serviceName,
        string $packageName,
    ): string {
        if (
            Str::contains(
                Str::lower($packageName),
                Str::lower($serviceName)
            )
        ) {
            return $packageName;
        }

        return $serviceName.' - '.$packageName;
    }
}
