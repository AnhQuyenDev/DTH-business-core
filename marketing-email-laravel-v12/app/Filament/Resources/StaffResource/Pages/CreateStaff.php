<?php

namespace App\Filament\Resources\StaffResource\Pages;

use App\Filament\Resources\StaffResource;
use App\Models\Crm\Staff;
use Filament\Resources\Pages\CreateRecord;

class CreateStaff extends CreateRecord
{
    protected static string $resource = StaffResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $nextId = (Staff::withTrashed()->max('id') ?? 0) + 1;
        $data['employee_code'] = 'EMP-'.str_pad($nextId, 4, '0', STR_PAD_LEFT);

        return $data;
    }
}
