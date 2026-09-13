<?php

namespace Dth\Marketing\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class UniqueSlug
{
    public static function forModel(Model $model, string $source, ?string $requested = null): string
    {
        $base = Str::slug(trim((string) ($requested ?: $source)));
        $base = $base !== '' ? $base : 'item';
        $candidate = $base;
        $counter = 2;

        while (
            $model->newQuery()
                ->where('slug', $candidate)
                ->when(
                    $model->exists,
                    fn ($query) => $query->whereKeyNot($model->getKey()),
                )
                ->exists()
        ) {
            $candidate = $base.'-'.$counter;
            $counter++;
        }

        return $candidate;
    }
}
