<?php

namespace App\Filament\Resources\Sales\QuotationResource\Pages;

use App\Filament\Resources\Sales\QuotationResource;
use App\Models\Crm\Customer;
use App\Models\Sales\PriceBook;
use App\Models\User;
use App\Services\Sales\QuotationCreationService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $service = app(QuotationCreationService::class);
        $user = auth()->user();

        $customer = Customer::findOrFail($data['customer_id']);
        $priceBook = PriceBook::findOrFail($data['price_book_id']);

        $items = $data['items'] ?? [];

        $params = [
            'title' => $data['title'] ?? '',
            'bank_account_id' => $data['bank_account_id'] ?? null,
            'quotation_date' => $data['quotation_date'] ?? now()->toDateString(),
            'valid_until' => $data['valid_until'] ?? now()->addDays(30)->toDateString(),
        ];

        return $service->create($customer, $user, $priceBook, $items, $params);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
