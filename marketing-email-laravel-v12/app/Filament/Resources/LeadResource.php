<?php

namespace App\Filament\Resources;

use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\ContactType;
use App\Enums\Crm\LeadIntakeStatus;
use App\Filament\Resources\LeadResource\Pages;
use App\Filament\Resources\LeadResource\RelationManagers\QualificationNotesRelationManager;
use App\Models\Crm\Lead;
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
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.crm');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.leads');
    }

    public static function getModelLabel(): string
    {
        return __('field.lead');
    }

    public static function getPluralModelLabel(): string
    {
        return __('navigation.leads');
    }

    public static function canViewAny(): bool
    {
        return config('business_flow.v2_enabled')
            && static::userCanViewLeads();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        return config('business_flow.v2_enabled')
            && ($user?->isAdmin() || $user?->isCustomerServiceManager() || $user?->isCustomerServiceStaff()) ?? false;
    }

    public static function canDelete($record): bool
    {
        return config('business_flow.v2_enabled')
            && (auth()->user()?->isAdmin() ?? false);
    }

    public static function shouldRegisterNavigation(): bool
    {
        return config('business_flow.v2_enabled')
            && static::userCanViewLeads();
    }

    private static function userCanViewLeads(): bool
    {
        $user = auth()->user();

        return $user !== null
            && (
                $user->isAdmin()
                || $user->isMarketingManager()
                || $user->isCustomerServiceManager()
                || $user->isCustomerServiceStaff()
            );
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()->schema([
                    TextEntry::make('lead_code')->label(__('field.lead_code')),
                    TextEntry::make('contact.full_name')->label(__('field.contact')),
                    TextEntry::make('company.legal_name')->label(__('field.company')),
                    TextEntry::make('contact.contact_type')
                        ->label(__('field.contact_type'))
                        ->badge()
                        ->formatStateUsing(fn (?ContactType $state): string => $state?->label() ?? '—'),
                    TextEntry::make('intake_status')
                        ->label(__('field.intake_status'))
                        ->badge()
                        ->formatStateUsing(fn (LeadIntakeStatus $state): string => $state->label()),
                    TextEntry::make('source')->label(__('field.source')),
                    TextEntry::make('source_detail')->label(__('field.source_detail')),
                    TextEntry::make('title')->label(__('field.title')),
                    TextEntry::make('service_interest')->label(__('field.service_interest')),
                    TextEntry::make('estimated_value')->label(__('field.estimated_value'))->money('VND'),
                    TextEntry::make('assignedStaff.full_name')->label(__('field.assigned_staff')),
                    TextEntry::make('qualification.status')
                        ->label(__('field.status'))
                        ->badge()
                        ->formatStateUsing(fn ($state): string => $state instanceof ContactQualificationStatus
                            ? $state->label()
                            : ContactQualificationStatus::tryFrom((string) $state)?->label() ?? __('action.not_applicable')),
                    TextEntry::make('qualification.next_follow_up_at')
                        ->label(__('field.next_follow_up'))
                        ->dateTime('d/m/Y H:i'),
                    TextEntry::make('created_at')->label(__('field.created_at'))->dateTime('d/m/Y H:i'),
                ])->columns(2),
            ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('lead_code')->label(__('field.lead_code'))->disabled(),
            Select::make('contact_id')
                ->label(__('field.contact'))
                ->relationship('contact', 'full_name')
                ->searchable()
                ->required(),
            Select::make('company_id')
                ->label(__('field.company'))
                ->relationship('company', 'legal_name')
                ->searchable(),
            Select::make('intake_status')
                ->label(__('field.intake_status'))
                ->options(LeadIntakeStatus::options())
                ->required(),
            TextInput::make('title')->label(__('field.title'))->required()->maxLength(255),
            TextInput::make('service_interest')->label(__('field.service_interest'))->maxLength(255),
            TextInput::make('source')->label(__('field.source'))->maxLength(100),
            TextInput::make('source_detail')->label(__('field.source_detail'))->maxLength(255),
            TextInput::make('estimated_value')
                ->label(__('field.estimated_value'))
                ->numeric(),
            Select::make('assigned_staff_id')
                ->label(__('field.assigned_staff'))
                ->relationship('assignedStaff', 'full_name')
                ->searchable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('lead_code')->label(__('field.lead_code'))->searchable()->sortable(),
                Tables\Columns\TextColumn::make('contact.full_name')->label(__('field.contact'))->searchable(),
                Tables\Columns\TextColumn::make('company.legal_name')->label(__('field.company'))->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('contact.contact_type')
                    ->label(__('field.contact_type'))
                    ->badge()
                    ->formatStateUsing(fn (?ContactType $state): string => $state?->label() ?? '—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('source')->label(__('field.source'))->toggleable(),
                Tables\Columns\TextColumn::make('service_interest')->label(__('field.service_interest'))->searchable(),
                Tables\Columns\TextColumn::make('assignedStaff.full_name')->label(__('field.assigned_staff'))->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('qualification.status')
                    ->label(__('field.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof ContactQualificationStatus
                        ? $state->label()
                        : ContactQualificationStatus::tryFrom((string) $state)?->label() ?? (string) $state)
                    ->color(fn ($state): string => match (is_string($state) ? $state : $state?->value) {
                        'new' => 'gray',
                        'assigned' => 'info',
                        'contacting' => 'warning',
                        'follow_up' => 'primary',
                        'qualified' => 'success',
                        'converted' => 'success',
                        'unqualified', 'spam', 'duplicate', 'archived' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('qualification.next_follow_up_at')
                    ->label(__('field.next_follow_up'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('field.created_at'))
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('intake_status')
                    ->label(__('field.intake_status'))
                    ->options(LeadIntakeStatus::options()),
                Tables\Filters\SelectFilter::make('assigned_staff_id')
                    ->label(__('field.assigned_staff'))
                    ->relationship('assignedStaff', 'full_name'),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('field.status'))
                    ->options(ContactQualificationStatus::options())
                    ->query(fn (Builder $query, array $data) => $query
                        ->when(
                            filled($data['value'] ?? null),
                            fn (Builder $query, string $value) => $query->whereHas(
                                'qualification',
                                fn (Builder $query) => $query->where('status', $value)
                            )
                        )),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            QualificationNotesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'view' => Pages\ViewLead::route('/{record}'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
