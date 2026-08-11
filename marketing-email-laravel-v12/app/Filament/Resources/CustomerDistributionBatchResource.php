<?php

namespace App\Filament\Resources;

use App\Filament\Actions\QuickViewAction;
use App\Enums\Crm\DistributionBatchStatus;
use App\Enums\Crm\DistributionBatchType;
use App\Enums\Crm\DistributionStrategy;
use App\Filament\Resources\CustomerDistributionBatchResource\Pages;
use App\Models\Crm\CustomerDistributionBatch;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CustomerDistributionBatchResource extends Resource
{
    protected static ?string $model = CustomerDistributionBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.customer_care');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.customer_distribution_batch.singular');
    }

    public static function getModelLabel(): string
    {
        return __('resource.customer_distribution_batch.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.customer_distribution_batch.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can(
            'customer-care.manage-assignments'
        ) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('resource.customer_distribution_batch.singular'))
                ->schema([
                    TextInput::make('batch_code')
                        ->label(__('field.batch_code'))
                        ->disabled()
                        ->dehydrated(),
                    Select::make('type')
                        ->label(__('field.type'))
                        ->options(DistributionBatchType::options())
                        ->native(false)
                        ->disabled(),
                    Select::make('status')
                        ->label(__('field.status'))
                        ->options(DistributionBatchStatus::options())
                        ->native(false)
                        ->disabled(),
                    Select::make('strategy')
                        ->label(__('field.strategy'))
                        ->options(DistributionStrategy::options())
                        ->native(false)
                        ->disabled(),
                    Select::make('source_staff_id')
                        ->label(__('field.source_staff'))
                        ->relationship('sourceStaff', 'full_name')
                        ->searchable()
                        ->disabled(),
                    DateTimePicker::make('effective_from')
                        ->label(__('field.effective_from'))
                        ->seconds(false)
                        ->disabled(),
                    DateTimePicker::make('effective_until')
                        ->label(__('field.effective_until'))
                        ->seconds(false)
                        ->disabled(),
                    Textarea::make('note')
                        ->label(__('field.note'))
                        ->rows(3)
                        ->disabled()
                        ->columnSpanFull(),
                ])
                ->columns(['default' => 1, 'md' => 2]),
            Section::make(__('uiux.form.system_metrics'))
                ->schema([
                    TextInput::make('total_customers')
                        ->label(__('field.total_customers'))
                        ->numeric()
                        ->disabled(),
                    TextInput::make('total_assigned')
                        ->label(__('field.total_assigned'))
                        ->numeric()
                        ->disabled(),
                ])
                ->columns(['default' => 1, 'md' => 2])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('batch_code')->label(__('field.batch_code'))->searchable()->sortable(),
            TextColumn::make('type')->label(__('field.type'))->badge()
                ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : (DistributionBatchType::tryFrom((string) $state)?->color() ?? 'gray')),
            TextColumn::make('status')->label(__('field.status'))->badge()
                ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : (DistributionBatchStatus::tryFrom((string) $state)?->color() ?? 'gray')),
            TextColumn::make('strategy')->label(__('field.strategy'))->badge()
                ->formatStateUsing(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'label') ? $state->label() : ($state ?? ''))
                ->color(fn ($state): string => $state instanceof \BackedEnum && method_exists($state, 'color') ? $state->color() : (DistributionStrategy::tryFrom((string) $state)?->color() ?? 'gray')),
            TextColumn::make('total_customers')->label(__('field.total_customers'))->sortable(),
            TextColumn::make('total_assigned')->label(__('field.total_assigned'))->sortable(),
            TextColumn::make('completed_at')->label(__('field.completed_at'))->dateTime('d/m/Y H:i')->sortable(),
        ])
            ->actions([ActionGroup::make([
                QuickViewAction::make(),
                EditAction::make(),
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
            'index' => Pages\ListCustomerDistributionBatches::route('/'),
            'view' => Pages\ViewCustomerDistributionBatch::route('/{record}'),
        ];
    }
}
