<x-filament-widgets::widget class="dth-com-pipeline-widget">
    <section class="dth-com-pipeline">
        <header class="dth-com-pipeline__header">
            <div>
                <span class="dth-com-eyebrow">{{ \Dth\Commercial\Support\UiText::get('overview.kicker_pipeline', 'PIPELINE') }}</span>
                <h2>{{ $title }}</h2>
                <p>{{ $subheading }}</p>
            </div>
        </header>

        <div class="dth-com-pipeline__scroll">
            <div class="dth-com-pipeline__grid">
                @foreach ($columns as $column)
                    <section class="dth-com-stage" data-tone="{{ $column['tone'] }}">
                        <header class="dth-com-stage__header">
                            <div class="dth-com-stage__title-row">
                                <span class="dth-com-stage__dot"></span>
                                <strong>{{ $column['label'] }}</strong>
                                <span class="dth-com-stage__count">{{ $column['count'] }}</span>
                            </div>
                            <div class="dth-com-stage__value">{{ number_format($column['value'], 0, ',', '.') }} ₫</div>
                        </header>

                        <div class="dth-com-stage__cards">
                            @forelse ($column['records'] as $record)
                                <a class="dth-com-opportunity-card" href="{{ \Dth\Commercial\Filament\Resources\OpportunityResource::getUrl('edit', ['record' => $record]) }}">
                                    <div class="dth-com-opportunity-card__head">
                                        <strong>{{ $record->title }}</strong>
                                        <x-filament::icon icon="heroicon-o-chevron-right" />
                                    </div>
                                    <span class="dth-com-opportunity-card__customer">
                                        {{ $record->company_name_snapshot ?: ($record->contact_name_snapshot ?: $record->opportunity_code) }}
                                    </span>
                                    @if ($record->service_name_snapshot)
                                        <span class="dth-com-opportunity-card__service">{{ $record->service_name_snapshot }}</span>
                                    @endif
                                    <div class="dth-com-opportunity-card__numbers">
                                        <strong>{{ number_format((float) $record->estimated_value, 0, ',', '.') }} ₫</strong>
                                        <span>{{ (int) $record->probability }}%</span>
                                    </div>
                                    <div class="dth-com-opportunity-card__footer">
                                        <span>
                                            <x-filament::icon icon="heroicon-o-calendar-days" />
                                            {{ $record->expected_close_date?->format('d/m/Y') ?: '—' }}
                                        </span>
                                        @if ($record->assigned_employee_name_snapshot)
                                            <span class="dth-com-owner-chip">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($record->assigned_employee_name_snapshot, 0, 2)) }}</span>
                                        @endif
                                    </div>
                                </a>
                            @empty
                                <div class="dth-com-stage__empty">{{ \Dth\Commercial\Support\UiText::get('pipeline.empty', 'No opportunities in this stage') }}</div>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>
        </div>
    </section>
</x-filament-widgets::widget>
