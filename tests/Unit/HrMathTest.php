<?php

namespace Tests\Unit;

use App\Services\HrMath;
use PHPUnit\Framework\TestCase;

class HrMathTest extends TestCase
{
    public function test_turnover_uses_the_average_headcount(): void
    {
        $this->assertSame(10.0, HrMath::turnover(5, 50, 50));
        $this->assertSame(8.3, HrMath::turnover(5, 55, 65));
        $this->assertNull(HrMath::turnover(2, 0, 0));
    }

    public function test_years_between_dates(): void
    {
        $this->assertEqualsWithDelta(1.0, HrMath::years('2025-01-01', '2026-01-01'), 0.01);
        $this->assertSame(0.0, HrMath::years('2026-01-01', '2025-01-01'));
        $this->assertSame(0.0, HrMath::years('n’importe quoi', '2025-01-01'));
    }

    public function test_age_and_outliers(): void
    {
        $this->assertSame(30, HrMath::ageAt('1996-03-10', '2026-11-02'));
        $this->assertNull(HrMath::ageAt(null, '2026-11-02'));
        $this->assertNull(HrMath::ageAt('2026-01-01', '2026-11-02'));   // trop jeune : donnée aberrante
    }

    public function test_gender_normalisation(): void
    {
        foreach (['F', 'f', 'Femme', 'female', 'Féminin'] as $g) { $this->assertSame('F', HrMath::genderKey($g)); }
        foreach (['M', 'H', 'Homme', 'male', 'masculin'] as $g) { $this->assertSame('M', HrMath::genderKey($g)); }
        $this->assertSame('N', HrMath::genderKey(null));
        $this->assertSame('N', HrMath::genderKey('autre'));
    }

    public function test_buckets(): void
    {
        $this->assertSame(0, HrMath::seniorityIndex(0.5));
        $this->assertSame(1, HrMath::seniorityIndex(1.0));
        $this->assertSame(4, HrMath::seniorityIndex(12));
        $this->assertSame(1, HrMath::ageIndex(25));
        $this->assertSame(5, HrMath::ageIndex(null));
        $this->assertSame(4, HrMath::ageIndex(60));
    }

    public function test_termination_labels_never_lose_a_value(): void
    {
        $this->assertSame('Démission', HrMath::terminationLabel('resignation'));
        $this->assertSame('Licenciement', HrMath::terminationLabel('DISMISSAL'));
        $this->assertSame('Fin de contrat', HrMath::terminationLabel('end_of_contract'));
        $this->assertSame('Non précisé', HrMath::terminationLabel(null));
        $this->assertSame('Fin de stage', HrMath::terminationLabel('fin_de_stage'));
    }
}
