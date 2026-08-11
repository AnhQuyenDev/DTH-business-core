<?php
namespace App\Filament\Resources\SupportTicketResource\Pages;
use App\Filament\Resources\SupportTicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;
    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
