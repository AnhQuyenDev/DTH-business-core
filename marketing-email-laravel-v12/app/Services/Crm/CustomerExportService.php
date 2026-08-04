<?php

namespace App\Services\Crm;

use App\Models\Crm\Customer;
use Illuminate\Support\Collection;

class CustomerExportService
{
    private const HEADERS = [
        'customer_code',
        'display_name',
        'email',
        'phone',
        'customer_type',
        'status',
        'consent_status',
        'priority',
        'acquisition_source',
        'lifecycle_stage',
        'tags',
        'lists',
        'created_at',
    ];

    public function toCsv(Collection $customers): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, self::HEADERS);

        $customers->loadMissing(['tags', 'lists']);

        foreach ($customers as $customer) {
            fputcsv($handle, $this->customerToRow($customer));
        }

        rewind($handle);
        return stream_get_contents($handle) ?: '';
    }

    public function toExcel(Collection $customers): string
    {
        $tmpPath = tempnam(sys_get_temp_dir(), 'customers_export_') . '.xlsx';

        $writer = new \OpenSpout\Writer\XLSX\Writer();
        $writer->openToFile($tmpPath);
        $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues(self::HEADERS));

        $customers->loadMissing(['tags', 'lists']);

        foreach ($customers as $customer) {
            $writer->addRow(\OpenSpout\Common\Entity\Row::fromValues($this->customerToRow($customer)));
        }

        $writer->close();
        return $tmpPath;
    }

    private function customerToRow(Customer $customer): array
    {
        return [
            $customer->customer_code,
            $customer->display_name,
            $customer->email,
            $customer->phone,
            $customer->customer_type,
            $customer->status instanceof \BackedEnum ? $customer->status->value : $customer->status,
            $customer->consent_status instanceof \BackedEnum ? $customer->consent_status->value : $customer->consent_status,
            $customer->priority,
            $customer->acquisition_source,
            $customer->lifecycle_stage,
            $customer->tags->pluck('name')->implode('; '),
            $customer->lists->pluck('name')->implode('; '),
            optional($customer->created_at)->toDateTimeString(),
        ];
    }
}
