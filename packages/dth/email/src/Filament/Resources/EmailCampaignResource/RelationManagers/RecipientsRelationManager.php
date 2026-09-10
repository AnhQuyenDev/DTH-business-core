<?php

namespace Dth\Email\Filament\Resources\EmailCampaignResource\RelationManagers;

use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Filament\Support\FormHelp;
use Dth\Email\Filament\Support\StatusColor;
use Dth\Email\Models\CampaignRecipient;
use Dth\Email\Services\CampaignRecipientImportService;
use Dth\Email\Services\CampaignRecipientService;
use Dth\Email\Services\TemplateVariableRegistry;
use Dth\Email\Support\UiText;
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
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';
    protected static bool $isLazy = false;

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return UiText::get('recipient.title', 'Recipients');
    }

    public function form(Schema $schema): Schema
    {
        $requirements = app(TemplateVariableRegistry::class)
            ->recipientRequirements($this->getOwnerRecord());

        $fields = [
            TextInput::make('email')
                ->label(UiText::get('recipient.email', 'Email'))
                ->email()
                ->required()
                ->maxLength(255),

            TextInput::make('name')
                ->label(UiText::get('recipient.name', 'Name'))
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
                    FormHelp::icon(UiText::get(
                        'recipient.required_variable',
                        'Required by {{ :variable }} in the campaign content.',
                        ['variable' => $key]
                    )),
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
                TextColumn::make('email')
                    ->label(UiText::get('recipient.email', 'Email'))
                    ->searchable(),
                TextColumn::make('name')
                    ->label(UiText::get('recipient.name', 'Name'))
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label(UiText::get('common.fields.status', 'Status'))
                    ->badge()
                    ->formatStateUsing(fn ($state): string => UiText::status($state))
                    ->color(fn ($state): string => StatusColor::for($state)),
                TextColumn::make('sent_at')
                    ->label(UiText::get('recipient.sent', 'Sent'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextColumn::make('opened_at')
                    ->label(UiText::get('recipient.opened', 'Opened'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('clicked_at')
                    ->label(UiText::get('recipient.clicked', 'Clicked'))
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(UiText::get('recipient.add', 'Add'))
                    ->icon('heroicon-o-plus')
                    ->createAnother(false)
                    ->visible(fn (): bool => $this->getOwnerRecord()->status === EmailCampaignStatus::Draft)
                    ->using(fn (array $data): CampaignRecipient => app(CampaignRecipientService::class)
                        ->add($this->getOwnerRecord(), $data)),

                Action::make('import')
                    ->label(UiText::get('recipient.import', 'Import'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->visible(fn (): bool => $this->getOwnerRecord()->status === EmailCampaignStatus::Draft)
                    ->schema([
                        FileUpload::make('file')
                            ->label(UiText::get('recipient.csv_file', 'CSV file'))
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
                                tooltip: UiText::get(
                                    'recipient.expected_columns',
                                    'Expected columns: :columns. Rows missing required personalization values are skipped.',
                                    ['columns' => implode(', ', $expectedColumns)]
                                )
                            ),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $file = $data['file'];

                            if (! $file instanceof TemporaryUploadedFile) {
                                throw new \RuntimeException(UiText::get('recipient.invalid_csv', 'Invalid CSV upload.'));
                            }

                            $result = app(CampaignRecipientImportService::class)->importCsv(
                                $this->getOwnerRecord(),
                                $file->getRealPath(),
                            );

                            Notification::make()
                                ->title(UiText::get('recipient.imported_title', 'Recipients imported'))
                                ->body(UiText::get(
                                    'recipient.imported_summary',
                                    'Added: :added · Updated: :updated · Invalid/incomplete: :invalid',
                                    [
                                        'added' => $result->added,
                                        'updated' => $result->updated,
                                        'invalid' => $result->invalid,
                                    ]
                                ))
                                ->success()
                                ->send();
                        } catch (Throwable $e) {
                            Notification::make()
                                ->title(UiText::get('recipient.import_failed', 'Import failed'))
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
                            ->title(UiText::get('recipient.cannot_delete', 'Recipient cannot be deleted'))
                            ->body(UiText::get(
                                'recipient.frozen',
                                'Recipients are frozen after the campaign leaves Draft status.'
                            ))
                            ->danger()
                            ->send();

                        $action->halt();
                    }),
            ]);
    }
}
