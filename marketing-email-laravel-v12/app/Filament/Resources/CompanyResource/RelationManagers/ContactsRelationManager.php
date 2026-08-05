<?php

namespace App\Filament\Resources\CompanyResource\RelationManagers;

use App\Enums\Crm\CompanyContactDecisionRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContactsRelationManager extends RelationManager
{
    protected static string $relationship = 'contacts';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('relation.title.company_contacts');
    }

    public function getLabel(): string
    {
        return __('relation.title.company_contacts');
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Select::make('id')
                ->label(__('field.contact'))
                ->relationship('contacts', 'id')
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                ->required(),
            TextInput::make('job_title')->label(__('field.job_title')),
            TextInput::make('department')->label(__('field.department')),
            Select::make('decision_role')
                ->label(__('field.decision_role'))
                ->options(collect(CompanyContactDecisionRole::cases())->mapWithKeys(fn ($case) => [$case->value => ucfirst(str_replace('_', ' ', $case->value))])),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')->label(__('field.contact'))->searchable(),
                TextColumn::make('pivot.job_title')->label(__('field.job_title'))->searchable(),
                TextColumn::make('pivot.department')->label(__('field.department'))->searchable(),
                TextColumn::make('pivot.decision_role')->label(__('field.decision_role'))->badge(),
                TextColumn::make('pivot.is_primary')->label(__('field.is_primary'))->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Có' : 'Không')
                    ->color(fn ($state): string => $state ? 'success' : 'gray'),
                TextColumn::make('pivot.is_active')->label(__('field.is_active'))->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Hoạt động' : 'Không hoạt động')
                    ->color(fn ($state): string => $state ? 'success' : 'gray'),
            ])
            ->headerActions([
                CreateAction::make()->label(__('action.add_contact_to_company')),
            ])
            ->actions([
                EditAction::make(),
            ]);
    }
}
