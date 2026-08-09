<x-filament-panels::page>
    @php($stats=$this->stats; $money=static fn($v)=>number_format((float)$v,0,',','.').' ₫')
    <div class="dth-analytics-shell" wire:loading.class="opacity-70">
        <div class="dth-analytics-toolbar">
            <div><div class="dth-analytics-toolbar__title">{{ __('page.campaign_report.title') }}</div><div class="dth-analytics-toolbar__subtitle">{{ __('analytics.email_report_subtitle') }}</div></div>
            <div class="flex flex-wrap items-center gap-2"><div class="w-72"><x-filament::input.wrapper><x-filament::input.select wire:model.live="campaignId">@foreach($this->campaignOptions as $id=>$name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</x-filament::input.select></x-filament::input.wrapper></div><x-filament::button wire:click="exportCsv" color="gray" icon="heroicon-o-arrow-down-tray" :disabled="!$this->selectedCampaign">{{ __('uiux.dashboard.common.export_csv') }}</x-filament::button></div>
        </div>

        <div class="dth-analytics-kpi-grid">
            <x-analytics.kpi-card :label="__('report.total_recipients')" :value="number_format($stats['total'])" icon="heroicon-o-users" tone="primary" />
            <x-analytics.kpi-card :label="__('analytics.open_rate')" :value="number_format($stats['open_rate'],1).'%'" icon="heroicon-o-envelope-open" tone="info" />
            <x-analytics.kpi-card :label="__('analytics.click_rate')" :value="number_format($stats['click_rate'],1).'%'" icon="heroicon-o-cursor-arrow-rays" tone="warning" />
            <x-analytics.kpi-card :label="__('analytics.email_to_paid_conversion')" :value="number_format($stats['conversion_rate'],2).'%'" icon="heroicon-o-check-badge" tone="success" />
            <x-analytics.kpi-card :label="__('analytics.paid_customers')" :value="number_format($stats['paid_customers'])" icon="heroicon-o-user-group" tone="success" />
            <x-analytics.kpi-card :label="__('analytics.attributed_revenue')" :value="$money($stats['net_revenue'])" icon="heroicon-o-banknotes" tone="success" />
            <x-analytics.kpi-card :label="__('report.failed')" :value="number_format($stats['failed'])" icon="heroicon-o-exclamation-circle" tone="danger" />
            <x-analytics.kpi-card :label="__('report.unsubscribed')" :value="number_format($stats['unsubscribed'])" icon="heroicon-o-user-minus" tone="warning" />
        </div>

        <div class="dth-analytics-layout-equal">
            <x-filament::section><x-slot name="heading">{{ __('analytics.email_engagement_funnel') }}</x-slot><x-analytics.funnel :items="[
                ['label'=>__('report.total_recipients'),'value'=>$stats['total']],
                ['label'=>__('report.sent'),'value'=>$stats['sent']],
                ['label'=>__('report.opened'),'value'=>$stats['opened']],
                ['label'=>__('report.clicked'),'value'=>$stats['clicked']],
                ['label'=>__('analytics.paid_customers'),'value'=>$stats['paid_customers']],
            ]" /></x-filament::section>
            <x-filament::section><x-slot name="heading">{{ __('analytics.email_health') }}</x-slot><x-analytics.donut-chart :items="[
                ['label'=>__('report.opened'),'value'=>$stats['opened']],
                ['label'=>__('report.clicked'),'value'=>$stats['clicked']],
                ['label'=>__('report.failed'),'value'=>$stats['failed']],
                ['label'=>__('report.unsubscribed'),'value'=>$stats['unsubscribed']],
            ]" :money="false" :center-label="__('analytics.events')" /></x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">{{ __('analytics.recipient_detail') }}</x-slot>
            @if($this->selectedCampaign)
                <div class="overflow-x-auto"><table class="dth-analytics-table"><thead><tr><th>{{ __('report.contact') }}</th><th>{{ __('report.email') }}</th><th>{{ __('report.status') }}</th><th>{{ __('report.sent_at') }}</th><th>{{ __('report.opened_at') }}</th><th>{{ __('report.clicked_at') }}</th></tr></thead><tbody>@forelse($this->selectedCampaign->recipients->take(50) as $recipient)<tr><td>{{ $recipient->contact?->full_name ?: '—' }}</td><td>{{ $recipient->email }}</td><td><x-filament::badge color="gray">{{ \App\Enums\Marketing\CampaignRecipientStatus::tryFrom((string) $recipient->status)?->label() ?: str($recipient->status)->headline() }}</x-filament::badge></td><td>{{ $recipient->sent_at?->format('d/m/Y H:i') ?: '—' }}</td><td>{{ $recipient->opened_at?->format('d/m/Y H:i') ?: '—' }}</td><td>{{ $recipient->clicked_at?->format('d/m/Y H:i') ?: '—' }}</td></tr>@empty<tr><td colspan="6" class="text-center text-gray-500">{{ __('uiux.dashboard.common.no_data') }}</td></tr>@endforelse</tbody></table></div>
            @else<div class="dth-empty-state">{{ __('uiux.dashboard.common.no_data') }}</div>@endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
