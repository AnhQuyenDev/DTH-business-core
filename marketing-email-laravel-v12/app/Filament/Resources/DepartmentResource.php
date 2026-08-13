<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Crm\Department;
use App\Support\Ui\SystemColorPalette;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
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
use Illuminate\Support\HtmlString;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('configuration.navigation.departments');
    }

    public static function getModelLabel(): string
    {
        return __('configuration.department.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('configuration.department.plural');
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
            Section::make(__('configuration.department.section'))
                ->icon('heroicon-o-building-office-2')
                ->iconColor('primary')
                ->compact()
                ->extraAttributes(['class' => 'dth-config-form'])
                ->schema([
                    TextInput::make('name')
                        ->label(__('configuration.department.name'))
                        ->prefixIcon('heroicon-o-building-office-2')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.department.name_hint'))
                        ->columnSpan([
                            'default' => 12,
                            'md' => fn (?Department $record): int => $record ? 5 : 8,
                        ]),

                    TextInput::make('code')
                        ->label(__('configuration.department.code'))
                        ->prefixIcon('heroicon-o-hashtag')
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (?Department $record): bool => $record !== null)
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.department.code_helper'))
                        ->columnSpan(['default' => 12, 'md' => 4]),

                    Toggle::make('is_active')
                        ->label(__('configuration.department.active'))
                        ->inline(false)
                        ->default(true)
                        ->extraFieldWrapperAttributes(['class' => 'dth-config-toggle-wrap'])
                        ->columnSpan([
                            'default' => 12,
                            'md' => fn (?Department $record): int => $record ? 3 : 4,
                        ]),

                    ToggleButtons::make('color')
                        ->label(new HtmlString(
                            self::departmentColorPickerStyles() . e(__('configuration.department.color'))
                        ))
                        ->options(
                            collect(SystemColorPalette::options())
                                ->mapWithKeys(fn (string $label, string $color): array => [
                                    $color => new HtmlString(sprintf(
                                        '<span class="dth-department-color-swatch" title="%s" aria-hidden="true" style="--dth-swatch:%s;background-color:%s;"></span><span class="sr-only">%s</span>',
                                        e($label),
                                        e(SystemColorPalette::hex($color)),
                                        e(SystemColorPalette::hex($color)),
                                        e($label),
                                    )),
                                ])
                                ->all()
                        )
                        ->inline()
                        ->extraAttributes(['class' => 'dth-color-swatch-picker'])
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.department.color_helper'))
                        ->default(SystemColorPalette::DEFAULT)
                        ->required()
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label(__('configuration.department.description'))
                        ->rows(3)
                        ->maxLength(1000)
                        ->columnSpanFull(),
                ])
                ->columns(12),
        ]);
    }


    private static function departmentColorPickerStyles(): string
    {
        return <<<'HTML'
<style>
    .dth-color-swatch-picker.fi-fo-toggle-buttons,
    .dth-color-swatch-picker .fi-fo-toggle-buttons {
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        gap: .62rem !important;
    }

    .dth-color-swatch-picker > div,
    .dth-color-swatch-picker .fi-fo-toggle-buttons > div {
        padding: 0 !important;
        margin: 0 !important;
    }

    .dth-color-swatch-picker label.fi-btn {
        width: 2rem !important;
        min-width: 2rem !important;
        height: 2rem !important;
        min-height: 2rem !important;
        padding: 0 !important;
        border: 0 !important;
        border-radius: 9999px !important;
        background: transparent !important;
        box-shadow: none !important;
        outline: none !important;
        transform: none !important;
        overflow: visible !important;
    }

    .dth-color-swatch-picker label.fi-btn:hover,
    .dth-color-swatch-picker label.fi-btn:focus-visible {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
    }

    .dth-color-swatch-picker .dth-department-color-swatch {
        display: block;
        width: 1.72rem;
        height: 1.72rem;
        border-radius: 9999px;
        border: 2px solid color-mix(in srgb, var(--dth-swatch) 72%, #111827 28%);
        box-shadow:
            inset 0 0 0 2px color-mix(in srgb, var(--dth-swatch) 88%, white 12%),
            0 0 0 1px rgba(255, 255, 255, .08);
        box-sizing: border-box;
        transition: box-shadow .14s ease, transform .14s ease, filter .14s ease;
    }

    .dth-color-swatch-picker label.fi-btn:hover .dth-department-color-swatch,
    .dth-color-swatch-picker label.fi-btn:focus-visible .dth-department-color-swatch {
        transform: scale(1.06);
        filter: saturate(1.06) brightness(1.04);
        box-shadow:
            inset 0 0 0 2px color-mix(in srgb, var(--dth-swatch) 88%, white 12%),
            0 0 0 2px #ffffff,
            0 0 0 4px var(--dth-swatch),
            0 0 12px color-mix(in srgb, var(--dth-swatch) 72%, transparent);
    }

    .dth-color-swatch-picker input:checked + label.fi-btn .dth-department-color-swatch {
        transform: scale(1.06);
        box-shadow:
            inset 0 0 0 2px color-mix(in srgb, var(--dth-swatch) 88%, white 12%),
            0 0 0 2px #ffffff,
            0 0 0 4px var(--dth-swatch),
            0 0 12px color-mix(in srgb, var(--dth-swatch) 72%, transparent);
    }
</style>
HTML;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                
                TextColumn::make('code')
                    ->label(__('configuration.department.code'))
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('name')
                    ->label(__('configuration.department.name'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (Department $record): string => SystemColorPalette::normalize($record->color)),

                TextColumn::make('staff_count')
                    ->label(__('configuration.department.staff_count'))
                    ->counts('staff')
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('configuration.department.active'))
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label(__('configuration.common.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('configuration.department.active')),
                SelectFilter::make('color')
                    ->label(__('configuration.department.color'))
                    ->options(SystemColorPalette::options()),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('structure')
                        ->label(__('configuration.department.structure'))
                        ->icon('heroicon-o-users')
                        ->action(fn (Department $record, $livewire): mixed => $livewire->selectDepartment($record->id)),
                    EditAction::make()
                        ->label(__('configuration.department.edit'))
                        ->icon('heroicon-o-pencil-square')
                        ->modalIcon('heroicon-o-pencil-square')
                        ->modalIconColor('primary')
                        ->modalWidth('5xl')
                        ->modalSubmitAction(fn (\Filament\Actions\StaticAction $action) => $action->icon('heroicon-o-check-circle'))
                        ->modalCancelAction(fn (\Filament\Actions\StaticAction $action) => $action->icon('heroicon-o-x-mark')),
                    Action::make('delete')
                        ->label(__('configuration.department.delete'))
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading(__('configuration.department.delete_confirm_title'))
                        ->modalDescription(__('configuration.department.delete_confirm_description'))
                        ->action(function (Department $record): void {
                            if ($record->staff()->exists()) {
                                Notification::make()
                                    ->danger()
                                    ->title(__('configuration.department.delete_blocked_title'))
                                    ->body(__('configuration.department.delete_blocked_staff'))
                                    ->send();

                                return;
                            }

                            $record->delete();

                            Notification::make()
                                ->success()
                                ->title(__('configuration.department.deleted'))
                                ->send();
                        }),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('activate')
                        ->label(__('configuration.common.activate_selected'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => Department::query()->whereKey($records->modelKeys())->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('deactivate')
                        ->label(__('configuration.common.deactivate_selected'))
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => Department::query()->whereKey($records->modelKeys())->update(['is_active' => false]))
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
            'index' => Pages\ListDepartments::route('/'),
        ];
    }
}
