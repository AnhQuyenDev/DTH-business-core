<?php

namespace Dth\HumanResource\Models;

use Dth\HumanResource\Enums\BusinessFunction;
use Dth\HumanResource\Support\CodeGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Department extends Model
{
    protected $table = 'hr_departments';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'function_key' => BusinessFunction::class,
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $department): void {
            if (blank($department->code)) {
                $department->code = app(CodeGenerator::class)->next(
                    self::class,
                    'code',
                    (string) config('dth-human-resource.department_code_prefix', 'DEPT'),
                );
            }
        });

        static::saving(function (self $department): void {
            $department->name = trim((string) $department->name);
        });

        static::deleting(function (self $department): void {
            $count = $department->employees()->count();
            if ($count > 0) {
                throw ValidationException::withMessages([
                    'department' => 'Không thể xóa phòng ban đang được sử dụng bởi '.$count.' nhân viên. Hãy chuyển nhân viên sang phòng ban khác trước.',
                ]);
            }
        });
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'department_id');
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
}
