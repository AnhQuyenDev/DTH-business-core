<?php

namespace Dth\HumanResource\Filament\Support;

use Closure;
use Dth\HumanResource\Services\HumanResourceDataExchangeService;
use Dth\HumanResource\Support\HumanResourceAuthorization;
use Dth\HumanResource\Support\UiText;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

final class HumanResourceDataActions
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
                ->extraAttributes(['class' => 'dth-hr-data-action dth-hr-data-action--import'])
                ->modalHeading(UiText::get('data.import_title', 'Import CSV / Excel'))
                ->modalDescription(UiText::get(
                    'data.import_description',
                    'Upload a CSV or XLSX file. For the safest round trip, use the same column names as the exported file.',
                ))
                ->modalIcon('heroicon-o-arrow-up-tray')
                ->modalSubmitAction(fn (Action $action): Action => $action
                    ->label(UiText::get('data.import_action', 'Import data'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->extraAttributes(['class' => 'dth-hr-modal-action dth-hr-modal-action--primary']))
                ->modalCancelAction(fn (Action $action): Action => $action
                    ->label(UiText::get('common.actions.cancel', 'Cancel'))
                    ->icon('heroicon-o-x-mark')
                    ->color('gray')
                    ->extraAttributes(['class' => 'dth-hr-modal-action dth-hr-modal-action--secondary']))
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
                                ['columns' => implode(', ', app(HumanResourceDataExchangeService::class)->headers($entity))],
                            ),
                        ),
                ])
                ->action(function (array $data) use ($entity): void {
                    try {
                        $file = $data['file'] ?? null;
                        if (! $file instanceof TemporaryUploadedFile) {
                            throw new \RuntimeException(UiText::get('data.invalid_file', 'The uploaded import file is invalid.'));
                        }

                        $result = app(HumanResourceDataExchangeService::class)->import(
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
                ->visible(fn (): bool => app(HumanResourceAuthorization::class)->allows('hr.manage')),

            Action::make('export_'.$entity.'_xlsx')
                ->label(UiText::get('data.export_excel', 'Excel'))
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-hr-data-action dth-hr-data-action--excel'])
                ->action(fn () => app(HumanResourceDataExchangeService::class)->xlsxDownload($entity, $query(), $filename, $sheetName)),

            Action::make('export_'.$entity.'_csv')
                ->label(UiText::get('data.export_csv', 'CSV'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-hr-data-action dth-hr-data-action--csv'])
                ->action(fn () => app(HumanResourceDataExchangeService::class)->csvDownload($entity, $query(), $filename)),
        ];
    }
}
