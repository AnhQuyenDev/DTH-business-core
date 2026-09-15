<?php

namespace Dth\Marketing\Filament\Resources;

use Dth\Marketing\Filament\Navigation\MarketingNavigationGroup;
use Dth\Marketing\Filament\Resources\SegmentResource\Pages;
use Dth\Marketing\Filament\Support\StatusColor;
use Dth\Marketing\Models\Segment;
use Dth\Marketing\Services\SegmentQueryService;
use Dth\Marketing\Services\SegmentRuleRegistry;
use Dth\Marketing\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Throwable;

class SegmentResource extends Resource
{
    protected static ?string $model = Segment::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-funnel';
    protected static string|\UnitEnum|null $navigationGroup = MarketingNavigationGroup::Marketing;
    protected static ?int $navigationSort = 60;
    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.segments', 'Segments', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.segment', 'Segment', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.segments', 'Segments', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('segment.details', 'Segment'))
                ->schema([
                    TextInput::make('name')->label(UiText::get('common.fields.name', 'Name'))->required()->maxLength(255),
                    TextInput::make('slug')->label(UiText::get('segment.slug', 'Slug'))->maxLength(255)->unique(ignoreRecord: true),
                    Select::make('status')
                        ->label(UiText::get('common.fields.status', 'Status'))
                        ->options([
                            'active' => UiText::get('common.status.active', 'Active'),
                            'archived' => UiText::get('common.status.archived', 'Archived'),
                        ])
                        ->default('active')->required()->native(false),
                    Toggle::make('is_automatic')
                        ->label(UiText::get('segment.automatic', 'Automatic'))
                        ->disabled()
                        ->dehydrated(),
                    Textarea::make('description')->label(UiText::get('common.fields.description', 'Description'))->rows(3)->columnSpanFull(),
                ])->columns(2)->columnSpanFull(),
            Section::make(UiText::get('segment.rules', 'Rules'))
                ->description(UiText::get('segment.rules_description', 'Only rules supported by the installed providers can be selected. All conditions are combined with AND.'))
                ->schema([
                    Repeater::make('rules.conditions')
                        ->label(UiText::get('segment.conditions', 'Conditions'))
                        ->defaultItems(1)
                        ->addActionLabel(UiText::get('segment.add_condition', 'Add condition'))
                        ->schema([
                            Select::make('field')
                                ->label(UiText::get('segment.field', 'Field'))
                                ->options(fn (): array => app(SegmentRuleRegistry::class)->fieldOptions())
                                ->required()->native(false)->searchable()->live()
                                ->afterStateUpdated(function (?string $state, Set $set): void {
                                    $set('operator', array_key_first(app(SegmentRuleRegistry::class)->operatorOptions($state)));
                                    $set('value_text', null);
                                    $set('value_number', null);
                                    $set('value_select', null);
                                    $set('value_from', null);
                                    $set('value_to', null);
                                }),
                            Select::make('operator')
                                ->label(UiText::get('segment.operator_label', 'Operator'))
                                ->options(fn (Get $get): array => app(SegmentRuleRegistry::class)->operatorOptions((string) $get('field')))
                                ->required()->native(false),
                            TextInput::make('value_text')
                                ->label(UiText::get('segment.value', 'Value'))
                                ->visible(fn (Get $get): bool => app(SegmentRuleRegistry::class)->valueType((string) $get('field')) === 'text')
                                ->required(fn (Get $get): bool => app(SegmentRuleRegistry::class)->valueType((string) $get('field')) === 'text'),
                            TextInput::make('value_number')
                                ->label(UiText::get('segment.days', 'Days'))
                                ->numeric()->minValue(1)
                                ->visible(fn (Get $get): bool => app(SegmentRuleRegistry::class)->valueType((string) $get('field')) === 'number')
                                ->required(fn (Get $get): bool => app(SegmentRuleRegistry::class)->valueType((string) $get('field')) === 'number'),
                            Select::make('value_select')
                                ->label(UiText::get('segment.value', 'Value'))
                                ->options(fn (Get $get): array => app(SegmentRuleRegistry::class)->valueOptions((string) $get('field')))
                                ->searchable()->native(false)
                                ->visible(fn (Get $get): bool => app(SegmentRuleRegistry::class)->valueType((string) $get('field')) === 'select')
                                ->required(fn (Get $get): bool => app(SegmentRuleRegistry::class)->valueType((string) $get('field')) === 'select'),
                            DatePicker::make('value_from')
                                ->label(UiText::get('segment.from', 'From'))
                                ->visible(fn (Get $get): bool => app(SegmentRuleRegistry::class)->valueType((string) $get('field')) === 'date_range')
                                ->required(fn (Get $get): bool => app(SegmentRuleRegistry::class)->valueType((string) $get('field')) === 'date_range'),
                            DatePicker::make('value_to')
                                ->label(UiText::get('segment.to', 'To'))
                                ->afterOrEqual('value_from')
                                ->visible(fn (Get $get): bool => app(SegmentRuleRegistry::class)->valueType((string) $get('field')) === 'date_range')
                                ->required(fn (Get $get): bool => app(SegmentRuleRegistry::class)->valueType((string) $get('field')) === 'date_range'),
                        ])->columns(2)->columnSpanFull(),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label(UiText::get('common.fields.name', 'Name'))->searchable()->sortable(),
                TextColumn::make('status')->label(UiText::get('common.fields.status', 'Status'))->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))->color(fn ($state): string => StatusColor::for($state)),
                TextColumn::make('is_automatic')->label(UiText::get('segment.automatic', 'Automatic'))->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? UiText::get('common.yes', 'Yes') : UiText::get('common.no', 'No')),
                TextColumn::make('last_evaluated_at')->label(UiText::get('segment.last_evaluated', 'Last evaluated'))
                    ->dateTime('d/m/Y H:i')->placeholder('—')->sortable(),
                TextColumn::make('created_at')->label(UiText::get('common.fields.created_at', 'Created'))->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label(UiText::get('common.fields.status', 'Status'))->options([
                    'active' => UiText::get('common.status.active', 'Active'),
                    'archived' => UiText::get('common.status.archived', 'Archived'),
                ]),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(1)
            ->deferFilters(false)
            ->hiddenFilterIndicators()
            ->recordActions([
                \Filament\Actions\ActionGroup::make([
                    Actions\Action::make('preview_count')
                        ->label(UiText::get('segment.preview_count', 'Count'))
                        ->icon('heroicon-o-calculator')
                        ->action(function (Segment $record): void {
                            try {
                                $count = app(SegmentQueryService::class)->countForSegment($record);
                                $record->forceFill(['last_evaluated_at' => now()])->saveQuietly();
                                Notification::make()->title(UiText::get('segment.preview', 'Segment preview'))->body(UiText::get('segment.matching_contacts', 'Matching contacts: :count', ['count' => $count]))->success()->send();
                            } catch (Throwable $exception) {
                                Notification::make()->title(UiText::get('segment.preview_failed', 'Segment preview failed'))->body($exception->getMessage())->danger()->send();
                            }
                        }),
                    Actions\Action::make('preview_sample')
                        ->label(UiText::get('segment.preview_sample', 'Sample'))
                        ->icon('heroicon-o-list-bullet')
                        ->modalHeading(UiText::get('segment.preview_sample', 'Segment sample'))
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalContent(function (Segment $record) {
                            $sample = app(SegmentQueryService::class)->sampleForSegment($record, 10);
                            $record->forceFill(['last_evaluated_at' => now()])->saveQuietly();
                            return view('dth-marketing::filament.segment-sample', ['sample' => $sample]);
                        }),
                    Actions\EditAction::make()->label(UiText::get('common.actions.edit', 'Edit'))->visible(fn (Segment $record): bool => app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->manage(auth()->user()) && ! $record->is_automatic),
                    Actions\DeleteAction::make()->label(UiText::get('common.actions.delete', 'Delete')),
            
                ]),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\BulkAction::make('activate')->label(UiText::get('common.actions.activate', 'Activate'))
                        ->visible(fn (): bool => app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->manage(auth()->user()))
                        ->action(fn ($records) => Segment::query()->whereKey($records->modelKeys())->update(['status' => 'active'])),
                    Actions\BulkAction::make('archive')->label(UiText::get('common.actions.archive', 'Archive'))
                        ->visible(fn (): bool => app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->manage(auth()->user()))
                        ->action(fn ($records) => Segment::query()->whereKey($records->modelKeys())->update(['status' => 'archived'])),
                    Actions\DeleteBulkAction::make()->label(UiText::get('common.actions.delete', 'Delete')),
                ]),
            ])->defaultSort('id', 'desc');
    }

    public static function canEdit(Model $record): bool
    {
        return app(\Dth\Marketing\Support\MarketingAuthorizationService::class)->manage(auth()->user())
            && $record instanceof Segment
            && ! $record->is_automatic;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSegments::route('/'),
            'create' => Pages\CreateSegment::route('/create'),
            'edit' => Pages\EditSegment::route('/{record}/edit'),
        ];
    }
}
