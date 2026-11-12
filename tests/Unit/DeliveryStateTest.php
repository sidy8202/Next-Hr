<?php

namespace Tests\Unit;

use App\Services\Payroll\DeliveryState;
use PHPUnit\Framework\TestCase;

class DeliveryStateTest extends TestCase
{
    public function test_a_channel_without_a_valid_address(): void
    {
        $this->assertSame('no_address', DeliveryState::channel(null, null)['state']);
        $this->assertSame('no_address', DeliveryState::channel(null, '')['state']);
        $this->assertSame('no_address', DeliveryState::channel(['status' => 'sent'], 'pas-un-email')['state']);
    }

    public function test_a_valid_address_never_used_is_not_sent(): void
    {
        $s = DeliveryState::channel(null, 'a@exemple.ml');

        $this->assertSame('not_sent', $s['state']);
        $this->assertSame('a@exemple.ml', $s['address']);
    }

    public function test_a_sent_channel_keeps_its_dates(): void
    {
        $s = DeliveryState::channel(['status' => 'sent', 'sent_at' => '2026-12-01 09:00:00', 'opened_at' => '2026-12-01 10:30:00'], 'a@exemple.ml');

        $this->assertSame('sent', $s['state']);
        $this->assertSame('2026-12-01 09:00:00', $s['at']);
        $this->assertSame('2026-12-01 10:30:00', $s['opened_at']);
    }

    public function test_a_failed_channel_keeps_its_error(): void
    {
        $s = DeliveryState::channel(['status' => 'failed', 'error' => 'Connection refused'], 'a@exemple.ml');

        $this->assertSame('failed', $s['state']);
        $this->assertSame('Connection refused', $s['error']);
    }

    public function test_overall_state_from_the_two_email_channels(): void
    {
        $sent = ['state' => 'sent'];
        $failed = ['state' => 'failed'];
        $none = ['state' => 'not_sent'];
        $absent = ['state' => 'no_address'];

        $this->assertSame('sent', DeliveryState::overall([$sent, $sent]));
        $this->assertSame('sent', DeliveryState::overall([$sent, $absent]));           // une seule adresse, bien envoyée
        $this->assertSame('partial', DeliveryState::overall([$sent, $failed]));
        $this->assertSame('partial', DeliveryState::overall([$sent, $none]));
        $this->assertSame('failed', DeliveryState::overall([$failed, $none]));
        $this->assertSame('not_sent', DeliveryState::overall([$none, $absent]));
        $this->assertSame('no_address', DeliveryState::overall([$absent, $absent]));
    }

    public function test_summary_counts_sent_failed_and_seen(): void
    {
        $s = DeliveryState::summarize([
            ['overall' => 'sent', 'seen_app' => true, 'opened_email' => true],
            ['overall' => 'sent', 'seen_app' => false, 'opened_email' => true],
            ['overall' => 'partial', 'seen_app' => false, 'opened_email' => false],
            ['overall' => 'failed', 'seen_app' => false, 'opened_email' => false],
            ['overall' => 'not_sent', 'seen_app' => false, 'opened_email' => false],
            ['overall' => 'no_address', 'seen_app' => false, 'opened_email' => false],
        ]);

        $this->assertSame(6, $s['total']);
        $this->assertSame(2, $s['sent']);
        $this->assertSame(1, $s['partial']);
        $this->assertSame(1, $s['failed']);
        $this->assertSame(1, $s['not_sent']);
        $this->assertSame(1, $s['no_address']);
        $this->assertSame(1, $s['seen_app']);
        $this->assertSame(2, $s['opened_email']);
    }
}
