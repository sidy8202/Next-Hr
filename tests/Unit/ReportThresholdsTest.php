<?php

namespace Tests\Unit;

use App\Services\Reports\ReportThresholds;
use PHPUnit\Framework\TestCase;

class ReportThresholdsTest extends TestCase
{
    public function test_defaults(): void
    {
        $this->assertSame(['cost_change_notice_percent' => 5, 'cost_trend_months' => 3, 'dept_concentration_percent' => 40, 'grid_gap_alert_percent' => 10], ReportThresholds::defaults());
    }

    /** Une valeur enregistrée invalide (hors bornes, non numérique, clé inconnue) retombe sur le défaut. */
    public function test_merge_keeps_valid_values_and_ignores_the_rest(): void
    {
        $m = ReportThresholds::merge(['cost_change_notice_percent' => '8', 'cost_trend_months' => '99', 'dept_concentration_percent' => 'abc', 'inconnu' => '3', 'grid_gap_alert_percent' => 15]);

        $this->assertSame(8, $m['cost_change_notice_percent']);
        $this->assertSame(3, $m['cost_trend_months']);
        $this->assertSame(40, $m['dept_concentration_percent']);
        $this->assertSame(15, $m['grid_gap_alert_percent']);
        $this->assertArrayNotHasKey('inconnu', $m);
    }

    public function test_sanitize_value_bounds(): void
    {
        $this->assertSame(12, ReportThresholds::sanitizeValue('cost_trend_months', '12'));
        $this->assertSame(1, ReportThresholds::sanitizeValue('cost_trend_months', '1'));
        $this->assertNull(ReportThresholds::sanitizeValue('cost_trend_months', '0'));
        $this->assertNull(ReportThresholds::sanitizeValue('cost_trend_months', '13'));
        $this->assertSame(5, ReportThresholds::sanitizeValue('cost_change_notice_percent', ' 5 '));
        $this->assertSame(7, ReportThresholds::sanitizeValue('cost_change_notice_percent', 7));
    }

    public function test_sanitize_value_refuses_non_integers_and_unknown_keys(): void
    {
        $this->assertNull(ReportThresholds::sanitizeValue('cost_change_notice_percent', '5.5'));
        $this->assertNull(ReportThresholds::sanitizeValue('cost_change_notice_percent', '-5'));
        $this->assertNull(ReportThresholds::sanitizeValue('cost_change_notice_percent', ''));
        $this->assertNull(ReportThresholds::sanitizeValue('cost_change_notice_percent', ['5']));
        $this->assertNull(ReportThresholds::sanitizeValue('inconnu', '5'));
    }

    public function test_thresholds_of_one_report(): void
    {
        $this->assertSame(['grid_gap_alert_percent'], array_keys(ReportThresholds::forReport('grid_gaps')));
        $this->assertSame(['cost_change_notice_percent', 'cost_trend_months', 'dept_concentration_percent'], array_keys(ReportThresholds::forReport('payroll_cost')));
        $this->assertSame([], ReportThresholds::forReport('synthesis'));
    }

    public function test_small_group_guard_is_not_a_threshold(): void
    {
        foreach (array_keys(ReportThresholds::DEFINITIONS) as $key) {
            $this->assertStringNotContainsString('group', $key);
        }
    }
}
