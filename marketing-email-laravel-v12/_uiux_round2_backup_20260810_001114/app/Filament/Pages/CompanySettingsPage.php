<?php

namespace App\Filament\Pages;

use App\Models\CompanySetting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class CompanySettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?int $navigationSort = 90;

    protected static string $view = 'filament.pages.company-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = CompanySetting::firstOrCreateDefault();
        $data = $settings->toArray();
        // Never hydrate a stored API secret back into the browser.
        $data['vietqr_api_key'] = null;
        $this->form->fill($data);
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('navigation.company_settings');
    }

    public static function getNavigationSort(): ?int
    {
        return 90;
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('section.company_info'))->schema([
                    TextInput::make('company_name')->label(__('field.company_name'))->required()->maxLength(255),
                    TextInput::make('tax_code')->label(__('field.tax_code'))->maxLength(50),
                    TextInput::make('address')->label(__('field.address'))->maxLength(255),
                    TextInput::make('phone')->label(__('field.phone'))->maxLength(30),
                    TextInput::make('email')->label(__('field.email'))->email()->maxLength(255),
                    TextInput::make('website')->label(__('field.website'))->url()->maxLength(255),
                    FileUpload::make('logo_path')
                        ->label(__('field.logo'))
                        ->image()
                        ->directory('company')
                        ->maxSize(2048),
                ])->columns(2),
                Section::make(__('section.vietqr'))->schema([
                    TextInput::make('vietqr_client_id')
                        ->label(__('field.vietqr_client_id'))
                        ->helperText(__('field.vietqr_helper'))
                        ->maxLength(255),
                    TextInput::make('vietqr_api_key')
                        ->label(__('field.vietqr_api_key'))
                        ->password()
                        ->revealable()
                        ->maxLength(255)
                        ->helperText(__('helper.vietqr_api_key_blank')),
                ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        if (blank($data['vietqr_api_key'] ?? null)) {
            unset($data['vietqr_api_key']);
        }

        CompanySetting::firstOrCreateDefault()->update($data);

        Notification::make()
            ->success()
            ->title(__('notification.settings_saved'))
            ->send();
    }
}
