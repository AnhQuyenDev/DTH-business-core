<?php

namespace Dth\AccountManagement\Filament\Support;

use Dth\AccountManagement\Services\AccountGroupDataExchangeService;
use Dth\AccountManagement\Support\AccountAuthorization;
use Dth\AccountManagement\Support\UiText;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use RuntimeException;
use Throwable;

final class AccountGroupDataActions
{
    public static function import(): Action
    {
        return Action::make('import_groups')
            ->label(UiText::get('data.group_import', 'Nhập dữ liệu CSV/Excel'))
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->extraAttributes(['class' => 'dth-acc-entry-action'])
            ->modalHeading(UiText::get('data.group_import_title', 'Nhập dữ liệu nhóm & đơn vị'))
            ->modalDescription(UiText::get(
                'data.group_import_description',
                'Tải lên tệp CSV hoặc XLSX (Excel). Cột bắt buộc: name, code. Nhóm cha dùng parent_code; thành viên dùng email trong cột users.',
            ))
            ->modalIcon('heroicon-o-arrow-up-tray')
            ->modalSubmitAction(fn (Action $action): Action => $action
                ->label(UiText::get('data.import_action', 'Nhập dữ liệu'))
                ->icon('heroicon-o-arrow-up-tray')
                ->extraAttributes(['class' => 'dth-acc-form-action dth-acc-form-action--primary']))
            ->modalCancelAction(fn (Action $action): Action => $action
                ->label(UiText::get('common.actions.cancel', 'Hủy'))
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->extraAttributes(['class' => 'dth-acc-form-action dth-acc-form-action--secondary']))
            ->schema([
                FileUpload::make('file')
                    ->label(UiText::get('data.file', 'Tệp CSV / Excel'))
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
                        UiText::get('data.expected_columns', 'Các cột: :columns'),
                        [':columns' => implode(', ', app(AccountGroupDataExchangeService::class)->headers())],
                    )),
            ])
            ->action(function (array $data): void {
                try {
                    $file = $data['file'] ?? null;
                    if (! $file instanceof TemporaryUploadedFile) {
                        throw new RuntimeException(UiText::get('data.invalid_file', 'Tệp tải lên không hợp lệ.'));
                    }

                    $result = app(AccountGroupDataExchangeService::class)->import(
                        $file->getRealPath(),
                        $file->getClientOriginalExtension(),
                    );

                    Notification::make()
                        ->title(UiText::get('data.group_import_success', 'Nhập nhóm & đơn vị hoàn tất'))
                        ->body(strtr(
                            UiText::get('data.import_summary', 'Tạo mới: :imported · Cập nhật: :updated · Lỗi: :errors'),
                            [
                                ':imported' => $result['imported'],
                                ':updated' => $result['updated'],
                                ':errors' => count($result['errors']),
                            ],
                        ))
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->title(UiText::get('data.group_import_failed', 'Nhập nhóm & đơn vị thất bại'))
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            })
            ->visible(fn (): bool => app(AccountAuthorization::class)->allows('accounts.groups.manage'));
    }
}
