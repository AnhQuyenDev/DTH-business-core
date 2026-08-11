<?php

namespace App\Filament\Resources;

use App\Filament\Actions\QuickViewAction;
use App\Enums\Crm\CompanyLifecycleStage;
use App\Filament\Resources\CompanyResource\Pages;
use App\Filament\Resources\CompanyResource\RelationManagers\AssignmentsRelationManager;
use App\Filament\Resources\CompanyResource\RelationManagers\ContactsRelationManager;
use App\Models\Crm\Company;
use App\Models\Crm\Staff;
use Filament\Forms\Components\Section as FormSection;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.crm');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.companies');
    }

    public static function getModelLabel(): string
    {
        return __('resource.company.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('resource.company.plural');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()->schema([
                    TextEntry::make('company_code')->label(__('field.company_code')),
                    TextEntry::make('legal_name')->label(__('field.legal_name')),
                    TextEntry::make('tax_code')->label(__('field.tax_code')),
                    TextEntry::make('lifecycle_stage')
                        ->label(__('field.lifecycle_stage'))
                        ->badge()
                        ->formatStateUsing(fn (CompanyLifecycleStage $state): string => $state->label()),
                    TextEntry::make('accountOwner.full_name')->label(__('field.account_owner')),
                    TextEntry::make('email_domain')->label(__('field.email_domain')),
                    TextEntry::make('website')->label(__('field.website')),
                    TextEntry::make('phone')->label(__('field.phone')),
                    TextEntry::make('industry')->label(__('field.industry')),
                    TextEntry::make('address')->label(__('field.company_address')),
                ])->columns(['default' => 1, 'md' => 2]),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            FormSection::make(__('section.company_info'))
                ->columns(['default' => 1, 'md' => 2, 'xl' => 3])
                ->schema([
                    TextInput::make('company_code')
                        ->label(__('field.company_code'))
                        ->disabled(),
                    TextInput::make('legal_name')
                        ->label(__('field.legal_name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(['default' => 1, 'md' => 2]),
                    TextInput::make('tax_code')->label(__('field.tax_code'))->maxLength(50),
                    TextInput::make('industry')->label(__('field.industry'))->maxLength(255),
                    Select::make('lifecycle_stage')
                        ->label(__('field.lifecycle_stage'))
                        ->options(collect(CompanyLifecycleStage::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()]))
                        ->required(),
                ]),
            FormSection::make(__('section.contact_info'))
                ->columns(['default' => 1, 'md' => 2, 'xl' => 3])
                ->schema([
                    TextInput::make('email_domain')->label(__('field.email_domain'))->maxLength(255),
                    TextInput::make('website')->label(__('field.website'))->url()->maxLength(255),
                    TextInput::make('phone')->label(__('field.phone'))->tel()->maxLength(50),
                    TextInput::make('address')->label(__('field.company_address'))->columnSpan(['default' => 1, 'md' => 2]),
                    TextInput::make('province')->label(__('field.province'))->maxLength(255),
                    Select::make('account_owner_staff_id')
                        ->label(__('field.account_owner'))
                        ->relationship('accountOwner', 'full_name')
                        ->searchable()
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText(__('help.account_owner_managed_by_assignment')),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_code')->label(__('field.company_code'))->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('legal_name')->label(__('field.legal_name'))->searchable(),
                Tables\Columns\TextColumn::make('tax_code')->label(__('field.tax_code'))->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('email_domain')->label(__('field.email_domain'))->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('lifecycle_stage')
                    ->label(__('field.lifecycle_stage'))
                    ->badge()
                    ->formatStateUsing(fn (CompanyLifecycleStage $state): string => $state->label())
                    ->color(fn (CompanyLifecycleStage $state): string => $state->color()),
                Tables\Columns\TextColumn::make('accountOwner.full_name')->label(__('field.account_owner'))->searchable(),
                Tables\Columns\TextColumn::make('contacts_count')
                    ->label(__('field.contacts_count'))
                    ->counts('contacts'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('lifecycle_stage')
                    ->label(__('field.lifecycle_stage'))
                    ->options(collect(CompanyLifecycleStage::cases())->mapWithKeys(fn ($case) => [$case->value => $case->label()])),
                Tables\Filters\SelectFilter::make('account_owner_staff_id')
                    ->label(__('field.account_owner'))
                    ->options(Staff::query()->orderBy('full_name')->pluck('full_name', 'id')),
            ])
            ->actions([
                ActionGroup::make([
                    QuickViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ContactsRelationManager::class,
            AssignmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompanies::route('/'),
            'view' => Pages\ViewCompany::route('/{record}'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('business_flow.v2_enabled')
            && static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('viewAny', Company::class) ?? false;
    }

    public static function canView(Model $record): bool
    {
        return $record instanceof Company
            && (auth()->user()?->can('view', $record) ?? false);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create', Company::class) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        return $record instanceof Company
            && (auth()->user()?->can('update', $record) ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        return $record instanceof Company
            && (auth()->user()?->can('delete', $record) ?? false);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if (! $user) {
            return $query->whereRaw('0 = 1');
        }

        if (
            $user->isAdmin()
            || $user->canReadAcrossBusiness()
            || $user->isMarketingManager()
            || $user->isSalesManager()
        ) {
            return $query;
        }

        $staffId = $user->staff?->id;

        if ($staffId === null) {
            return $query->whereRaw('0 = 1');
        }

        if ($user->isSalesStaff()) {
            return $query->where(function (Builder $query) use ($staffId): void {
                $query->where('account_owner_staff_id', $staffId)
                    ->orWhereHas(
                        'leads',
                        fn (Builder $leadQuery): Builder => $leadQuery
                            ->where('assigned_staff_id', $staffId)
                    )
                    ->orWhereHas(
                        'opportunities',
                        fn (Builder $opportunityQuery): Builder => $opportunityQuery
                            ->where('assigned_staff_id', $staffId)
                    );
            });
        }

        return $query->whereRaw('0 = 1');
    }
}
