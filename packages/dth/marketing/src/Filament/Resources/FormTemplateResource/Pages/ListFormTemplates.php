<?php

namespace Dth\Marketing\Filament\Resources\FormTemplateResource\Pages;

use Dth\Marketing\Enums\FormAudienceType;
use Dth\Marketing\Filament\Resources\FormTemplateResource;
use Dth\Marketing\Filament\Support\MarketingPageUi;
use Dth\Marketing\Services\FormTemplateHtmlImportService;
use Dth\Marketing\Support\UiText;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

class ListFormTemplates extends ListRecords
{
    protected static string $resource = FormTemplateResource::class;

    public function getTitle(): string|Htmlable
    {
        return MarketingPageUi::title(UiText::get('pages.form_templates.title', 'Form Templates'), 'form', 'amber');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('pages.form_templates.subheading', 'Design reusable lead-capture forms for landing pages and keep audience rules consistent.');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(UiText::get('pages.form_templates.create', 'Create form template'))
                ->icon('heroicon-o-plus')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-mkt-entry-action dth-mkt-entry-action--amber']),
            Action::make('import_html')
                ->label(UiText::get('form.import_html', 'Import HTML'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-mkt-entry-action dth-mkt-entry-action--amber'])
                ->schema([
                    TextInput::make('name')->label(UiText::get('common.fields.name', 'Name'))->required()->maxLength(255),
                    Select::make('audience_type')->label(UiText::get('form.audience_type', 'Audience type'))->options(FormAudienceType::options())->default(FormAudienceType::Personal->value)->required()->native(false),
                    FileUpload::make('html_file')->label(UiText::get('form.html_file', 'HTML file'))->acceptedFileTypes(['text/html', 'application/xhtml+xml', 'text/plain'])->maxSize(2048)->storeFiles(false)->previewable(false)->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        $file = $data['html_file'] ?? null;
                        if (! $file instanceof TemporaryUploadedFile) {
                            throw new \RuntimeException('The HTML form file is missing or cannot be read.');
                        }

                        $template = app(FormTemplateHtmlImportService::class)->fromPath($data, $file->getRealPath(), auth()->id());
                        Notification::make()
                            ->title(UiText::get('form.import_success', 'Form Template imported'))
                            ->body(UiText::get('form.import_success_body', ':count field(s) were detected and mapped. The imported template remains Draft for review.', ['count' => $template->fields->count()]))
                            ->success()->send();
                    } catch (Throwable $exception) {
                        report($exception);
                        Notification::make()->title(UiText::get('form.import_failed', 'Form Template import failed'))->body($exception->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
