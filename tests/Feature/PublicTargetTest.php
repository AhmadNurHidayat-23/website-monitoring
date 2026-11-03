<?php

namespace Tests\Feature;

use App\Services\Monitoring\DnsResolver;
use App\Services\Monitoring\PublicTarget;
use App\Services\Monitoring\UnsafeTarget;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\MonitoringTestCase;

class PublicTargetTest extends MonitoringTestCase
{
    public static function unsafeUrls(): array
    {
        return array_map(fn ($url) => [$url], [
            'http://localhost', 'http://foo.local', 'http://internal', 'http://127.0.0.1',
            'http://10.0.0.1', 'http://172.16.0.1', 'http://192.168.1.1', 'http://169.254.169.254',
            'http://100.64.0.1', 'http://0.0.0.0', 'http://224.0.0.1', 'http://255.255.255.255',
            'http://192.0.2.1', 'http://198.18.0.1', 'http://[::1]', 'http://[fe80::1]',
            'http://[fc00::1]', 'http://[::ffff:127.0.0.1]', 'http://[64:ff9b::7f00:1]',
            'http://[2002:7f00:1::]', 'http://[2001:db8::1]', 'file:///etc/passwd',
            'gopher://example.com', 'ftp://example.com', 'https://example.com:8080',
            'http://example.com:443', 'https://example.com:80', 'https://user:pass@example.com',
            'https://example.com#fragment', 'http://2130706433', 'http://0177.0.0.1',
            'http://0x7f000001', 'http://127.1', 'https://example.com\\@127.0.0.1',
            "https://example.com\n", 'https://example.com.', 'https://foo.internal',
        ]);
    }

    #[DataProvider('unsafeUrls')]
    public function test_unsafe_urls_are_rejected(string $url): void
    {
        $this->expectException(UnsafeTarget::class);
        app(PublicTarget::class)->validate($url);
    }

    public function test_all_dns_answers_must_be_public(): void
    {
        $this->mock(DnsResolver::class)->shouldReceive('resolve')->once()->andReturn(['93.184.215.14', '10.0.0.1']);
        $this->expectException(UnsafeTarget::class);
        app(PublicTarget::class)->validate('https://example.com');
    }

    public function test_unresolvable_domains_fail_closed(): void
    {
        $this->mock(DnsResolver::class)->shouldReceive('resolve')->once()->andReturn([]);
        $this->expectException(UnsafeTarget::class);
        app(PublicTarget::class)->validate('https://example.com');
    }

    public function test_public_ipv4_ipv6_and_domain_are_accepted(): void
    {
        foreach (['https://example.com/path?q=1', 'http://1.1.1.1', 'https://[2606:4700:4700::1111]'] as $url) {
            $this->assertSame($url, app(PublicTarget::class)->validate($url)['url']);
        }
    }
}
