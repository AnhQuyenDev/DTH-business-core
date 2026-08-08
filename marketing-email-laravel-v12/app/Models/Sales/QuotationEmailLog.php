<?php

namespace App\Models\Sales;

use App\Enums\Sales\QuotationEmailStatus;
use App\Models\Marketing\SendingAccount;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationEmailLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'sending_account_id',
        'sender_email',
        'sender_name',
        'recipient_email',
        'cc',
        'bcc',
        'subject',
        'body_snapshot',
        'attachment_path',
        'provider_message_id',
        'status',
        'error_message',
        'queued_at',
        'sent_at',
        'failed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'cc' => 'json',
            'bcc' => 'json',
            'status' => QuotationEmailStatus::class,
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function sendingAccount(): BelongsTo
    {
        return $this->belongsTo(SendingAccount::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
