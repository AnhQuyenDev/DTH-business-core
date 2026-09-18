<?php
namespace Dth\AccountManagement\Services;

use Dth\AccountManagement\Enums\AccountStatus;
use Dth\AccountManagement\Models\AccountRole;
use Dth\AccountManagement\Models\AccountUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;

final class AccountUserDataExchangeService
{
    /** @return array<int, string> */
    public function headers(): array
    {
        return ['name', 'email', 'phone', 'account_status', 'preferred_locale', 'timezone', 'roles', 'password', 'must_change_password'];
    }

    /** @return array{imported:int, updated:int, errors:array<int, array{row:int, message:string}>} */
    public function import(string $path, ?string $extension = null): array
    {
        $rows = $this->readRows($path, $extension);
        if ($rows === []) {
            throw new RuntimeException('The import file does not contain any data rows.');
        }

        $imported = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                if ($this->importRow($row)) {
                    $updated++;
                } else {
                    $imported++;
                }
            } catch (\Throwable $exception) {
                $errors[] = ['row' => $index + 2, 'message' => $exception->getMessage()];
            }
        }

        return ['imported' => $imported, 'updated' => $updated, 'errors' => $errors];
    }

    /** @param array<string, mixed> $row @return bool true if an existing user was updated, false if created */
    private function importRow(array $row): bool
    {
        $email = $this->stringOrNull($row['email'] ?? null);
        if ($email === null || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('A valid email is required.');
        }

        $user = AccountUser::query()->where('email', $email)->first();
        $isUpdate = $user !== null;

        $data = ['email' => $email];

        $name = $this->stringOrNull($row['name'] ?? null);
        if ($name !== null) {
            $data['name'] = $name;
        } elseif (! $isUpdate) {
            throw new RuntimeException('name is required.');
        }

        if ($this->rowHasValue($row, 'phone')) {
            $data['phone'] = $this->stringOrNull($row['phone']);
        }

        $data['account_status'] = $this->rowHasValue($row, 'account_status')
            ? $this->enumOrDefault(AccountStatus::class, $row['account_status'], AccountStatus::Active->value, 'account_status')
            : ($isUpdate ? $user->account_status?->value ?? AccountStatus::Active->value : AccountStatus::Active->value);

        if ($this->rowHasValue($row, 'preferred_locale')) {
            $locale = strtolower($this->stringOrNull($row['preferred_locale']) ?? 'vi');
            if (! in_array($locale, ['vi', 'en'], true)) {
                throw new RuntimeException("Invalid preferred_locale: {$locale}");
            }
            $data['preferred_locale'] = $locale;
        } elseif (! $isUpdate) {
            $data['preferred_locale'] = 'vi';
        }

        if ($this->rowHasValue($row, 'timezone')) {
            $data['timezone'] = $this->stringOrNull($row['timezone']);
        } elseif (! $isUpdate) {
            $data['timezone'] = 'Asia/Ho_Chi_Minh';
        }

        $generatedPassword = false;
        if ($this->rowHasValue($row, 'password')) {
            $data['password'] = (string) $row['password'];
        } elseif (! $isUpdate) {
            $data['password'] = Str::random(20);
            $generatedPassword = true;
        }

        $data['must_change_password'] = $this->rowHasValue($row, 'must_change_password')
            ? $this->booleanOrDefault($row['must_change_password'], false, 'must_change_password')
            : ($generatedPassword || ! $isUpdate ? true : $user->must_change_password);

        $roleIds = $this->rowHasValue($row, 'roles') ? $this->resolveRoleIds((string) $row['roles']) : null;

        DB::transaction(function () use (&$user, $data, $roleIds, $isUpdate): void {
            if ($isUpdate) {
                $user->update($data);
            } else {
                $user = AccountUser::query()->create($data);
            }

            if ($roleIds !== null) {
                $user->roles()->sync($roleIds);
            }
        });

        return $isUpdate;
    }

    /** @return array<int, int> */
    private function resolveRoleIds(string $keys): array
    {
        $keys = array_values(array_filter(array_map('trim', preg_split('/[,|]/', $keys) ?: [])));
        if ($keys === []) return [];

        $ids = AccountRole::query()->whereIn('key', $keys)->pluck('id', 'key');
        $missing = array_diff($keys, $ids->keys()->all());
        if ($missing !== []) {
            throw new RuntimeException('Unknown role key(s): '.implode(', ', $missing));
        }

        return $ids->values()->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function readRows(string $path, ?string $extension = null): array
    {
        $extension = strtolower($extension ?: pathinfo($path, PATHINFO_EXTENSION));

        return $extension === 'xlsx' ? $this->readXlsx($path) : $this->readCsv($path);
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
            if ($this->rowIsEmpty($values)) continue;
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
                    if ($this->rowIsEmpty($values)) continue;
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
            if ($value !== null && trim((string) $value) !== '') return false;
        }

        return true;
    }

    /** @param array<string, mixed> $row */
    private function rowHasValue(array $row, string $key): bool
    {
        if (! array_key_exists($key, $row)) return false;
        $value = $row[$key];
        if (is_bool($value) || is_int($value) || is_float($value)) return true;

        return $value !== null && trim((string) $value) !== '';
    }

    private function stringOrNull(mixed $value): ?string
    {
        if ($value === null) return null;
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /** @param class-string<\BackedEnum> $enum */
    private function enumOrDefault(string $enum, mixed $value, string $default, string $field): string
    {
        $value = $this->stringOrNull($value);
        if ($value === null) return $default;
        if ($enum::tryFrom($value) === null) {
            throw new RuntimeException("Invalid {$field}: {$value}");
        }

        return $value;
    }

    private function booleanOrDefault(mixed $value, bool $default, string $field): bool
    {
        if (is_bool($value)) return $value;
        if ($value === null || trim((string) $value) === '') return $default;

        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            '1', 'true', 'yes', 'y', 'on' => true,
            '0', 'false', 'no', 'n', 'off' => false,
            default => throw new RuntimeException("Invalid {$field}: {$value}"),
        };
    }
}
