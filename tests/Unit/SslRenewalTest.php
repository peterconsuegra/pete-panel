<?php

namespace Tests\Unit;

use App\Console\Commands\RenewSslCommand;
use App\Services\SslCertificate;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class SslRenewalTest extends TestCase
{
    public function test_a_certificate_is_due_within_the_window_or_when_none_is_served(): void
    {
        $now = Carbon::parse('2026-10-14 12:00:00', 'UTC');

        $this->assertTrue(RenewSslCommand::isDue(null, 30, $now));
        $this->assertTrue(RenewSslCommand::isDue(Carbon::parse('2026-10-13 15:58:25', 'UTC'), 30, $now)); // expired
        $this->assertTrue(RenewSslCommand::isDue(Carbon::parse('2026-11-01 15:03:50', 'UTC'), 30, $now)); // 18 days left
        $this->assertTrue(RenewSslCommand::isDue(Carbon::parse('2026-11-13 12:00:00', 'UTC'), 30, $now)); // exactly 30 days
        $this->assertFalse(RenewSslCommand::isDue(Carbon::parse('2026-11-13 12:00:01', 'UTC'), 30, $now));
        $this->assertFalse(RenewSslCommand::isDue(Carbon::parse('2027-01-01 12:20:54', 'UTC'), 30, $now));
    }

    public function test_only_a_certificate_naming_the_domain_covers_it(): void
    {
        $cert = [
            'subject'    => ['CN' => 'deploypete.com'],
            'extensions' => ['subjectAltName' => 'DNS:deploypete.com, DNS:www.deploypete.com'],
        ];
        $this->assertTrue(SslCertificate::covers($cert, 'deploypete.com'));
        $this->assertTrue(SslCertificate::covers($cert, 'WWW.deploypete.com'));
        $this->assertFalse(SslCertificate::covers($cert, 'ozonegroup.co'));

        $wildcard = ['subject' => ['CN' => '*.ozonegroup.co'], 'extensions' => []];
        $this->assertTrue(SslCertificate::covers($wildcard, 'demo.ozonegroup.co'));
        $this->assertFalse(SslCertificate::covers($wildcard, 'ozonegroup.co'));
        $this->assertFalse(SslCertificate::covers($wildcard, 'a.demo.ozonegroup.co'));

        $this->assertFalse(SslCertificate::covers(['subject' => []], 'deploypete.com'));
    }
}
