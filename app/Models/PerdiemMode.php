<?php

namespace App\Enums;

enum PerdiemMode: string
{
    case Manual = 'manual';
    case DailySalary = 'daily_salary';
    case ToDefine = 'to_define';   // à définir par la RH (participants hors département)

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Montant manuel',
            self::DailySalary => 'Salaire journalier',
            self::ToDefine => 'À définir par la RH',
        };
    }
}
