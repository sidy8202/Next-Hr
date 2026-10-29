<?php

namespace App\Enums;

enum MissionStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';      // en attente de la RH
    case Returned = 'returned';        // renvoyée au demandeur pour correction
    case HrApproved = 'hr_approved';   // validée RH, en attente du DG
    case Approved = 'approved';        // approuvée par le DG
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Brouillon',
            self::Submitted => 'En attente RH',
            self::Returned => 'À corriger',
            self::HrApproved => 'En attente DG',
            self::Approved => 'Approuvée',
            self::Rejected => 'Rejetée',
            self::Cancelled => 'Annulée',
        };
    }

    /** Le demandeur peut modifier la mission seulement dans ces états. */
    public function isEditableByRequester(): bool
    {
        return in_array($this, [self::Draft, self::Returned], true);
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Approved, self::Rejected, self::Cancelled], true);
    }
}
