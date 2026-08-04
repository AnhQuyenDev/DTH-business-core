<?php

namespace App\Filament\Resources;

use App\Enums\Marketing\LandingPageContactAction;
use App\Enums\Marketing\LandingPageSubmissionStatus;
use App\Filament\Resources\LandingPageSubmissionResource\Pages;
use App\Models\Crm\Staff;
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
            TextColumn::make('normalized_email')->label(__('field.email'))->searchable(),
            TextColumn::make('status')->label(__('field.status'))->badge()
                ->formatStateUsing(fn (?LandingPageSubmissionStatus $state): string => $state ? __('enum.landing_page_submission.' . $state->value) : __('common.not_available'))
                ->color(fn (?LandingPageSubmissionStatus $state): string => match ($state?->value) {
                    'processed' => 'success',
                    'received' => 'info',
                    'failed' => 'danger',
                    'spam' => 'warning',
                    default => 'gray',
                }),
            TextColumn::make('contact.qualification.assignedStaff.full_name')->label(__('field.assigned_staff'))->toggleable(),
            TextColumn::make('contact_action')->label(__('field.action'))->badge()
                ->formatStateUsing(fn (?LandingPageContactAction $state): string => $state ? __('enum.landing_page_contact_action.' . $state->value) : __('common.not_available'))
                ->color(fn (?LandingPageContactAction $state): string => match ($state?->value) {
                    'created' => 'success',
                    'updated' => 'info',
                    'skipped' => 'gray',
                    default => 'gray',
                }),
            TextColumn::make('submitted_at')->label(__('field.submitted_at'))->dateTime()->sortable(),
            TextColumn::make('created_at')->label(__('field.created_at'))->dateTime()->toggleable(),
        ])
            ->actions([ActionGroup::make([
                ViewAction::make(),
                Action::make('edit')
                    ->label(__('action.edit'))
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        Select::make('qualification_status')
                            ->label(__('field.status'))
                            ->options([
                                'received' => __('enum.landing_page_submission.received'),
                                'processed' => __('enum.landing_page_submission.processed'),
                                'failed' => __('enum.landing_page_submission.failed'),
                                'spam' => __('enum.landing_page_submission.spam'),
                            ]),
                        Select::make('assigned_staff_id')
                            ->label(__('field.assigned_staff'))
                            ->options(Staff::query()->orderBy('full_name')->pluck('full_name', 'id'))
                            ->searchable(),
                        Textarea::make('note')->label(__('field.note'))->rows(3),
                    ])
                    ->fillForm(fn (LandingPageSubmission $record): array => [
                        'qualification_status' => $record->qualification_status,
                        'assigned_staff_id' => $record->contact?->qualification?->assigned_staff_id,
                    ])
                    ->action(function (LandingPageSubmission $record, array $data): void {
                        $record->update([
                            'qualification_status' => $data['qualification_status'] ?? $record->qualification_status,
                        ]);

                        if ($record->contact?->qualification) {
                            $record->contact->qualification->update([
                                'assigned_staff_id' => $data['assigned_staff_id'] ?? null,
                            ]);
                        }
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
