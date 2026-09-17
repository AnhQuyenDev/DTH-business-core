<?php

namespace Dth\HumanResource\Services;

use DateTimeInterface;
use Dth\HumanResource\Enums\BusinessFunction;
use Dth\HumanResource\Enums\EmploymentStatus;
use Dth\HumanResource\Enums\PositionAuthority;
use Dth\HumanResource\Enums\PositionGroup;
use Dth\HumanResource\Models\Department;
use Dth\HumanResource\Models\Employee;
use Dth\HumanResource\Models\Position;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Options as XlsxOptions;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class HumanResourceDataExchangeService
{
    /** @return array<int, string> */
    public function headers(string $entity): array
    {
        return match ($entity) {
            'employees' => [
                'employee_code',
                'full_name',
                'email',
                'phone',
                'login_email',
                'department_code',
                'position_code',
                'employment_status',
                'started_at',
                'ended_at',
            ],
            'departments' => [
                'code',
                'name',
                'function_key',
                'color',
                'description',
                'sort_order',
                'is_active',
            ],
            'positions' => [
                'code',
                'title',
                'group_key',
                'authority_level',
                'function_key',
                'description',
                'sort_order',
                'is_active',
            ],
            default => throw new RuntimeException("Unsupported Human Resource exchange entity: {$entity}"),
        };
    }

    /** @return iterable<int, array<int, mixed>> */
    public function rows(string $entity, Builder $query): iterable
    {
        $query = clone $query;

        if ($entity === 'employees') {
            $query->with([
                'department:id,code',
                'position:id,code',
                'user:id,email',
            ]);
        }

        foreach ($query->orderBy('id')->cursor() as $record) {
            yield match ($entity) {
                'employees' => [
                    $record->employee_code,
                    $record->full_name,
                    $record->email,
                    $record->phone,
                    $record->user?->email,
                    $record->department?->code,
                    $record->position?->code,
                    $this->enumValue($record->employment_status),
                    $this->dateValue($record->started_at),
                    $this->dateValue($record->ended_at),
                ],
                'departments' => [
                    $record->code,
                    $record->name,
                    $this->enumValue($record->function_key),
                    $record->color,
                    $record->description,
                    $record->sort_order,
                    $record->is_active ? 1 : 0,
                ],
                'positions' => [
                    $record->code,
                    $record->title,
                    $this->enumValue($record->group_key),
                    $this->enumValue($record->authority_level),
                    $this->enumValue($record->function_key),
                    $record->description,
                    $record->sort_order,
                    $record->is_active ? 1 : 0,
                ],
                default => throw new RuntimeException("Unsupported Human Resource exchange entity: {$entity}"),
            };
        }
    }

    public function csvDownload(string $entity, Builder $query, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($entity, $query): void {
            $stream = fopen('php://output', 'wb');
            if ($stream === false) {
                throw new RuntimeException('Unable to open Human Resource CSV output stream.');
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
        $directory = storage_path('app/private/dth-human-resource-exports');
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create Human Resource XLSX export directory: {$directory}");
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
            'employees' => $this->importEmployee($row),
            'departments' => $this->importDepartment($row),
            'positions' => $this->importPosition($row),
            default => throw new RuntimeException("Unsupported Human Resource exchange entity: {$entity}"),
        };
    }

    /** @param array<string, mixed> $row */
    private function importEmployee(array $row): void
    {
        $fullName = $this->stringOrNull($row['full_name'] ?? null);
        if ($fullName === null) {
            throw new RuntimeException('full_name is required.');
        }

        $employeeCode = $this->stringOrNull($row['employee_code'] ?? null);
        $employee = $employeeCode
            ? Employee::query()->withTrashed()->where('employee_code', $employeeCode)->first()
            : null;

        $data = ['full_name' => $fullName];

        foreach (['email', 'phone'] as $field) {
            if ($this->rowHasValue($row, $field)) {
                $data[$field] = $this->stringOrNull($row[$field]);
            } elseif (! $employee) {
                $data[$field] = null;
            }
        }

        if ($this->rowHasValue($row, 'login_email')) {
            $data['user_id'] = $this->resolveUserId($row['login_email']);
        } elseif (! $employee) {
            $data['user_id'] = null;
        }

        if ($this->rowHasValue($row, 'department_code')) {
            $data['department_id'] = $this->resolveDepartmentId($row['department_code']);
        } elseif (! $employee) {
            $data['department_id'] = null;
        }

        if ($this->rowHasValue($row, 'position_code')) {
            $data['position_id'] = $this->resolvePositionId($row['position_code']);
        } elseif (! $employee) {
            $data['position_id'] = null;
        }

        if ($this->rowHasValue($row, 'employment_status')) {
            $data['employment_status'] = $this->enumOrDefault(
                EmploymentStatus::class,
                $row['employment_status'],
                EmploymentStatus::Active->value,
                'employment_status',
            );
        } elseif (! $employee) {
            $data['employment_status'] = EmploymentStatus::Active->value;
        }

        foreach (['started_at', 'ended_at'] as $field) {
            if ($this->rowHasValue($row, $field)) {
                $data[$field] = $this->dateOrNull($row[$field], $field);
            } elseif (! $employee) {
                $data[$field] = null;
            }
        }

        if ($employee) {
            if ($employee->trashed()) {
                $employee->restore();
            }
            $employee->update($data);
            return;
        }

        if ($employeeCode !== null) {
            $data['employee_code'] = $employeeCode;
        }

        Employee::query()->create($data);
    }

    /** @param array<string, mixed> $row */
    private function importDepartment(array $row): void
    {
        $name = $this->stringOrNull($row['name'] ?? null);
        if ($name === null) {
            throw new RuntimeException('name is required.');
        }

        $code = $this->stringOrNull($row['code'] ?? null);
        $department = $code
            ? Department::query()->where('code', $code)->first()
            : Department::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();

        $data = ['name' => $name];

        if ($this->rowHasValue($row, 'function_key')) {
            $data['function_key'] = $this->nullableEnum(BusinessFunction::class, $row['function_key'], 'function_key');
        } elseif (! $department) {
            $data['function_key'] = null;
        }

        if ($this->rowHasValue($row, 'color')) {
            $data['color'] = $this->stringOrNull($row['color']) ?? 'gray';
        } elseif (! $department) {
            $data['color'] = 'gray';
        }

        if ($this->rowHasValue($row, 'description')) {
            $data['description'] = $this->stringOrNull($row['description']);
        } elseif (! $department) {
            $data['description'] = null;
        }

        if ($this->rowHasValue($row, 'sort_order')) {
            $data['sort_order'] = $this->integerOrDefault($row['sort_order'], 0, 'sort_order');
        } elseif (! $department) {
            $data['sort_order'] = 0;
        }

        if ($this->rowHasValue($row, 'is_active')) {
            $data['is_active'] = $this->booleanOrDefault($row['is_active'], true, 'is_active');
        } elseif (! $department) {
            $data['is_active'] = true;
        }

        if ($department) {
            $department->update($data);
            return;
        }

        if ($code !== null) {
            $data['code'] = $code;
        }

        Department::query()->create($data);
    }

    /** @param array<string, mixed> $row */
    private function importPosition(array $row): void
    {
        $title = $this->stringOrNull($row['title'] ?? null);
        if ($title === null) {
            throw new RuntimeException('title is required.');
        }

        $code = $this->stringOrNull($row['code'] ?? null);
        $position = $code
            ? Position::query()->where('code', $code)->first()
            : Position::query()->whereRaw('LOWER(title) = ?', [mb_strtolower($title)])->first();

        $data = ['title' => $title];

        if ($this->rowHasValue($row, 'group_key')) {
            $data['group_key'] = $this->enumOrDefault(PositionGroup::class, $row['group_key'], PositionGroup::Professional->value, 'group_key');
        } elseif (! $position) {
            $data['group_key'] = PositionGroup::Professional->value;
        }

        if ($this->rowHasValue($row, 'authority_level')) {
            $data['authority_level'] = $this->enumOrDefault(PositionAuthority::class, $row['authority_level'], PositionAuthority::Member->value, 'authority_level');
        } elseif (! $position) {
            $data['authority_level'] = PositionAuthority::Member->value;
        }

        if ($this->rowHasValue($row, 'function_key')) {
            $data['function_key'] = $this->nullableEnum(BusinessFunction::class, $row['function_key'], 'function_key');
        } elseif (! $position) {
            $data['function_key'] = null;
        }

        if ($this->rowHasValue($row, 'description')) {
            $data['description'] = $this->stringOrNull($row['description']);
        } elseif (! $position) {
            $data['description'] = null;
        }

        if ($this->rowHasValue($row, 'sort_order')) {
            $data['sort_order'] = $this->integerOrDefault($row['sort_order'], 0, 'sort_order');
        } elseif (! $position) {
            $data['sort_order'] = 0;
        }

        if ($this->rowHasValue($row, 'is_active')) {
            $data['is_active'] = $this->booleanOrDefault($row['is_active'], true, 'is_active');
        } elseif (! $position) {
            $data['is_active'] = true;
        }

        if ($position) {
            $position->update($data);
            return;
        }

        if ($code !== null) {
            $data['code'] = $code;
        }

        Position::query()->create($data);
    }

    private function resolveDepartmentId(mixed $value): ?int
    {
        $code = $this->stringOrNull($value);
        if ($code === null) {
            return null;
        }

        $id = Department::query()->where('code', $code)->value('id');
        if (! $id) {
            throw new RuntimeException("Unknown department_code: {$code}");
        }

        return (int) $id;
    }

    private function resolvePositionId(mixed $value): ?int
    {
        $code = $this->stringOrNull($value);
        if ($code === null) {
            return null;
        }

        $id = Position::query()->where('code', $code)->value('id');
        if (! $id) {
            throw new RuntimeException("Unknown position_code: {$code}");
        }

        return (int) $id;
    }

    private function resolveUserId(mixed $value): ?int
    {
        $email = $this->stringOrNull($value);
        if ($email === null) {
            return null;
        }

        $model = (string) config('auth.providers.users.model', \App\Models\User::class);
        if (! class_exists($model)) {
            throw new RuntimeException('The configured user model is not available.');
        }

        $userId = $model::query()->where('email', strtolower($email))->value('id');
        if (! $userId) {
            throw new RuntimeException("Unknown login_email: {$email}");
        }

        return (int) $userId;
    }

    /** @param class-string<\BackedEnum> $enum */
    private function enumOrDefault(string $enum, mixed $value, string $default, string $field): string
    {
        $value = $this->stringOrNull($value);
        if ($value === null) {
            return $default;
        }

        if ($enum::tryFrom($value) === null) {
            throw new RuntimeException("Invalid {$field}: {$value}");
        }

        return $value;
    }

    /** @param class-string<\BackedEnum> $enum */
    private function nullableEnum(string $enum, mixed $value, string $field): ?string
    {
        $value = $this->stringOrNull($value);
        if ($value === null) {
            return null;
        }

        if ($enum::tryFrom($value) === null) {
            throw new RuntimeException("Invalid {$field}: {$value}");
        }

        return $value;
    }

    private function integerOrDefault(mixed $value, int $default, string $field): int
    {
        $value = $this->stringOrNull($value);
        if ($value === null) {
            return $default;
        }

        if (filter_var($value, FILTER_VALIDATE_INT) === false || (int) $value < 0) {
            throw new RuntimeException("Invalid {$field}: {$value}");
        }

        return (int) $value;
    }

    private function booleanOrDefault(mixed $value, bool $default, string $field): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if ($value === null || trim((string) $value) === '') {
            return $default;
        }

        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            '1', 'true', 'yes', 'y', 'on', 'active' => true,
            '0', 'false', 'no', 'n', 'off', 'inactive' => false,
            default => throw new RuntimeException("Invalid {$field}: {$value}"),
        };
    }

    private function dateOrNull(mixed $value, string $field): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->toDateString();
        }

        $value = $this->stringOrNull($value);
        if ($value === null) {
            return null;
        }

        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            throw new RuntimeException("Invalid {$field}: {$value}");
        }
    }

    /** @param array<string, mixed> $row */
    private function rowHasValue(array $row, string $key): bool
    {
        if (! array_key_exists($key, $row)) {
            return false;
        }

        $value = $row[$key];
        if ($value instanceof DateTimeInterface || is_bool($value) || is_int($value) || is_float($value)) {
            return true;
        }

        return $value !== null && trim((string) $value) !== '';
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function enumValue(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }

    private function dateValue(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return $this->stringOrNull($value);
    }

    /** @param array<int, mixed> $row @return array<int, scalar|null> */
    private function sanitizeRow(array $row): array
    {
        return array_map(static function (mixed $value): string|int|float|bool|null {
            if ($value instanceof \BackedEnum) {
                return $value->value;
            }
            if ($value instanceof DateTimeInterface) {
                return $value->format('Y-m-d H:i:s');
            }
            if ($value === null || is_scalar($value)) {
                return $value;
            }

            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: (string) $value;
        }, $row);
    }

    private function safeFilename(string $filename, string $extension): string
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim($filename)) ?: 'human-resource-export';
        $filename = trim($filename, '.-_');
        $filename = $filename !== '' ? $filename : 'human-resource-export';

        return str_ends_with(strtolower($filename), '.'.$extension)
            ? $filename
            : $filename.'.'.$extension;
    }
}
