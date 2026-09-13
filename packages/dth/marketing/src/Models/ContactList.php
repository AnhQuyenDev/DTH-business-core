<?php

namespace Dth\Marketing\Models;

use Dth\Marketing\Support\UniqueSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContactList extends Model
{
    protected $table = 'marketing_contact_lists';

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (self $list): void {
            if ($list->isDirty('slug') || trim((string) $list->slug) === '') {
                $list->slug = UniqueSlug::forModel(
                    $list,
                    (string) $list->name,
                    $list->slug,
                );
            }
        });
    }

    public function members(): HasMany
    {
        return $this->hasMany(ContactListMember::class, 'contact_list_id');
    }
}
