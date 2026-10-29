<?php

namespace App\Enums;

enum MissionRole: string
{
    case Member = 'member';
    case Chief = 'chief';
    case Driver = 'driver';

    public function label(): string
    {
        return match ($this) {
            self::Member => 'Missionnaire',
            self::Chief => 'Chef de mission',
            self::Driver => 'Chauffeur',
        };
    }
}
