<?php

namespace Dth\HumanResource\Filament\Resources\EmployeeResource\RelationManagers;

use Dth\HumanResource\Enums\BusinessFunction;
use Dth\HumanResource\Enums\PositionAuthority;
use Dth\HumanResource\Support\StatusColor;
use Dth\HumanResource\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BusinessFunctionsRelationManager extends RelationManager
{
    protected static string $relationship = 'businessFunctions';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return UiText::get('relations.business_functions', 'Business functions');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('function_key')
                ->label(UiText::get('fields.business_function', 'Business function'))
                ->options(BusinessFunction::options(includeSystem: false))
                ->required()
                ->native(false),
            Select::make('authority_level')
                ->label(UiText::get('fields.authority_level', 'Authority level'))
                ->options(PositionAuthority::options())
                ->default(PositionAuthority::Member->value)
                ->required()
                ->native(false),
            Toggle::make('is_primary')
                ->label(UiText::get('fields.is_primary', 'Primary function'))
                ->default(false),
            Toggle::make('is_active')
                ->label(UiText::get('common.status.active', 'Active'))
                ->default(true),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('function_key')
                    ->label(UiText::get('fields.business_function', 'Business function'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof BusinessFunction ? $state : BusinessFunction::tryFrom((string) $state))?->label() ?? (string) $state)
                    ->color(fn ($state): string => StatusColor::businessFunction($state)),
                TextColumn::make('authority_level')
                    ->label(UiText::get('fields.authority_level', 'Authority level'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof PositionAuthority ? $state : PositionAuthority::tryFrom((string) $state))?->label() ?? (string) $state)
                    ->color(fn ($state): string => StatusColor::authority($state)),
                IconColumn::make('is_primary')
                    ->label(UiText::get('fields.is_primary', 'Primary'))
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label(UiText::get('common.status.active', 'Active'))
                    ->boolean(),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label(UiText::get('actions.add_business_function', 'Add business function'))
                    ->icon('heroicon-o-squares-2x2')
                    ->color('gray')
                    ->modalIcon('heroicon-o-squares-2x2')
                    ->modalHeading(UiText::get('actions.add_business_function', 'Thêm chức năng nghiệp vụ'))
                    ->modalDescription(UiText::get('relations.business_function_modal_description', 'Gán thêm chức năng nghiệp vụ, mức thẩm quyền và trạng thái hoạt động cho nhân viên này.'))
                    ->modalSubmitAction(fn (Actions\Action $action): Actions\Action => $action
                        ->label(UiText::get('actions.add_business_function', 'Thêm chức năng nghiệp vụ'))
                        ->icon('heroicon-o-squares-2x2')
                        ->extraAttributes(['class' => 'dth-hr-modal-action dth-hr-modal-action--primary']))
                    ->modalCancelAction(fn (Actions\Action $action): Actions\Action => $action
                        ->label(UiText::get('common.actions.cancel', 'Hủy thao tác'))
                        ->extraAttributes(['class' => 'dth-hr-modal-action dth-hr-modal-action--secondary']))
                    ->extraAttributes(['class' => 'dth-hr-entry-action dth-hr-entry-action--violet']),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\EditAction::make()->label(UiText::get('common.actions.edit', 'Edit')),
                    Actions\DeleteAction::make()->label(UiText::get('common.actions.delete', 'Delete')),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ]);
    }
}
