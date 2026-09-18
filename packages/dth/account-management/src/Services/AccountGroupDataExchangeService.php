<?php

namespace Dth\AccountManagement\Services;

use Dth\AccountManagement\Enums\GroupType;
use Dth\AccountManagement\Models\AccountGroup;
use Dth\AccountManagement\Models\AccountUser;
use Dth\AccountManagement\Support\UiText;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;
use Throwable;

final class AccountGroupDataExchangeService
{
    /** @return array<int, string> */
    public function headers(): array
    {
        return ['name', 'code', 'type', 'parent_code', 'is_active', 'description', 'users'];
    }

    /** @return array{imported:int, updated:int, errors:array<int, array{row:int, message:string}>} */
    public function import(string $path, ?string $extension = null): array
    {
        $rows = $this->readRows($path, $extension);
        if ($rows === []) {
            throw new RuntimeException(UiText::get('data.group_empty_file', 'Tệp nhập không chứa dòng dữ liệu nào.'));
        }

        $indexedRows = [];
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $code = $this->validateCode($row['code'] ?? null);
                if (isset($indexedRows[$code])) {
                    throw new RuntimeException(strtr(
                        UiText::get('data.group_duplicate_code', 'Mã nhóm :code bị lặp trong tệp nhập.'),
                        [':code' => $code],
                    ));
                }

                $indexedRows[$code] = [
                    'row' => $row,
                    'row_number' => $rowNumber,
                ];
            } catch (Throwable $exception) {
                $errors[] = ['row' => $rowNumber, 'message' => $exception->getMessage()];
            }
        }

        if ($indexedRows === []) {
            return ['imported' => 0, 'updated' => 0, 'errors' => $errors];
        }

        $processed = [];
        $processing = [];
        $imported = 0;
        $updated = 0;

        foreach (array_keys($indexedRows) as $code) {
            if (isset($processed[$code])) {
                continue;
            }

            try {
                $result = $this->importCode($code, $indexedRows, $processed, $processing);
                $imported += $result['imported'];
                $updated += $result['updated'];
            } catch (Throwable $exception) {
                $rowNumber = $indexedRows[$code]['row_number'] ?? 0;
                $errors[] = ['row' => $rowNumber, 'message' => $exception->getMessage()];
                $processed[$code] = true;
                $processing = [];
            }
        }

        return ['imported' => $imported, 'updated' => $updated, 'errors' => $errors];
    }

    /**
     * @param array<string, array{row:array<string,mixed>,row_number:int}> $indexedRows
     * @param array<string, bool> $processed
     * @param array<string, bool> $processing
     * @return array{imported:int,updated:int}
     */
    private function importCode(string $code, array $indexedRows, array &$processed, array &$processing): array
    {
        if (isset($processed[$code])) {
            return ['imported' => 0, 'updated' => 0];
        }

        if (isset($processing[$code])) {
            throw new RuntimeException(strtr(
                UiText::get('data.group_parent_cycle', 'Phát hiện vòng lặp nhóm cha liên quan đến :code.'),
                [':code' => $code],
            ));
        }

        $entry = $indexedRows[$code] ?? null;
        if ($entry === null) {
            throw new RuntimeException(strtr(
                UiText::get('data.group_unknown_code', 'Không tìm thấy nhóm có mã :code.'),
                [':code' => $code],
            ));
        }

        $processing[$code] = true;
        $row = $entry['row'];
        // parent_code is special: when the column exists, an empty value means
        // explicitly make the group a top-level group. If the column is omitted
        // entirely, an existing parent's relationship is preserved.
        $parentSpecified = array_key_exists('parent_code', $row);
        $parentCode = $parentSpecified ? $this->stringOrNull($row['parent_code'] ?? null) : null;
        $parentId = null;
        $imported = 0;
        $updated = 0;

        if ($parentCode !== null) {
            if ($parentCode === $code) {
                unset($processing[$code]);
                throw new RuntimeException(UiText::get('data.group_parent_self', 'Nhóm không thể chọn chính nó làm nhóm cha.'));
            }

            if (isset($indexedRows[$parentCode])) {
                $parentResult = $this->importCode($parentCode, $indexedRows, $processed, $processing);
                $imported += $parentResult['imported'];
                $updated += $parentResult['updated'];
            }

            $parent = AccountGroup::query()->where('code', $parentCode)->first();
            if ($parent === null) {
                unset($processing[$code]);
                throw new RuntimeException(strtr(
                    UiText::get('data.group_parent_missing', 'Không tìm thấy nhóm cha có mã :code.'),
                    [':code' => $parentCode],
                ));
            }

            $this->assertParentDoesNotCreateCycle($code, $parent);
            $parentId = $parent->getKey();
        }

        $result = $this->importRow($row, $parentId, $parentSpecified);
        $imported += $result === 'imported' ? 1 : 0;
        $updated += $result === 'updated' ? 1 : 0;

        unset($processing[$code]);
        $processed[$code] = true;

        return ['imported' => $imported, 'updated' => $updated];
    }

    /** @param array<string, mixed> $row */
    private function importRow(array $row, ?int $parentId, bool $parentSpecified): string
    {
        $code = $this->validateCode($row['code'] ?? null);
        $group = AccountGroup::query()->where('code', $code)->first();
        $isUpdate = $group !== null;

        $name = $this->stringOrNull($row['name'] ?? null);
        if ($name === null && ! $isUpdate) {
            throw new RuntimeException(UiText::get('data.group_name_required', 'Cột name là bắt buộc.'));
        }

        if ($name !== null && mb_strlen($name) > 255) {
            throw new RuntimeException(UiText::get('data.group_name_too_long', 'Tên nhóm không được vượt quá 255 ký tự.'));
        }

        $type = $this->rowHasValue($row, 'type')
            ? $this->enumOrDefault(GroupType::class, $row['type'], GroupType::Team->value, 'type')
            : ($isUpdate ? $group->type?->value ?? GroupType::Team->value : GroupType::Team->value);

        $isActive = $this->rowHasValue($row, 'is_active')
            ? $this->booleanOrDefault($row['is_active'], true, 'is_active')
            : ($isUpdate ? (bool) $group->is_active : true);

        $description = $this->rowHasValue($row, 'description')
            ? $this->stringOrNull($row['description'])
            : ($isUpdate ? $group->description : null);

        $userIds = $this->rowHasValue($row, 'users')
            ? $this->resolveUserIds((string) $row['users'])
            : null;

        $data = [
            'code' => $code,
            'type' => $type,
            'is_active' => $isActive,
            'description' => $description,
        ];

        if ($parentSpecified || ! $isUpdate) {
            $data['parent_id'] = $parentId;
        }

        if ($name !== null) {
            $data['name'] = $name;
        }

        DB::transaction(function () use (&$group, $data, $userIds, $isUpdate): void {
            if ($isUpdate) {
                $group->update($data);
            } else {
                $group = AccountGroup::query()->create($data);
            }

            if ($userIds !== null) {
                $group->users()->sync($userIds);
            }
        });

        return $isUpdate ? 'updated' : 'imported';
    }

    private function assertParentDoesNotCreateCycle(string $childCode, AccountGroup $parent): void
    {
        $seen = [];
        $cursor = $parent;

        while ($cursor !== null) {
            if ($cursor->code === $childCode) {
                throw new RuntimeException(strtr(
                    UiText::get('data.group_parent_cycle', 'Phát hiện vòng lặp nhóm cha liên quan đến :code.'),
                    [':code' => $childCode],
                ));
            }

            $id = (int) $cursor->getKey();
            if (isset($seen[$id])) {
                throw new RuntimeException(strtr(
                    UiText::get('data.group_parent_cycle', 'Phát hiện vòng lặp nhóm cha liên quan đến :code.'),
                    [':code' => $childCode],
                ));
            }

            $seen[$id] = true;
            $cursor = $cursor->parent()->first();
        }
    }

    private function validateCode(mixed $value): string
    {
        $code = $this->stringOrNull($value);
        if ($code === null) {
            throw new RuntimeException(UiText::get('data.group_code_required', 'Cột code là bắt buộc.'));
        }

        if (mb_strlen($code) > 80) {
            throw new RuntimeException(UiText::get('data.group_code_too_long', 'Mã nhóm không được vượt quá 80 ký tự.'));
        }

        if (! preg_match('/^[A-Za-z0-9_-]+$/', $code)) {
            throw new RuntimeException(strtr(
                UiText::get('data.group_code_invalid', 'Mã nhóm :code chỉ được chứa chữ, số, dấu gạch ngang và gạch dưới.'),
                [':code' => $code],
            ));
        }

        return $code;
    }

    /** @return array<int, int> */
    private function resolveUserIds(string $emails): array
    {
        $emails = array_values(array_unique(array_filter(array_map(
            fn (string $email): string => strtolower(trim($email)),
            preg_split('/[,|]/', $emails) ?: [],
        ))));

        if ($emails === []) {
            return [];
        }

        foreach ($emails as $email) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException(strtr(
                    UiText::get('data.group_user_email_invalid', 'Email thành viên không hợp lệ: :email'),
                    [':email' => $email],
                ));
            }
        }

        $ids = AccountUser::query()->whereIn('email', $emails)->pluck('id', 'email');
        $knownEmails = array_map('strtolower', $ids->keys()->all());
        $missing = array_diff($emails, $knownEmails);

        if ($missing !== []) {
            throw new RuntimeException(strtr(
                UiText::get('data.group_users_missing', 'Không tìm thấy người dùng: :users'),
                [':users' => implode(', ', $missing)],
            ));
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
            throw new RuntimeException(UiText::get('data.group_csv_unreadable', 'Không thể đọc tệp CSV đã tải lên.'));
        }

        $headers = fgetcsv($handle);
        if (! is_array($headers)) {
            fclose($handle);
            throw new RuntimeException(UiText::get('data.group_csv_missing_header', 'Tệp CSV thiếu dòng tiêu đề.'));
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
            throw new RuntimeException(UiText::get('data.group_xlsx_missing_header', 'Tệp XLSX thiếu dòng tiêu đề.'));
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
    private function rowHasValue(array $row, string $key): bool
    {
        if (! array_key_exists($key, $row)) {
            return false;
        }

        $value = $row[$key];
        if (is_bool($value) || is_int($value) || is_float($value)) {
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

    /** @param class-string<\BackedEnum> $enum */
    private function enumOrDefault(string $enum, mixed $value, string $default, string $field): string
    {
        $value = $this->stringOrNull($value);
        if ($value === null) {
            return $default;
        }

        if ($enum::tryFrom($value) === null) {
            throw new RuntimeException(strtr(
                UiText::get('data.group_enum_invalid', 'Giá trị :value không hợp lệ cho :field.'),
                [':value' => $value, ':field' => $field],
            ));
        }

        return $value;
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
            '1', 'true', 'yes', 'y', 'on' => true,
            '0', 'false', 'no', 'n', 'off' => false,
            default => throw new RuntimeException(strtr(
                UiText::get('data.group_boolean_invalid', 'Giá trị :value không hợp lệ cho :field.'),
                [':value' => (string) $value, ':field' => $field],
            )),
        };
    }
}
