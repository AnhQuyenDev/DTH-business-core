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
        return $this->function_key
            ? DepartmentFunction::tryFrom($this->function_key)
            : null;
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
