<?php

namespace App\Profiles\Enums;

enum WorkType: string
{
    case Products = 'products';
    case Services = 'services';
    case Both = 'both';

    public function label(): string
    {
        return match ($this) {
            self::Products => 'products',
            self::Services => 'services',
            self::Both => 'products and services',
        };
    }
}
