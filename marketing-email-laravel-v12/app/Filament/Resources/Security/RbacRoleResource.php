<?php

namespace App\Filament\Resources\Security;

use App\Filament\Resources\Security\RbacRoleResource\Pages;
use App\Models\Security\Permission;
use App\Models\Security\Role;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
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
        $actor = auth()->user();

        if (! ($actor?->can('system.manage-rbac') ?? false)) {
            return false;
        }

        // The Super Admin role must always exist.
        if ($record->name === 'super_admin') {
            return false;
        }

        // System roles can only be removed by the Super Admin.
        if ($record->is_system && ! $actor->isSuperAdmin()) {
            return false;
        }

        return true;
    }

    /**
     * Persists the permissions of one module while keeping the permissions
     * assigned from every other module untouched.
     *
     * @param  array<int, string>  $selected
     */
    public static function syncRolePermissions(Role $role, ?string $module, array $selected): bool
    {
        $kept = $role->permissions()
            ->when(filled($module), fn ($query) => $query->where('module', '!=', $module))
            ->pluck('name');

        $names = $kept->merge($selected)->unique()->values()->all();

        $role->syncPermissions(Permission::query()->whereIn('name', $names)->pluck('id')->all());

        return true;
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
                    Select::make('selected_module')
                        ->label(__('configuration.rbac.module'))
                        ->placeholder(__('configuration.rbac.select_module'))
                        ->options(function (): array {
                            return Permission::query()
                                ->whereNotNull('module')
                                ->where('module', '!=', '')
                                ->distinct()
                                ->orderBy('module')
                                ->pluck('module', 'module')
                                ->all();
                        })
                        ->live()
                        ->dehydrated(false)
                        ->columnSpan(['default' => 12, 'md' => 6]),
                    CheckboxList::make('permissions')
                        ->relationship(
                            'permissions',
                            'name',
                            fn ($query, Get $get) => $query
                                ->where('module', $get('selected_module'))
                                ->orderBy('module')
                                ->orderBy('label'),
                        )
                        ->label(fn (Get $get): string => filled($get('selected_module'))
                            ? __('configuration.rbac.module_permissions', ['module' => $get('selected_module')])
                            : __('configuration.rbac.permissions'))
                        ->helperText(__('configuration.rbac.module_permissions_help'))
                        ->visible(fn (Get $get): bool => filled($get('selected_module')))
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
                        ->saveRelationshipsUsing(
                            fn (Role $record, array $state, Get $get): bool => static::syncRolePermissions(
                                $record,
                                $get('selected_module'),
                                $state,
                            )
                        )
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
                    EditAction::make()
                        ->label(__('configuration.rbac.edit'))
                        ->icon('heroicon-o-pencil-square')
                        ->visible(fn (Role $record): bool => static::canEdit($record)),
                    DeleteAction::make()
                        ->label(__('configuration.rbac.delete'))
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->modalHeading(__('configuration.rbac.delete_confirm_title'))
                        ->modalDescription(__('configuration.rbac.delete_confirm_description'))
                        ->visible(fn (Role $record): bool => static::canDelete($record)),
                ])->iconButton(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('configuration.common.delete_selected'))
                        ->modalHeading(__('configuration.rbac.bulk_delete_confirm_title'))
                        ->modalDescription(__('configuration.rbac.bulk_delete_confirm_description'))
                        ->using(function (Collection $records): void {
                            $records->each(function (Role $record): void {
                                if (static::canDelete($record)) {
                                    $record->delete();
                                }
                            });
                        }),
                ]),
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
