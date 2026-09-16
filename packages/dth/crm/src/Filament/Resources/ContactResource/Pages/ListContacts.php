<?php

namespace Dth\Crm\Filament\Resources\ContactResource\Pages;

use Dth\Crm\Enums\ContactType;
use Dth\Crm\Filament\Resources\ContactResource;
use Dth\Crm\Filament\Support\CrmDataActions;
use Dth\Crm\Filament\Support\CrmPageUi;
use Dth\Crm\Models\Contact;
use Dth\Crm\Support\UiText;
use Filament\Actions\Action;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class ListContacts extends ListRecords
{
    protected static string $resource = ContactResource::class;

    public function getTitle(): string|Htmlable
    {
        return CrmPageUi::title(UiText::get('pages.contacts.title', 'Contacts'), 'contact');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.contacts.subheading', 'Manage personal and business contacts, source information, and CRM identity in one place.');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createContact')
                ->label(UiText::get('pages.contacts.create', 'Add contact'))
                ->icon('heroicon-o-user-plus')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-crm-entry-action dth-crm-entry-action--teal'])
                ->modalHeading(UiText::get('pages.contacts.create', 'Add contact'))
                ->modalIcon('heroicon-o-user-plus')
                ->modalWidth(Width::ThreeExtraLarge)
                ->modalSubmitAction(fn (Action $action): Action => $action
                    ->label(UiText::get('common.actions.save', 'Save'))
                    ->icon('heroicon-o-check'))
                ->modalCancelAction(fn (Action $action): Action => $action
                    ->label(UiText::get('common.actions.cancel', 'Cancel'))
                    ->icon('heroicon-o-x-mark')
                    ->color('gray'))
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('contact_code')
                            ->label(UiText::get('fields.contact_code', 'Contact code'))
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Mã nhận diện nội bộ của liên hệ trong CRM.'),
                        Select::make('type')
                            ->label(UiText::get('fields.contact_type', 'Contact type'))
                            ->options(ContactType::options())
                            ->native(false)
                            ->required()
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Chọn liên hệ cá nhân hoặc liên hệ doanh nghiệp để phân loại đúng dữ liệu.'),
                        TextInput::make('display_name')
                            ->label(UiText::get('fields.display_name', 'Display name'))
                            ->required()
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Tên hiển thị chính dùng khi tìm kiếm và liên kết dữ liệu CRM.')
                            ->columnSpanFull(),
                        TextInput::make('email')
                            ->label(UiText::get('common.fields.email', 'Email'))
                            ->email()
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Email dùng để liên hệ và đối soát với các module liên quan.'),
                        TextInput::make('phone')
                            ->label(UiText::get('fields.phone', 'Phone'))
                            ->tel()
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Số điện thoại dùng cho chăm sóc, gọi ra và xác thực thông tin.'),
                        TextInput::make('source')
                            ->label(UiText::get('common.fields.source', 'Source'))
                            ->hintIcon('heroicon-o-question-mark-circle', tooltip: 'Nguồn phát sinh liên hệ, ví dụ Website, Form, Sales hoặc Event.'),
                    ]),
                ])
                ->action(function (array $data): void {
                    Contact::query()->create($data);
                    Notification::make()->success()->title(UiText::get('notifications.contact_created', 'Contact created'))->send();
                })
                ->visible(fn (): bool => ContactResource::canCreate()),
            ...CrmDataActions::make('contacts', fn () => $this->getFilteredTableQuery(), 'crm-contacts', 'Contacts'),
        ];
    }

    public function getTabs(): array
    {
        return [
            'personal' => Tab::make(UiText::get('navigation.personal_contacts', 'Personal contacts'))
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'personal')),
            'business' => Tab::make(UiText::get('navigation.business_contacts', 'Business contacts'))
                ->modifyQueryUsing(fn ($query) => $query->where('type', 'business')),
        ];
    }
}
