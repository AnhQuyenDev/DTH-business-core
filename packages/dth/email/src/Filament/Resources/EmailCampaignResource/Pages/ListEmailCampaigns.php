<?php

namespace Dth\Email\Filament\Resources\EmailCampaignResource\Pages;

use Dth\Email\Filament\Resources\EmailCampaignResource;
use Dth\Email\Filament\Resources\EmailCampaignResource\Widgets\CampaignListStats;
use Dth\Email\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class ListEmailCampaigns extends ListRecords
{
    protected static string $resource = EmailCampaignResource::class;

    public function getTitle(): string|Htmlable
    {
        $title = e(UiText::get('campaign.list.title', 'Chiến dịch Email'));

        return new HtmlString(<<<HTML
            <span class="dth-campaign-page-title">
                <span class="dth-campaign-page-title__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 5.75A2.75 2.75 0 0 1 5.75 3h12.5A2.75 2.75 0 0 1 21 5.75v12.5A2.75 2.75 0 0 1 18.25 21H5.75A2.75 2.75 0 0 1 3 18.25V5.75Z" />
                        <path d="m5.6 7.2 5.16 4.02a2 2 0 0 0 2.48 0L18.4 7.2" />
                    </svg>
                </span>
                <span>{$title}</span>
            </span>
        HTML);
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get(
            'campaign.list.subheading',
            'Quản lý, theo dõi và tối ưu hiệu quả các chiến dịch email marketing.'
        );
    }

    /** @return array<class-string<\Filament\Widgets\Widget>> */
    protected function getHeaderWidgets(): array
    {
        return [
            CampaignListStats::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(UiText::get('campaign.list.create', 'Tạo chiến dịch'))
                ->icon('heroicon-o-plus'),
        ];
    }
}
