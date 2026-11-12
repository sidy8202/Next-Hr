<?php

namespace Tests\Unit;

use App\Services\Payroll\NetImportParser;
use PHPUnit\Framework\TestCase;

class NetImportParserTest extends TestCase
{
    public function test_it_reads_matricule_and_net_with_common_separators(): void
    {
        $r = NetImportParser::parse("EMP001;225000\nEMP002\t200049\nEMP003,180000\nEMP004:150000");

        $this->assertSame(['EMP001' => 225000, 'EMP002' => 200049, 'EMP003' => 180000, 'EMP004' => 150000], $r['pairs']);
        $this->assertSame([], $r['invalid']);
    }

    public function test_spaces_and_thousand_separators_in_the_amount_are_ignored(): void
    {
        $r = NetImportParser::parse("EMP001;225 000\nEMP002;200,049\nEMP003 ; 1 250 000 FCFA");

        $this->assertSame(['EMP001' => 225000, 'EMP002' => 200049, 'EMP003' => 1250000], $r['pairs']);
    }

    public function test_blank_lines_are_skipped_and_bad_lines_are_reported(): void
    {
        $r = NetImportParser::parse("\nEMP001;225000\n\nEMP002\nEMP003;abc\n;5000\nEMP004;0\n");

        $this->assertSame(['EMP001' => 225000], $r['pairs']);
        $this->assertSame(['EMP002', 'EMP003;abc', ';5000', 'EMP004;0'], $r['invalid']);
    }

    public function test_windows_line_endings_work(): void
    {
        $r = NetImportParser::parse("EMP001;100000\r\nEMP002;200000\r\n");

        $this->assertSame(['EMP001' => 100000, 'EMP002' => 200000], $r['pairs']);
    }

    public function test_the_last_value_wins_for_a_repeated_matricule(): void
    {
        $this->assertSame(['EMP001' => 120000], NetImportParser::parse("EMP001;100000\nEMP001;120000")['pairs']);
    }
}
