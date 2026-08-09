<?php

namespace App\Filament\Resources\LandingPageResource\Pages;

use App\Filament\Resources\LandingPageResource;
use App\Models\Marketing\FormTemplate;
use App\Models\Sales\Service;
use App\Models\Sales\ServicePackage;
use App\Services\Marketing\MarketingCampaignServiceScopeService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateLandingPage extends CreateRecord
{
    protected static string $resource =
        LandingPageResource::class;

    private ?int $personalFormTemplateId = null;

    private ?int $businessFormTemplateId = null;

    /** @var array<int, int> */
    private array $servicePackageIds = [];

    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        $this->personalFormTemplateId = filled(
            $data['personal_form_template_id'] ?? null
        )
            ? (int) $data['personal_form_template_id']
            : null;

        $this->businessFormTemplateId = filled(
            $data['business_form_template_id'] ?? null
        )
            ? (int) $data['business_form_template_id']
            : null;

        $this->servicePackageIds = collect(
            $data['service_package_ids'] ?? []
        )
            ->filter(fn (mixed $id): bool => filled($id))
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();

        unset(
            $data['personal_form_template_id'],
            $data['business_form_template_id'],
            $data['service_package_ids']
        );

        $this->validateServiceContext($data);
        $this->validateBeforePublish($data);

        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncForm(
            'personal',
            $this->personalFormTemplateId,
            0
        );

        $this->syncForm(
            'business',
            $this->businessFormTemplateId,
            1
        );

        $this->record->servicePackages()->sync(
            $this->servicePackageIds
        );
    }

    private function syncForm(
        string $type,
        ?int $templateId,
        int $sortOrder
    ): void {
        if ($templateId === null) {
            return;
        }

        $this->record->forms()->updateOrCreate(
            ['form_type' => $type],
            [
                'form_template_id' => $templateId,
                'display_mode' => 'embedded',
                'position_key' => 'end',
                'is_default' => true,
                'sort_order' => $sortOrder,
                'status' => 'active',
            ]
        );
    }


    private function validateServiceContext(array $data): void
    {
        $serviceId = filled($data['service_id'] ?? null)
            ? (int) $data['service_id']
            : null;

        $marketingCampaignId = filled($data['marketing_campaign_id'] ?? null)
            ? (int) $data['marketing_campaign_id']
            : null;

        app(MarketingCampaignServiceScopeService::class)
            ->assertLandingPageServiceAllowed(
                $marketingCampaignId,
                $serviceId,
            );

        if ($serviceId === null) {
            if ($this->servicePackageIds !== []) {
                throw ValidationException::withMessages([
                    'service_id' => __('validation.select_service_before_packages'),
                ]);
            }

            return;
        }

        $validService = Service::query()
            ->whereKey($serviceId)
            ->where('status', 'active')
            ->exists();

        if (! $validService) {
            throw ValidationException::withMessages([
                'service_id' => __('validation.service_inactive_or_missing'),
            ]);
        }

        if ($this->servicePackageIds === []) {
            return;
        }

        $validPackageCount = ServicePackage::query()
            ->whereIn('id', $this->servicePackageIds)
            ->where('service_id', $serviceId)
            ->where('status', 'active')
            ->count();

        if ($validPackageCount !== count($this->servicePackageIds)) {
            throw ValidationException::withMessages([
                'service_package_ids' => __('validation.package_not_in_service'),
            ]);
        }
    }

    private function formUsesServiceInterest(?int $templateId): bool
    {
        if ($templateId === null) {
            return false;
        }

        return FormTemplate::query()
            ->whereKey($templateId)
            ->whereHas(
                'fields',
                fn ($query) => $query->whereIn('contact_mapping', [
                    'lead.service_interest',
                    'personal.service_interest',
                    'business.service_interest',
                ])
            )
            ->exists();
    }

    private function validateBeforePublish(
        array $data
    ): void {
        if (($data['status'] ?? null) !== 'published') {
            return;
        }

        $errors = [];

        if (
            empty($data['campaign_id']) &&
            empty($data['marketing_campaign_id'])
        ) {
            $errors['campaign_id'] =
                __('notification.landing_page_requires_campaign');
        }

        if ($this->personalFormTemplateId === null) {
            $errors['personal_form_template_id'] =
                __('validation.personal_form_required');
        }

        if ($this->businessFormTemplateId === null) {
            $errors['business_form_template_id'] =
                __('validation.business_form_required');
        }

        $needsServiceContext = $this->formUsesServiceInterest(
            $this->personalFormTemplateId
        ) || $this->formUsesServiceInterest(
            $this->businessFormTemplateId
        );

        if ($needsServiceContext && empty($data['service_id'])) {
            $errors['service_id'] =
                __('validation.landing_service_required');
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
