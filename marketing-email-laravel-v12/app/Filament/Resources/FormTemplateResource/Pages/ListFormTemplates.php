<?php

namespace App\Filament\Resources\FormTemplateResource\Pages;

use App\Enums\Marketing\FormAudienceType;
use App\Filament\Resources\FormTemplateResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use App\Services\Marketing\FormTemplateImportService;
use Throwable;
class ListFormTemplates extends ListRecords
{
    protected static string $resource = FormTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label(__('action.new_form')),
            Action::make('import_html')
                ->label(__('action.import_html'))
                ->icon('heroicon-o-arrow-up-tray')
                ->color('gray')
                ->form([
                    TextInput::make('name')->label(__('field.name'))->required()->maxLength(255),
                    TextInput::make('slug')->label(__('field.slug'))->required()->maxLength(255),
                    Select::make('audience_type')
                        ->label(__('field.form_type'))
                        ->options([
                            FormAudienceType::Personal->value =>
                                FormAudienceType::Personal->label(),
                            FormAudienceType::Business->value =>
                                FormAudienceType::Business->label(),
                        ])
                        ->default(FormAudienceType::Personal->value)
                        ->required(),
                    FileUpload::make('html_file')
                        ->label(__('field.html_file'))
                        ->acceptedFileTypes(['text/html', 'text/plain'])
                        ->required()
                        ->disk('local'),
                ])
                ->action(function (array $data): void {
                    $filePath = storage_path(
                        'app/private/'.$data['html_file']
                    );

                    try {
                        if (! is_file($filePath)) {
                            throw new \RuntimeException(
                                'Không tìm thấy file HTML đã tải lên.'
                            );
                        }

                        $content = file_get_contents($filePath);

                        if ($content === false) {
                            throw new \RuntimeException(
                                'Không thể đọc nội dung Form HTML.'
                            );
                        }

                        $template = app(
                            FormTemplateImportService::class
                        )->import(
                            data: $data,
                            sourceHtml: $content,
                            userId: auth()->id(),
                        );

                        Notification::make()
                            ->title('Import Form Template thành công')
                            ->body(
                                "Đã tạo {$template->fields->count()} trường dữ liệu. "
                                .'Form đang ở trạng thái nháp để bạn xem trước và kiểm tra mapping.'
                            )
                            ->success()
                            ->send();
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title('Không thể import Form Template')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();
                    } finally {
                        if (is_file($filePath)) {
                            unlink($filePath);
                        }
                    }
                }),
        ];
    }
}
