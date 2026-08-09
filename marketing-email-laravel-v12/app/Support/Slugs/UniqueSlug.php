<?php

namespace App\Support\Slugs;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class UniqueSlug
{
    public static function make(Model $model, string $source, string $column = 'slug'): string
    {
        $base = Str::slug($source);
        $base = $base !== '' ? $base : Str::lower(Str::random(8));
        $candidate = $base;
        $suffix = 2;

        $query = $model->newQueryWithoutScopes();

        while ((clone $query)
            ->where($column, $candidate)
            ->when($model->exists, fn ($q) => $q->whereKeyNot($model->getKey()))
            ->exists()) {
            $candidate = $base.'-'.$suffix;
            $suffix++;
        }

        return $candidate;
    }
}
