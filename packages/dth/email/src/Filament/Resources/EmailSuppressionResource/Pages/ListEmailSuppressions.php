<?php

namespace Dth\Email\Filament\Resources\EmailSuppressionResource\Pages;

use Dth\Email\Filament\Resources\EmailSuppressionResource;
use Dth\Email\Filament\Support\EmailPageUi;
use Dth\Email\Support\UiText;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListEmailSuppressions extends ListRecords
{
    protected static string $resource = EmailSuppressionResource::class;

    public function getTitle(): string|Htmlable
    {
        return EmailPageUi::title(UiText::get('suppression.list.title', 'Danh sách chặn Email'), 'shield', 'red');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return UiText::get('suppression.list.subheading', 'Theo dõi email bị chặn, lý do phát sinh và xử lý gỡ chặn khi cần thiết.');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(UiText::get('common.actions.add', 'Thêm email'))
                ->icon('heroicon-o-plus'),
        ];
    }
}
