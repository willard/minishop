<?php

namespace App\Enums;

enum PageTemplate: string
{
    case Default = 'default';
    case FullWidth = 'full_width';
    case Landing = 'landing';

    public function label(): string
    {
        return match ($this) {
            self::Default => 'Default',
            self::FullWidth => 'Full width',
            self::Landing => 'Landing',
        };
    }
}
