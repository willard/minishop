<?php

namespace App\Enums;

enum MenuLocation: string
{
    case HeaderPrimary = 'header_primary';
    case FooterAbout = 'footer_about';
    case FooterLegal = 'footer_legal';
    case FooterShop = 'footer_shop';

    public function label(): string
    {
        return match ($this) {
            self::HeaderPrimary => 'Header (primary)',
            self::FooterAbout => 'Footer: About',
            self::FooterLegal => 'Footer: Legal',
            self::FooterShop => 'Footer: Shop',
        };
    }
}
