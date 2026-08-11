<?php

namespace App\Models\Security;

use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'label',
        'description',
        'is_system',
    ];


    protected static function booted(): void
    {
        static::creating(function (self $role): void {
            if (blank($role->name) && filled($role->label)) {
                $role->name = static::nextCustomKey((string) $role->label, (string) ($role->guard_name ?: 'web'));
            }
        });
    }

    public static function nextCustomKey(string $label, string $guardName = 'web'): string
    {
        $base = Str::of($label)->ascii()->slug('_')->lower()->toString();
        $base = filled($base) ? 'custom_'.$base : 'custom_role';
        $candidate = $base;
        $suffix = 2;

        while (static::query()->where('guard_name', $guardName)->where('name', $candidate)->exists()) {
            $candidate = $base.'_'.$suffix++;
        }

        return $candidate;
    }

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }
}
