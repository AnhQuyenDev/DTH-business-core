<?php

namespace Dth\Crm\Filament\Resources;

use Dth\Crm\Filament\Navigation\CrmNavigationGroup;
use Dth\Crm\Filament\Resources\CustomerDistributionBatchResource\Pages;
use Dth\Crm\Models\CustomerDistributionBatch;
use Dth\Crm\Support\CrmOptions;
use Dth\Crm\Support\StatusColor;
use Dth\Crm\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CustomerDistributionBatchResource extends Resource
{
    protected static ?string $model = CustomerDistributionBatch::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static string|\UnitEnum|null $navigationGroup = CrmNavigationGroup::Crm;
    protected static ?int $navigationSort = 80;

    public static function getNavigationLabel(): string
    {
        return UiText::get('navigation.distribution', 'Customer distribution', context: 'navigation');
    }

    public static function getModelLabel(): string
    {
        return UiText::get('models.distribution', 'Customer distribution batch', context: 'model');
    }

    public static function getPluralModelLabel(): string
    {
        return UiText::get('models.distributions', 'Customer distribution batches', context: 'model');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(UiText::get('sections.distribution', 'Customer distribution'))
                ->schema([
                    TextInput::make('batch_code')
                        ->label(UiText::get('fields.batch_code', 'Batch code'))
                        ->disabled()
                        ->dehydrated(false)
                        ->hiddenOn('create'),
                    Select::make('batch_type')
                        ->label(UiText::get('fields.batch_type', 'Batch type'))
                        ->options(CrmOptions::batchTypes())
                        ->native(false),
                    Select::make('strategy')
                        ->label(UiText::get('fields.strategy', 'Strategy'))
                        ->options(CrmOptions::distributionStrategies())
                        ->native(false),
                    Select::make('status')
                        ->label(UiText::get('common.fields.status', 'Status'))
                        ->options(CrmOptions::batchStatuses())
                        ->native(false),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('batch_code')
                    ->label(UiText::get('fields.batch_code', 'Batch code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('strategy')
                    ->label(UiText::get('fields.strategy', 'Strategy'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CrmOptions::label('distribution_strategy', $state))
                    ->color('info')
                    ->sortable(),
                TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn ($state): string => CrmOptions::label('batch_status', $state))
                    ->color(fn ($state): string => StatusColor::for($state, 'batch_status')),
                TextColumn::make('total_items')
                    ->label(UiText::get('fields.total_items', 'Total'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('processed_items')
                    ->label(UiText::get('fields.processed_items', 'Processed'))
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('strategy')
                    ->label(UiText::get('fields.strategy', 'Strategy'))
                    ->options(CrmOptions::distributionStrategies()),
                SelectFilter::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->options(CrmOptions::batchStatuses()),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\ViewAction::make()->icon('heroicon-o-eye')
                        ->label(UiText::get('common.actions.view', 'View')),
                    Actions\EditAction::make()->icon('heroicon-o-pencil-square')
                        ->label(UiText::get('common.actions.edit', 'Edit')),
                    Actions\DeleteAction::make()->icon('heroicon-o-trash')
                        ->label(UiText::get('common.actions.delete', 'Delete')),
                ]),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
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
            'index' => Pages\ListCustomerDistributionBatchs::route('/'),
            'create' => Pages\CreateCustomerDistributionBatch::route('/create'),
            'view' => Pages\ViewCustomerDistributionBatch::route('/{record}'),
            'edit' => Pages\EditCustomerDistributionBatch::route('/{record}/edit'),
        ];
    }
}
