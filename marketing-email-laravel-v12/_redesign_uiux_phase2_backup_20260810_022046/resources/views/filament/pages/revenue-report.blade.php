<x-filament-panels::page>
    @php
        $r = $this->report;
        $s = $r['summary'];
        $money = static fn ($value): string => number_format((float) $value, 0, ',', '.').' ₫';
        $na = __('common.not_available');
    @endphp

    <div class="space-y-6" wire:loading.class="opacity-70">
        <x-filament::section>
            <x-slot name="heading">{{ __('finance.report.filters_heading') }}</x-slot>
            <x-slot name="description">{{ __('finance.report.filters_description') }}</x-slot>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="dth-filter-label">{{ __('finance.report.start_date') }}</label>
                    <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="startDate" /></x-filament::input.wrapper>
                </div>
                <div>
                    <label class="dth-filter-label">{{ __('finance.report.end_date') }}</label>
                    <x-filament::input.wrapper><x-filament::input type="date" wire:model.live="endDate" /></x-filament::input.wrapper>
                </div>
                <div>
                    <label class="dth-filter-label">{{ __('finance.report.marketing_campaign') }}</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="marketingCampaignId">
                            <option value="">{{ __('finance.report.all') }}</option>
                            @foreach($this->options['marketing_campaigns'] as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <label class="dth-filter-label">{{ __('finance.report.email_campaign') }}</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="emailCampaignId">
                            <option value="">{{ __('finance.report.all') }}</option>
                            @foreach($this->options['email_campaigns'] as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <label class="dth-filter-label">{{ __('finance.report.landing_page') }}</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="landingPageId">
                            <option value="">{{ __('finance.report.all') }}</option>
                            @foreach($this->options['landing_pages'] as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <label class="dth-filter-label">{{ __('finance.report.utm_source') }}</label>
                    <x-filament::input.wrapper><x-filament::input wire:model.live.debounce.400ms="utmSource" placeholder="facebook, instagram, email..." /></x-filament::input.wrapper>
                </div>
                <div>
                    <label class="dth-filter-label">{{ __('finance.report.utm_medium') }}</label>
                    <x-filament::input.wrapper><x-filament::input wire:model.live.debounce.400ms="utmMedium" placeholder="paid_social, organic, email..." /></x-filament::input.wrapper>
                </div>
                <div>
                    <label class="dth-filter-label">{{ __('finance.report.utm_campaign') }}</label>
                    <x-filament::input.wrapper><x-filament::input wire:model.live.debounce.400ms="utmCampaign" placeholder="hosting_q3_2026" /></x-filament::input.wrapper>
                </div>
                <div>
                    <label class="dth-filter-label">{{ __('finance.report.utm_content') }}</label>
                    <x-filament::input.wrapper><x-filament::input wire:model.live.debounce.400ms="utmContent" placeholder="video_a, banner_b..." /></x-filament::input.wrapper>
                </div>
                <div>
                    <label class="dth-filter-label">{{ __('finance.report.service') }}</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="serviceId">
                            <option value="">{{ __('finance.report.all') }}</option>
                            @foreach($this->options['services'] as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <label class="dth-filter-label">{{ __('finance.report.package') }}</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="servicePackageId">
                            <option value="">{{ __('finance.report.all') }}</option>
                            @foreach($this->options['packages'] as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <label class="dth-filter-label">{{ __('finance.report.sales_staff') }}</label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="salesStaffId">
                            <option value="">{{ __('finance.report.all') }}</option>
                            @foreach($this->options['sales_staff'] as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap justify-end gap-2">
                <x-filament::button color="gray" icon="heroicon-m-arrow-path" wire:click="resetFilters">
                    {{ __('finance.report.reset') }}
                </x-filament::button>
                <x-filament::button color="success" icon="heroicon-o-arrow-down-tray" wire:click="exportCsv">
                    {{ __('uiux.dashboard.common.export_csv') }}
                </x-filament::button>
            </div>
        </x-filament::section>

        <div class="dth-report-grid dth-report-grid-4">
            @foreach([
                [__('finance.report.gross_collected'), $money($s['gross_collected']), 'heroicon-o-banknotes'],
                [__('finance.report.net_revenue'), $money($s['net_revenue']), 'heroicon-o-chart-bar-square'],
                [__('finance.report.vat'), $money($s['tax']), 'heroicon-o-receipt-percent'],
                [__('finance.report.paid_transactions'), number_format($s['payments']), 'heroicon-o-check-circle'],
                [__('finance.report.paid_customers'), number_format($s['customers']), 'heroicon-o-user-group'],
                [__('finance.report.average_payment'), $money($s['average_payment']), 'heroicon-o-calculator'],
                [__('finance.report.pending_verification'), $money($s['pending_verification']), 'heroicon-o-clock'],
                [__('finance.report.accepted_outstanding'), $money($s['outstanding_accepted']), 'heroicon-o-exclamation-triangle'],
            ] as [$label, $value, $icon])
                <div class="dth-metric-card">
                    <div class="flex items-center justify-between gap-3">
                        <span class="dth-metric-label">{{ $label }}</span>
                        <x-filament::icon :icon="$icon" class="h-5 w-5 text-gray-400" />
                    </div>
                    <div class="dth-metric-value text-gray-950 dark:text-white">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        <x-filament::section>
            <x-slot name="heading">{{ __('finance.report.campaign_performance') }}</x-slot>
            <x-slot name="description">{{ __('finance.report.campaign_performance_desc') }}</x-slot>
            <div class="overflow-x-auto">
                <table class="dth-report-table">
                    <thead><tr>
                        <th class="text-left">{{ __('finance.report.campaign') }}</th>
                        <th class="text-right">{{ __('finance.report.budget') }}</th>
                        <th class="text-right">{{ __('finance.report.leads') }}</th>
                        <th class="text-right">{{ __('finance.report.paid') }}</th>
                        <th class="text-right">{{ __('finance.report.conversion') }}</th>
                        <th class="text-right">{{ __('finance.report.net_revenue') }}</th>
                        <th class="text-right">{{ __('finance.report.cac') }}</th>
                        <th class="text-right">{{ __('finance.report.roas') }}</th>
                    </tr></thead>
                    <tbody>
                    @forelse($r['marketing_campaigns'] as $row)
                        <tr>
                            <td class="font-semibold">{{ $row['name'] }}</td>
                            <td class="text-right">{{ $money($row['budget']) }}</td>
                            <td class="text-right">{{ $row['leads'] }}</td>
                            <td class="text-right">{{ $row['paid_customers'] }}</td>
                            <td class="text-right">{{ $row['conversion_rate'] }}%</td>
                            <td class="text-right font-semibold">{{ $money($row['net_revenue']) }}</td>
                            <td class="text-right">{{ $row['cac'] === null ? $na : $money($row['cac']) }}</td>
                            <td class="text-right">{{ $row['roas'] === null ? $na : number_format($row['roas'], 2).'x' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-gray-500">{{ __('finance.report.empty_campaign') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">{{ __('finance.report.sources_heading') }}</x-slot>
                <div class="overflow-x-auto"><table class="dth-report-table"><thead><tr>
                    <th class="text-left">{{ __('finance.report.source') }}</th><th class="text-right">{{ __('finance.report.paid') }}</th><th class="text-right">{{ __('finance.report.net_revenue') }}</th><th class="text-right">{{ __('finance.report.gross_collected') }}</th>
                </tr></thead><tbody>
                    @forelse($r['sources'] as $row)<tr><td class="font-semibold">{{ $row['name'] }}</td><td class="text-right">{{ $row['paid_customers'] }}</td><td class="text-right">{{ $money($row['net_revenue']) }}</td><td class="text-right">{{ $money($row['gross_collected']) }}</td></tr>@empty<tr><td colspan="4" class="text-center text-gray-500">{{ __('finance.report.empty') }}</td></tr>@endforelse
                </tbody></table></div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">{{ __('finance.report.landing_pages_heading') }}</x-slot>
                <div class="overflow-x-auto"><table class="dth-report-table"><thead><tr>
                    <th class="text-left">{{ __('finance.report.landing_page') }}</th><th class="text-right">{{ __('finance.report.leads') }}</th><th class="text-right">{{ __('finance.report.paid') }}</th><th class="text-right">{{ __('finance.report.conversion') }}</th><th class="text-right">{{ __('finance.report.net_revenue') }}</th>
                </tr></thead><tbody>
                    @forelse($r['landing_pages'] as $row)<tr><td class="font-semibold">{{ $row['name'] }}</td><td class="text-right">{{ $row['leads'] }}</td><td class="text-right">{{ $row['paid_customers'] }}</td><td class="text-right">{{ $row['conversion_rate'] }}%</td><td class="text-right">{{ $money($row['net_revenue']) }}</td></tr>@empty<tr><td colspan="5" class="text-center text-gray-500">{{ __('finance.report.empty') }}</td></tr>@endforelse
                </tbody></table></div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">{{ __('finance.report.utm_heading') }}</x-slot>
            <div class="overflow-x-auto"><table class="dth-report-table"><thead><tr>
                <th class="text-left">{{ __('finance.report.utm_campaign') }}</th><th class="text-left">{{ __('finance.report.content') }}</th><th class="text-right">{{ __('finance.report.paid_customers') }}</th><th class="text-right">{{ __('finance.report.payments') }}</th><th class="text-right">{{ __('finance.report.net_revenue') }}</th><th class="text-right">{{ __('finance.report.gross_collected') }}</th>
            </tr></thead><tbody>
                @forelse($r['utm_campaigns'] as $row)<tr><td class="font-semibold">{{ $row['name'] }}</td><td>{{ $row['content'] }}</td><td class="text-right">{{ $row['paid_customers'] }}</td><td class="text-right">{{ $row['payments'] }}</td><td class="text-right">{{ $money($row['net_revenue']) }}</td><td class="text-right">{{ $money($row['gross_collected']) }}</td></tr>@empty<tr><td colspan="6" class="text-center text-gray-500">{{ __('finance.report.empty') }}</td></tr>@endforelse
            </tbody></table></div>
        </x-filament::section>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">{{ __('finance.report.services_heading') }}</x-slot>
                <div class="overflow-x-auto"><table class="dth-report-table"><thead><tr>
                    <th class="text-left">{{ __('finance.report.service') }}</th><th class="text-right">{{ __('finance.report.payments') }}</th><th class="text-right">{{ __('finance.report.net_revenue') }}</th><th class="text-right">{{ __('finance.report.vat') }}</th><th class="text-right">{{ __('finance.report.gross_collected') }}</th>
                </tr></thead><tbody>
                    @forelse($r['services'] as $row)<tr><td class="font-semibold">{{ $row['name'] }}</td><td class="text-right">{{ $row['payments'] }}</td><td class="text-right">{{ $money($row['net_revenue']) }}</td><td class="text-right">{{ $money($row['tax']) }}</td><td class="text-right">{{ $money($row['gross_collected']) }}</td></tr>@empty<tr><td colspan="5" class="text-center text-gray-500">{{ __('finance.report.empty') }}</td></tr>@endforelse
                </tbody></table></div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">{{ __('finance.report.packages_heading') }}</x-slot>
                <div class="overflow-x-auto"><table class="dth-report-table"><thead><tr>
                    <th class="text-left">{{ __('finance.report.package') }}</th><th class="text-right">{{ __('finance.report.payments') }}</th><th class="text-right">{{ __('finance.report.net_revenue') }}</th><th class="text-right">{{ __('finance.report.vat') }}</th><th class="text-right">{{ __('finance.report.gross_collected') }}</th>
                </tr></thead><tbody>
                    @forelse($r['packages'] as $row)<tr><td class="font-semibold">{{ $row['name'] }}</td><td class="text-right">{{ $row['payments'] }}</td><td class="text-right">{{ $money($row['net_revenue']) }}</td><td class="text-right">{{ $money($row['tax']) }}</td><td class="text-right">{{ $money($row['gross_collected']) }}</td></tr>@empty<tr><td colspan="5" class="text-center text-gray-500">{{ __('finance.report.empty') }}</td></tr>@endforelse
                </tbody></table></div>
            </x-filament::section>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">{{ __('finance.report.sales_heading') }}</x-slot>
                <div class="overflow-x-auto"><table class="dth-report-table"><thead><tr>
                    <th class="text-left">{{ __('finance.report.sales') }}</th><th class="text-right">{{ __('finance.report.paid_customers') }}</th><th class="text-right">{{ __('finance.report.payments') }}</th><th class="text-right">{{ __('finance.report.net_revenue') }}</th>
                </tr></thead><tbody>
                    @forelse($r['sales'] as $row)<tr><td class="font-semibold">{{ $row['name'] }}</td><td class="text-right">{{ $row['paid_customers'] }}</td><td class="text-right">{{ $row['payments'] }}</td><td class="text-right">{{ $money($row['net_revenue']) }}</td></tr>@empty<tr><td colspan="4" class="text-center text-gray-500">{{ __('finance.report.empty') }}</td></tr>@endforelse
                </tbody></table></div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">{{ __('finance.report.customers_heading') }}</x-slot>
                <div class="overflow-x-auto"><table class="dth-report-table"><thead><tr>
                    <th class="text-left">{{ __('finance.report.customer') }}</th><th class="text-right">{{ __('finance.report.payments') }}</th><th class="text-right">{{ __('finance.report.net_revenue') }}</th><th class="text-right">{{ __('finance.report.gross_collected') }}</th>
                </tr></thead><tbody>
                    @forelse($r['customers'] as $row)<tr><td class="font-semibold">{{ $row['name'] }}</td><td class="text-right">{{ $row['payments'] }}</td><td class="text-right">{{ $money($row['net_revenue']) }}</td><td class="text-right">{{ $money($row['gross_collected']) }}</td></tr>@empty<tr><td colspan="4" class="text-center text-gray-500">{{ __('finance.report.empty') }}</td></tr>@endforelse
                </tbody></table></div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">{{ __('finance.report.email_campaigns_heading') }}</x-slot>
            <div class="overflow-x-auto"><table class="dth-report-table"><thead><tr>
                <th class="text-left">{{ __('finance.report.email_campaign') }}</th><th class="text-right">{{ __('finance.report.paid_customers') }}</th><th class="text-right">{{ __('finance.report.payments') }}</th><th class="text-right">{{ __('finance.report.net_revenue') }}</th><th class="text-right">{{ __('finance.report.gross_collected') }}</th>
            </tr></thead><tbody>
                @forelse($r['email_campaigns'] as $row)<tr><td class="font-semibold">{{ $row['name'] }}</td><td class="text-right">{{ $row['paid_customers'] }}</td><td class="text-right">{{ $row['payments'] }}</td><td class="text-right">{{ $money($row['net_revenue']) }}</td><td class="text-right">{{ $money($row['gross_collected']) }}</td></tr>@empty<tr><td colspan="5" class="text-center text-gray-500">{{ __('finance.report.empty_email_campaign') }}</td></tr>@endforelse
            </tbody></table></div>
        </x-filament::section>

        <div class="dth-note">
            <strong class="text-gray-950 dark:text-white">{{ __('finance.report.v1_note_title') }}:</strong>
            {{ __('finance.report.v1_note') }}
        </div>
    </div>
</x-filament-panels::page>
