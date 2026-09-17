<?php

namespace Dth\Commercial\Filament\Resources;

use Dth\Commercial\Enums\OpportunityStage;
use Dth\Commercial\Filament\Navigation\CommercialNavigationGroup;
use Dth\Commercial\Filament\Resources\OpportunityResource\Pages;
use Dth\Commercial\Models\Opportunity;
use Dth\Commercial\Models\Service;
use Dth\Commercial\Services\OpportunityWorkflowService;
use Dth\Commercial\Support\CommercialAuthorization;
use Dth\Commercial\Support\CrmLeadLookup;
use Dth\Commercial\Support\StatusColor;
use Dth\Commercial\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class OpportunityResource extends Resource
{
    protected static ?string $model = Opportunity::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-briefcase';
    protected static string|\UnitEnum|null $navigationGroup = CommercialNavigationGroup::Commercial;
    protected static ?int $navigationSort = 30;
    protected static ?string $slug = 'commercial-opportunities';
    protected static ?string $recordTitleAttribute = 'title';

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.opportunities', 'Business opportunities', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.opportunity', 'Business opportunity', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.opportunities', 'Business opportunities', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
                Section::make(UiText::get('sections.opportunity_basic', 'Basic information'))
                    ->description(UiText::get('sections.opportunity_basic_help', 'Select a CRM Lead when available to reuse customer context automatically, then complete the commercial details for the opportunity.'))
                    ->schema([
                        Select::make('lead_reference')
                            ->label(UiText::get('fields.crm_lead', 'CRM Lead'))
                            ->placeholder(fn (): string => app(CrmLeadLookup::class)->available()
                                ? UiText::get('fields.crm_lead_placeholder', 'Search by Lead code, title, contact or company')
                                : UiText::get('fields.crm_lead_unavailable', 'CRM module is not currently available'))
                            ->helperText(fn (): string => app(CrmLeadLookup::class)->available()
                                ? UiText::get('fields.crm_lead_help', 'Selecting a Lead automatically fills customer, contact, owner, service context and estimated value when available.')
                                : UiText::get('fields.crm_lead_unavailable_help', 'The opportunity can still be created independently. Existing CRM snapshots remain readable.'))
                            ->options(fn (): array => app(CrmLeadLookup::class)->search('', 25))
                            ->searchable()
                            ->getSearchResultsUsing(fn (string $search): array => app(CrmLeadLookup::class)->search($search))
                            ->getOptionLabelUsing(fn ($value): ?string => app(CrmLeadLookup::class)->label($value))
                            ->disabled(fn (): bool => ! app(CrmLeadLookup::class)->available())
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get): void {
                                if (blank($state)) {
                                    $set('lead_code_snapshot', null);
                                    $set('contact_reference', null);
                                    $set('company_reference', null);
                                    $set('assigned_employee_reference', null);
                                    return;
                                }

                                $snapshot = app(CrmLeadLookup::class)->snapshot($state);
                                if (! $snapshot) {
                                    return;
                                }

                                $set('lead_code_snapshot', $snapshot['lead_code']);
                                $set('contact_reference', $snapshot['contact_reference']);
                                $set('contact_name_snapshot', $snapshot['contact_name']);
                                $set('company_reference', $snapshot['company_reference']);
                                $set('company_name_snapshot', $snapshot['company_name']);
                                $set('assigned_employee_reference', $snapshot['owner_reference']);
                                $set('assigned_employee_name_snapshot', $snapshot['owner_name']);

                                if (blank($get('title')) && filled($snapshot['title'])) {
                                    $set('title', $snapshot['title']);
                                }

                                if (blank($get('estimated_value')) && filled($snapshot['estimated_value'])) {
                                    $set('estimated_value', $snapshot['estimated_value']);
                                }

                                $service = null;
                                if (filled($snapshot['service_reference'])) {
                                    $service = Service::query()
                                        ->where('slug', $snapshot['service_reference'])
                                        ->orWhere('service_code', $snapshot['service_reference'])
                                        ->first();
                                }

                                if (! $service && filled($snapshot['service_name'])) {
                                    $service = Service::query()
                                        ->where('name', $snapshot['service_name'])
                                        ->first();
                                }

                                if ($service) {
                                    $set('service_id', $service->getKey());
                                    $set('service_reference', $service->reference());
                                    $set('service_name_snapshot', $service->name);
                                } else {
                                    $set('service_id', null);
                                    $set('service_reference', $snapshot['service_reference']);
                                    $set('service_name_snapshot', $snapshot['service_name'] ?: $snapshot['service_reference']);
                                }
                            })
                            ->columnSpanFull(),

                        TextInput::make('title')
                            ->label(UiText::get('fields.opportunity_name', 'Opportunity name'))
                            ->placeholder(UiText::get('fields.opportunity_name_placeholder', 'Example: CRM solution for ABC Company'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('opportunity_code')
                            ->label(UiText::get('fields.opportunity_code', 'Opportunity code'))
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder(UiText::get('fields.generated_automatically', 'Generated automatically')),
                        TextInput::make('company_name_snapshot')
                            ->label(UiText::get('fields.company', 'Company'))
                            ->placeholder(UiText::get('fields.company_placeholder', 'Customer or company name'))
                            ->maxLength(255),
                        TextInput::make('contact_name_snapshot')
                            ->label(UiText::get('fields.contact', 'Contact'))
                            ->placeholder(UiText::get('fields.contact_placeholder', 'Main contact person'))
                            ->maxLength(255),
                        Select::make('service_id')
                            ->label(UiText::get('fields.service', 'Service'))
                            ->options(fn (): array => Service::query()
                                ->where('status', 'active')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set): void {
                                $service = $state ? Service::query()->find($state) : null;
                                $set('service_reference', $service?->reference());
                                $set('service_name_snapshot', $service?->name);
                            }),
                        Select::make('stage')
                            ->label(UiText::get('fields.stage', 'Stage'))
                            ->options(OpportunityStage::options())
                            ->default(OpportunityStage::Discovery->value)
                            ->disabledOn('edit')
                            ->required()
                            ->native(false),
                        TextInput::make('assigned_employee_name_snapshot')
                            ->label(UiText::get('fields.owner', 'Owner'))
                            ->placeholder(UiText::get('fields.owner_placeholder', 'Person responsible for this opportunity'))
                            ->maxLength(255),

                        Hidden::make('lead_code_snapshot'),
                        Hidden::make('contact_reference'),
                        Hidden::make('company_reference'),
                        Hidden::make('assigned_employee_reference'),
                        Hidden::make('service_reference'),
                        Hidden::make('service_name_snapshot'),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make(UiText::get('sections.opportunity_value', 'Value & timeline'))
                    ->description(UiText::get('sections.opportunity_value_help', 'Estimate commercial value, close date and probability so the pipeline can be forecasted consistently.'))
                    ->schema([
                        TextInput::make('estimated_value')
                            ->label(UiText::get('fields.estimated_value', 'Estimated value'))
                            ->numeric()
                            ->minValue(0)
                            ->prefix('VND')
                            ->placeholder('0'),
                        TextInput::make('probability')
                            ->label(UiText::get('fields.probability', 'Probability'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->default(OpportunityStage::Discovery->probability()),
                        DatePicker::make('expected_close_date')
                            ->label(UiText::get('fields.expected_close_date', 'Expected close date'))
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        Textarea::make('lost_reason')
                            ->label(UiText::get('fields.lost_reason', 'Lost reason'))
                            ->placeholder(UiText::get('fields.lost_reason_placeholder', 'Record the reason so the team can learn from the outcome.'))
                            ->rows(3)
                            ->columnSpanFull()
                            ->visible(fn ($record): bool => $record?->stage === OpportunityStage::Lost),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('opportunity_code')
                    ->label(UiText::get('fields.opportunity_code', 'Opportunity code'))
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('title')
                    ->label(UiText::get('common.fields.name', 'Name'))
                    ->description(fn (Opportunity $record): ?string => $record->company_name_snapshot ?: $record->contact_name_snapshot)
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('service_name_snapshot')
                    ->label(UiText::get('fields.service', 'Service'))
                    ->placeholder('—')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('stage')
                    ->label(UiText::get('fields.stage', 'Stage'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof OpportunityStage
                        ? $state->label()
                        : (OpportunityStage::tryFrom((string) $state)?->label() ?? (string) $state))
                    ->color(fn ($state): string => StatusColor::for($state instanceof \BackedEnum ? $state->value : (string) $state)),
                TextColumn::make('estimated_value')
                    ->label(UiText::get('fields.estimated_value', 'Estimated value'))
                    ->money('VND')
                    ->sortable(),
                TextColumn::make('probability')
                    ->label(UiText::get('fields.probability', 'Probability'))
                    ->suffix('%')
                    ->sortable(),
                TextColumn::make('expected_close_date')
                    ->label(UiText::get('fields.expected_close_date', 'Expected close date'))
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),
                TextColumn::make('assigned_employee_name_snapshot')
                    ->label(UiText::get('fields.owner', 'Owner'))
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('stage')
                    ->label(UiText::get('fields.stage', 'Stage'))
                    ->options(OpportunityStage::options()),
                SelectFilter::make('service_id')
                    ->label(UiText::get('fields.service', 'Service'))
                    ->options(fn (): array => Service::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\Action::make('transition')
                        ->label(UiText::get('actions.move_stage', 'Move stage'))
                        ->icon('heroicon-o-arrow-right-circle')
                        ->visible(fn (Opportunity $record): bool => ! $record->isTerminal())
                        ->schema(fn (Opportunity $record): array => [
                            Select::make('stage')
                                ->label(UiText::get('fields.next_stage', 'Next stage'))
                                ->options(app(OpportunityWorkflowService::class)->allowedTransitions($record))
                                ->native(false)
                                ->required(),
                            Textarea::make('lost_reason')
                                ->label(UiText::get('fields.lost_reason', 'Lost reason'))
                                ->rows(3),
                            DatePicker::make('expected_close_date')
                                ->label(UiText::get('fields.expected_close_date', 'Expected close date'))
                                ->native(false),
                        ])
                        ->action(function (Opportunity $record, array $data): void {
                            try {
                                app(OpportunityWorkflowService::class)->transition(
                                    $record,
                                    OpportunityStage::from((string) $data['stage']),
                                    $data,
                                    auth()->id(),
                                );

                                Notification::make()
                                    ->success()
                                    ->title(UiText::get('notifications.stage_updated', 'Opportunity stage updated'))
                                    ->send();
                            } catch (ValidationException $exception) {
                                Notification::make()
                                    ->danger()
                                    ->title(UiText::get('notifications.stage_update_failed', 'Unable to update stage'))
                                    ->body(collect($exception->errors())->flatten()->first() ?: $exception->getMessage())
                                    ->send();
                            }
                        }),
                    Actions\EditAction::make()
                        ->label(UiText::get('common.actions.edit', 'Edit'))
                        ->icon('heroicon-o-pencil-square'),
                    Actions\DeleteAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete'))
                        ->icon('heroicon-o-trash'),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()
                        ->label(UiText::get('common.actions.delete', 'Delete'))
                        ->authorizeIndividualRecords(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpportunities::route('/'),
            'create' => Pages\CreateOpportunity::route('/create'),
            'edit' => Pages\EditOpportunity::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return app(CommercialAuthorization::class)->allows('view');
    }

    public static function canCreate(): bool
    {
        return app(CommercialAuthorization::class)->allows('manage-opportunities');
    }

    public static function canEdit(Model $record): bool
    {
        return app(CommercialAuthorization::class)->allows('manage-opportunities');
    }

    public static function canDelete(Model $record): bool
    {
        return app(CommercialAuthorization::class)->allows('manage-opportunities');
    }
}
