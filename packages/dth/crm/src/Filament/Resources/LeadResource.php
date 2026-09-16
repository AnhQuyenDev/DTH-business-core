<?php

namespace Dth\Crm\Filament\Resources;

use Dth\Crm\Enums\LeadActivityType;
use Dth\Crm\Enums\LeadIntakeStatus;
use Dth\Crm\Filament\Navigation\CrmNavigationGroup;
use Dth\Crm\Filament\Resources\LeadResource\Pages;
use Dth\Crm\Filament\Resources\LeadResource\RelationManagers\ActivitiesRelationManager;
use Dth\Crm\Models\Lead;
use Dth\Crm\Models\CrmAgentProfile;
use Dth\Crm\Services\LeadActivityService;
use Dth\Crm\Services\LeadDistributionService;
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

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-funnel';
    protected static string|\UnitEnum|null $navigationGroup = CrmNavigationGroup::Crm;
    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.leads', 'Leads', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.lead', 'Lead', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.leads', 'Leads', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.lead', 'Lead'))
                ->schema([
                    TextInput::make('lead_code')
                        ->label(UiText::get('fields.lead_code', 'Lead code'))
                        ->disabledOn('edit'),
                    TextInput::make('title')
                        ->label(UiText::get('fields.title', 'Title')),
                    TextInput::make('source')
                        ->label(UiText::get('common.fields.source', 'Source')),
                    TextInput::make('service_interest')
                        ->label(UiText::get('fields.service_interest', 'Service interest')),
                    TextInput::make('service_reference')
                        ->label(UiText::get('fields.service_reference', 'Service reference')),
                    TextInput::make('estimated_value')
                        ->label(UiText::get('fields.estimated_value', 'Estimated value'))
                        ->numeric(),
                    Select::make('assigned_agent_profile_id')
                        ->label(UiText::get('fields.assigned_agent', 'CRM assignee'))
                        ->options(fn (): array => CrmAgentProfile::options(assignmentEnabledOnly: true))
                        ->searchable()
                        ->preload(),
                    Select::make('intake_status')
                        ->label(UiText::get('common.fields.status', 'Status'))
                        ->options(LeadIntakeStatus::options())
                        ->native(false),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('lead_code')
                    ->label(UiText::get('fields.lead_code', 'Lead code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('contact.display_name')
                    ->label(UiText::get('models.contact', 'Contact'))
                    ->searchable(),
                TextColumn::make('company.legal_name')
                    ->label(UiText::get('models.company', 'Company'))
                    ->searchable(),
                TextColumn::make('service_interest')
                    ->label(UiText::get('fields.service', 'Service'))
                    ->placeholder('—'),
                TextColumn::make('intake_status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CrmOptions::label('lead_status', $state))
                    ->color(fn ($state): string => StatusColor::for($state, 'lead_status')),
                TextColumn::make('assignedAgentProfile.employee.full_name')
                    ->label(UiText::get('fields.owner', 'Owner'))
                    ->placeholder(UiText::get('fields.unassigned', 'Unassigned')),
            ])
            ->filters([
                SelectFilter::make('intake_status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->options(LeadIntakeStatus::options()),
                SelectFilter::make('assigned_agent_profile_id')
                    ->label(UiText::get('fields.assigned_agent', 'CRM assignee'))
                    ->options(fn (): array => CrmAgentProfile::options())
                    ->searchable(),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make()->icon('heroicon-o-eye')
                        ->label(UiText::get('common.actions.view', 'View')),
                    Actions\Action::make('distribute')
                        ->label(UiText::get('actions.distribute_lead', 'Distribute Lead'))
                        ->icon('heroicon-o-paper-airplane')
                        ->visible(fn (Lead $record): bool => $record->assigned_agent_profile_id === null
                            && ! in_array($record->intake_status, ['duplicate', 'spam', 'closed', 'converted_to_opportunity'], true))
                        ->action(function (Lead $record): void {
                            app(LeadDistributionService::class)->distribute($record, auth()->id());

                            Notification::make()
                                ->success()
                                ->title(UiText::get('notifications.lead_distributed', 'Lead distributed'))
                                ->send();
                        }),
                    Actions\Action::make('accept')
                        ->label(UiText::get('actions.accept_lead', 'Accept Lead'))
                        ->icon('heroicon-o-check')
                        ->visible(fn (Lead $record): bool => $record->assigned_agent_profile_id !== null
                            && CrmAgentProfile::query()
                                ->forUser(auth()->id())
                                ->whereKey($record->assigned_agent_profile_id)
                                ->exists())
                        ->action(function (Lead $record): void {
                            $profile = CrmAgentProfile::query()->forUser(auth()->id())->firstOrFail();
                            app(LeadDistributionService::class)->accept($record, $profile->id);

                            Notification::make()
                                ->success()
                                ->title(UiText::get('notifications.lead_accepted', 'Lead accepted'))
                                ->send();
                        }),
                    Actions\Action::make('activity')
                        ->label(UiText::get('actions.log_activity', 'Log activity'))
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->modalSubmitAction(fn ($action) => $action->label(UiText::get('common.actions.save', 'Save'))->icon('heroicon-o-check-circle'))
                        ->modalCancelAction(fn ($action) => $action->label(UiText::get('common.actions.cancel', 'Cancel'))->icon('heroicon-o-x-mark')->color('gray'))
                        ->form([
                            Select::make('type')
                                ->label(UiText::get('fields.activity_type', 'Activity type'))
                                ->options(LeadActivityType::options())
                                ->required(),
                            TextInput::make('subject')
                                ->label(UiText::get('fields.subject', 'Subject')),
                            Textarea::make('content')
                                ->label(UiText::get('fields.content', 'Content'))
                                ->rows(3),
                            DateTimePicker::make('next_follow_up_at')
                                ->label(UiText::get('fields.next_follow_up', 'Next follow up')),
                        ])
                        ->action(function (Lead $record, array $data): void {
                            $profile = CrmAgentProfile::query()->forUser(auth()->id())->first();
                            app(LeadActivityService::class)->record($record, $data + ['agent_profile_id' => $profile?->id]);

                            Notification::make()
                                ->success()
                                ->title(UiText::get('notifications.activity_logged', 'Activity logged'))
                                ->send();
                        }),
                    Actions\EditAction::make()->icon('heroicon-o-pencil-square')
                        ->label(UiText::get('common.actions.edit', 'Edit')),
                    Actions\DeleteAction::make()->icon('heroicon-o-trash')
                        ->label(UiText::get('common.actions.delete', 'Delete')),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('distribute')
                        ->label(UiText::get('actions.distribute_leads', 'Distribute Leads'))
                        ->icon('heroicon-o-paper-airplane')
                        ->action(fn ($records) => $records->each(
                            fn (Lead $record) => $record->assigned_agent_profile_id
                                ?: app(LeadDistributionService::class)->distribute($record, auth()->id())
                        )),
                    Actions\DeleteBulkAction::make()->icon('heroicon-o-trash')
                        ->label(UiText::get('common.actions.delete', 'Delete'))
                        ->authorizeIndividualRecords(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'view' => Pages\ViewLead::route('/{record}'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [ActivitiesRelationManager::class];
    }
}
