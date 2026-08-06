<?php

namespace App\Filament\Resources\Sales\QuotationResource\Pages;

use App\Filament\Resources\Sales\QuotationResource;
use App\Models\Crm\Customer;
use App\Models\Sales\Opportunity;
use App\Models\Sales\PriceBook;
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
        $priceBook = PriceBook::findOrFail(
            $data['price_book_id']
        );

        $items = $data['items'] ?? [];
        $params = [
            'title' => $data['title'] ?? '',
            'bank_account_id' => $data['bank_account_id'] ?? null,
            'quotation_date' => $data['quotation_date']
                ?? now()->toDateString(),
            'valid_until' => $data['valid_until']
                ?? now()->addDays(30)->toDateString(),
            'terms_scope' => $data['terms_scope'] ?? null,
            'terms' => $data['terms'] ?? null,
            'notes' => $data['notes'] ?? null,
        ];

        if (
            config('business_flow.opportunity_quotation_enabled')
            && filled($data['opportunity_id'] ?? null)
        ) {
            $opportunity = Opportunity::query()
                ->with([
                    'company',
                    'primaryContact.personalProfile',
                    'primaryContact.businessProfile',
                ])
                ->findOrFail($data['opportunity_id']);

            return $service->createForOpportunity(
                $opportunity,
                $user,
                $priceBook,
                $items,
                $params,
            );
        }

        /* Legacy flow during transition. */
        $customer = Customer::findOrFail(
            $data['customer_id']
        );

        return $service->create(
            $customer,
            $user,
            $priceBook,
            $items,
            $params,
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', [
            'record' => $this->record,
        ]);
    }
}
