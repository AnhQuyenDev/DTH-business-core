<?php

namespace App\Filament\Resources;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\PositionAuthority;
use App\Enums\Crm\PositionGroup;
use App\Filament\Pages\OrganizationAccessPage;
use App\Filament\Resources\PositionResource\Pages;
use App\Models\Crm\Position;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PositionResource extends Resource
{
    protected static ?string $model = Position::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?int $navigationSort = 22;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('system.manage-organization') ?? false;
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationParentItem(): ?string
    {
        return OrganizationAccessPage::getNavigationLabel();
    }

    public static function getNavigationLabel(): string
    {
        return __('configuration.navigation.positions');
    }

    public static function getModelLabel(): string
    {
        return __('configuration.position.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('configuration.position.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('system.manage-organization') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('system.manage-organization') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->can('system.manage-organization') ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->can('system.manage-organization') ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('configuration.position.section'))
                ->icon('heroicon-o-identification')
                ->iconColor('primary')
                ->compact()
                ->extraAttributes(['class' => 'dth-config-form'])
                ->schema([
                    TextInput::make('title')
                        ->label(__('configuration.position.title'))
                        ->prefixIcon('heroicon-o-identification')
                        ->datalist(Position::titleSuggestions())
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.position.title_hint'))
                        ->columnSpan(['default' => 12, 'md' => 6]),

                    TextInput::make('code')
                        ->label(__('configuration.position.code'))
                        ->prefixIcon('heroicon-o-hashtag')
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (?Position $record): bool => $record !== null)
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.position.code_helper'))
                        ->columnSpan(['default' => 12, 'md' => 3]),

                    Select::make('group_key')
                        ->label(__('configuration.position.group'))
                        ->options(PositionGroup::options())
                        ->default(PositionGroup::Professional->value)
                        ->native(false)
                        ->required()
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.position.group_helper'))
                        ->columnSpan(['default' => 12, 'md' => 3]),

                    Select::make('authority_level')
                        ->label(__('configuration.position.authority'))
                        ->options(PositionAuthority::options())
                        ->native(false)
                        ->default(PositionAuthority::Member->value)
                        ->required()
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.position.authority_helper'))
                        ->columnSpan(['default' => 12, 'md' => 4]),

                    Select::make('function_key')
                        ->label(__('configuration.position.function'))
                        ->options(collect(DepartmentFunction::options())->except(['admin', 'other'])->all())
                        ->placeholder(__('configuration.position.function_all'))
                        ->native(false)
                        ->searchable()
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.position.function_helper'))
                        ->columnSpan(['default' => 12, 'md' => 5]),

                    Toggle::make('is_active')
                        ->label(__('configuration.position.active'))
                        ->inline()
                        ->default(true)
                        ->extraFieldWrapperAttributes(['class' => 'dth-config-toggle-wrap'])
                        ->columnSpan(['default' => 12, 'md' => 3]),

                    Textarea::make('description')
                        ->label(__('configuration.position.description'))
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                ])
                ->columns(12),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('configuration.position.title'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('group_key')
                    ->label(__('configuration.position.group'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof PositionGroup
                        ? $state
                        : PositionGroup::tryFrom((string) $state))?->label() ?? __('common.not_available'))
                    ->color(fn ($state): string => ($state instanceof PositionGroup
                        ? $state
                        : PositionGroup::tryFrom((string) $state))?->color() ?? 'gray'),

                TextColumn::make('authority_level')
                    ->label(__('configuration.position.authority'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof PositionAuthority
                        ? $state
                        : PositionAuthority::tryFrom((string) $state))?->label() ?? __('common.not_available'))
                    ->color(fn ($state): string => match ($state instanceof PositionAuthority
                        ? $state
                        : PositionAuthority::tryFrom((string) $state)) {
                        PositionAuthority::Executive => 'danger',
                        PositionAuthority::Manager => 'warning',
                        PositionAuthority::Lead => 'info',
                        PositionAuthority::Member => 'success',
                        PositionAuthority::Limited => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('staff_count')
                    ->label(__('configuration.position.staff_count'))
                    ->counts('staff')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('configuration.position.active'))
                    ->boolean(),

                TextColumn::make('function_key')
                    ->label(__('configuration.position.function'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof DepartmentFunction
                        ? $state
                        : DepartmentFunction::tryFrom((string) $state))?->label() ?? __('configuration.position.function_all'))
                    ->color(fn ($state): string => ($state instanceof DepartmentFunction
                        ? $state
                        : DepartmentFunction::tryFrom((string) $state))?->color() ?? 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('code')
                    ->label(__('configuration.position.code'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('group_key')
                    ->label(__('configuration.position.group'))
                    ->options(PositionGroup::options()),
                SelectFilter::make('authority_level')
                    ->label(__('configuration.position.authority'))
                    ->options(PositionAuthority::options()),
                TernaryFilter::make('is_active')
                    ->label(__('configuration.position.active')),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()->label(__('configuration.position.edit'))->icon('heroicon-o-pencil-square'),
                    Action::make('delete')
                        ->label(__('configuration.position.delete'))
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('configuration.position.delete_confirm_title'))
                        ->modalDescription(__('configuration.position.delete_confirm_description'))
                        ->action(function (Position $record): void {
                            if ($record->staff()->exists()) {
                                Notification::make()
                                    ->danger()
                                    ->title(__('configuration.position.delete_blocked_title'))
                                    ->body(__('configuration.position.delete_blocked_staff'))
                                    ->send();

                                return;
                            }

                            $record->delete();
                            Notification::make()->success()->title(__('configuration.position.deleted'))->send();
                        }),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label(__('configuration.common.activate_selected'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => Position::query()->whereKey($records->modelKeys())->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label(__('configuration.common.deactivate_selected'))
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => Position::query()->whereKey($records->modelKeys())->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('delete')
                        ->label(__('configuration.common.delete_selected'))
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $deleted = 0;
                            $blocked = 0;

                            foreach ($records as $record) {
                                if ($record->staff()->exists()) {
                                    $blocked++;
                                    continue;
                                }

                                $record->delete();
                                $deleted++;
                            }

                            Notification::make()
                                ->color($blocked > 0 ? 'warning' : 'success')
                                ->title(__('configuration.common.bulk_delete_result', [
                                    'deleted' => $deleted,
                                    'blocked' => $blocked,
                                ]))
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPositions::route('/'),
            'create' => Pages\CreatePosition::route('/create'),
            'edit' => Pages\EditPosition::route('/{record}/edit'),
        ];
    }
}
