<?php

namespace App\Filament\Resources;

use App\Actions\Contacts\ConvertContactToCustomerAction;
use App\Enums\Crm\ContactQualificationStatus;
use App\Enums\Crm\QualificationResult;
use App\Filament\Resources\ContactQualificationResource\Pages;
use App\Models\Crm\ContactQualification;
use App\Models\Crm\ContactQualificationNote;
use App\Models\Crm\Staff;
use App\Services\Crm\ContactQualificationWorkflowService;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ContactQualificationResource extends Resource
{
    protected static ?string $model = ContactQualification::class;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    protected static ?int $navigationSort = 50;

    public static function getNavigationLabel(): string
    {
        return __('page.title.lead_pipeline');
    }

    public static function getModelLabel(): string
    {
        return __('page.title.lead_pipeline');
    }

    public static function getPluralModelLabel(): string
    {
        return __('page.title.lead_pipeline');
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.crm');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return ! config('business_flow.v2_enabled');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() || $user?->isMarketingManager() || $user?->isCustomerServiceManager() || $user?->isCustomerServiceStaff()) ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();

        return ($user?->isAdmin() || $user?->isCustomerServiceManager() || $user?->isCustomerServiceStaff()) ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('contact_id')->label(__('field.lead'))->relationship('contact', 'id')->searchable()->required(),
            Select::make('assigned_staff_id')->label(__('field.assigned_staff'))->relationship('assignedStaff', 'full_name')->searchable(),
            TextInput::make('priority')->label(__('field.priority'))->maxLength(20),
            TextInput::make('score')->label(__('field.score'))->numeric(),
            TextInput::make('service_interest')->label(__('field.service_interest'))->maxLength(255),
            Select::make('status')->label(__('field.status'))->options(ContactQualificationStatus::options()),
            Select::make('qualification_result')->label(__('field.qualification_result'))->options(array_combine(array_keys(QualificationResult::options()), array_values(QualificationResult::options()))),
            DateTimePicker::make('next_follow_up_at')->label(__('field.next_follow_up'))->label(__('field.next_follow_up')),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('contact.full_name')->label(__('field.lead'))->searchable(),
            TextColumn::make('contact.email')->label(__('field.email'))->searchable()->toggleable(),
            TextColumn::make('assignedStaff.full_name')->label(__('field.assigned_staff'))->searchable(),
            TextColumn::make('status')
                ->badge()
                ->formatStateUsing(function ($state): string {
                    if ($state instanceof ContactQualificationStatus) {
                        return $state->label();
                    }

                    return ContactQualificationStatus::tryFrom((string) $state)?->label() ?? (string) $state;
                })
                ->color(fn ($state): string => $state instanceof ContactQualificationStatus
                    ? $state->color()
                    : (ContactQualificationStatus::tryFrom((string) $state)?->color() ?? 'gray')),
            TextColumn::make('qualification_result')
                ->badge()
                ->formatStateUsing(function ($state): string {
                    if ($state instanceof QualificationResult) {
                        return $state->label();
                    }

                    $value = (string) $state;
                    if ($value === '') {
                        return '—';
                    }

                    $normalized = $value === 'confirm_need' ? 'confirmed_need' : $value;

                    return QualificationResult::tryFrom($normalized)?->label()
                        ?? Str::of($normalized)->replace('_', ' ')->headline()->toString();
                })
                ->color(function ($state): string {
                    if ($state instanceof QualificationResult) {
                        return $state->color();
                    }

                    $value = (string) $state;
                    $normalized = $value === 'confirm_need' ? 'confirmed_need' : $value;

                    return QualificationResult::tryFrom($normalized)?->color() ?? 'gray';
                }),
            TextColumn::make('priority')->label(__('field.priority'))
                ->badge()
                ->formatStateUsing(fn ($state): string => __('field.priority.'.Str::lower((string) $state)))
                ->color(function ($state): string {
                    return match (Str::lower((string) $state)) {
                        'vip' => 'danger',
                        'high' => 'warning',
                        'normal' => 'info',
                        'low' => 'gray',
                        default => 'gray',
                    };
                }),
            TextColumn::make('next_follow_up_at')->dateTime('d/m/Y H:i')->sortable(),
        ])
            ->filters([
                SelectFilter::make('status')->options(ContactQualificationStatus::options()),
                SelectFilter::make('assigned_staff_id')->relationship('assignedStaff', 'full_name'),
            ])
            ->actions([ActionGroup::make([
                Action::make('start_contacting')
                    ->label(__('action.start_contacting'))
                    ->icon('heroicon-o-phone-arrow-up-right')
                    ->color('info')
                    ->visible(fn (ContactQualification $record): bool => in_array(
                        (string) ($record->status?->value ?? $record->status),
                        [
                            ContactQualificationStatus::Assigned->value,
                            ContactQualificationStatus::FollowUp->value,
                        ],
                        true,
                    ))
                    ->action(function (ContactQualification $record): void {
                        app(ContactQualificationWorkflowService::class)->transition(
                            qualification: $record,
                            to: ContactQualificationStatus::Contacting,
                            actorUserId: auth()->id(),
                        );
                        Notification::make()->success()->title(__('notification.updated'))->send();
                    }),
                Action::make('process_lead')
                    ->label(__('action.process_lead'))
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->visible(fn (ContactQualification $record): bool => ! config('business_flow.v2_enabled')
                            && blank($record->last_contacted_at)
                    )
                    ->form([
                    Select::make('status')->label(__('field.status'))->options(ContactQualificationStatus::options())->required(),
                    Select::make('qualification_result')->label(__('field.qualification_result'))->options(QualificationResult::options()),
                    DateTimePicker::make('next_follow_up_at')->label(__('field.next_follow_up')),
                    Textarea::make('note')->label(__('field.note'))->rows(3),
                    ])
                    ->action(function (ContactQualification $record, array $data): void {
                        $record->update([
                            'status' => $data['status'],
                            'qualification_result' => $data['qualification_result'] ?? $record->qualification_result,
                            'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
                            'last_contacted_at' => now(),
                        ]);

                        if (filled($data['note'] ?? null)) {
                            ContactQualificationNote::create([
                                'contact_qualification_id' => $record->id,
                                'staff_id' => auth()->user()?->staff?->id,
                                'note_type' => 'manual',
                                'content' => $data['note'],
                                'contacted_at' => now(),
                                'next_follow_up_at' => $data['next_follow_up_at'] ?? null,
                            ]);
                        }

                        Notification::make()->success()->title(__('notification.updated'))->send();
                    }),
                Action::make('processed')
                    ->label(__('action.processed'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->disabled()
                    ->visible(fn (ContactQualification $record): bool => filled($record->last_contacted_at)),
                Action::make('convert_to_customer')
                    ->label(__('action.convert_to_customer'))
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (ContactQualification $record): bool => ! config('business_flow.v2_enabled')
                            && $record->isConvertible()
                    )
                    ->form([
                    Select::make('assign_to_staff_id')
                        ->label(__('field.assigned_staff'))
                        ->options(Staff::query()->orderBy('full_name')->pluck('full_name', 'id'))
                        ->searchable(),
                    ])
                    ->action(function (ContactQualification $record, array $data): void {
                        $actor = auth()->user()?->staff;
                        if (! $actor) {
                            Notification::make()->danger()->title(__('notification.failed'))->body(__('notification.no_staff_profile_for_current_user'))->send();

                            return;
                        }

                        $assignTo = filled($data['assign_to_staff_id'] ?? null)
                            ? Staff::find($data['assign_to_staff_id'])
                            : null;

                        app(ConvertContactToCustomerAction::class)->execute(
                            $record->contact,
                            $actor,
                            $assignTo,
                        );

                        Notification::make()->success()->title(__('notification.converted'))->send();
                    }),
            ])->icon('heroicon-o-ellipsis-vertical')->iconButton()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContactQualifications::route('/'),
        ];
    }
}
