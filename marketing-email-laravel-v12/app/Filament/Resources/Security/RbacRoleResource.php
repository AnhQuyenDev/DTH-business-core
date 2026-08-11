<?php

namespace App\Filament\Resources\Security;

use App\Filament\Resources\Security\RbacRoleResource\Pages;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RbacRoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?int $navigationSort = 31;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('configuration.rbac.navigation');
    }

    public static function getModelLabel(): string
    {
        return __('configuration.rbac.role');
    }

    public static function getPluralModelLabel(): string
    {
        return __('configuration.rbac.roles');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('system.manage-rbac') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('system.manage-rbac') ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $actor = auth()->user();
        if (! ($actor?->can('system.manage-rbac') ?? false)) {
            return false;
        }

        return $record->name !== 'super_admin' || $actor->isSuperAdmin();
    }

    public static function canDelete(Model $record): bool
    {
        return (auth()->user()?->can('system.manage-rbac') ?? false)
            && ! $record->is_system
            && $record->name !== 'super_admin';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('configuration.rbac.role_information'))
                ->icon('heroicon-o-identification')
                ->iconColor('primary')
                ->compact()
                ->extraAttributes(['class' => 'dth-config-form'])
                ->columns(12)
                ->schema([
                    TextInput::make('label')
                        ->label(__('field.name'))
                        ->prefixIcon('heroicon-o-identification')
                        ->required()
                        ->maxLength(150)
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.rbac.role_label_hint'))
                        ->columnSpan(['default' => 12, 'md' => 8]),

                    TextInput::make('name')
                        ->label(__('configuration.rbac.role_key'))
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn (?Role $record): bool => $record !== null)
                        ->hintIcon('heroicon-m-question-mark-circle', __('configuration.rbac.role_key_hint'))
                        ->columnSpan(['default' => 12, 'md' => 4]),

                    Textarea::make('description')
                        ->label(__('field.description'))
                        ->rows(2)
                        ->columnSpanFull(),

                    TextInput::make('guard_name')
                        ->default('web')
                        ->hidden()
                        ->dehydrated(),
                ]),

            Section::make(__('configuration.rbac.permissions'))
                ->icon('heroicon-o-shield-check')
                ->iconColor('primary')
                ->compact()
                ->schema([
                    CheckboxList::make('permissions')
                        ->relationship(
                            'permissions',
                            'name',
                            fn ($query) => $query->orderBy('module')->orderBy('label'),
                        )
                        ->getOptionLabelFromRecordUsing(
                            fn (Permission $permission): string => sprintf(
                                '%s · %s',
                                $permission->module ?: __('configuration.rbac.system_module'),
                                $permission->label ?: $permission->name,
                            )
                        )
                        ->columns(['default' => 1, 'lg' => 2, '2xl' => 3])
                        ->searchable()
                        ->bulkToggleable()
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')
                    ->label(__('field.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('configuration.rbac.role_key'))
                    ->badge()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label(__('configuration.rbac.permission_count'))
                    ->sortable(),
                IconColumn::make('is_system')
                    ->label(__('configuration.rbac.system_role'))
                    ->boolean(),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()->icon('heroicon-o-pencil-square')->visible(fn (Role $record): bool => static::canEdit($record)),
                    DeleteAction::make()->visible(fn (Role $record): bool => static::canDelete($record)),
                ])->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRbacRoles::route('/'),
            'create' => Pages\CreateRbacRole::route('/create'),
            'edit' => Pages\EditRbacRole::route('/{record}/edit'),
        ];
    }
}
