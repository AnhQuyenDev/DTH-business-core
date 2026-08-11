<?php

namespace App\Enums\Sales;

enum CustomerResponseChannel: string
{
    case PublicLink = 'public_link';
    case Phone = 'phone';
    case Zalo = 'zalo';
    case Messenger = 'messenger';
    case Email = 'email';
    case Meeting = 'meeting';
    case Other = 'other';

    public function label(): string { return __('v1.response_channel.'.$this->value); }

    /** @return array<string,string> */
    public static function assistedOptions(): array
    {
        return collect(self::cases())->reject(fn (self $c) => $c === self::PublicLink)
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
