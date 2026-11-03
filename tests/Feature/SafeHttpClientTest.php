<?php

namespace Tests\Feature;

use App\Services\Monitoring\BoundedStream;
use App\Services\Monitoring\PublicTarget;
use App\Services\Monitoring\ResponseTooLarge;
use App\Services\Monitoring\SafeHttpClient;
use GuzzleHttp\Handler\CurlFactory;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Support\Facades\Http;
use Tests\MonitoringTestCase;

class SafeHttpClientTest extends MonitoringTestCase
{
    public function test_transport_pins_dns_disables_proxy_redirects_and_enforces_tls_limits(): void
    {
        Http::fake(function ($request, $options) use (&$captured) {
            $captured = $options;

            return Http::response('ok');
        });
        $client = app(SafeHttpClient::class);
        $client->get(app(PublicTarget::class)->validate('https://example.com'));
        $options = $captured;
        $this->assertFalse($options['allow_redirects']);
        $this->assertTrue($options['verify']);
        $this->assertSame('', $options['proxy']);
        $this->assertFalse($options['decode_content']);
        $this->assertSame(8, $options['timeout']);
        $this->assertSame(3, $options['connect_timeout']);
        $this->assertSame(['example.com:443:93.184.215.14'], $options['curl'][CURLOPT_RESOLVE]);
        $this->assertTrue($options['curl'][CURLOPT_FRESH_CONNECT]);
        $this->assertTrue($options['curl'][CURLOPT_FORBID_REUSE]);
        $this->assertSame(['http', 'https'], $options['protocols']);
        $this->assertInstanceOf(BoundedStream::class, $options['sink']);

        $factory = new CurlFactory(0);
        $handle = $factory->create(new Request('GET', 'https://example.com'), $options);
        $this->assertInstanceOf(\CurlHandle::class, $handle->handle);
        $factory->release($handle);
        Http::assertSentCount(1);
    }

    public function test_public_ipv6_literal_uses_its_validated_address_directly(): void
    {
        Http::fake(function ($request, $options) {
            $this->assertArrayNotHasKey(CURLOPT_RESOLVE, $options['curl']);
            $factory = new CurlFactory(0);
            $handle = $factory->create($request->toPsrRequest(), $options);
            $this->assertInstanceOf(\CurlHandle::class, $handle->handle);
            $factory->release($handle);

            return Http::response('ok');
        });
        app(SafeHttpClient::class)->get(app(PublicTarget::class)->validate('https://[2606:4700:4700::1111]'));
        Http::assertSentCount(1);
    }

    public function test_chunked_response_cannot_exceed_sink_limit(): void
    {
        $sink = new BoundedStream(Utils::streamFor(''));
        $sink->write(str_repeat('x', BoundedStream::LIMIT));
        $this->assertSame(BoundedStream::LIMIT, $sink->getSize());
        $this->expectException(ResponseTooLarge::class);
        $sink->write('x');
    }

    public function test_declared_oversized_body_is_rejected_before_download(): void
    {
        Http::fake(function ($request, $options) use (&$captured) {
            $captured = $options;

            return Http::response('ok');
        });
        app(SafeHttpClient::class)->get(app(PublicTarget::class)->validate('https://example.com'));
        $options = $captured;
        $this->expectException(ResponseTooLarge::class);
        $options['on_headers'](new Response(200, ['Content-Length' => (string) (BoundedStream::LIMIT + 1)]));
    }
}
