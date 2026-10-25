<?php

namespace Tests\Feature;

use Tests\TestCase;

class PermissionMatrixTest extends TestCase
{
    public function test_chaque_modele_respecte_la_regle_lire(): void
    {
        foreach (config('permission_meta.templates') as $key => $t) {
            foreach ($t['permissions'] as $resource => [$letters, $scope]) {
                if (preg_match('/[cud]/', $letters)) {
                    $this->assertStringContainsString('r', $letters, "Modèle {$key}, ressource {$resource} : créer/modifier/supprimer sans lire.");
                }
            }
        }
    }
}
