<?php

namespace App\Filament\Pages;

use App\Enums\Marketing\CampaignRecipientStatus;
use App\Models\Marketing\Campaign;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;

class CampaignReportPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?int $navigationSort = 40;

    public static function getNavigationLabel(): string
    {
        return __('navigation.campaign_reports');
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.email_marketing');
    }

    protected static string $view = 'filament.pages.campaign-report-page';

    public ?int $campaignId = null;

    public function mount(): void
    {
        $this->campaignId = Campaign::query()->latest()->value('id');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('marketing.view-reports') ?? false;
    }

    public function getCampaignOptionsProperty(): array
    {
        return Campaign::query()->orderByDesc('created_at')->pluck('name', 'id')->all();
    }

    public function getSelectedCampaignProperty(): ?Campaign
    {
        return $this->campaignId ? Campaign::query()->with(['recipients.contact'])->find($this->campaignId) : null;
    }

    public function getStatsProperty(): array
    {
        $campaign = $this->selectedCampaign;

        if (! $campaign) {
            return [
                'total' => 0,
                'pending' => 0,
                'queued' => 0,
                'sent' => 0,
                'failed' => 0,
                'opened' => 0,
                'clicked' => 0,
                'unsubscribed' => 0,
                'open_rate' => 0,
                'click_rate' => 0,
                'failed_rate' => 0,
                'unsubscribe_rate' => 0,
            ];
        }

        $recipients = $campaign->recipients;
        $total = $recipients->count();

        $opened = $recipients->whereNotNull('opened_at')->count();
        $clicked = $recipients->whereNotNull('clicked_at')->count();

        return [
            'total' => $total,
            'pending' => $recipients->where('status', CampaignRecipientStatus::Pending->value)->count(),
            'queued' => $recipients->where('status', CampaignRecipientStatus::Queued->value)->count(),
            'sent' => $recipients->where('status', CampaignRecipientStatus::Sent->value)->count(),
            'failed' => $recipients->where('status', CampaignRecipientStatus::Failed->value)->count(),
            'opened' => $opened,
            'clicked' => $clicked,
            'unsubscribed' => $recipients->where('status', CampaignRecipientStatus::Unsubscribed->value)->count(),
            'open_rate' => $total > 0 ? round(($opened / $total) * 100, 1) : 0,
            'click_rate' => $total > 0 ? round(($clicked / $total) * 100, 1) : 0,
            'failed_rate' => $total > 0 ? round(($recipients->where('status', CampaignRecipientStatus::Failed->value)->count() / $total) * 100, 1) : 0,
            'unsubscribe_rate' => $total > 0 ? round(($recipients->where('status', CampaignRecipientStatus::Unsubscribed->value)->count() / $total) * 100, 1) : 0,
        ];
    }
}
