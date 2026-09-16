<?php

namespace Dth\Crm\Services;

use Dth\Crm\Models\Company;
use Dth\Crm\Models\Contact;
use Dth\Crm\Models\CrmAgentProfile;
use Dth\Crm\Models\Customer;
use Dth\Crm\Models\Lead;
use Dth\Crm\Support\CodeGenerator;
use Dth\Crm\Support\Normalizer;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Options as XlsxOptions;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class CrmDataExchangeService
{
    public function __construct(
        private readonly ContactService $contacts,
        private readonly CodeGenerator $codes,
        private readonly Normalizer $normalizer,
    ) {}

    /** @return array<int, string> */
    public function headers(string $entity): array
    {
        return match ($entity) {
            'contacts' => ['contact_code', 'type', 'display_name', 'email', 'phone', 'source'],
            'companies' => ['company_code', 'legal_name', 'tax_code', 'email_domain', 'phone', 'industry', 'address', 'lifecycle_stage'],
            'customers' => ['customer_code', 'customer_type', 'display_name', 'email', 'phone', 'status', 'priority', 'acquisition_source', 'total_revenue'],
            'leads' => ['lead_code', 'contact_code', 'company_code', 'title', 'source', 'service_interest', 'service_reference', 'estimated_value', 'intake_status', 'assigned_employee_code'],
            default => throw new RuntimeException("Unsupported CRM exchange entity: {$entity}"),
        };
    }

    /** @return iterable<int, array<int, mixed>> */
    public function rows(string $entity, Builder $query): iterable
    {
        $query = clone $query;

        if ($entity === 'leads') {
            $query->with(['contact:id,contact_code', 'company:id,company_code', 'assignedAgentProfile.employee:id,employee_code']);
        }

        foreach ($query->orderBy('id')->cursor() as $record) {
            yield match ($entity) {
                'contacts' => [
                    $record->contact_code,
                    $record->type,
                    $record->display_name,
                    $record->email,
                    $record->phone,
                    $record->source,
                ],
                'companies' => [
                    $record->company_code,
                    $record->legal_name,
                    $record->tax_code,
                    $record->email_domain,
                    $record->phone,
                    $record->industry,
                    $record->address,
                    $record->lifecycle_stage,
                ],
                'customers' => [
                    $record->customer_code,
                    $record->customer_type,
                    $record->display_name,
                    $record->email,
                    $record->phone,
                    $record->status,
                    $record->priority,
                    $record->acquisition_source,
                    $record->total_revenue,
                ],
                'leads' => [
                    $record->lead_code,
                    $record->contact?->contact_code,
                    $record->company?->company_code,
                    $record->title,
                    $record->source,
                    $record->service_interest,
                    $record->service_reference,
                    $record->estimated_value,
                    $record->intake_status,
                    $record->assignedAgentProfile?->employee?->employee_code,
                ],
                default => throw new RuntimeException("Unsupported CRM exchange entity: {$entity}"),
            };
        }
    }

    public function csvDownload(string $entity, Builder $query, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($entity, $query): void {
            $stream = fopen('php://output', 'wb');
            if ($stream === false) {
                throw new RuntimeException('Unable to open CRM CSV output stream.');
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $this->sanitizeRow($this->headers($entity)));

            foreach ($this->rows($entity, $query) as $row) {
                fputcsv($stream, $this->sanitizeRow($row));
            }

            fclose($stream);
        }, $this->safeFilename($filename, 'csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function xlsxDownload(string $entity, Builder $query, string $filename, string $sheetName): BinaryFileResponse
    {
        $directory = storage_path('app/private/dth-crm-exports');
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create CRM XLSX export directory: {$directory}");
        }

        $path = $directory.'/'.bin2hex(random_bytes(16)).'.xlsx';
        $options = new XlsxOptions();
        $writer = new XlsxWriter($options);
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName(mb_substr($sheetName, 0, 31));
        $writer->addRow(Row::fromValues($this->sanitizeRow($this->headers($entity)), (new Style())->setFontBold()));

        foreach ($this->rows($entity, $query) as $row) {
            $writer->addRow(Row::fromValues($this->sanitizeRow($row)));
        }

        $writer->close();

        return response()->download(
            $path,
            $this->safeFilename($filename, 'xlsx'),
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'private, no-store, max-age=0',
            ],
        )->deleteFileAfterSend(true);
    }

    /** @return array{imported:int, errors:array<int, array{row:int, message:string}>} */
    public function import(string $entity, string $path, ?string $extension = null): array
    {
        $rows = $this->readRows($path, $extension);
        if ($rows === []) {
            throw new RuntimeException('The import file does not contain any data rows.');
        }

        $imported = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                $this->importRow($entity, $row);
                $imported++;
            } catch (\Throwable $exception) {
                $errors[] = [
                    'row' => $index + 2,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return ['imported' => $imported, 'errors' => $errors];
    }

    /** @return array<int, array<string, mixed>> */
    private function readRows(string $path, ?string $extension = null): array
    {
        $extension = strtolower($extension ?: pathinfo($path, PATHINFO_EXTENSION));

        return $extension === 'xlsx'
            ? $this->readXlsx($path)
            : $this->readCsv($path);
    }

    /** @return array<int, array<string, mixed>> */
    private function readCsv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to read the uploaded CSV file.');
        }

        $headers = fgetcsv($handle);
        if (! is_array($headers)) {
            fclose($handle);
            throw new RuntimeException('The CSV file is missing a header row.');
        }

        $headers = $this->normalizeHeaders($headers);
        $rows = [];

        while (($values = fgetcsv($handle)) !== false) {
            if ($this->rowIsEmpty($values)) {
                continue;
            }
            $rows[] = $this->combineRow($headers, $values);
        }

        fclose($handle);

        return $rows;
    }

    /** @return array<int, array<string, mixed>> */
    private function readXlsx(string $path): array
    {
        $reader = new XlsxReader();
        $reader->open($path);
        $headers = null;
        $rows = [];

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $values = $row->toArray();
                    if ($headers === null) {
                        $headers = $this->normalizeHeaders($values);
                        continue;
                    }
                    if ($this->rowIsEmpty($values)) {
                        continue;
                    }
                    $rows[] = $this->combineRow($headers, $values);
                }
                break;
            }
        } finally {
            $reader->close();
        }

        if ($headers === null) {
            throw new RuntimeException('The XLSX file is missing a header row.');
        }

        return $rows;
    }

    /** @param array<int, mixed> $headers @return array<int, string> */
    private function normalizeHeaders(array $headers): array
    {
        return array_map(function (mixed $header): string {
            $value = trim((string) $header);
            $value = preg_replace('/^\xEF\xBB\xBF/', '', $value) ?? $value;
            $value = strtolower($value);
            $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? $value;
            return trim($value, '_');
        }, $headers);
    }

    /** @param array<int, string> $headers @param array<int, mixed> $values @return array<string, mixed> */
    private function combineRow(array $headers, array $values): array
    {
        $values = array_pad($values, count($headers), null);
        $values = array_slice($values, 0, count($headers));
        return array_combine($headers, $values) ?: [];
    }

    /** @param array<int, mixed> $values */
    private function rowIsEmpty(array $values): bool
    {
        foreach ($values as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }
        return true;
    }

    /** @param array<string, mixed> $row */
    private function importRow(string $entity, array $row): void
    {
        match ($entity) {
            'contacts' => $this->importContact($row),
            'companies' => $this->importCompany($row),
            'customers' => $this->importCustomer($row),
            'leads' => $this->importLead($row),
            default => throw new RuntimeException("Unsupported CRM exchange entity: {$entity}"),
        };
    }

    /** @param array<string, mixed> $row */
    private function importContact(array $row): void
    {
        if (blank($row['display_name'] ?? null) && blank($row['email'] ?? null) && blank($row['phone'] ?? null)) {
            throw new RuntimeException('display_name, email or phone is required.');
        }

        $contactCode = filled($row['contact_code'] ?? null)
            ? trim((string) $row['contact_code'])
            : null;

        if ($contactCode) {
            $existing = Contact::query()->where('contact_code', $contactCode)->first();
            if ($existing) {
                $existing->update(array_filter([
                    'type' => $row['type'] ?? null,
                    'display_name' => $row['display_name'] ?? null,
                    'email' => $row['email'] ?? null,
                    'phone' => $row['phone'] ?? null,
                    'source' => $row['source'] ?? null,
                ], static fn (mixed $value): bool => $value !== null && $value !== ''));
                return;
            }
        }

        $contact = $this->contacts->upsert([
            'type' => $row['type'] ?: 'personal',
            'display_name' => $row['display_name'] ?? null,
            'email' => $row['email'] ?? null,
            'phone' => $row['phone'] ?? null,
            'source' => $row['source'] ?? 'import',
        ]);

        if ($contactCode && $contact->contact_code !== $contactCode) {
            $contact->update(['contact_code' => $contactCode]);
        }
    }

    /** @param array<string, mixed> $row */
    private function importCompany(array $row): void
    {
        if (blank($row['legal_name'] ?? null)) {
            throw new RuntimeException('legal_name is required.');
        }

        $company = null;
        if (filled($row['company_code'] ?? null)) {
            $company = Company::query()->where('company_code', trim((string) $row['company_code']))->first();
        }
        if (! $company && filled($row['tax_code'] ?? null)) {
            $company = Company::query()->where('tax_code', trim((string) $row['tax_code']))->first();
        }
        if (! $company && filled($row['email_domain'] ?? null)) {
            $company = Company::query()->where('email_domain', trim((string) $row['email_domain']))->first();
        }

        $data = array_filter([
            'company_code' => $row['company_code'] ?? null,
            'legal_name' => $row['legal_name'] ?? null,
            'tax_code' => $row['tax_code'] ?? null,
            'email_domain' => $row['email_domain'] ?? null,
            'phone' => $row['phone'] ?? null,
            'industry' => $row['industry'] ?? null,
            'address' => $row['address'] ?? null,
            'lifecycle_stage' => $row['lifecycle_stage'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        if ($company) {
            $company->update($data);
        } else {
            Company::query()->create($data);
        }
    }

    /** @param array<string, mixed> $row */
    private function importCustomer(array $row): void
    {
        $email = $this->normalizer->email($row['email'] ?? null);
        $phone = $this->normalizer->phone($row['phone'] ?? null);

        $customer = null;
        if (filled($row['customer_code'] ?? null)) {
            $customer = Customer::query()
                ->where('customer_code', trim((string) $row['customer_code']))
                ->first();
        }
        if (! $customer && $email) {
            $customer = Customer::query()->where('normalized_email', $email)->first();
        }
        if (! $customer && $phone) {
            $customer = Customer::query()->where('normalized_phone', $phone)->first();
        }

        $data = [
            'customer_type' => $row['customer_type'] ?? 'personal',
            'display_name' => $row['display_name'] ?? $email ?? $phone ?? 'Customer',
            'email' => $row['email'] ?? null,
            'normalized_email' => $email,
            'phone' => $row['phone'] ?? null,
            'normalized_phone' => $phone,
            'status' => $row['status'] ?? 'potential',
            'priority' => $row['priority'] ?? 'normal',
            'acquisition_source' => $row['acquisition_source'] ?? 'import',
        ];

        if (isset($row['total_revenue']) && $row['total_revenue'] !== '') {
            $data['total_revenue'] = (float) $row['total_revenue'];
        }

        if ($customer) {
            $customer->update(array_filter($data, static fn (mixed $value): bool => $value !== null));
            return;
        }

        Customer::query()->create($data + [
            'customer_code' => filled($row['customer_code'] ?? null)
                ? trim((string) $row['customer_code'])
                : $this->codes->make('CUS'),
        ]);
    }

    /** @param array<string, mixed> $row */
    private function importLead(array $row): void
    {
        $lead = null;
        if (filled($row['lead_code'] ?? null)) {
            $lead = Lead::query()->where('lead_code', trim((string) $row['lead_code']))->first();
        }

        $contactId = null;
        if (filled($row['contact_code'] ?? null)) {
            $contactId = Contact::query()->where('contact_code', trim((string) $row['contact_code']))->value('id');
        }

        $companyId = null;
        if (filled($row['company_code'] ?? null)) {
            $companyId = Company::query()->where('company_code', trim((string) $row['company_code']))->value('id');
        }

        $agentProfileId = null;
        if (filled($row['assigned_employee_code'] ?? null)) {
            $agentProfileId = CrmAgentProfile::query()
                ->whereHas('employee', fn (Builder $query): Builder => $query->where('employee_code', trim((string) $row['assigned_employee_code'])))
                ->value('id');
        }

        $data = array_filter([
            'lead_code' => $row['lead_code'] ?? null,
            'contact_id' => $contactId,
            'company_id' => $companyId,
            'title' => $row['title'] ?? null,
            'source' => $row['source'] ?? 'import',
            'service_interest' => $row['service_interest'] ?? null,
            'service_reference' => $row['service_reference'] ?? null,
            'estimated_value' => $row['estimated_value'] ?? null,
            'intake_status' => $row['intake_status'] ?? 'new',
            'assigned_agent_profile_id' => $agentProfileId,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        if ($lead) {
            $lead->update($data);
        } else {
            Lead::query()->create($data);
        }
    }

    /** @param array<int, mixed> $row @return array<int, mixed> */
    private function sanitizeRow(array $row): array
    {
        return array_map(static function (mixed $value): mixed {
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d H:i:s');
            }
            if (! is_string($value)) {
                return $value;
            }
            $value = trim($value);
            if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
                return "'".$value;
            }
            return $value;
        }, $row);
    }

    private function safeFilename(string $filename, string $extension): string
    {
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $basename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $basename) ?: 'crm-export';
        $basename = trim($basename, '-.');
        return ($basename !== '' ? $basename : 'crm-export').'.'.$extension;
    }
}
