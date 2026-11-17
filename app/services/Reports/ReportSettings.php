<?php

namespace App\Services\Reports;

use App\Models\ReportSetting;

/** Seuils de lecture enregistrés par entreprise (table report_settings). Les valeurs invalides sont ignorées à la lecture. */
final class ReportSettings
{
    /** @return array<string, int> seuils effectifs (défauts + valeurs enregistrées valides) */
    public static function thresholds(int $companyId): array
    {
        return ReportThresholds::merge(ReportSetting::where('company_id', $companyId)->pluck('value', 'key')->all());
    }

    /** Enregistre un seuil déjà validé par ReportThresholds::sanitizeValue(). */
    public static function save(int $companyId, string $key, int $value, ?int $userId): void
    {
        $row = ReportSetting::where('company_id', $companyId)->where('key', $key)->first() ?? new ReportSetting(['key' => $key]);
        $row->company_id = $companyId;
        $row->value = (string) $value;
        $row->updated_by = $userId;
        $row->save();
    }
}
