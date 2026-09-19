<?php

namespace Dth\Commercial\Filament\Support;

use Dth\Commercial\Services\CommercialDataExchangeService;
use Dth\Commercial\Support\CommercialAuthorization;
use Dth\Commercial\Support\UiText;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Throwable;

final class CommercialDataActions
{
    public static function import(string $entity, string $ability): Action
    {
        return Action::make('import_'.$entity)
            ->label(UiText::get('data.import', 'Import CSV/Excel'))
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->extraAttributes(['class' => 'dth-com-entry-action dth-com-entry-action--teal'])
            ->modalIcon('heroicon-o-arrow-up-tray')
            ->modalHeading(self::heading($entity))
            ->modalDescription(self::description($entity))
            ->modalSubmitAction(fn (Action $action): Action => $action
                ->label(UiText::get('data.import_action', 'Import data'))
                ->icon('heroicon-o-arrow-up-tray')
                ->extraAttributes(['class' => 'dth-com-form-action dth-com-form-action--primary']))
            ->modalCancelAction(fn (Action $action): Action => $action
                ->label(UiText::get('common.actions.cancel', 'Cancel'))
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-com-form-action dth-com-form-action--secondary']))
            ->schema([
                FileUpload::make('file')
                    ->label(UiText::get('data.file', 'CSV / Excel file'))
                    ->helperText(UiText::get('data.file_help', 'Use the first row as column names. Existing records with the same business code are updated instead of duplicated.'))
                    ->required()
                    ->storeFiles(false)
                    ->previewable(false)
                    ->acceptedFileTypes([
                        'text/csv',
                        'text/plain',
                        'application/csv',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->maxSize(10240)
                    ->hintIcon('heroicon-o-information-circle', tooltip: strtr(
                        UiText::get('data.expected_columns', 'Columns: :columns'),
                        [':columns' => implode(', ', app(CommercialDataExchangeService::class)->headers($entity))],
                    )),
            ])
            ->action(function (array $data) use ($entity): void {
                try {
                    $file = $data['file'] ?? null;
                    if (! $file instanceof TemporaryUploadedFile) {
                        throw new RuntimeException(UiText::get('data.invalid_file', 'The uploaded file is invalid.'));
                    }

                    $result = app(CommercialDataExchangeService::class)->import(
                        $entity,
                        $file->getRealPath(),
                        $file->getClientOriginalExtension(),
                    );

                    $body = strtr(
                        UiText::get('data.import_summary', 'Created: :imported · Updated: :updated · Errors: :errors'),
                        [
                            ':imported' => $result['imported'],
                            ':updated' => $result['updated'],
                            ':errors' => count($result['errors']),
                        ],
                    );
                    if ($result['errors'] !== []) {
                        $first = collect($result['errors'])->take(3)->map(
                            fn (array $error): string => '#'.$error['row'].' '.$error['message']
                        )->implode(' · ');
                        $body .= ' · '.$first;
                    }

                    Notification::make()
                        ->title(UiText::get('data.import_success', 'Commercial data import completed'))
                        ->body($body)
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);
                    Notification::make()
                        ->title(UiText::get('data.import_failed', 'Commercial data import failed'))
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->visible(fn (): bool => app(CommercialAuthorization::class)->allows($ability));
    }

    private static function heading(string $entity): string
    {
        return match ($entity) {
            'services' => UiText::get('data.import_services_title', 'Import services'),
            'products' => UiText::get('data.import_products_title', 'Import products & prices'),
            'bundles' => UiText::get('data.import_bundles_title', 'Import bundles & bundle items'),
            'opportunities' => UiText::get('data.import_opportunities_title', 'Import business opportunities & line items'),
            default => UiText::get('data.import_title', 'Import Commercial data'),
        };
    }

    private static function description(string $entity): string
    {
        return match ($entity) {
            'services' => UiText::get('data.import_services_description', 'Upload CSV or XLSX. Required columns: service_code, name.'),
            'products' => UiText::get('data.import_products_description', 'One row may create/update a product and one price. Repeat product_code on multiple rows to add multiple price-list entries.'),
            'bundles' => UiText::get('data.import_bundles_description', 'One row may create/update a bundle and one product component. Repeat bundle_code to add products from the same or different services.'),
            'opportunities' => UiText::get('data.import_opportunities_description', 'One row may create/update an opportunity and one product/bundle line item. Repeat opportunity_code to import multiple line items.'),
            default => UiText::get('data.import_description', 'Upload a CSV or XLSX file using the documented columns.'),
        };
    }
}
