<?php

namespace Dth\Email\Filament\Resources\EmailCampaignResource\RelationManagers;

use Dth\Email\Enums\CampaignRecipientStatus;
use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Filament\Support\FormHelp;
use Dth\Email\Filament\Support\StatusColor;
use Dth\Email\Models\CampaignRecipient;
use Dth\Email\Services\CampaignRecipientImportService;
use Dth\Email\Services\CampaignRecipientService;
use Dth\Email\Services\TemplateVariableRegistry;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';
    protected static ?string $title = 'Recipients';
    protected static bool $isLazy = false;

    public function form(Schema $schema): Schema
    {
        $requirements = app(TemplateVariableRegistry::class)
            ->recipientRequirements($this->getOwnerRecord());

        $fields = [
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->required()
                ->maxLength(255),

            TextInput::make('name')
                ->label('Name')
                ->required(array_key_exists('name', $requirements))
                ->maxLength(255),
        ];

        foreach ($requirements as $key => $label) {
            if ($key === 'name') {
                continue;
            }

            $fields[] = TextInput::make('variables.'.$key)
                ->label($label)
                ->required()
                ->maxLength(1000)
                ->afterLabel([
                    FormHelp::icon('Required by {{ '.$key.' }} in the campaign content.'),
                ]);
        }

        return $schema->components($fields);
    }

    public function table(Table $table): Table
    {
        $expectedColumns = app(TemplateVariableRegistry::class)
            ->expectedCsvColumns($this->getOwnerRecord());

        return $table
            ->recordTitleAttribute('email')
            ->columns([
                TextColumn::make('email')->searchable(),
                TextColumn::make('name')->searchable()->placeholder('—'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => ucfirst(
                        $state instanceof CampaignRecipientStatus
                            ? $state->value
                            : (string) $state
                    ))
                    ->color(fn ($state): string => StatusColor::for($state)),
                TextColumn::make('sent_at')->label('Sent')->dateTime('d/m/Y H:i')->placeholder('—'),
                TextColumn::make('opened_at')->label('Opened')->dateTime('d/m/Y H:i')->placeholder('—')->toggleable(),
                TextColumn::make('clicked_at')->label('Clicked')->dateTime('d/m/Y H:i')->placeholder('—')->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add')
                    ->icon('heroicon-o-plus')
                    ->createAnother(false)
                    ->visible(fn (): bool => $this->getOwnerRecord()->status === EmailCampaignStatus::Draft)
                    ->using(fn (array $data): CampaignRecipient => app(CampaignRecipientService::class)
                        ->add($this->getOwnerRecord(), $data)),

                Action::make('import')
                    ->label('Import')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible(fn (): bool => $this->getOwnerRecord()->status === EmailCampaignStatus::Draft)
                    ->schema([
                        FileUpload::make('file')
                            ->label('CSV file')
                            ->required()
                            ->storeFiles(false)
                            ->acceptedFileTypes([
                                'text/csv',
                                'text/plain',
                                'application/csv',
                                'application/vnd.ms-excel',
                            ])
                            ->maxSize(5120)
                            ->hintIcon(
                                'heroicon-o-question-mark-circle',
                                tooltip: 'Expected columns: '.implode(', ', $expectedColumns).'. Rows missing required personalization values are skipped.'
                            ),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $file = $data['file'];

                            if (! $file instanceof TemporaryUploadedFile) {
                                throw new \RuntimeException('Invalid CSV upload.');
                            }

                            $result = app(CampaignRecipientImportService::class)->importCsv(
                                $this->getOwnerRecord(),
                                $file->getRealPath(),
                            );

                            Notification::make()
                                ->title('Recipients imported')
                                ->body(
                                    "Added: {$result->added}"
                                    ." · Updated: {$result->updated}"
                                    ." · Invalid/incomplete: {$result->invalid}"
                                )
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title('Import failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (): bool => $this->getOwnerRecord()->status === EmailCampaignStatus::Draft)
                    ->using(fn (CampaignRecipient $record, array $data): CampaignRecipient => app(CampaignRecipientService::class)
                        ->update($record, $data)),

                DeleteAction::make()
                    ->visible(fn (): bool => $this->getOwnerRecord()->status === EmailCampaignStatus::Draft)
                    ->before(function (DeleteAction $action, CampaignRecipient $record): void {
                        $record->loadMissing('campaign');

                        if ($record->campaign->status === EmailCampaignStatus::Draft) {
                            return;
                        }

                        Notification::make()
                            ->title('Recipient cannot be deleted')
                            ->body('Recipients are frozen after the campaign leaves Draft status.')
                            ->danger()
                            ->send();

                        $action->halt();
                    }),
            ]);
    }
}
