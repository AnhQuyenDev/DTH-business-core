<?php

namespace App\Filament\Resources\LandingPageResource\Pages;

use App\Filament\Resources\LandingPageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditLandingPage extends EditRecord
{
    protected static string $resource =
        LandingPageResource::class;

    private ?int $personalFormTemplateId = null;

    private ?int $businessFormTemplateId = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(
        array $data
    ): array {
        $data['personal_form_template_id'] =
            $this->record
                ->forms()
                ->where('form_type', 'personal')
                ->value('form_template_id');

        $data['business_form_template_id'] =
            $this->record
                ->forms()
                ->where('form_type', 'business')
                ->value('form_template_id');

        return $data;
    }

    protected function mutateFormDataBeforeSave(
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

        return $data;
    }

    protected function afterSave(): void
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
            $this->record
                ->forms()
                ->where('form_type', $type)
                ->delete();

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
