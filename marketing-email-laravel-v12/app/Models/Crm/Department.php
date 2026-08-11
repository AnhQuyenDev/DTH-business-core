<?php

namespace App\Models\Crm;

use App\Enums\Crm\DepartmentFunction;
use App\Models\Marketing\SendingAccount;
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
        return [
            'gray' => __('color.gray'),
            'primary' => __('color.primary'),
            'info' => __('color.info'),
            'success' => __('color.success'),
            'warning' => __('color.warning'),
            'danger' => __('color.danger'),
            'orange' => __('color.orange'),
            'lime' => __('color.lime'),
            'emerald' => __('color.emerald'),
            'teal' => __('color.teal'),
            'cyan' => __('color.cyan'),
            'sky' => __('color.sky'),
            'blue' => __('color.blue'),
            'indigo' => __('color.indigo'),
            'violet' => __('color.violet'),
            'purple' => __('color.purple'),
            'fuchsia' => __('color.fuchsia'),
            'pink' => __('color.pink'),
            'rose' => __('color.rose'),
        ];
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
