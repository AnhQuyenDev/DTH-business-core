<?php

namespace App\Models\Marketing;

use App\Models\Crm\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactListMember extends Model
{
    use HasFactory;

    protected $table = 'customer_list_members';

    protected $fillable = [
        'customer_id',
        'customer_list_id',
        'status',
        'subscribed_at',
        'unsubscribed_at',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function list(): BelongsTo
    {
        return $this->belongsTo(ContactList::class, 'customer_list_id');
    }
}
