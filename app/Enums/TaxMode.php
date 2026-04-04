<?php

namespace App\Enums;

enum TaxMode: string
{
    case FlatRate = 'flat_rate';
    case ZoneBased = 'zone_based';
}
