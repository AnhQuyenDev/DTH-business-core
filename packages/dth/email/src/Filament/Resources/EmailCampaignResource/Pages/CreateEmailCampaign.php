<?php

namespace Dth\Email\Filament\Resources\EmailCampaignResource\Pages;

use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Filament\Resources\EmailCampaignResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Models\EmailTemplate;
use Dth\Email\Support\UiText;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateEmailCampaign extends CreateRecord
{
    protected static string $resource = EmailCampaignResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(
            UiText::get('campaign.create.title', 'Create Email Campaign'),
            'campaign',
        );
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get(
            'campaign.create.subheading',
            'Configure campaign information, content, and sending settings before scheduling or sending.'
        );
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->label(UiText::get('common.actions.save', 'Save'))
                ->icon('heroicon-o-check-circle')
                ->color('primary')
                ->extraAttributes(['class' => 'dth-email-form-action dth-email-form-action--primary']),
            $this->getCancelFormAction()
                ->label(UiText::get('common.actions.cancel', 'Cancel'))
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-email-form-action dth-email-form-action--secondary']),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $template = EmailTemplate::query()->findOrFail($data['email_template_id']);

        $data['status'] = EmailCampaignStatus::Draft->value;
        $data['created_by'] = auth()->id();
        $data['subject'] = $data['subject'] ?: $template->subject;
        $data['preheader'] = $data['preheader'] ?? $template->preheader;
        $data['html_body'] = $data['html_body'] ?: $template->html_body;
        $data['text_body'] = $data['text_body'] ?? $template->text_body;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
