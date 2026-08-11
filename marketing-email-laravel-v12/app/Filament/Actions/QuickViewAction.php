<?php

namespace App\Filament\Actions;

use App\Support\Ui\QuickViewPresenter;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;

final class QuickViewAction
{
    public static function make(string $name = 'quick_view'): Action
    {
        return Action::make($name)
            ->label(__('action.view'))
            ->icon('heroicon-o-eye')
            ->modalHeading(fn (Model $record): string => __('action.view').' · '.self::title($record))
            ->modalContent(fn (Model $record) => view('filament.quick-view', ['rows' => QuickViewPresenter::rows($record)]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('action.close'));
    }

    private static function title(Model $record): string
    {
        foreach (['name','title','full_name','quotation_code','opportunity_code','lead_code','code','email'] as $key) {
            if (filled($record->getAttribute($key))) return (string) $record->getAttribute($key);
        }
        return class_basename($record);
    }
}
