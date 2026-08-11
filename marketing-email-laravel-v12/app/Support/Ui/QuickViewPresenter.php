<?php

namespace App\Support\Ui;

use BackedEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class QuickViewPresenter
{
    /** @return array<int,array{label:string,value:string}> */
    public static function rows(Model $record): array
    {
        $hidden = [
            'id','password','remember_token','public_token','token','secret','config','config_encrypted',
            'metadata','payload','raw_payload','html_content','text_content','deleted_at','created_by','updated_by',
        ];

        return collect($record->getAttributes())
            ->reject(fn ($value, string $key): bool => in_array($key, $hidden, true)
                || str_ends_with($key, '_id')
                || str_contains($key, 'password')
                || str_contains($key, 'secret')
                || str_contains($key, 'token'))
            ->take(18)
            ->map(function ($value, string $key) use ($record): array {
                try { $value = $record->getAttribute($key); } catch (\Throwable) {}
                if ($value instanceof BackedEnum) $value = method_exists($value, 'label') ? $value->label() : $value->value;
                if ($value instanceof Carbon || $value instanceof \DateTimeInterface) $value = $value->format('d/m/Y H:i');
                if (is_bool($value)) $value = $value ? __('field.yes') : __('field.no');
                if (is_array($value) || is_object($value)) $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($value === null || $value === '') $value = '—';

                $fieldKey = 'field.'.$key;
                $translated = __($fieldKey);
                $label = $translated !== $fieldKey ? $translated : Str::headline($key);
                return ['label' => $label, 'value' => Str::limit((string) $value, 500)];
            })->values()->all();
    }
}
