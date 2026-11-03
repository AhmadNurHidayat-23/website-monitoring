<?php

namespace Tests\Feature;

use App\Services\Monitoring\DnsResolver;
use App\Services\Monitoring\ResponseTooLarge;
use App\Services\Monitoring\WebsiteMonitor;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\MonitoringTestCase;

class WebsiteMonitorTest extends MonitoringTestCase
{
    public function test_success_persists_status_code_duration_and_history(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);
        $website = $this->website();
        $check = app(WebsiteMonitor::class)->check($website);
        $this->assertSame('up', $check->status);
        $this->assertSame(200, $check->http_status_code);
        $this->assertGreaterThan(0, $check->response_time_ms);
        $this->assertNotNull($check->checked_at);
        $this->assertSame('up', $website->fresh()->status);
        $this->assertDatabaseCount('checks', 1);
    }

    public function test_http_error_is_down_with_original_status_code(): void
    {
        Http::fake(['*' => Http::response('unavailable', 503)]);
        $check = app(WebsiteMonitor::class)->check($this->website());
        $this->assertSame('down', $check->status);
        $this->assertSame(503, $check->http_status_code);
    }

    public function test_timeout_and_connection_errors_are_sanitized(): void
    {
        foreach (['cURL error 28: secret timeout detail', 'private server credential error'] as $message) {
            Http::swap(new Factory);
            Http::preventStrayRequests();
            Http::fake(fn () => throw new ConnectionException($message));
            $check = app(WebsiteMonitor::class)->check($this->website(['url' => 'https://example.com/'.md5($message)]));
            $this->assertSame('down', $check->status);
            $this->assertNull($check->http_status_code);
            $this->assertStringNotContainsString('secret', $check->error);
            $this->assertStringNotContainsString('credential', $check->error);
            $this->assertSame(str_contains($message, '28') ? 'Batas waktu koneksi terlampaui.' : 'Koneksi gagal atau sertifikat TLS tidak valid.', $check->error);
        }
    }

    public function test_public_redirect_is_followed_manually(): void
    {
        Http::fake(['https://example.com' => Http::response('', 302, ['Location' => '/health']), 'https://example.com/health' => Http::response('ok')]);
        $this->assertSame('up', app(WebsiteMonitor::class)->check($this->website())->status);
        Http::assertSentCount(2);
    }

    public function test_redirect_to_internal_ip_is_blocked_before_connection(): void
    {
        Http::fake(['*' => Http::response('', 302, ['Location' => 'http://169.254.169.254/latest/meta-data/'])]);
        $check = app(WebsiteMonitor::class)->check($this->website());
        $this->assertSame('down', $check->status);
        $this->assertStringContainsString('internal', $check->error);
        Http::assertSentCount(1);
    }

    public function test_dns_is_revalidated_on_each_hop(): void
    {
        $this->mock(DnsResolver::class)->shouldReceive('resolve')->twice()->andReturn(['93.184.215.14'], ['127.0.0.1']);
        Http::fake(['*' => Http::response('', 302, ['Location' => '/next'])]);
        $check = app(WebsiteMonitor::class)->check($this->website());
        $this->assertSame('down', $check->status);
        Http::assertSentCount(1);
    }

    public function test_dns_change_after_save_is_blocked_on_next_check(): void
    {
        $website = $this->website();
        $this->mock(DnsResolver::class)->shouldReceive('resolve')->once()->andReturn(['10.0.0.1']);
        $this->assertSame('down', app(WebsiteMonitor::class)->check($website)->status);
        Http::assertNothingSent();
    }

    public function test_redirect_loop_stops_after_three_redirects(): void
    {
        Http::fake(['*' => Http::response('', 302, ['Location' => '/loop'])]);
        $check = app(WebsiteMonitor::class)->check($this->website());
        $this->assertSame('down', $check->status);
        $this->assertStringContainsString('tiga', $check->error);
        Http::assertSentCount(4);
    }

    public function test_large_response_failure_is_saved_without_sensitive_detail(): void
    {
        Http::fake(fn () => throw new ResponseTooLarge('sensitive implementation details'));
        $check = app(WebsiteMonitor::class)->check($this->website());
        $this->assertSame('Respons melebihi batas 1 MiB.', $check->error);
        $this->assertSame('down', $check->status);
    }

    public function test_per_website_lock_prevents_duplicate_requests(): void
    {
        $website = $this->website();
        $lock = Cache::lock('monitor:website:'.$website->id, 90);
        $lock->get();
        try {
            $this->assertNull(app(WebsiteMonitor::class)->check($website));
            Http::assertNothingSent();
            $this->assertDatabaseCount('checks', 0);
        } finally {
            $lock->release();
        }
    }

    public function test_scheduler_checks_only_due_targets_and_force_is_available(): void
    {
        Http::fake(['*' => Http::response('ok')]);
        $this->website();
        $recent = $this->website(['url' => 'https://laravel.com']);
        $recent->forceFill(['checked_at' => now()])->save();
        $this->artisan('monitor:check')->expectsOutput('1 website diperiksa.')->assertSuccessful();
        $this->assertDatabaseCount('checks', 1);
        $this->artisan('monitor:check')->expectsOutput('0 website diperiksa.')->assertSuccessful();
        $this->artisan('monitor:check --force')->expectsOutput('2 website diperiksa.')->assertSuccessful();
        $this->assertDatabaseCount('checks', 3);
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains($event->command ?? '', 'monitor:check'));
        $this->assertSame('* * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }

    public function test_manual_checks_are_rate_limited_per_target(): void
    {
        Http::fake(['*' => Http::response('ok')]);
        $website = $this->website();
        $this->post(route('websites.check', $website))->assertRedirect();
        $this->post(route('websites.check', $website))->assertRedirect();
        $this->post(route('websites.check', $website))->assertStatus(429)->assertSee('Terlalu banyak');
        $this->assertDatabaseCount('checks', 2);
    }
}
