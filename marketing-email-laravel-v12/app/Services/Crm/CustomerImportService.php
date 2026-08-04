<?php

namespace App\Services\Crm;

use App\Models\Crm\Customer;
use App\Models\Marketing\ContactList;
use App\Models\Marketing\ImportBatch;
use App\Models\Marketing\Tag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CustomerImportService
{
    public function import(
        string $storedPath,
        bool $updateExisting = false,
        bool $autoCreateRelations = true,
        ?int $createdBy = null
    ): array {
        $absolutePath = Storage::path($storedPath);
        $batch = ImportBatch::create([
            'filename' => basename($storedPath),
            'status' => 'processing',
            'created_by' => $createdBy,
        ]);

        $handle = fopen($absolutePath, 'r');

        if ($handle === false) {
            $batch->update(['status' => 'failed']);
            return [
                'batch' => $batch,
                'total_rows' => 0,
                'success_rows' => 0,
                'failed_rows' => 1,
                'errors' => [['row' => 0, 'email' => null, 'message' => 'Unable to open import file.']],
            ];
        }

        $headers = array_map(static fn ($h) => Str::of((string) $h)->trim()->lower()->toString(), fgetcsv($handle) ?: []);
        $rowNumber = 1;
        $totalRows = 0;
        $successRows = 0;
        $failedRows = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                $rowNumber++;
                $totalRows++;
                $record = array_combine($headers, $row) ?: [];
                $email = strtolower(trim((string) ($record['email'] ?? '')));

                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $failedRows++;
                    $errors[] = ['row' => $rowNumber, 'email' => $email, 'message' => 'Invalid email address.'];
                    continue;
                }

                $customer = Customer::where('email', $email)->first();
                $isNew = $customer === null;

                if ($customer === null) {
                    $customer = new Customer();
                    $customer->email = $email;
                    $customer->customer_code = CustomerCodeGenerator::generate();
                    $customer->customer_type = $record['customer_type'] ?? 'personal';
                    $customer->consent_status = 'subscribed';
                    $customer->status = 'potential';
                    $customer->lifecycle_stage = 'new_customer';
                } elseif (! $updateExisting) {
                    $failedRows++;
                    $errors[] = ['row' => $rowNumber, 'email' => $email, 'message' => 'Customer already exists.'];
                    continue;
                }

                $this->fillIfBlank($customer, 'display_name', $record['display_name'] ?? $record['full_name'] ?? null);
                $this->fillIfBlank($customer, 'phone', $record['phone'] ?? null);
                $this->fillIfBlank($customer, 'acquisition_source', $record['source'] ?? $record['acquisition_source'] ?? null);
                $this->fillIfBlank($customer, 'priority', $record['priority'] ?? null);

                if (blank($customer->display_name) && filled($email)) {
                    $customer->display_name = Str::before($email, '@');
                }

                $customer->save();

                $this->syncTags($customer, (string) ($record['tags'] ?? ''), $autoCreateRelations);
                $this->syncLists($customer, (string) ($record['lists'] ?? ''), $autoCreateRelations);

                $successRows++;
            }

            fclose($handle);
            $batch->update([
                'total_rows' => $totalRows,
                'success_rows' => $successRows,
                'failed_rows' => $failedRows,
                'status' => 'completed',
            ]);
            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();
            if (isset($handle)) fclose($handle);
            $batch->update([
                'total_rows' => $totalRows,
                'success_rows' => $successRows,
                'failed_rows' => $failedRows + 1,
                'status' => 'failed',
            ]);
            $errors[] = [
                'row' => $rowNumber,
                'email' => $email ?? null,
                'message' => $throwable->getMessage(),
            ];
        }

        return [
            'batch' => $batch->fresh(),
            'total_rows' => $totalRows,
            'success_rows' => $successRows,
            'failed_rows' => $failedRows,
            'errors' => $errors,
        ];
    }

    public function importExcel(
        string $storedPath,
        bool $updateExisting = false,
        bool $autoCreateRelations = true,
        ?int $createdBy = null
    ): array {
        $absolutePath = Storage::path($storedPath);
        $batch = ImportBatch::create([
            'filename' => basename($storedPath),
            'status' => 'processing',
            'created_by' => $createdBy,
        ]);

        $reader = new \OpenSpout\Reader\XLSX\Reader();
        $reader->open($absolutePath);

        $rowNumber = 0;
        $totalRows = 0;
        $successRows = 0;
        $failedRows = 0;
        $errors = [];
        $headers = [];
        $email = null;

        DB::beginTransaction();
        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $cells = array_map(
                        static fn (\OpenSpout\Common\Entity\Cell $c) => (string) ($c->getValue() ?? ''),
                        $row->getCells()
                    );

                    if ($rowNumber === 0) {
                        $headers = array_map(
                            static fn (string $h) => Str::of($h)->trim()->lower()->toString(),
                            $cells
                        );
                        $rowNumber++;
                        continue;
                    }

                    $rowNumber++;
                    $totalRows++;

                    if (count($cells) < count($headers)) {
                        $cells = array_pad($cells, count($headers), '');
                    }

                    $record = array_combine($headers, array_slice($cells, 0, count($headers))) ?: [];
                    $email = strtolower(trim($record['email'] ?? ''));

                    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $failedRows++;
                        $errors[] = ['row' => $rowNumber, 'email' => $email, 'message' => 'Invalid email address.'];
                        continue;
                    }

                    $customer = Customer::where('email', $email)->first();
                    $isNew = $customer === null;

                    if ($customer === null) {
                        $customer = new Customer();
                        $customer->email = $email;
                        $customer->customer_code = CustomerCodeGenerator::generate();
                        $customer->customer_type = $record['customer_type'] ?? 'personal';
                        $customer->consent_status = 'subscribed';
                        $customer->status = 'potential';
                        $customer->lifecycle_stage = 'new_customer';
                    } elseif (! $updateExisting) {
                        $failedRows++;
                        $errors[] = ['row' => $rowNumber, 'email' => $email, 'message' => 'Customer already exists.'];
                        continue;
                    }

                    $this->fillIfBlank($customer, 'display_name', $record['display_name'] ?? $record['full_name'] ?? null);
                    $this->fillIfBlank($customer, 'phone', $record['phone'] ?? null);
                    $this->fillIfBlank($customer, 'acquisition_source', $record['source'] ?? $record['acquisition_source'] ?? null);
                    $this->fillIfBlank($customer, 'priority', $record['priority'] ?? null);

                    if (blank($customer->display_name) && filled($email)) {
                        $customer->display_name = Str::before($email, '@');
                    }

                    $customer->save();

                    $this->syncTags($customer, $record['tags'] ?? '', $autoCreateRelations);
                    $this->syncLists($customer, $record['lists'] ?? '', $autoCreateRelations);

                    $successRows++;
                }
                break;
            }

            $reader->close();
            $batch->update([
                'total_rows' => $totalRows,
                'success_rows' => $successRows,
                'failed_rows' => $failedRows,
                'status' => 'completed',
            ]);
            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();
            $reader->close();
            $batch->update([
                'total_rows' => $totalRows,
                'success_rows' => $successRows,
                'failed_rows' => $failedRows + 1,
                'status' => 'failed',
            ]);
            $errors[] = [
                'row' => $rowNumber,
                'email' => $email ?? null,
                'message' => $throwable->getMessage(),
            ];
        }

        return [
            'batch' => $batch->fresh(),
            'total_rows' => $totalRows,
            'success_rows' => $successRows,
            'failed_rows' => $failedRows,
            'errors' => $errors,
        ];
    }

    private function fillIfBlank(Customer $customer, string $attribute, mixed $value): void
    {
        $value = is_string($value) ? trim($value) : $value;
        if (blank($value) || filled($customer->{$attribute})) return;
        $customer->{$attribute} = $value;
    }

    private function syncTags(Customer $customer, string $value, bool $autoCreateRelations): void
    {
        $names = $this->splitValues($value);
        if ($names === []) return;

        $tagIds = [];
        foreach ($names as $name) {
            $tag = Tag::whereRaw('lower(name) = ?', [Str::lower($name)])->first();
            if (! $tag && $autoCreateRelations) {
                $tag = Tag::create(['name' => $name, 'slug' => Str::slug($name)]);
            }
            if ($tag) $tagIds[] = $tag->id;
        }
        if ($tagIds !== []) {
            $customer->tags()->syncWithoutDetaching(array_unique($tagIds));
        }
    }

    private function syncLists(Customer $customer, string $value, bool $autoCreateRelations): void
    {
        $names = $this->splitValues($value);
        if ($names === []) return;

        $listIds = [];
        foreach ($names as $name) {
            $list = ContactList::whereRaw('lower(name) = ?', [Str::lower($name)])->first();
            if (! $list && $autoCreateRelations) {
                $list = ContactList::create([
                    'name' => $name,
                    'slug' => Str::slug($name),
                    'type' => 'newsletter',
                    'status' => 'active',
                ]);
            }
            if ($list) $listIds[] = $list->id;
        }
        if ($listIds !== []) {
            $customer->lists()->syncWithoutDetaching(array_unique($listIds));
        }
    }

    private function splitValues(string $value): array
    {
        $chunks = preg_split('/[|,;]+/', $value) ?: [];
        return array_values(array_filter(array_map(static fn (string $item) => trim($item), $chunks)));
    }
}
