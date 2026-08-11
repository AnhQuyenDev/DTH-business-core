<?php

namespace App\Filament\Pages;

use App\Models\CompanySetting;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
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

    protected static ?int $navigationSort = 50;

    protected static string $view = 'filament.pages.company-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = CompanySetting::firstOrCreateDefault();
        $data = $settings->toArray();
        $data['vietqr_api_key'] = null;
        $this->form->fill($data);
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('configuration.company.navigation');
    }

    public function getTitle(): string
    {
        return __('configuration.company.title');
    }

    public static function getNavigationSort(): ?int
    {
        return 50;
    }

    public function getSubheading(): ?string
    {
        return __('configuration.company.subheading');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('system.manage-company-settings') ?? false;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(__('section.company_info'))
                    ->icon('heroicon-o-building-office-2')
                    ->iconColor('primary')
                    ->compact()
                    ->extraAttributes(['class' => 'dth-config-form'])
                    ->schema([
                        TextInput::make('company_name')
                            ->label(__('field.company_name'))
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['default' => 12, 'md' => 6]),
                        TextInput::make('tax_code')
                            ->label(__('field.tax_code'))
                            ->maxLength(50)
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        TextInput::make('phone')
                            ->label(__('field.phone'))
                            ->maxLength(30)
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        TextInput::make('address')
                            ->label(__('field.address'))
                            ->maxLength(255)
                            ->columnSpan(['default' => 12, 'md' => 6]),
                        TextInput::make('email')
                            ->label(__('field.email'))
                            ->email()
                            ->maxLength(255)
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        TextInput::make('website')
                            ->label(__('field.website'))
                            ->url()
                            ->maxLength(255)
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        FileUpload::make('logo_path')
                            ->label(__('field.logo'))
                            ->image()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('company')
                            ->imagePreviewHeight('88')
                            ->maxSize(2048)
                            ->hintIcon('heroicon-m-question-mark-circle', __('helper.company_logo'))
                            ->columnSpan(['default' => 12, 'md' => 4]),
                    ])
                    ->columns(12),

                Section::make(__('configuration.company.operations_policy'))
                    ->icon('heroicon-o-scale')
                    ->iconColor('primary')
                    ->compact()
                    ->collapsible()
                    ->extraAttributes(['class' => 'dth-config-form'])
                    ->schema([
                        Select::make('quotation_approval_mode')
                            ->label(__('v1.workflow.approval_mode'))
                            ->options([
                                'none' => __('v1.workflow.approval.none'),
                                'always' => __('v1.workflow.approval.always'),
                                'amount_threshold' => __('v1.workflow.approval.amount_threshold'),
                                'discount_threshold' => __('v1.workflow.approval.discount_threshold'),
                                'amount_or_discount' => __('v1.workflow.approval.amount_or_discount'),
                            ])
                            ->native(false)
                            ->required()
                            ->columnSpan(['default' => 12, 'md' => 6]),
                        TextInput::make('quotation_approval_amount_threshold')
                            ->label(__('v1.workflow.approval_amount'))
                            ->numeric()
                            ->minValue(0)
                            ->prefix('VND')
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        TextInput::make('quotation_approval_discount_threshold_percent')
                            ->label(__('v1.workflow.approval_discount'))
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->suffix('%')
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Select::make('quotation_confirmation_mode')
                            ->label(__('v1.workflow.confirmation_mode'))
                            ->options([
                                'click' => __('v1.workflow.confirmation.click'),
                                'otp' => __('v1.workflow.confirmation.otp'),
                            ])
                            ->native(false)
                            ->required()
                            ->columnSpan(['default' => 12, 'md' => 6]),
                        Toggle::make('payment_evidence_required')
                            ->label(__('v1.workflow.payment_evidence_required'))
                            ->inline()
                            ->hintIcon('heroicon-m-question-mark-circle', __('v1.workflow.payment_evidence_help'))
                            ->extraFieldWrapperAttributes(['class' => 'dth-config-toggle-wrap'])
                            ->columnSpan(['default' => 12, 'md' => 3]),
                        Toggle::make('support_tickets_enabled')
                            ->label(__('v1.workflow.support_tickets'))
                            ->inline()
                            ->hintIcon('heroicon-m-question-mark-circle', __('v1.workflow.support_tickets_help'))
                            ->extraFieldWrapperAttributes(['class' => 'dth-config-toggle-wrap'])
                            ->columnSpan(['default' => 12, 'md' => 3]),
                    ])
                    ->columns(12),

                Section::make(__('section.vietqr'))
                    ->icon('heroicon-o-qr-code')
                    ->iconColor('primary')
                    ->compact()
                    ->collapsible()
                    ->collapsed()
                    ->extraAttributes(['class' => 'dth-config-form'])
                    ->schema([
                        TextInput::make('vietqr_client_id')
                            ->label(__('field.vietqr_client_id'))
                            ->hintIcon('heroicon-m-question-mark-circle', __('field.vietqr_helper'))
                            ->maxLength(255),
                        TextInput::make('vietqr_api_key')
                            ->label(__('field.vietqr_api_key'))
                            ->password()
                            ->revealable()
                            ->maxLength(255)
                            ->hintIcon('heroicon-m-question-mark-circle', __('helper.vietqr_api_key_blank')),
                    ])
                    ->columns(['default' => 1, 'md' => 2]),
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
