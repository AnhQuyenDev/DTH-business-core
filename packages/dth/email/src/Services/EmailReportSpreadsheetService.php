<?php

namespace Dth\Email\Services;

use Dth\Email\DTO\EmailAnalyticsFilters;
use Dth\Email\Models\EmailCampaign;
use Dth\Email\Support\SpreadsheetExportSanitizer;
use Dth\Email\Support\UiText;
use Illuminate\Support\Facades\DB;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmailReportSpreadsheetService
{
    public function __construct(
        private readonly EmailAnalyticsService $analytics,
    ) {}

    /**
     * @return array<int, string>
     */
    public function dashboardHeaders(): array
    {
        return [
            UiText::get('reports.columns.id', 'ID'),
            UiText::get('reports.columns.campaign', 'Campaign'),
            UiText::get('reports.columns.subject', 'Subject'),
            UiText::get('reports.columns.status', 'Status'),
            UiText::get('reports.columns.started_at', 'Started at'),
            UiText::get('reports.columns.recipients', 'Recipients'),
            UiText::get('reports.columns.sent', 'Sent'),
            UiText::get('reports.columns.unique_opens', 'Unique opens'),
            UiText::get('reports.columns.open_rate', 'Open rate (%)'),
            UiText::get('reports.columns.unique_clicks', 'Unique clicks'),
            UiText::get('reports.columns.click_rate', 'Click rate (%)'),
            UiText::get('reports.columns.ctor', 'CTOR (%)'),
            UiText::get('reports.columns.unsubscribed', 'Unsubscribed'),
            UiText::get('reports.columns.unsubscribe_rate', 'Unsubscribe rate (%)'),
            UiText::get('reports.columns.failed', 'Failed'),
            UiText::get('reports.columns.failure_rate', 'Failure rate (%)'),
        ];
    }

    /**
     * @return iterable<int, array<int, mixed>>
     */
    public function dashboardRows(EmailAnalyticsFilters $filters): iterable
    {
        foreach ($this->analytics->campaignExportRows($filters) as $row) {
            yield [
                $row->campaignId,
                $row->name,
                $row->subject,
                UiText::status($row->status),
                $row->startedAt?->format('d/m/Y H:i'),
                $row->recipients,
                $row->sent,
                $row->opened,
                $row->openRate,
                $row->clicked,
                $row->clickRate,
                $row->clickToOpenRate,
                $row->unsubscribed,
                $row->unsubscribeRate,
                $row->failed,
                $row->failureRate,
            ];
        }
    }

    /**
     * @return array<int, string>
     */
    public function campaignRecipientHeaders(): array
    {
        return [
            UiText::get('reports.columns.id', 'ID'),
            UiText::get('reports.columns.recipient_email', 'Recipient email'),
            UiText::get('reports.columns.recipient_name', 'Recipient name'),
            UiText::get('reports.columns.status', 'Status'),
            UiText::get('reports.columns.sent_at', 'Sent at'),
            UiText::get('reports.columns.opened_at', 'Opened at'),
            UiText::get('reports.columns.clicked_at', 'Clicked at'),
            UiText::get('reports.columns.failed_at', 'Failed at'),
            UiText::get('reports.columns.failure_reason', 'Failure reason'),
            UiText::get('reports.columns.message_uuid', 'Message UUID'),
            UiText::get('reports.columns.message_status', 'Message status'),
            UiText::get('reports.columns.provider_message_id', 'Provider message ID'),
        ];
    }

    public function assertCampaignRecipientExportAllowed(EmailCampaign $campaign): void
    {
        $maxRows = max(1, (int) config('dth-email.reports.max_recipient_export_rows', 100000));
        $count = DB::table('email_campaign_recipients')
            ->where('campaign_id', $campaign->getKey())
            ->count();

        if ($count > $maxRows) {
            throw new RuntimeException(UiText::get(
                'reports.errors.recipient_limit',
                'This campaign has :count recipients, exceeding the current export limit of :limit rows.',
                ['count' => $count, 'limit' => $maxRows],
            ));
        }
    }

    /**
     * @return iterable<int, array<int, mixed>>
     */
    public function campaignRecipientRows(EmailCampaign $campaign): iterable
    {
        $this->assertCampaignRecipientExportAllowed($campaign);

        $rows = DB::table('email_campaign_recipients as ecr')
            ->leftJoin('email_messages as em', 'em.campaign_recipient_id', '=', 'ecr.id')
            ->where('ecr.campaign_id', $campaign->getKey())
            ->select([
                'ecr.id',
                'ecr.email',
                'ecr.name',
                'ecr.status',
                'ecr.sent_at',
                'ecr.opened_at',
                'ecr.clicked_at',
                'ecr.failed_at',
                'ecr.failure_reason',
                'em.uuid as message_uuid',
                'em.status as message_status',
                'em.provider_message_id',
            ])
            ->orderBy('ecr.id')
            ->cursor();

        foreach ($rows as $row) {
            yield [
                (int) $row->id,
                $row->email,
                $row->name,
                UiText::status($row->status),
                $this->formatDateTime($row->sent_at),
                $this->formatDateTime($row->opened_at),
                $this->formatDateTime($row->clicked_at),
                $this->formatDateTime($row->failed_at),
                $row->failure_reason,
                $row->message_uuid,
                filled($row->message_status) ? UiText::status($row->message_status) : null,
                $row->provider_message_id,
            ];
        }
    }

    /**
     * @param array<int, string> $headers
     * @param iterable<int, array<int, mixed>> $rows
     */
    public function csvDownload(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(
            function () use ($headers, $rows): void {
                $stream = fopen('php://output', 'wb');

                if ($stream === false) {
                    throw new RuntimeException('Unable to open CSV output stream.');
                }

                fwrite($stream, "\xEF\xBB\xBF");
                fputcsv($stream, $this->sanitizeRow($headers));

                foreach ($rows as $row) {
                    fputcsv($stream, $this->sanitizeRow($row));
                }

                fclose($stream);
            },
            $this->safeFilename($filename, 'csv'),
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'private, no-store, max-age=0',
            ],
        );
    }

    /**
     * @param array<int, string> $headers
     * @param iterable<int, array<int, mixed>> $rows
     * @param array<int, float|int> $columnWidths 1-based column index => width
     */
    public function xlsxDownload(
        string $filename,
        array $headers,
        iterable $rows,
        array $columnWidths = [],
        string $sheetName = 'Report',
    ): BinaryFileResponse {
        $directory = (string) config(
            'dth-email.reports.xlsx_temp_directory',
            storage_path('app/private/dth-email-exports'),
        );

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create XLSX export directory: {$directory}");
        }

        $path = $directory.'/'.bin2hex(random_bytes(16)).'.xlsx';
        $options = new Options();

        foreach ($columnWidths as $column => $width) {
            if ((int) $column >= 1 && (float) $width > 0) {
                $options->setColumnWidth((float) $width, (int) $column);
            }
        }

        $writer = new Writer($options);
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName(mb_substr($sheetName, 0, 31));

        $headerStyle = (new Style())->setFontBold();
        $writer->addRow(Row::fromValues($this->sanitizeRow($headers), $headerStyle));

        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($this->sanitizeRow($row)));
        }

        $writer->close();

        return response()
            ->download(
                $path,
                $this->safeFilename($filename, 'xlsx'),
                [
                    'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'Cache-Control' => 'private, no-store, max-age=0',
                ],
            )
            ->deleteFileAfterSend(true);
    }

    /**
     * @param array<int, mixed> $row
     * @return array<int, mixed>
     */
    private function sanitizeRow(array $row): array
    {
        return array_map(
            static fn (mixed $value): mixed => SpreadsheetExportSanitizer::value($value),
            $row,
        );
    }

    private function safeFilename(string $filename, string $extension): string
    {
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $basename = preg_replace('/[^A-Za-z0-9._-]+/', '-', $basename) ?: 'email-report';
        $basename = trim($basename, '-.');

        return ($basename !== '' ? $basename : 'email-report').'.'.$extension;
    }

    private function formatDateTime(mixed $value): ?string
    {
        if (! filled($value)) {
            return null;
        }

        return \Illuminate\Support\Carbon::parse($value)->format('d/m/Y H:i:s');
    }
}
