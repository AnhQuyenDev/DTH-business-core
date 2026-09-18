<?php

namespace Dth\HumanResource\Filament\Resources\EmployeeResource\RelationManagers;

use Dth\HumanResource\Enums\AvailabilityStatus;
use Dth\HumanResource\Support\StatusColor;
use Dth\HumanResource\Support\UiText;
use Filament\Actions;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AvailabilitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'availabilities';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return UiText::get('relations.availability', 'Availability & leave');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('status')
                ->label(UiText::get('common.fields.status', 'Status'))
                ->options(AvailabilityStatus::options())
                ->required()
                ->native(false)
                ->live()
                ->afterStateUpdated(function ($state, $set): void {
                    $status = AvailabilityStatus::tryFrom((string) $state);
                    if ($status) {
                        $set('can_receive_new_work', $status->canReceiveNewWork());
                        $set('can_support_existing_work', $status->canSupportExistingWork());
                    }
                }),
            DateTimePicker::make('starts_at')
                ->label(UiText::get('fields.starts_at', 'Starts at'))
                ->required(),
            DateTimePicker::make('ends_at')
                ->label(UiText::get('fields.ends_at', 'Ends at'))
                ->required()
                ->afterOrEqual('starts_at'),
            TextInput::make('reason')
                ->label(UiText::get('common.fields.reason', 'Reason'))
                ->maxLength(255),
            Toggle::make('can_receive_new_work')
                ->label(UiText::get('fields.can_receive_new_work', 'Can receive new work')),
            Toggle::make('can_support_existing_work')
                ->label(UiText::get('fields.can_support_existing_work', 'Can support existing work')),
            Textarea::make('note')
                ->label(UiText::get('common.fields.notes', 'Notes'))
                ->columnSpanFull(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ($state instanceof AvailabilityStatus ? $state : AvailabilityStatus::tryFrom((string) $state))?->label() ?? (string) $state)
                    ->color(fn ($state): string => StatusColor::availability($state)),
                TextColumn::make('starts_at')
                    ->label(UiText::get('fields.starts_at', 'Starts at'))
                    ->dateTime('d/m/Y H:i'),
                TextColumn::make('ends_at')
                    ->label(UiText::get('fields.ends_at', 'Ends at'))
                    ->dateTime('d/m/Y H:i'),
                IconColumn::make('can_receive_new_work')
                    ->label(UiText::get('fields.can_receive_new_work_short', 'New work'))
                    ->boolean(),
                TextColumn::make('reason')
                    ->label(UiText::get('common.fields.reason', 'Reason'))
                    ->placeholder('—'),
            ])
            ->headerActions([
                Actions\CreateAction::make()
                    ->label(UiText::get('actions.add_availability', 'Add availability'))
                    ->icon('heroicon-o-calendar-days')
                    ->color('gray')
                    ->modalIcon('heroicon-o-calendar-days')
                    ->modalHeading(UiText::get('actions.add_availability', 'Thêm lịch làm việc / nghỉ phép'))
                    ->modalDescription(UiText::get('relations.availability_modal_description', 'Khai báo thời gian làm việc, nghỉ phép hoặc tạm thời không nhận việc cho nhân viên này.'))
                    ->modalSubmitAction(fn (Actions\Action $action): Actions\Action => $action
                        ->label(UiText::get('actions.add_availability', 'Thêm lịch làm việc / nghỉ phép'))
                        ->icon('heroicon-o-calendar-days')
                        ->extraAttributes(['class' => 'dth-hr-modal-action dth-hr-modal-action--primary']))
                    ->modalCancelAction(fn (Actions\Action $action): Actions\Action => $action
                        ->label(UiText::get('common.actions.cancel', 'Hủy thao tác'))
                        ->extraAttributes(['class' => 'dth-hr-modal-action dth-hr-modal-action--secondary']))
                    ->extraAttributes(['class' => 'dth-hr-entry-action dth-hr-entry-action--green']),
            ])
            ->recordActions([
                Actions\ActionGroup::make([
                    Actions\EditAction::make()->label(UiText::get('common.actions.edit', 'Edit')),
                    Actions\DeleteAction::make()->label(UiText::get('common.actions.delete', 'Delete')),
                ])->icon('heroicon-o-ellipsis-vertical')->iconButton(),
            ]);
    }
}
