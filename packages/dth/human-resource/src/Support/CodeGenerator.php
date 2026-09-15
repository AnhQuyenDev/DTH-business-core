<?php

namespace Dth\HumanResource\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class CodeGenerator
{
    /** @param class-string<Model> $model */
    public function next(string $model, string $column, string $prefix): string
    {
        $usesSoftDeletes = in_array(SoftDeletes::class, class_uses_recursive($model), true);
        $base = $usesSoftDeletes ? $model::withTrashed() : $model::query();
        $next = ((int) (clone $base)->max('id')) + 1;

        do {
            $code = $prefix.'-'.str_pad((string) $next++, 4, '0', STR_PAD_LEFT);
            $exists = (clone $base)->where($column, $code)->exists();
        } while ($exists);

        return $code;
    }
}
