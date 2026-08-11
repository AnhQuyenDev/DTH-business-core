<?php
namespace App\Filament\Resources\SupportTicketResource\Pages;
use App\Filament\Resources\SupportTicketResource;
use App\Models\Crm\Customer;
use App\Services\Support\SupportTicketService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
class CreateSupportTicket extends CreateRecord
{
    protected static string $resource = SupportTicketResource::class;
    protected function handleRecordCreation(array $data): Model
    {
        return app(SupportTicketService::class)->create(
            Customer::query()->findOrFail($data['customer_id']),
            auth()->user(),
            $data,
        );
    }
}
