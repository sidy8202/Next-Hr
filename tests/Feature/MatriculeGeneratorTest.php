<?php

namespace Tests\Feature;

use App\Models\MatriculeSetting;
use App\Services\MatriculeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Concerns\CreatesTenants;
use Tests\TestCase;

class MatriculeGeneratorTest extends TestCase
{
    use CreatesTenants, RefreshDatabase;

    private function setupCompany(string $pattern = '{PREFIX}{SEQ:4}', string $scope = 'global'): array
    {
        $company = $this->makeCompany();
        $this->actingAs($this->makeUser($company, owner: true));

        MatriculeSetting::forceCreate([
            'company_id' => $company->id, 'pattern' => $pattern, 'prefix' => 'EM', 'counter_scope' => $scope, 'is_active' => true,
        ]);

        return [$company, $this->makeDepartment($company)];
    }

    public function test_cinq_recrues_recoivent_cinq_numeros_consecutifs(): void
    {
        [, $dept] = $this->setupCompany();
        $generator = app(MatriculeGenerator::class);
        $ids = [];

        DB::transaction(function () use ($generator, $dept, &$ids) {
            for ($i = 0; $i < 5; $i++) {
                $ids[] = $generator->next($dept);
            }
        });

        $this->assertSame(['EM0001', 'EM0002', 'EM0003', 'EM0004', 'EM0005'], $ids);
    }

    public function test_une_transaction_annulee_ne_laisse_aucun_trou(): void
    {
        [, $dept] = $this->setupCompany();
        $generator = app(MatriculeGenerator::class);

        DB::transaction(fn () => $generator->next($dept)); // EM0001

        try {
            DB::transaction(function () use ($generator, $dept) {
                $generator->next($dept); // EM0002 réservé... puis annulé
                throw new RuntimeException('échec');
            });
        } catch (RuntimeException) {
            // attendu
        }

        $this->assertSame('EM0002', DB::transaction(fn () => $generator->next($dept)));
    }

    public function test_compteur_par_annee_et_gabarit_avec_annee(): void
    {
        [, $dept] = $this->setupCompany('{YYYY}-{SEQ:3}', 'year');
        $generator = app(MatriculeGenerator::class);

        $a = DB::transaction(fn () => $generator->next($dept, now()->setYear(2025)->startOfYear()));
        $b = DB::transaction(fn () => $generator->next($dept, now()->setYear(2026)->startOfYear()));
        $c = DB::transaction(fn () => $generator->next($dept, now()->setYear(2026)->startOfYear()));

        $this->assertSame(['2025-001', '2026-001', '2026-002'], [$a, $b, $c]);
    }

    public function test_les_homonymes_sont_departages_sans_compteur(): void
    {
        [$company, $dept] = $this->setupCompany('{PRENOM}.{NOM}');
        $this->makeEmployee($dept, $this->makePosition($dept), 'awa.traore', 'Awa', 'Traoré');

        $m = DB::transaction(fn () => app(MatriculeGenerator::class)->next($dept, null, 'Awa', 'Traoré'));

        $this->assertSame('awa.traore2', $m);
    }
}
