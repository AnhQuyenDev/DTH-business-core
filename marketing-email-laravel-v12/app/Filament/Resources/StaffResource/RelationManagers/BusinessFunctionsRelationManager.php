<?php

namespace App\Filament\Resources\StaffResource\RelationManagers;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\PositionAuthority;
use App\Models\Crm\StaffBusinessFunction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class BusinessFunctionsRelationManager extends RelationManager
{
    protected static string $relationship = 'businessFunctions';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('v1.business_functions.title');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('function_key')
                ->label(__('v1.business_functions.function'))
                ->options(collect(DepartmentFunction::options())
                    ->except(['admin', 'other'])
                    ->all())
                ->required()
                ->rules([
                    fn (?StaffBusinessFunction $record) => Rule::unique(
                        'staff_business_functions',
                        'function_key',
                    )->where('staff_id', $this->getOwnerRecord()->id)
                        ->ignore($record?->id),
                ]),
            Select::make('authority_level')
                ->label(__('v1.business_functions.authority'))
                ->options(PositionAuthority::options())
                ->default(PositionAuthority::Member->value)
                ->required(),
            Toggle::make('is_primary')
                ->label(__('v1.business_functions.primary'))
                ->default(false),
            Toggle::make('is_active')
                ->label(__('field.active'))
                ->default(true),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('function_key')
            ->columns([
                TextColumn::make('function_key')
                    ->label(__('v1.business_functions.function'))
                    ->formatStateUsing(fn ($state): string => $state instanceof DepartmentFunction
                        ? $state->label()
                        : (DepartmentFunction::tryFrom((string) $state)?->label() ?? (string) $state))
                    ->badge(),
                TextColumn::make('authority_level')
                    ->label(__('v1.business_functions.authority'))
                    ->formatStateUsing(fn ($state): string => $state instanceof PositionAuthority
                        ? $state->label()
                        : (PositionAuthority::tryFrom((string) $state)?->label() ?? (string) $state)),
                IconColumn::make('is_primary')->label(__('v1.business_functions.primary'))->boolean(),
                IconColumn::make('is_active')->label(__('field.active'))->boolean(),
            ])
            ->headerActions([CreateAction::make()->label(__('v1.business_functions.add'))])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])->iconButton(),
            ]);
    }
}
