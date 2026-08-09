<?php

namespace App\Filament\Resources;

use App\Enums\Crm\PositionAuthority;
use App\Filament\Resources\PositionResource\Pages;
use App\Models\Crm\Department;
use App\Models\Crm\Position;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PositionResource extends Resource
{
    protected static ?string $model = Position::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?int $navigationSort = 30;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('resource.position.singular');
    }

    public static function getModelLabel(): string
    {
        return __('resource.position.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.position.plural');
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
            Section::make(__('resource.position.singular'))
                ->schema([
                    Select::make('department_id')->label(__('field.department'))->options(Department::options())->native(false)->searchable()->required(),
                    TextInput::make('title')->label(__('field.title'))->datalist(Position::titleSuggestions())->helperText(__('helper.position_title_generic'))->required()->maxLength(255),
                    Select::make('authority_level')->label(__('field.position_authority'))->options(PositionAuthority::options())->native(false)->default(PositionAuthority::Member->value)->helperText(__('helper.position_authority'))->required(),
                    Textarea::make('description')->label(__('field.description'))->rows(3)->columnSpanFull(),
                    Toggle::make('is_active')->label(__('field.is_active'))->default(true),
                ])->columns(['default' => 1, 'md' => 2]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('title')
                ->label(__('field.title'))
                ->searchable()
                ->sortable(),

            TextColumn::make('department.name')
                ->label(__('field.department'))
                ->badge()
                ->searchable()
                ->color(fn (Position $record): string => $record->department?->color ?? 'gray'),

            TextColumn::make('authority_level')
                ->label(__('field.position_authority'))
                ->badge()
                ->formatStateUsing(
                    fn ($state): string => $state instanceof PositionAuthority
                        ? $state->label()
                        : PositionAuthority::tryFrom((string) $state)?->label()
                            ?? __('common.not_available')
                )
                ->color(fn ($state): string => match (
                    $state instanceof PositionAuthority
                        ? $state
                        : PositionAuthority::tryFrom((string) $state)
                ) {
                    PositionAuthority::Executive => 'danger',
                    PositionAuthority::Manager => 'warning',
                    PositionAuthority::Lead => 'info',
                    PositionAuthority::Member => 'success',
                    default => 'gray',
                }),

            IconColumn::make('is_active')
                ->label(__('field.is_active'))
                ->boolean(),

            TextColumn::make('created_at')
                ->label(__('field.created_at'))
                ->dateTime('d/m/Y H:i')
                ->sortable(),
        ])
            ->defaultSort('name')
            ->actions([ActionGroup::make([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton()])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->filters([
                SelectFilter::make('department_id')
                    ->label(__('field.department'))
                    ->relationship('department', 'name'),
                SelectFilter::make('authority_level')
                    ->label(__('field.position_authority'))
                    ->options(PositionAuthority::options()),
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
