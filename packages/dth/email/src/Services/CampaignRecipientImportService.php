<?php

namespace Dth\Email\Services;

use Dth\Email\DTO\CampaignRecipientImportResult;
use Dth\Email\Enums\EmailCampaignStatus;
use Dth\Email\Models\CampaignRecipient;
use Dth\Email\Models\EmailCampaign;
use InvalidArgumentException;
use LogicException;
use RuntimeException;

class CampaignRecipientImportService
{
    public function __construct(
        private readonly CampaignRecipientService $recipients,
        private readonly TemplateVariableRegistry $variables,
    ) {}

    public function importCsv(
        EmailCampaign $campaign,
        string $path,
    ): CampaignRecipientImportResult {
        if ($campaign->status !== EmailCampaignStatus::Draft) {
            throw new LogicException(
                'Recipients can only be imported into draft campaigns.'
            );
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException(
                'Unable to open CSV file.'
            );
        }

        $rows = 0;
        $added = 0;
        $updated = 0;
        $invalid = 0;

        try {
            $firstLine = fgets($handle);

            if ($firstLine === false) {
                throw new InvalidArgumentException(
                    'CSV file is empty.'
                );
            }

            $delimiter = $this->detectDelimiter($firstLine);

            rewind($handle);

            $headers = fgetcsv(
                $handle,
                0,
                $delimiter,
            );

            if (! is_array($headers)) {
                throw new InvalidArgumentException(
                    'CSV header could not be read.'
                );
            }

            $headers = array_map(
                fn ($header): string => trim(
                    preg_replace(
                        '/^\xEF\xBB\xBF/',
                        '',
                        (string) $header
                    )
                ),
                $headers,
            );

            $normalizedHeaders = array_map(
                static fn (string $header): string => mb_strtolower($header),
                $headers,
            );

            $requiredColumns = $this->variables->expectedCsvColumns($campaign);
            $missingColumns = [];

            foreach ($requiredColumns as $column) {
                if (! in_array(mb_strtolower($column), $normalizedHeaders, true)) {
                    $missingColumns[] = $column;
                }
            }

            if ($missingColumns !== []) {
                throw new InvalidArgumentException(
                    'CSV is missing required personalization columns: '
                    .implode(', ', $missingColumns)
                    .'. Expected columns: '.implode(', ', $requiredColumns).'.'
                );
            }

            $emailIndex = $this->headerIndex(
                $headers,
                'email'
            );

            if ($emailIndex === null) {
                throw new InvalidArgumentException(
                    'CSV must contain an "email" column.'
                );
            }

            $nameIndex = $this->headerIndex(
                $headers,
                'name'
            );

            $requiredRecipientColumns = array_values(array_filter(
                $requiredColumns,
                static fn (string $column): bool => $column !== 'email',
            ));

            while (
                ($row = fgetcsv(
                    $handle,
                    0,
                    $delimiter,
                )) !== false
            ) {
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $rows++;

                $email = mb_strtolower(
                    trim((string) ($row[$emailIndex] ?? ''))
                );

                if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $invalid++;
                    continue;
                }

                $rowHasMissingPersonalization = false;

                foreach ($requiredRecipientColumns as $column) {
                    $columnIndex = $this->headerIndex($headers, $column);
                    $value = $columnIndex === null
                        ? ''
                        : trim((string) ($row[$columnIndex] ?? ''));

                    if ($value === '') {
                        $rowHasMissingPersonalization = true;
                        break;
                    }
                }

                if ($rowHasMissingPersonalization) {
                    $invalid++;
                    continue;
                }

                $name = $nameIndex === null
                    ? null
                    : trim(
                        (string) ($row[$nameIndex] ?? '')
                    );

                $variables = [];

                foreach ($headers as $index => $header) {
                    if (
                        $index === $emailIndex
                        || $index === $nameIndex
                        || blank($header)
                    ) {
                        continue;
                    }

                    $value = trim(
                        (string) ($row[$index] ?? '')
                    );

                    if ($value === '') {
                        continue;
                    }

                    data_set(
                        $variables,
                        $header,
                        $value
                    );
                }

                $exists = CampaignRecipient::query()
                    ->where('campaign_id', $campaign->id)
                    ->where('email', $email)
                    ->exists();

                $this->recipients->add(
                    $campaign,
                    [
                        'email' => $email,
                        'name' => $name ?: null,
                        'variables' => $variables ?: null,
                    ],
                );

                if ($exists) {
                    $updated++;
                } else {
                    $added++;
                }
            }
        } finally {
            fclose($handle);
        }

        return new CampaignRecipientImportResult(
            rows: $rows,
            added: $added,
            updated: $updated,
            invalid: $invalid,
        );
    }

    private function detectDelimiter(
        string $line,
    ): string {
        $candidates = [
            ',' => substr_count($line, ','),
            ';' => substr_count($line, ';'),
            "\t" => substr_count($line, "\t"),
        ];

        arsort($candidates);

        return (string) array_key_first($candidates);
    }

    private function headerIndex(
        array $headers,
        string $name,
    ): ?int {
        foreach ($headers as $index => $header) {
            if (mb_strtolower($header) === mb_strtolower($name)) {
                return $index;
            }
        }

        return null;
    }

    private function isEmptyRow(
        array $row,
    ): bool {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
