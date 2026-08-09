<?php

namespace App\Models\Marketing;

use App\Models\Crm\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class ContactList extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'type',
        'status',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $list): void {
            if (blank($list->slug) && filled($list->name)) {
                $list->slug = Str::slug($list->name);
            }
        });
    }

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_list_members', 'customer_list_id', 'customer_id')
            ->withPivot(['status', 'subscribed_at', 'unsubscribed_at'])
            ->withTimestamps();
    }
}
