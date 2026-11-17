<?php

namespace Tests\Unit;

use App\Services\Reports\HistoryGuard;
use PHPUnit\Framework\TestCase;

class HistoryGuardTest extends TestCase
{
    public function test_months_of_history_counts_both_ends(): void
    {
        $this->assertSame(12, HistoryGuard::monthsOfHistory('2026-01', '2026-12'));
        $this->assertSame(1, HistoryGuard::monthsOfHistory('2026-05', '2026-05'));
        $this->assertSame(13, HistoryGuard::monthsOfHistory('2025-11', '2026-11'));
    }

    public function test_empty_or_inconsistent_history_is_zero(): void
    {
        $this->assertSame(0, HistoryGuard::monthsOfHistory(null, '2026-11'));
        $this->assertSame(0, HistoryGuard::monthsOfHistory('2026-01', null));
        $this->assertSame(0, HistoryGuard::monthsOfHistory('2026-12', '2026-01'));
        $this->assertSame(0, HistoryGuard::monthsOfHistory('2026-13', '2026-12'));
        $this->assertSame(0, HistoryGuard::monthsOfHistory('abc', '2026-12'));
    }

    /** Sous 12 mois, aucun rapport ne parle de saisonnalité. */
    public function test_seasonality_needs_twelve_months(): void
    {
        $this->assertFalse(HistoryGuard::allowsSeasonality(0));
        $this->assertFalse(HistoryGuard::allowsSeasonality(11));
        $this->assertTrue(HistoryGuard::allowsSeasonality(12));
        $this->assertTrue(HistoryGuard::allowsSeasonality(30));
    }

    public function test_note_only_when_seasonality_is_refused(): void
    {
        $this->assertSame('Pas de lecture saisonnière : l\'historique couvre 8 mois, il en faut au moins 12.', HistoryGuard::seasonalityNote(8));
        $this->assertNull(HistoryGuard::seasonalityNote(12));
    }
}
