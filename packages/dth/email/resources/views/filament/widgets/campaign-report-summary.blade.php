<x-filament::section>
    <style>
        .dth-campaign-summary {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1.5rem;
        }

        .dth-campaign-summary__identity,
        .dth-campaign-summary__meta {
            min-width: 0;
        }

        .dth-campaign-summary__identity {
            display: flex;
            flex-direction: column;
            gap: 0.625rem;
        }

        .dth-campaign-summary__title {
            margin: 0;
            color: rgb(17 24 39);
            font-size: 1.125rem;
            font-weight: 600;
            line-height: 1.5rem;
        }

        .dth-campaign-summary__subject {
            margin: 0;
            color: rgb(75 85 99);
            font-size: 0.875rem;
            line-height: 1.25rem;
        }

        .dth-campaign-summary__meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem 1.5rem;
        }

        .dth-campaign-summary__label {
            color: rgb(107 114 128);
            font-size: 0.75rem;
            font-weight: 500;
            letter-spacing: 0.04em;
            line-height: 1rem;
            text-transform: uppercase;
        }

        .dth-campaign-summary__value {
            margin-top: 0.25rem;
            color: rgb(17 24 39);
            font-size: 0.875rem;
            font-weight: 500;
            line-height: 1.25rem;
        }

        .dark .dth-campaign-summary__title,
        .dark .dth-campaign-summary__value {
            color: rgb(255 255 255);
        }

        .dark .dth-campaign-summary__subject {
            color: rgb(209 213 219);
        }

        .dark .dth-campaign-summary__label {
            color: rgb(156 163 175);
        }

        body .fi .fi-grid.lg\:fi-grid-cols:has(.dth-campaign-summary),
        body .fi .fi-grid.lg\:fi-grid-cols:has(.fi-wi-stats-overview),
        body .fi .fi-grid.lg\:fi-grid-cols[style*="--cols-lg: repeat(1"] {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        @media (min-width: 1024px) {
            .dth-campaign-summary {
                grid-template-columns: minmax(0, 5fr) minmax(0, 7fr);
                align-items: start;
            }

            .dth-campaign-summary__meta {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }
    </style>

    <div class="dth-campaign-summary">
        <div class="dth-campaign-summary__identity">
            <div class="flex flex-wrap items-center gap-3">
                <h2 class="dth-campaign-summary__title">
                    {{ $campaign->name }}
                </h2>

                <x-filament::badge :color="$statusColor">
                    {{ $statusLabel }}
                </x-filament::badge>
            </div>

            <p class="dth-campaign-summary__subject">
                {{ $campaign->subject }}
            </p>
        </div>

        <div class="dth-campaign-summary__meta">
            <div>
                <div class="dth-campaign-summary__label">
                    {{ \Dth\Email\Support\UiText::get('reports.sending_account', 'Sending account') }}
                </div>
                <div class="dth-campaign-summary__value">
                    {{ $campaign->sendingAccount?->name ?? '—' }}
                </div>
            </div>

            <div>
                <div class="dth-campaign-summary__label">
                    {{ \Dth\Email\Support\UiText::get('reports.template', 'Template') }}
                </div>
                <div class="dth-campaign-summary__value">
                    {{ $campaign->template?->name ?? '—' }}
                </div>
            </div>

            <div>
                <div class="dth-campaign-summary__label">
                    {{ \Dth\Email\Support\UiText::get('reports.started_at', 'Started at') }}
                </div>
                <div class="dth-campaign-summary__value">
                    {{ $campaign->started_at?->format('d/m/Y H:i') ?? '—' }}
                </div>
            </div>

            <div>
                <div class="dth-campaign-summary__label">
                    {{ \Dth\Email\Support\UiText::get('reports.completed_at', 'Completed at') }}
                </div>
                <div class="dth-campaign-summary__value">
                    {{ $campaign->completed_at?->format('d/m/Y H:i') ?? '—' }}
                </div>
            </div>
        </div>
    </div>
</x-filament::section>
