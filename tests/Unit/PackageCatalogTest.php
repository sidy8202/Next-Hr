<?php

namespace Tests\Unit;

use App\Services\Payroll\PackageCatalog;
use PHPUnit\Framework\TestCase;

class PackageCatalogTest extends TestCase
{
    protected function row(int $id, string $code, int $version, string $status, bool $empty = false): array
    {
        return ['id' => $id, 'code' => $code, 'version' => $version, 'status' => $status, 'empty' => $empty];
    }

    public function test_the_latest_validated_version_wins_over_a_newer_unvalidated_one(): void
    {
        $offered = PackageCatalog::offered([
            $this->row(1, 'commerce', 1, 'validated'),
            $this->row(2, 'commerce', 2, 'validated'),
            $this->row(3, 'commerce', 3, 'to_validate'),
        ]);

        $this->assertSame([2], array_column($offered, 'id'));
    }

    public function test_without_a_validated_version_the_latest_unvalidated_one_is_offered(): void
    {
        $offered = PackageCatalog::offered([$this->row(1, 'transit', 1, 'to_validate'), $this->row(2, 'transit', 2, 'to_validate')]);

        $this->assertSame([2], array_column($offered, 'id'));
        $this->assertSame('to_validate', $offered[0]['status']);
    }

    public function test_empty_shells_are_never_offered(): void
    {
        $offered = PackageCatalog::offered([
            $this->row(1, 'btp', 1, 'to_validate', true),
            $this->row(2, 'mines', 1, 'validated', true),
            $this->row(3, 'commerce', 1, 'to_validate'),
        ]);

        $this->assertSame(['commerce'], array_column($offered, 'code'));
    }

    public function test_an_empty_newer_version_does_not_hide_a_filled_older_one(): void
    {
        $offered = PackageCatalog::offered([$this->row(1, 'commerce', 1, 'validated'), $this->row(2, 'commerce', 2, 'validated', true)]);

        $this->assertSame([1], array_column($offered, 'id'));
    }

    public function test_packages_are_sorted_by_code(): void
    {
        $offered = PackageCatalog::offered([$this->row(1, 'transit', 1, 'validated'), $this->row(2, 'commerce', 1, 'validated'), $this->row(3, 'banques', 1, 'to_validate')]);

        $this->assertSame(['banques', 'commerce', 'transit'], array_column($offered, 'code'));
    }

    public function test_no_rows_means_no_package(): void
    {
        $this->assertSame([], PackageCatalog::offered([]));
    }
}
