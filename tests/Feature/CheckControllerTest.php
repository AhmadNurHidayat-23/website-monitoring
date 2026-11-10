<?php

namespace Tests\Feature;

use App\Models\Website;
use App\Services\Monitoring\WebsiteMonitor;
use Illuminate\Support\Facades\Http;
use Tests\MonitoringTestCase;

/**
 * Tests for CheckController – the manual "check now" endpoint.
 *
 * Verifies that the controller delegates to WebsiteMonitor, communicates
 * success/busy via session flash, and respects the throttle middleware.
 */
class CheckControllerTest extends MonitoringTestCase
{
    public function test_manual_check_runs_monitor_and_flashes_success_notice(): void
    {
        Http::fake(['*' => Http::response('ok', 200)]);
        $website = $this->website();

        $this->post(route('websites.check', $website))
            ->assertRedirect()
            ->assertSessionHas('notice', 'Pemeriksaan selesai. Hasil terbaru sudah tersimpan.');

        $this->assertSame('up', $website->fresh()->status);
        $this->assertDatabaseCount('checks', 1);
    }

    public function test_manual_check_records_down_status_on_http_error(): void
    {
        Http::fake(['*' => Http::response('error', 503)]);
        $website = $this->website();

        $this->post(route('websites.check', $website))
            ->assertRedirect()
            ->assertSessionHas('notice', 'Pemeriksaan selesai. Hasil terbaru sudah tersimpan.');

        $this->assertSame('down', $website->fresh()->status);
        $this->assertSame(503, $website->fresh()->http_status_code);
    }

    public function test_manual_check_flashes_busy_notice_when_monitor_returns_null(): void
    {
        $website = $this->website();

        // Mock the monitor to simulate an active check holding the lock
        $this->mock(WebsiteMonitor::class, function ($mock) {
            $mock->shouldReceive('check')->andReturn(null);
        });

        $this->post(route('websites.check', $website))
            ->assertRedirect()
            ->assertSessionHas('notice', 'Pemeriksaan website ini sedang berlangsung. Silakan tunggu.');

        // No check record should be created
        $this->assertDatabaseCount('checks', 0);
    }

    public function test_manual_check_requires_a_known_website(): void
    {
        $this->post('/websites/999/check')
            ->assertNotFound();
    }
}
