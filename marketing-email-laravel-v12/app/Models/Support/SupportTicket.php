<?php

namespace App\Models\Support;

use App\Enums\Support\TicketPriority;
use App\Enums\Support\TicketStatus;
use App\Models\Crm\Customer;
use App\Models\Crm\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $ticket): void {
            $ticket->opened_at ??= now();
            $ticket->last_activity_at ??= now();
        });

        static::updating(function (self $ticket): void {
            if (! $ticket->isDirty('status')) {
                return;
            }

            $status = $ticket->status instanceof TicketStatus
                ? $ticket->status
                : TicketStatus::tryFrom((string) $ticket->status);

            $ticket->last_activity_at = now();

            if ($status === TicketStatus::Resolved && $ticket->resolved_at === null) {
                $ticket->resolved_at = now();
            } elseif ($status !== TicketStatus::Resolved && $status !== TicketStatus::Closed) {
                $ticket->resolved_at = null;
            }

            if ($status === TicketStatus::Closed && $ticket->closed_at === null) {
                $ticket->closed_at = now();
            } elseif ($status !== TicketStatus::Closed) {
                $ticket->closed_at = null;
            }
        });
    }

    protected $fillable = [
        'ticket_code', 'customer_id', 'assigned_staff_id', 'requester_name',
        'requester_email', 'subject', 'description', 'channel', 'status',
        'priority', 'opened_at', 'last_activity_at', 'resolved_at',
        'closed_at', 'created_by_user_id', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'status' => TicketStatus::class,
            'priority' => TicketPriority::class,
            'opened_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function assignedStaff(): BelongsTo { return $this->belongsTo(Staff::class, 'assigned_staff_id'); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by_user_id'); }
    public function messages(): HasMany { return $this->hasMany(SupportTicketMessage::class); }
}
