<?php

namespace App\Filament\Resources\Sales\QuotationResource\Pages;

use App\Filament\Resources\Sales\QuotationResource;
use App\Models\Sales\QuotationItem;
use App\Services\Sales\QuotationCreationService;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->record->items
            ->map(fn (QuotationItem $item): array => [
                'price_book_item_id' => $item->price_book_item_id,
                'service_name_snapshot' => $item->service_name_snapshot,
                'package_name_snapshot' => $item->package_name_snapshot,
                'unit' => $item->unit,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_type' => $item->discount_type?->value,
                'discount_value' => (float) $item->discount_value,
                'vat_rate' => (float) $item->vat_rate,
                'description_snapshot' => $item->description_snapshot,
            ])
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $service = app(QuotationCreationService::class);

        $params = [
            'title' => $data['title'] ?? $record->title,
            'bank_account_id' => $data['bank_account_id'] ?? null,
            'valid_until' => $data['valid_until'] ?? $record->valid_until,
        ];

        return $service->update($record, $data['items'] ?? [], $params);
    }
}
