<?php

namespace Dth\Marketing\Filament\Resources;

use Dth\Marketing\Enums\LandingPageContactAction;
use Dth\Marketing\Enums\LandingPageSubmissionStatus;
use Dth\Marketing\Filament\Navigation\MarketingNavigationGroup;
use Dth\Marketing\Filament\Resources\LandingPageSubmissionResource\Pages;
use Dth\Marketing\Filament\Support\StatusColor;
use Dth\Marketing\Models\LandingPageSubmission;
use Dth\Marketing\Services\LandingPageSubmissionService;
use Dth\Marketing\Support\UiText;
use Filament\Actions;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Throwable;

class LandingPageSubmissionResource extends Resource
{
    protected static ?string $model = LandingPageSubmission::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-inbox-stack';
    protected static string|\UnitEnum|null $navigationGroup = MarketingNavigationGroup::Marketing;
    protected static ?int $navigationSort = 40;
    protected static ?string $recordTitleAttribute = 'display_name';

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.submissions', 'Submissions', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.submission', 'Landing Submission', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.submissions', 'Landing Submissions', context: 'model');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('landingPage.name')
                    ->label(UiText::get('submission.landing_page', 'Landing Page'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('submission_type')
                    ->label(UiText::get('submission.type', 'Type'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'personal' => UiText::get('form.audience.personal', 'Personal'),
                        'business' => UiText::get('form.audience.business', 'Business'),
                        default => $state ?: UiText::get('common.fields.not_available', 'N/A'),
                    }),
                TextColumn::make('display_name')
                    ->label(UiText::get('submission.name', 'Name'))
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('normalized_email')
                    ->label(UiText::get('submission.email', 'Email'))
                    ->searchable()
                    ->copyable()
                    ->placeholder('—'),
                TextColumn::make('normalized_phone')
                    ->label(UiText::get('submission.phone', 'Phone'))
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->color(fn ($state): string => StatusColor::for($state)),
                TextColumn::make('contact_action')
                    ->label(UiText::get('submission.contact_action', 'Contact action'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? UiText::status($state) : UiText::get('common.fields.not_available', 'N/A'))
                    ->color(fn ($state): string => StatusColor::for($state))
                    ->placeholder(UiText::get('common.fields.not_available', 'N/A')),
                TextColumn::make('lead_code')
                    ->label(UiText::get('submission.lead_code', 'Lead'))
                    ->searchable()
                    ->placeholder(UiText::get('common.fields.not_available', 'N/A')),
                TextColumn::make('utm_source')
                    ->label(UiText::get('submission.utm_source', 'UTM source'))
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('submitted_at')
                    ->label(UiText::get('submission.submitted_at', 'Submitted'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('submission_type')
                    ->label(UiText::get('submission.type', 'Type'))
                    ->options([
                        'personal' => UiText::get('form.audience.personal', 'Personal'),
                        'business' => UiText::get('form.audience.business', 'Business'),
                    ]),
                SelectFilter::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->options(LandingPageSubmissionStatus::options()),
                SelectFilter::make('landing_page_id')
                    ->label(UiText::get('submission.landing_page', 'Landing Page'))
                    ->relationship('landingPage', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(UiText::get('common.actions.view', 'View'))
                    ->icon('heroicon-o-eye'),
                Actions\Action::make('retry')
                    ->label(UiText::get('submission.retry', 'Retry'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (LandingPageSubmission $record): bool => app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->processSubmissions(auth()->user()) && in_array(
                        self::statusValue($record),
                        [LandingPageSubmissionStatus::Failed->value, LandingPageSubmissionStatus::Received->value],
                        true,
                    ))
                    ->action(function (LandingPageSubmission $record): void {
                        try {
                            app(LandingPageSubmissionService::class)->retry($record);
                            Notification::make()
                                ->title(UiText::get('submission.retry_success', 'Submission processed'))
                                ->success()
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);
                            Notification::make()
                                ->title(UiText::get('submission.retry_failed', 'Submission could not be processed'))
                                ->body($exception->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Actions\Action::make('mark_spam')
                    ->label(UiText::get('submission.mark_spam', 'Mark spam'))
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (LandingPageSubmission $record): bool => app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->processSubmissions(auth()->user()) && self::statusValue($record) !== LandingPageSubmissionStatus::Spam->value)
                    ->action(fn (LandingPageSubmission $record) => app(LandingPageSubmissionService::class)->markSpamByAdmin($record)),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('retry_failed')
                        ->visible(fn (): bool => app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->processSubmissions(auth()->user()))
                        ->label(UiText::get('submission.retry_failed_bulk', 'Retry failed'))
                        ->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $processed = 0;
                            $failed = 0;

                            foreach ($records as $record) {
                                if (! $record instanceof LandingPageSubmission || ! in_array(
                                    self::statusValue($record),
                                    [LandingPageSubmissionStatus::Failed->value, LandingPageSubmissionStatus::Received->value],
                                    true,
                                )) {
                                    continue;
                                }

                                try {
                                    app(LandingPageSubmissionService::class)->retry($record);
                                    $processed++;
                                } catch (Throwable $exception) {
                                    report($exception);
                                    $failed++;
                                }
                            }

                            $notification = Notification::make()
                                ->title(UiText::get('submission.bulk_retry_done', 'Bulk retry completed'))
                                ->body(UiText::get('submission.bulk_retry_result', 'Processed: :processed; failed: :failed.', ['processed' => $processed, 'failed' => $failed]));

                            $failed > 0 ? $notification->warning() : $notification->success();
                            $notification->send();
                        }),
                    Actions\BulkAction::make('mark_spam')
                        ->visible(fn (): bool => app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->processSubmissions(auth()->user()))
                        ->label(UiText::get('submission.mark_spam', 'Mark spam'))
                        ->icon('heroicon-o-shield-exclamation')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                if ($record instanceof LandingPageSubmission) {
                                    app(LandingPageSubmissionService::class)->markSpamByAdmin($record);
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('submitted_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(12)
            ->components([
                Section::make(UiText::get('submission.identity', 'Submission'))
                    ->schema([
                        TextEntry::make('landingPage.name')->label(UiText::get('submission.landing_page', 'Landing Page')),
                        TextEntry::make('submission_type')->label(UiText::get('submission.type', 'Type')),
                        TextEntry::make('status')
                            ->label(UiText::get('common.fields.status', 'Status'))
                            ->badge()
                            ->formatStateUsing(fn ($state): string => UiText::status($state))
                            ->color(fn ($state): string => StatusColor::for($state)),
                        TextEntry::make('contact_action')
                            ->label(UiText::get('submission.contact_action', 'Contact action'))
                            ->placeholder(UiText::get('common.fields.not_available', 'N/A')),
                        TextEntry::make('display_name')->label(UiText::get('submission.name', 'Name'))->placeholder('—'),
                        TextEntry::make('normalized_email')->label(UiText::get('submission.email', 'Email'))->copyable()->placeholder('—'),
                        TextEntry::make('normalized_phone')->label(UiText::get('submission.phone', 'Phone'))->placeholder('—'),
                        TextEntry::make('company_name')->label(UiText::get('submission.company', 'Company'))->placeholder('—'),
                        TextEntry::make('contact_reference')->label(UiText::get('submission.contact_reference', 'CRM Contact reference'))->placeholder(UiText::get('common.fields.not_available', 'N/A'))->copyable(),
                        TextEntry::make('lead_reference')->label(UiText::get('submission.lead_reference', 'CRM Lead reference'))->placeholder(UiText::get('common.fields.not_available', 'N/A'))->copyable(),
                        TextEntry::make('lead_code')->label(UiText::get('submission.lead_code', 'Lead code'))->placeholder(UiText::get('common.fields.not_available', 'N/A')),
                        TextEntry::make('lead_status')->label(UiText::get('submission.lead_status', 'Lead status'))->placeholder(UiText::get('common.fields.not_available', 'N/A')),
                        TextEntry::make('submitted_at')->label(UiText::get('submission.submitted_at', 'Submitted'))->dateTime('d/m/Y H:i:s'),
                        TextEntry::make('processed_at')->label(UiText::get('submission.processed_at', 'Processed'))->dateTime('d/m/Y H:i:s')->placeholder('—'),
                        TextEntry::make('failure_reason')->label(UiText::get('submission.failure', 'Failure'))->placeholder('—')->columnSpanFull(),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
                Section::make(UiText::get('submission.attribution', 'Attribution'))
                    ->schema([
                        TextEntry::make('source')->label(UiText::get('submission.source', 'Source'))->placeholder(UiText::get('submission.direct', 'direct')),
                        TextEntry::make('utm_source')->label(UiText::get('submission.utm_source', 'UTM source'))->placeholder('—'),
                        TextEntry::make('utm_medium')->label(UiText::get('submission.utm_medium', 'UTM medium'))->placeholder('—'),
                        TextEntry::make('utm_campaign')->label(UiText::get('submission.utm_campaign', 'UTM campaign'))->placeholder('—'),
                        TextEntry::make('utm_content')->label(UiText::get('submission.utm_content', 'UTM content'))->placeholder('—'),
                        TextEntry::make('utm_term')->label(UiText::get('submission.utm_term', 'UTM term'))->placeholder('—'),
                        TextEntry::make('referrer')->label(UiText::get('submission.referrer', 'Referrer'))->placeholder('—')->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
                Section::make(UiText::get('submission.payload', 'Payload'))
                    ->schema([
                        TextEntry::make('data')
                            ->label(UiText::get('submission.data', 'Raw submitted data'))
                            ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}')
                            ->copyable()
                            ->columnSpanFull(),
                        TextEntry::make('normalized_data')
                            ->label(UiText::get('submission.normalized_data', 'Normalized semantic data'))
                            ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}')
                            ->copyable()
                            ->columnSpanFull(),
                        TextEntry::make('integration_snapshot')
                            ->label(UiText::get('submission.integration_snapshot', 'Integration capability snapshot'))
                            ->formatStateUsing(fn ($state): string => json_encode($state ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['landingPage', 'formTemplate.fields', 'marketingCampaign']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLandingPageSubmissions::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    private static function statusValue(LandingPageSubmission $record): string
    {
        return $record->status instanceof \BackedEnum
            ? (string) $record->status->value
            : (string) $record->status;
    }
}
