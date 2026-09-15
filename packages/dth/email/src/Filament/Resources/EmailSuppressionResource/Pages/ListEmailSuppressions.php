<?php

namespace Dth\Email\Filament\Resources\EmailSuppressionResource\Pages;

use Dth\Email\Enums\SuppressionReason;
use Dth\Email\Filament\Resources\EmailSuppressionResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Services\SuppressionService;
use Dth\Email\Support\UiText;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListEmailSuppressions extends ListRecords
{
    protected static string $resource = EmailSuppressionResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(
            UiText::get('suppression.list.title', 'Email Suppressions'),
            'shield',
            'red',
        );
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('suppression.list.subheading', 'Manage blocked email addresses, reasons, sources, and release history.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addSuppression')
                ->label(
                    UiText::get('suppression.create.title', 'Add Email to Suppression List')
                )
                ->icon('heroicon-o-plus')
                ->modalHeading(
                    UiText::get('suppression.create.title', 'Add Email to Suppression List')
                )
                ->modalSubmitActionLabel(UiText::get('common.actions.save', 'Save'))
                ->modalCancelActionLabel(UiText::get('common.actions.cancel', 'Cancel'))
                ->schema([
                    TextInput::make('email')
                        ->label(UiText::get('common.fields.email', 'Email'))
                        ->email()
                        ->required()
                        ->maxLength(255),
                    Textarea::make('note')
                        ->label(UiText::get('common.fields.notes', 'Notes'))
                        ->rows(4),
                ])
                ->action(function (array $data): void {
                    app(SuppressionService::class)->suppress(
                        email: $data['email'],
                        reason: SuppressionReason::Manual,
                        source: 'email.admin',
                        note: $data['note'] ?? null,
                        createdBy: auth()->id(),
                    );

                    Notification::make()
                        ->title(
                            UiText::get('suppression.create.title', 'Add Email to Suppression List')
                        )
                        ->success()
                        ->send();
                })
                ->visible(fn (): bool => EmailSuppressionResource::canCreate()),
        ];
    }
}
