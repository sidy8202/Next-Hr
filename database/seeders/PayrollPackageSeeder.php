<?php

namespace Database\Seeders;

use App\Models\PayrollPackage;
use App\Services\Payroll\PackageStarterData;
use Illuminate\Database\Seeder;

/**
 * Crée les paquets de départ (version 1, « à valider »). Idempotent : un code déjà présent n'est jamais touché.
 * Aussi disponible depuis l'écran plateforme « Paquets par secteur » (bouton « Charger les paquets de départ »).
 */
class PayrollPackageSeeder extends Seeder
{
    public function run(): void
    {
        static::seed();
    }

    /** @return int nombre de paquets créés */
    public static function seed(?int $userId = null): int
    {
        $created = 0;

        foreach (PackageStarterData::all() as $row) {
            if (PayrollPackage::where('code', $row['code'])->exists()) {
                continue;
            }

            PayrollPackage::create([
                'code' => $row['code'], 'name' => $row['name'], 'sector' => $row['sector'], 'version' => 1,
                'status' => 'to_validate', 'description' => $row['description'], 'source' => $row['source'],
                'payload' => $row['payload'], 'created_by' => $userId,
            ]);
            $created++;
        }

        return $created;
    }
}
