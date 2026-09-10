<?php

namespace Dth\Email\Jobs;

use Dth\Email\Enums\CampaignRecipientStatus;
use Dth\Email\Enums\EmailEventType;
use Dth\Email\Enums\EmailMessageStatus;
use Dth\Email\Models\CampaignRecipient;
use Dth\Email\Models\EmailMessage;
use Dth\Email\Services\CampaignService;
use Dth\Email\Services\CampaignVariableResolver;
use Dth\Email\Services\EmailComplianceContentService;
use Dth\Email\Services\EmailDispatchService;
use Dth\Email\Services\SuppressionService;
use Dth\Email\Services\TemplateRenderer;
use Dth\Email\Services\TrackingUrlService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class SendCampaignEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $recipientId) {}

    public function handle(
        SuppressionService $suppressions,
        TemplateRenderer $renderer,
        TrackingUrlService $tracking,
        CampaignVariableResolver $variableResolver,
        EmailComplianceContentService $compliance,
        EmailDispatchService $dispatch,
        CampaignService $campaigns,
    ): void {
        $recipient = CampaignRecipient::query()
            ->with(['campaign.template', 'campaign.sendingAccount', 'message'])
            ->findOrFail($this->recipientId);

        if (in_array($recipient->status, [CampaignRecipientStatus::Sent, CampaignRecipientStatus::Delivered, CampaignRecipientStatus::Opened, CampaignRecipientStatus::Clicked, CampaignRecipientStatus::Suppressed], true)) {
            return;
        }

        $campaign = $recipient->campaign;
        $account = $campaign->sendingAccount;
        $template = $campaign->template;

        if ($suppressions->isSuppressed($recipient->email)) {
            $recipient->update(['status' => CampaignRecipientStatus::Suppressed]);
            $campaigns->refreshCompletion($campaign);
            return;
        }

        $message = $recipient->message ?: EmailMessage::query()->create([
            'uuid' => (string) Str::uuid(),
            'sending_account_id' => $account->id,
            'template_id' => $template->id,
            'campaign_recipient_id' => $recipient->id,
            'related_type' => $campaign->source_type ?: 'email.campaign',
            'related_id' => $campaign->source_id ?: $campaign->id,
            'from_name' => $account->from_name,
            'from_email' => $account->from_email,
            'reply_to' => $account->reply_to,
            'recipient_email' => $recipient->email,
            'recipient_name' => $recipient->name,
            'subject' => $campaign->subject,
            'status' => EmailMessageStatus::Queued,
            'idempotency_key' => "email-campaign:{$campaign->id}:recipient:{$recipient->id}",
            'tracking_token' => (string) Str::uuid(),
            'unsubscribe_token' => (string) Str::uuid(),
            'queued_at' => now(),
        ]);

        if (! $message->events()->where('event_type', EmailEventType::Queued->value)->exists()) {
            $dispatch->event($message, EmailEventType::Queued);
        }

        $variables = $variableResolver->forRecipient(
            $recipient,
            $message,
        );

        $html = $renderer->render(
            $campaign->html_body,
            $variables,
        );

        $textBody = filled($campaign->text_body)
            ? $renderer->render(
                $campaign->text_body,
                $variables,
                false,
            )
            : $this->htmlToPlainText($html);

        if (
            filled($variables['unsubscribe_url'] ?? null)
            && ! str_contains(
                $textBody,
                $variables['unsubscribe_url']
            )
        ) {
            $textBody .= "\n\nUnsubscribe: "
                . $variables['unsubscribe_url'];
        }

        $preheader = $renderer->renderPreheader(
            $campaign->preheader,
            $variables,
        );

        $html = $compliance->injectPreheader(
            $html,
            $preheader,
        );

        $html = $compliance->ensureHtmlUnsubscribeFooter(
            $html,
            $variables['unsubscribe_url'],
            $account->from_name,
        );

        /*
        |--------------------------------------------------------------------------
        | Plain-text fallback
        |--------------------------------------------------------------------------
        |
        | Nếu Campaign có plain-text thủ công từ dữ liệu cũ thì vẫn sử dụng.
        | Nếu không, tự sinh text/plain từ HTML.
        |
        | Phải thực hiện trước rewriteLinks() để plain text sử dụng URL gốc,
        | không phải tracking redirect URL.
        |
        */

        $textBody = filled($campaign->text_body)
            ? $renderer->renderText(
                $campaign->text_body,
                $variables,
            )
            : $this->htmlToPlainText($html);

        $textBody = $compliance->ensureTextUnsubscribeFooter(
            $textBody,
            $variables['unsubscribe_url'],
            $account->from_name,
        );

        $html = $tracking->rewriteLinks(
            $message,
            $html,
        );

        $html = $tracking->injectOpenPixel(
            $html,
            $variables['open_pixel_url'],
        );

        $message->update([
            'subject' => $renderer->renderSubject(
                $campaign->subject ?: $template->subject,
                $variables,
            ),
            'html_body' => $html,
            'text_body' => $textBody,
        ]);

        try {
            $dispatch->send($message);

            $message->refresh();
            $recipient->update([
                'status' => $message->status === EmailMessageStatus::Suppressed
                    ? CampaignRecipientStatus::Suppressed
                    : CampaignRecipientStatus::Sent,
                'sent_at' => $message->sent_at,
                'failure_reason' => $message->failure_reason,
                'failed_at' => $message->failed_at,
            ]);
        } catch (Throwable $e) {
            $recipient->update([
                'failure_reason' => mb_substr(
                    $e->getMessage(),
                    0,
                    5000,
                ),
                'failed_at' => now(),
            ]);

            throw $e;
        }

        $campaigns->refreshCompletion($campaign);
    }

    public function failed(
        ?Throwable $exception,
    ): void {
        $recipient = CampaignRecipient::query()
            ->with('campaign')
            ->find($this->recipientId);

        if (! $recipient) {
            return;
        }

        $recipient->update([
            'status' => CampaignRecipientStatus::Failed,
            'failed_at' => now(),
            'failure_reason' => mb_substr(
                $exception?->getMessage()
                    ?? 'Email sending failed.',
                0,
                5000,
            ),
        ]);

        app(CampaignService::class)
            ->refreshCompletion($recipient->campaign);
    }

    private function htmlToPlainText(string $html): string
    {
        // Không đưa CSS / JavaScript vào plain-text email.
        $html = preg_replace(
            '/<(style|script)\b[^>]*>.*?<\/\1>/is',
            '',
            $html,
        ) ?? $html;

        /*
        * Chuyển:
        *
        * <a href="https://example.com">View services</a>
        *
        * thành:
        *
        * View services: https://example.com
        */
        $html = preg_replace_callback(
            '/<a\b[^>]*href=(["\'])(.*?)\1[^>]*>(.*?)<\/a>/is',
            static function (array $matches): string {
                $url = html_entity_decode(
                    $matches[2],
                    ENT_QUOTES | ENT_HTML5,
                    'UTF-8',
                );

                $label = trim(
                    html_entity_decode(
                        strip_tags($matches[3]),
                        ENT_QUOTES | ENT_HTML5,
                        'UTF-8',
                    ),
                );

                if ($label === '' || $label === $url) {
                    return $url;
                }

                return "{$label}: {$url}";
            },
            $html,
        ) ?? $html;

        // Giữ line-break cơ bản từ HTML.
        $html = preg_replace(
            '/<br\s*\/?>/i',
            "\n",
            $html,
        ) ?? $html;

        $html = preg_replace(
            '/<\/(p|div|li|tr|h[1-6]|section|article)>/i',
            "\n\n",
            $html,
        ) ?? $html;

        $text = strip_tags($html);

        $text = html_entity_decode(
            $text,
            ENT_QUOTES | ENT_HTML5,
            'UTF-8',
        );

        // Chuẩn hóa khoảng trắng.
        $text = preg_replace(
            "/[ \t]+\n/",
            "\n",
            $text,
        ) ?? $text;

        $text = preg_replace(
            "/\n{3,}/",
            "\n\n",
            $text,
        ) ?? $text;

        return trim($text);
    }
}
