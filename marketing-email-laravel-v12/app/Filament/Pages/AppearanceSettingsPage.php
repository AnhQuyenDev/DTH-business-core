<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AppearanceSettingsPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'configuration/appearance';

    protected static string $view = 'filament.pages.appearance-settings';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function getNavigationGroup(): string
    {
        return __('navigation.group.configuration');
    }

    public static function getNavigationLabel(): string
    {
        return __('configuration.navigation.appearance');
    }

    public function getTitle(): string
    {
        return __('configuration.appearance_hub.title');
    }

    public function getSubheading(): ?string
    {
        return __('configuration.appearance_hub.subheading');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('system.manage-company-settings') ?? false;
    }

    public function mount(): void
    {
        $this->redirect(SystemLabelManagementPage::getUrl());
    }
}
