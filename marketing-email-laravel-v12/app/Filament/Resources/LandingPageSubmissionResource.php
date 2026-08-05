<?php

namespace App\Filament\Resources;

use App\Enums\Marketing\LandingPageContactAction;
use App\Enums\Marketing\LandingPageSubmissionStatus;
use App\Filament\Resources\LandingPageSubmissionResource\Pages;
use App\Models\Marketing\LandingPageSubmission;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LandingPageSubmissionResource extends Resource
{
    protected static ?string $model = LandingPageSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?int $navigationSort = 40;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.landing_page_submission.singular');
    }

    public static function getModelLabel(): string
    {
        return __('resource.landing_page_submission.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.landing_page_submission.plural');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->isMarketingStaff() || $user?->isCustomerServiceStaff()) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Textarea::make('data')
                ->label(__('field.data'))
                ->disabled()
                ->rows(24)
                ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : (string) $state)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('landingPage.name')->label(__('field.landing_page'))->searchable(),
            TextColumn::make('submission_type')
                ->label(__('field.submission_type'))
                ->badge()
                ->formatStateUsing(fn (?string $state): string => match ($state) {
                    'personal' => __('submission.type.personal'),
                    'business' => __('submission.type.business'),
                    default => __('common.not_available'),
                })
                ->color(fn (?string $state): string => match ($state) {
                    'personal' => 'info',
                    'business' => 'warning',
                    default => 'gray',
                }),
            TextColumn::make('normalized_email')->label(__('field.email'))->searchable(),
            TextColumn::make('status')->label(__('field.intake_status'))->badge()
                ->formatStateUsing(function ($state): string {
                    $value = $state?->value ?? $state;

                    return match ($value) {
                        'received' => __('submission.status.received'),
                        'processed' => __('submission.status.processed'),
                        'failed' => __('submission.status.failed'),
                        'spam' => __('submission.status.spam'),
                        default => $value ?: __('common.not_available'),
                    };
                })
                ->color(fn (?LandingPageSubmissionStatus $state): string => match ($state?->value) {
                    'processed' => 'success',
                    'received' => 'info',
                    'failed' => 'danger',
                    'spam' => 'warning',
                    default => 'gray',
                }),
            TextColumn::make('distribution_state')
                ->label(__('field.distribution_state'))
                ->badge()
                ->getStateUsing(fn (LandingPageSubmission $record): string => $record->contact?->qualification?->assigned_staff_id
                    ? 'assigned'
                    : 'unassigned')
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'assigned' => __('distribution.assigned'),
                    default => __('distribution.unassigned'),
                })
                ->color(fn (string $state): string => $state === 'assigned' ? 'success' : 'warning'),
            TextColumn::make('contact.qualification.status')
                ->label(__('field.crm_status'))
                ->badge()
                ->formatStateUsing(function ($state): string {
                    $value = $state?->value ?? $state;

                    return match ($value) {
                        'new' => __('lead.status.new'),
                        'assigned' => __('lead.status.assigned'),
                        'contacting' => __('lead.status.contacting'),
                        'follow_up' => __('lead.status.follow_up'),
                        'qualified' => __('lead.status.qualified'),
                        'unqualified' => __('lead.status.unqualified'),
                        'converted' => __('lead.status.converted'),
                        default => $value ?: __('common.not_available'),
                    };
                })
                ->color(fn ($state): string => match ($state?->value ?? $state) {
                    'new' => 'gray',
                    'assigned' => 'info',
                    'contacting' => 'warning',
                    'follow_up' => 'primary',
                    'qualified', 'converted' => 'success',
                    'unqualified', 'spam', 'duplicate', 'archived' => 'danger',
                    default => 'gray',
                }),
            TextColumn::make('contact_action')->label(__('field.action'))->badge()
                ->formatStateUsing(fn (?LandingPageContactAction $state): string => $state ? __('enum.landing_page_contact_action.'.$state->value) : __('common.not_available'))
                ->color(fn (?LandingPageContactAction $state): string => match ($state?->value) {
                    'created' => 'success',
                    'updated' => 'info',
                    'skipped' => 'gray',
                    default => 'gray',
                }),
            TextColumn::make('submitted_at')->label(__('field.submitted_at'))->dateTime()->sortable(),
            TextColumn::make('created_at')->label(__('field.created_at'))->dateTime()->toggleable(),
        ])
            ->filters([
                SelectFilter::make('submission_type')
                    ->label(__('field.submission_type'))
                    ->options([
                        'personal' => __('submission.type.personal'),
                        'business' => __('submission.type.business'),
                    ]),
                SelectFilter::make('status')
                    ->label(__('field.intake_status'))
                    ->options([
                        'received' => __('submission.status.received'),
                        'processed' => __('submission.status.processed'),
                        'failed' => __('submission.status.failed'),
                        'spam' => __('submission.status.spam'),
                    ]),
            ])
            ->actions([ActionGroup::make([
                ViewAction::make(),
                Action::make('edit')
                    ->label(__('action.edit'))
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        Select::make('status')
                            ->label(__('field.intake_status'))
                            ->options([
                                'received' => __('submission.status.received'),
                                'processed' => __('submission.status.processed'),
                                'failed' => __('submission.status.failed'),
                                'spam' => __('submission.status.spam'),
                            ]),
                        Textarea::make('note')->label(__('field.note'))->rows(3),
                    ])
                    ->fillForm(fn (LandingPageSubmission $record): array => [
                        'status' => $record->status?->value ?? $record->status,
                        'note' => null,
                    ])
                    ->action(function (LandingPageSubmission $record, array $data): void {
                        $record->update([
                            'status' => $data['status'] ?? $record->status,
                        ]);
                    }),
                DeleteAction::make(),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton()])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLandingPageSubmissions::route('/'),
        ];
    }
}
