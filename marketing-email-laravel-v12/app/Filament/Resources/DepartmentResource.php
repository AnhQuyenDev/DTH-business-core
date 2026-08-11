<?php

namespace App\Filament\Resources;

use App\Filament\Pages\OrganizationAccessPage;
use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Crm\Department;
use App\Support\Ui\SystemColorPalette;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 21;

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
        return (auth()->user()?->can('system.manage-organization') ?? false)
            && $record instanceof Department
            && ! $record->staff()->exists();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('configuration.department.section'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('configuration.department.name'))
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Get $get, Set $set, ?string $state): void {
                            if (filled($get('code')) || blank($state)) {
                                return;
                            }

                            $slug = Str::of($state)->ascii()->slug('_')->lower()->toString();
                            if (filled($slug)) {
                                $set('code', $slug.'_department');
                            }
                        }),

                    TextInput::make('code')
                        ->label(__('configuration.department.code'))
                        ->required()
                        ->maxLength(50)
                        ->alphaDash()
                        ->unique(ignoreRecord: true)
                        ->disabled(fn (?Department $record): bool => $record !== null)
                        ->dehydrated()
                        ->helperText(__('configuration.department.code_helper')),

                    ToggleButtons::make('color')
                        ->label(__('configuration.department.color'))
                        ->options(SystemColorPalette::options())
                        ->colors(SystemColorPalette::toggleColors())
                        ->columns([
                            'default' => 2,
                            'sm' => 4,
                            'md' => 6,
                            'xl' => 11,
                        ])
                        ->default(SystemColorPalette::DEFAULT)
                        ->required()
                        ->columnSpanFull(),

                    Textarea::make('description')
                        ->label(__('configuration.department.description'))
                        ->rows(2)
                        ->columnSpanFull(),

                    Toggle::make('is_active')
                        ->label(__('configuration.department.active'))
                        ->default(true),
                ])
                ->columns(['default' => 1, 'md' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('configuration.department.name'))
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (Department $record): string => SystemColorPalette::normalize($record->color)),

                TextColumn::make('code')
                    ->label(__('configuration.department.code'))
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('staff_count')
                    ->label(__('configuration.department.staff_count'))
                    ->counts('staff')
                    ->sortable(),

                TextColumn::make('sending_accounts_count')
                    ->label(__('configuration.department.sending_accounts_count'))
                    ->counts('sendingAccounts')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label(__('configuration.department.active'))
                    ->boolean(),
            ])
            ->defaultSort('sort_order')
            ->actions([
                ActionGroup::make([
                    Action::make('structure')
                        ->label(__('configuration.department.structure'))
                        ->icon('heroicon-o-users')
                        ->action(fn (Department $record, $livewire): mixed => $livewire->selectDepartment($record->id)),
                    EditAction::make()->label(__('configuration.department.edit')),
                    DeleteAction::make()
                        ->label(__('configuration.department.delete'))
                        ->visible(fn (Department $record): bool => static::canDelete($record)),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
        ];
    }
}
