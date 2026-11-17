<?php

namespace Tests\Unit;

use App\Services\Reports\SavedReportFilters;
use PHPUnit\Framework\TestCase;

class SavedReportFiltersTest extends TestCase
{
    public function test_valid_filters_are_kept(): void
    {
        $f = SavedReportFilters::sanitize([
            'preset' => 'custom', 'from' => '2026-01-01', 'to' => '2026-06-30', 'compare' => 'year', 'depts' => ['1', 2], 'positions' => ['7'],
            'contracts' => ['cdi'], 'genders' => ['F', 'M'], 'categories' => ['3'], 'metric' => 'gross',
        ]);

        $this->assertSame(['preset' => 'custom', 'from' => '2026-01-01', 'to' => '2026-06-30', 'compare' => 'year', 'depts' => [1, 2], 'positions' => [7], 'contracts' => ['CDI'], 'genders' => ['F', 'M'], 'categories' => [3], 'metric' => 'gross'], $f);
    }

    public function test_empty_input_gives_safe_defaults(): void
    {
        $f = SavedReportFilters::sanitize([]);

        $this->assertSame('12m', $f['preset']);
        $this->assertSame('previous', $f['compare']);
        $this->assertSame('employer_cost', $f['metric']);
        $this->assertSame([], $f['depts']);
        $this->assertSame('', $f['from']);
    }

    public function test_unknown_values_fall_back(): void
    {
        $f = SavedReportFilters::sanitize(['preset' => 'jamais', 'compare' => 'bad', 'metric' => 'salaire_de_x', 'from' => '2026-13-45', 'to' => 'demain']);

        $this->assertSame('12m', $f['preset']);
        $this->assertSame('previous', $f['compare']);
        $this->assertSame('employer_cost', $f['metric']);
        $this->assertSame('', $f['from']);
        $this->assertSame('', $f['to']);
    }

    public function test_identifier_lists_keep_only_positive_integers_once(): void
    {
        $f = SavedReportFilters::sanitize(['depts' => ['1', '2', 'abc', 3, -4, '5; DROP TABLE', 1, '0', 2.5]]);

        $this->assertSame([1, 2, 3], $f['depts']);
    }

    public function test_contract_types_are_cleaned(): void
    {
        $f = SavedReportFilters::sanitize(['contracts' => ['cdi', '  cdd ', '<script>', str_repeat('a', 40), 'SANS CONTRAT', 'cdi', 12]]);

        $this->assertSame(['CDI', 'CDD', 'SANS CONTRAT'], $f['contracts']);
    }

    public function test_genders_are_limited_to_known_keys(): void
    {
        $this->assertSame(['F', 'M', 'N'], SavedReportFilters::sanitize(['genders' => ['F', 'X', 'M', 'N', 5, 'F']])['genders']);
    }

    public function test_non_array_lists_are_ignored_and_extra_keys_dropped(): void
    {
        $f = SavedReportFilters::sanitize(['depts' => 'tous', 'positions' => 7, 'company_id' => 9, 'user_id' => 1, 'sql' => '1=1']);

        $this->assertSame([], $f['depts']);
        $this->assertSame([], $f['positions']);
        $this->assertSame(['preset', 'from', 'to', 'compare', 'depts', 'positions', 'contracts', 'genders', 'categories', 'metric'], array_keys($f));
    }

    public function test_lists_are_capped(): void
    {
        $f = SavedReportFilters::sanitize(['depts' => range(1, 200)]);

        $this->assertCount(50, $f['depts']);
    }
}
