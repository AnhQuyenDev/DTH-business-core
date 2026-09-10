<?php

namespace Dth\Email\Filament\Resources\EmailCampaignResource\Pages;

use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Filament\Resources\EmailCampaignResource;
use Dth\Email\Models\EmailTemplate;
use Filament\Resources\Pages\CreateRecord;

class CreateEmailCampaign extends CreateRecord
{
    protected static string $resource =
        EmailCampaignResource::class;

    protected function mutateFormDataBeforeCreate(
        array $data,
    ): array {
        $template = EmailTemplate::query()
            ->findOrFail($data['email_template_id']);

        $data['status'] =
            EmailCampaignStatus::Draft->value;

        $data['created_by'] =
            auth()->id();

        $data['subject'] =
            $data['subject']
            ?: $template->subject;

        $data['preheader'] =
            $data['preheader']
            ?? $template->preheader;

        $data['html_body'] =
            $data['html_body']
            ?: $template->html_body;

        $data['text_body'] =
            $data['text_body']
            ?? $template->text_body;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl(
            'edit',
            [
                'record' => $this->record,
            ],
        );
    }
}