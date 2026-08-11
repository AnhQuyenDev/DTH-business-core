<?php

namespace App\Models\Crm;

use App\Enums\Crm\DepartmentFunction;
use App\Models\Marketing\SendingAccount;
use App\Support\Ui\SystemColorPalette;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'function_key',
        'color',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $department): void {
            if (blank($department->code)) {
                $department->code = static::nextCode();
            }

        });

        static::saving(function (self $department): void {
            $department->name = trim((string) $department->name);
        });
    }

    public static function nextCode(): string
    {
        $next = (static::max('id') ?? 0) + 1;

        do {
            $code = 'DEPT-'.str_pad((string) $next++, 4, '0', STR_PAD_LEFT);
        } while (static::where('code', $code)->exists());

        return $code;
    }

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function sendingAccounts(): HasMany
    {
        return $this->hasMany(SendingAccount::class);
    }

    /**
     * Legacy compatibility only. From Configuration V2, Department describes
     * organizational structure and no longer grants a business capability.
     * StaffBusinessFunction is the authoritative source for new records.
     */
    public function function(): ?DepartmentFunction
    {
        if ($this->function_key) {
            return DepartmentFunction::tryFrom($this->function_key);
        }

        // Compatibility guard for standard departments created by old code,
        // tests or imports before function_key became mandatory in the UI.
        // Custom departments still need an explicit function_key.
        return match (strtolower((string) $this->code)) {
            'admin' => DepartmentFunction::Admin,
            'marketing' => DepartmentFunction::Marketing,
            'customer_service', 'customer-service', 'cskh' => DepartmentFunction::CustomerService,
            'sales', 'kinh_doanh' => DepartmentFunction::Sales,
            'finance', 'financial', 'accounting', 'tai_chinh' => DepartmentFunction::Finance,
            'technical', 'ky_thuat' => DepartmentFunction::Technical,
            default => null,
        };
    }

    public static function colorOptions(): array
    {
        return SystemColorPalette::options();
    }

    public static function options(): array
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->all();
    }

    public static function codeOptions(): array
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->pluck('name', 'code')
            ->all();
    }
}
