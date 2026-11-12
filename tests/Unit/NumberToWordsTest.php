<?php

namespace Tests\Unit;

use App\Services\Payroll\NumberToWords;
use PHPUnit\Framework\TestCase;

class NumberToWordsTest extends TestCase
{
    /** @return array<string, array{0: int, 1: string}> */
    public static function cases(): array
    {
        return [
            'zéro' => [0, 'zéro'],
            'un' => [1, 'un'],
            'seize' => [16, 'seize'],
            'dix-sept' => [17, 'dix-sept'],
            'vingt et un' => [21, 'vingt et un'],
            'vingt-deux' => [22, 'vingt-deux'],
            'soixante' => [60, 'soixante'],
            'soixante et un' => [61, 'soixante et un'],
            'soixante-dix' => [70, 'soixante-dix'],
            'soixante et onze' => [71, 'soixante et onze'],
            'soixante-douze' => [72, 'soixante-douze'],
            'quatre-vingts' => [80, 'quatre-vingts'],
            'quatre-vingt-un' => [81, 'quatre-vingt-un'],
            'quatre-vingt-dix' => [90, 'quatre-vingt-dix'],
            'quatre-vingt-onze' => [91, 'quatre-vingt-onze'],
            'quatre-vingt-dix-neuf' => [99, 'quatre-vingt-dix-neuf'],
            'cent' => [100, 'cent'],
            'cent un' => [101, 'cent un'],
            'deux cents' => [200, 'deux cents'],
            'deux cent un' => [201, 'deux cent un'],
            'trois cent quatre-vingts' => [380, 'trois cent quatre-vingts'],
            'mille' => [1000, 'mille'],
            'mille un' => [1001, 'mille un'],
            'deux mille' => [2000, 'deux mille'],
            'quatre-vingt mille' => [80000, 'quatre-vingt mille'],
            'deux cent mille' => [200000, 'deux cent mille'],
            'bulletin 2 : net à payer' => [200049, 'deux cent mille quarante-neuf'],
            'bulletin 2 : brut' => [286919, 'deux cent quatre-vingt-six mille neuf cent dix-neuf'],
            'bulletin 1 : net' => [225000, 'deux cent vingt-cinq mille'],
            'un million' => [1000000, 'un million'],
            'deux millions' => [2000000, 'deux millions'],
            'un million deux cent mille' => [1200000, 'un million deux cent mille'],
            'deux cents millions' => [200000000, 'deux cents millions'],
            'un milliard' => [1000000000, 'un milliard'],
        ];
    }

    /** @dataProvider cases */
    public function test_spelling(int $n, string $expected): void
    {
        $this->assertSame($expected, NumberToWords::fr($n));
    }

    public function test_capitalized(): void
    {
        $this->assertSame('Deux cent mille quarante-neuf', NumberToWords::frCapitalized(200049));
    }

    public function test_negative(): void
    {
        $this->assertSame('moins quarante-cinq', NumberToWords::fr(-45));
    }
}
