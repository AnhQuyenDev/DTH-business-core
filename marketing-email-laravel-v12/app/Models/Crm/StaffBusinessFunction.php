<?php

namespace App\Models\Crm;

use App\Enums\Crm\DepartmentFunction;
use App\Enums\Crm\PositionAuthority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\Security\RbacSyncService;

class StaffBusinessFunction extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function (self $assignment): void {
            if ($assignment->is_primary && $assignment->is_active) {
                static::query()
                    ->where('staff_id', $assignment->staff_id)
                    ->whereKeyNot($assignment->id)
                    ->where('is_primary', true)
                    ->update(['is_primary' => false]);
            }

            try {
                $assignment->loadMissing('staff.user');
                if ($assignment->staff?->user) {
                    app(RbacSyncService::class)->syncUserCanonicalRoles($assignment->staff->user);
                }
            } catch (\Throwable) {
                // RBAC tables/package may not be ready during first migration.
            }
        });

        static::deleted(function (self $assignment): void {
            try {
                $assignment->loadMissing('staff.user');
                if ($assignment->staff?->user) {
                    app(RbacSyncService::class)->syncUserCanonicalRoles($assignment->staff->user);
                }
            } catch (\Throwable) {
                // Safe during bootstrap.
            }
        });
    }

    protected $fillable = [
        'staff_id',
        'function_key',
        'authority_level',
        'is_primary',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'function_key' => DepartmentFunction::class,
            'authority_level' => PositionAuthority::class,
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
