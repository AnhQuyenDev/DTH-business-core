<?php

namespace App\Filament\Resources\LandingPageResource\Pages;

use App\Filament\Resources\LandingPageResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateLandingPage extends CreateRecord
{
    protected static string $resource =
        LandingPageResource::class;

    private ?int $personalFormTemplateId = null;

    private ?int $businessFormTemplateId = null;

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

        unset(
            $data['personal_form_template_id'],
            $data['business_form_template_id']
        );

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
                'Vui lòng chọn Form cá nhân.';
        }

        if ($this->businessFormTemplateId === null) {
            $errors['business_form_template_id'] =
                'Vui lòng chọn Form doanh nghiệp.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
