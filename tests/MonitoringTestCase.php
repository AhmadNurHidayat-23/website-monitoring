<?php

namespace Tests;

use App\Models\Website;
use App\Services\Monitoring\DnsResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

abstract class MonitoringTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->mock(DnsResolver::class)->shouldReceive('resolve')->andReturn(['93.184.215.14'])->byDefault();
    }

    protected function website(array $attributes = []): Website
    {
        return Website::create([...['name' => 'Contoh', 'url' => 'https://example.com', 'interval_minutes' => 5], ...$attributes]);
    }
}
