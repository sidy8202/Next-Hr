<?php

namespace Tests\Unit;

use App\Services\Reports\ReportCatalog;
use PHPUnit\Framework\TestCase;

class ReportCatalogTest extends TestCase
{
    public function test_every_fiche_has_its_required_fields(): void
    {
        foreach (ReportCatalog::all() as $key => $f) {
            $this->assertSame($key, $f['key']);
            $this->assertArrayHasKey($f['family'], ReportCatalog::FAMILIES, $key);
            $this->assertContains($f['status'], ['available', 'soon'], $key);
            $this->assertNotSame('', $f['title']);
            $this->assertNotSame('', $f['objective']);
            $this->assertNotSame('', $f['dataset']);
            $this->assertNotEmpty($f['filters'], $key);
            $this->assertNotEmpty($f['requires'], $key);
        }
    }

    public function test_available_and_savable_reports(): void
    {
        $this->assertSame(['synthesis', 'workforce', 'payroll_cost', 'grid_gaps'], ReportCatalog::availableKeys());
        $this->assertSame(['synthesis', 'workforce', 'payroll_cost', 'grid_gaps'], ReportCatalog::savableKeys());
    }

    public function test_announced_reports_carry_their_lot_and_an_illustrative_phrase(): void
    {
        foreach (ReportCatalog::all() as $f) {
            if ($f['status'] === 'soon') {
                $this->assertContains($f['lot'], ['18b', '19c']);
                $this->assertNotSame('', $f['example']);
                $this->assertFalse($f['savable']);
            }
        }
    }

    /** Les rapports de saisonnalité exigent 12 mois d'historique ; aucun autre n'a cette exigence. */
    public function test_seasonality_reports_need_twelve_months_of_history(): void
    {
        foreach (ReportCatalog::all() as $key => $f) {
            $this->assertSame(in_array($key, ['leave_seasonality', 'departure_seasonality'], true) ? 12 : null, $f['needs_history_months'], $key);
        }
    }

    public function test_payroll_reports_need_the_payroll_dashboard_right(): void
    {
        $hrOnly = fn (string $a, string $r): bool => $r === 'hr_reports';
        $both = fn (string $a, string $r): bool => in_array($r, ['hr_reports', 'dashboard_payroll'], true);
        $none = fn (string $a, string $r): bool => false;

        $this->assertTrue(ReportCatalog::canOpen('synthesis', $hrOnly));
        $this->assertFalse(ReportCatalog::canOpen('payroll_cost', $hrOnly));
        $this->assertFalse(ReportCatalog::canOpen('grid_gaps', $hrOnly));
        $this->assertTrue(ReportCatalog::canOpen('payroll_cost', $both));
        $this->assertTrue(ReportCatalog::canOpen('grid_gaps', $both));
        $this->assertFalse(ReportCatalog::canOpen('synthesis', $none));
    }

    public function test_announced_and_unknown_reports_cannot_be_opened(): void
    {
        $all = fn (string $a, string $r): bool => true;

        $this->assertFalse(ReportCatalog::canOpen('absenteeism', $all));
        $this->assertFalse(ReportCatalog::canOpen('inconnu', $all));
    }

    public function test_visible_keeps_only_openable_available_reports(): void
    {
        $hrOnly = fn (string $a, string $r): bool => $r === 'hr_reports';

        $this->assertSame(['synthesis', 'workforce'], array_keys(ReportCatalog::visible($hrOnly)));
        $this->assertSame(['synthesis', 'workforce', 'payroll_cost', 'grid_gaps'], array_keys(ReportCatalog::visible(fn () => true)));
    }

    public function test_read_action_is_required_not_just_the_resource(): void
    {
        $readOnlyWhenAskedForRead = function (string $action, string $resource): bool {
            return $action === 'read';
        };

        $this->assertTrue(ReportCatalog::canOpen('payroll_cost', $readOnlyWhenAskedForRead));
        $this->assertFalse(ReportCatalog::canOpen('payroll_cost', fn (string $a, string $r): bool => $a === 'update'));
    }
}
