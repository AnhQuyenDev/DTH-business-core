<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.system');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.user.singular');
    }

    public static function getModelLabel(): string
    {
        return __('resource.user.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.user.plural');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
            TextInput::make('email')->label(__('field.email'))->email()->required()->unique(ignoreRecord: true),
            Select::make('role')->label(__('field.role'))->options([
                'admin' => __('enum.role.admin'),
                'marketing_manager' => __('enum.role.marketing_manager'),
                'marketing_staff' => __('enum.role.marketing_staff'),
                'customer_service_manager' => __('enum.role.customer_service_manager'),
                'customer_service_staff' => __('enum.role.customer_service_staff'),
                'viewer' => __('enum.role.viewer'),
            ])->required(),
            TextInput::make('password')
                ->label(__('field.password'))
                ->password()
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $operation) => $operation === 'create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('field.name'))->searchable()->sortable(),
            TextColumn::make('email')->label(__('field.email'))->searchable()->sortable(),
            TextColumn::make('role')->label(__('field.role'))->badge()
                ->formatStateUsing(fn (?string $state): string => $state ? __('enum.role.' . $state) : '')
                ->color(fn (?string $state): string => match ($state) {
                    'admin' => 'danger',
                    'marketing_manager', 'customer_service_manager' => 'warning',
                    'marketing_staff', 'customer_service_staff' => 'info',
                    'viewer' => 'gray',
                    default => 'gray',
                }),
            TextColumn::make('created_at')->label(__('field.created_at'))->dateTime()->sortable(),
        ])
            ->actions([ActionGroup::make([
                ViewAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
