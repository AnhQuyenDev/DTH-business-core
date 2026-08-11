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
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?int $navigationSort = 65;

    public static function getNavigationGroup(): string { return __('navigation.group.configuration'); }
    public static function getNavigationLabel(): string { return __('v1.rbac.roles_permissions'); }
    public static function getModelLabel(): string { return __('v1.rbac.role'); }
    public static function getPluralModelLabel(): string { return __('v1.rbac.roles_permissions'); }
    public static function canViewAny(): bool { return auth()->user()?->can('system.manage-rbac') ?? false; }
    public static function canCreate(): bool { return auth()->user()?->can('system.manage-rbac') ?? false; }
    public static function canEdit(Model $record): bool
    {
        $actor = auth()->user();
        if (! ($actor?->can('system.manage-rbac') ?? false)) return false;

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
            Section::make(__('v1.rbac.role_information'))->schema([
                TextInput::make('label')->label(__('field.name'))->required()->maxLength(150),
                TextInput::make('name')->label(__('v1.rbac.role_key'))->required()->alphaDash()->unique(ignoreRecord: true)
                    ->disabled(fn (?Role $record): bool => (bool) $record?->is_system)->dehydrated(),
                Textarea::make('description')->label(__('field.description'))->rows(2)->columnSpanFull(),
                TextInput::make('guard_name')->default('web')->hidden()->dehydrated(),
            ])->columns(2),
            Section::make(__('v1.rbac.permissions'))->description(__('v1.rbac.permissions_help'))->schema([
                CheckboxList::make('permissions')
                    ->relationship('permissions', 'name')
                    ->getOptionLabelFromRecordUsing(fn (Permission $permission): string => sprintf('[%s] %s', $permission->module ?: 'System', $permission->label ?: $permission->name))
                    ->columns(['default' => 1, 'lg' => 2, '2xl' => 3])
                    ->searchable()
                    ->bulkToggleable()
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('label')->label(__('field.name'))->searchable()->sortable(),
            TextColumn::make('name')->label(__('v1.rbac.role_key'))->badge()->searchable(),
            TextColumn::make('permissions_count')->counts('permissions')->label(__('v1.rbac.permission_count'))->sortable(),
            IconColumn::make('is_system')->label(__('v1.rbac.system_role'))->boolean(),
        ])->actions([ActionGroup::make([
            EditAction::make()->visible(fn (Role $record): bool => static::canEdit($record)),
            DeleteAction::make()->visible(fn (Role $record): bool => static::canDelete($record)),
        ])->iconButton()]);
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
