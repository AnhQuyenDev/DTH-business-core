<?php

namespace Dth\Crm\Filament\Support;

use Closure;
use Dth\Crm\Services\CrmDataExchangeService;
use Dth\Crm\Support\CrmAuthorization;
use Dth\Crm\Support\UiText;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

final class CrmDataActions
{
    /**
     * @param Closure(): Builder $query
     * @return array<int, Action>
     */
    public static function make(string $entity, Closure $query, string $filename, string $sheetName): array
    {
        return [
            Action::make('import_'.$entity)
                ->label(UiText::get('data.import', 'Import CSV / Excel'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-crm-data-action dth-crm-data-action--import'])
                ->modalHeading(UiText::get('data.import_title', 'Import CSV / Excel'))
                ->modalDescription(UiText::get(
                    'data.import_description',
                    'Upload a CSV or XLSX file. Use the same column names as the exported file for the safest round trip.',
                ))
                ->modalIcon('heroicon-o-arrow-up-tray')
                ->modalSubmitAction(fn (Action $action): Action => $action
                    ->label(UiText::get('data.import_action', 'Import data'))
                    ->icon('heroicon-o-arrow-up-tray'))
                ->modalCancelAction(fn (Action $action): Action => $action
                    ->label(UiText::get('common.actions.cancel', 'Cancel'))
                    ->icon('heroicon-o-x-mark')
                    ->color('gray'))
                ->schema([
                    FileUpload::make('file')
                        ->label(UiText::get('data.file', 'CSV / Excel file'))
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
                        ->hintIcon(
                            'heroicon-o-information-circle',
                            tooltip: UiText::get(
                                'data.expected_columns',
                                'Expected columns: :columns',
                                ['columns' => implode(', ', app(CrmDataExchangeService::class)->headers($entity))],
                            ),
                        ),
                ])
                ->action(function (array $data) use ($entity): void {
                    try {
                        $file = $data['file'] ?? null;
                        if (! $file instanceof TemporaryUploadedFile) {
                            throw new \RuntimeException(UiText::get('data.invalid_file', 'The uploaded import file is invalid.'));
                        }

                        $result = app(CrmDataExchangeService::class)->import(
                            $entity,
                            $file->getRealPath(),
                            $file->getClientOriginalExtension(),
                        );

                        Notification::make()
                            ->title(UiText::get('data.import_success', 'Import completed'))
                            ->body(UiText::get(
                                'data.import_summary',
                                'Imported: :imported · Errors: :errors',
                                [
                                    'imported' => $result['imported'],
                                    'errors' => count($result['errors']),
                                ],
                            ))
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);
                        Notification::make()
                            ->title(UiText::get('data.import_failed', 'Import failed'))
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn (): bool => app(CrmAuthorization::class)->allows('crm.manage')),

            Action::make('export_'.$entity.'_xlsx')
                ->label(UiText::get('data.export_excel', 'Excel'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-crm-data-action dth-crm-data-action--excel'])
                ->action(fn () => app(CrmDataExchangeService::class)->xlsxDownload($entity, $query(), $filename, $sheetName))
                ->visible(fn (): bool => app(CrmAuthorization::class)->allows('crm.export')),

            Action::make('export_'.$entity.'_csv')
                ->label(UiText::get('data.export_csv', 'CSV'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-crm-data-action dth-crm-data-action--csv'])
                ->action(fn () => app(CrmDataExchangeService::class)->csvDownload($entity, $query(), $filename))
                ->visible(fn (): bool => app(CrmAuthorization::class)->allows('crm.export')),
        ];
    }
}
