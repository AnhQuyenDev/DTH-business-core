<?php

namespace Dth\Crm\Filament\Resources;

use Dth\Crm\Enums\QualificationStatus;
use Dth\Crm\Filament\Navigation\CrmNavigationGroup;
use Dth\Crm\Filament\Resources\ContactQualificationResource\Pages;
use Dth\Crm\Models\ContactQualification;
use Dth\Crm\Services\QualificationService;
use Dth\Crm\Support\CrmOptions;
use Dth\Crm\Support\StatusColor;
use Dth\Crm\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactQualificationResource extends Resource
{
    protected static ?string $model = ContactQualification::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static string|\UnitEnum|null $navigationGroup = CrmNavigationGroup::Crm;
    protected static ?int $navigationSort = 40;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.qualifications', 'Lead qualification', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.qualification', 'Lead qualification', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.qualifications', 'Lead qualifications', context: 'model');
    }


    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.qualification', 'Lead qualification'))
                ->schema([
                    Select::make('status')
                        ->label(UiText::get('common.fields.status', 'Status'))
                        ->options(QualificationStatus::options())
                        ->disabled(),
                    Select::make('priority')
                        ->label(UiText::get('fields.priority', 'Priority'))
                        ->options(CrmOptions::priorities())
                        ->native(false),
                    TextInput::make('score')
                        ->label(UiText::get('fields.score', 'Score'))
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100),
                    TextInput::make('service_interest')
                        ->label(UiText::get('fields.service_interest', 'Service interest')),
                    Select::make('budget_status')
                        ->label(UiText::get('fields.budget_status', 'Budget status'))
                        ->options(CrmOptions::budgetStatuses())
                        ->native(false),
                    TextInput::make('budget_amount')
                        ->label(UiText::get('fields.budget_amount', 'Budget amount'))
                        ->numeric(),
                    Select::make('purchase_timeline')
                        ->label(UiText::get('fields.purchase_timeline', 'Purchase timeline'))
                        ->options(CrmOptions::purchaseTimelines())
                        ->native(false),
                    Select::make('decision_role')
                        ->label(UiText::get('fields.decision_role', 'Decision role'))
                        ->options(CrmOptions::decisionRoles())
                        ->native(false),
                    DateTimePicker::make('next_follow_up_at')
                        ->label(UiText::get('fields.next_follow_up', 'Next follow up')),
                    Textarea::make('qualification_note')
                        ->label(UiText::get('fields.qualification_note', 'Qualification note'))
                        ->columnSpanFull(),
                    Textarea::make('unqualified_reason')
                        ->label(UiText::get('fields.unqualified_reason', 'Unqualified reason'))
                        ->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lead.lead_code')
                    ->label(UiText::get('fields.lead_code', 'Lead code'))
                    ->searchable(),
                TextColumn::make('contact.display_name')
                    ->label(UiText::get('models.contact', 'Contact')),
                TextColumn::make('assignedAgentProfile.employee.full_name')
                    ->label(UiText::get('fields.owner', 'Owner'))
                    ->placeholder(UiText::get('fields.unassigned', 'Unassigned')),
                TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CrmOptions::label('qualification_status', $state))
                    ->color(fn ($state): string => StatusColor::for($state, 'qualification_status')),
                TextColumn::make('priority')
                    ->label(UiText::get('fields.priority', 'Priority'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CrmOptions::label('priority', $state))
                    ->color(fn ($state): string => StatusColor::for($state, 'priority')),
                TextColumn::make('score')
                    ->label(UiText::get('fields.score', 'Score')),
                TextColumn::make('next_follow_up_at')
                    ->label(UiText::get('fields.next_follow_up', 'Next follow up'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->options(QualificationStatus::options()),
                SelectFilter::make('priority')
                    ->label(UiText::get('fields.priority', 'Priority'))
                    ->options(CrmOptions::priorities()),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    self::transition('contacting', 'actions.start_contacting', 'Start contacting'),
                    self::transition('follow_up', 'actions.follow_up', 'Follow up', true),
                    self::transition('qualified', 'actions.mark_qualified', 'Mark qualified'),
                    self::transition('unqualified', 'actions.mark_unqualified', 'Mark unqualified'),
                    self::transition('duplicate', 'actions.mark_duplicate', 'Mark duplicate'),
                    self::transition('spam', 'actions.mark_spam', 'Mark spam'),
                    self::transition('archived', 'actions.archive', 'Archive'),
                    Actions\ViewAction::make()->icon('heroicon-o-eye')
                        ->label(UiText::get('common.actions.view', 'View')),
                    Actions\EditAction::make()->icon('heroicon-o-pencil-square')
                        ->label(UiText::get('common.actions.edit', 'Edit')),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->defaultSort('id', 'desc');
    }

    private static function transition(
        string $to,
        string $labelKey,
        string $defaultLabel,
        bool $needDate = false,
    ): Actions\Action {
        $action = Actions\Action::make('to_'.$to)
            ->label(UiText::get($labelKey, $defaultLabel))
            ->visible(fn (ContactQualification $record): bool => $record->status !== $to)
            ->modalSubmitAction(fn ($action) => $action->icon('heroicon-o-check-circle'))
            ->modalCancelAction(fn ($action) => $action->icon('heroicon-o-x-mark')->color('gray'))
            ->form(array_values(array_filter([
                $needDate
                    ? DateTimePicker::make('next_follow_up_at')
                        ->label(UiText::get('fields.next_follow_up', 'Next follow up'))
                        ->required()
                    : null,
                $to === 'qualified'
                    ? TextInput::make('service_interest')
                        ->label(UiText::get('fields.service_interest', 'Service interest'))
                        ->required()
                    : null,
                $to === 'qualified'
                    ? Select::make('budget_status')
                        ->label(UiText::get('fields.budget_status', 'Budget status'))
                        ->options(CrmOptions::budgetStatuses())
                        ->native(false)
                        ->required()
                    : null,
                $to === 'qualified'
                    ? TextInput::make('budget_amount')
                        ->label(UiText::get('fields.budget_amount', 'Budget amount'))
                        ->numeric()
                        ->minValue(0)
                    : null,
                $to === 'qualified'
                    ? Select::make('purchase_timeline')
                        ->label(UiText::get('fields.purchase_timeline', 'Purchase timeline'))
                        ->options(CrmOptions::purchaseTimelines())
                        ->native(false)
                        ->required()
                    : null,
                $to === 'qualified'
                    ? Select::make('decision_role')
                        ->label(UiText::get('fields.decision_role', 'Decision role'))
                        ->options(CrmOptions::decisionRoles())
                        ->native(false)
                    : null,
                $to === 'unqualified'
                    ? Textarea::make('unqualified_reason')
                        ->label(UiText::get('fields.reason', 'Reason'))
                        ->required()
                    : null,
            ])))
            ->action(function (ContactQualification $record, array $data) use ($to): void {
                app(QualificationService::class)->transition($record, $to, $data);

                Notification::make()
                    ->success()
                    ->title(UiText::get('notifications.status_updated', 'Status updated'))
                    ->send();
            });

        return match ($to) {
            'qualified' => $action->color('success')->icon('heroicon-o-check-circle'),
            'unqualified', 'spam' => $action->color('danger')->icon('heroicon-o-x-circle'),
            'duplicate', 'follow_up' => $action->color('warning')->icon('heroicon-o-clock'),
            'archived' => $action->color('gray')->icon('heroicon-o-archive-box'),
            default => $action->color('info')->icon('heroicon-o-phone-arrow-up-right'),
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactQualifications::route('/'),
            'view' => Pages\ViewContactQualification::route('/{record}'),
            'edit' => Pages\EditContactQualification::route('/{record}/edit'),
        ];
    }
}
