<?php

namespace Tests\Unit;

use App\Models\Check;
use App\Models\Website;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for the Website and Check models.
 *
 * Covers status helpers, the isDue() scheduling logic, query scopes,
 * and the Check model's own status helpers — all without an HTTP layer.
 */
class WebsiteModelTest extends TestCase
{
    use RefreshDatabase;

    private function website(array $attrs = []): Website
    {
        static $counter = 0;
        $counter++;
        return Website::create(array_merge([
            'name'             => 'Contoh',
            'url'              => "https://example{$counter}.com",
            'interval_minutes' => 5,
        ], $attrs));
    }

    // ── Website status helpers ───────────────────────────────────────────────

    public function test_status_label_returns_correct_indonesian_strings(): void
    {
        $website = $this->website();

        $website->status = 'up';
        $this->assertSame('Up', $website->statusLabel());

        $website->status = 'down';
        $this->assertSame('Down', $website->statusLabel());

        $website->status = 'pending';
        $this->assertSame('Belum Diperiksa', $website->statusLabel());

        $website->status = null;
        $this->assertSame('Belum Diperiksa', $website->statusLabel());
    }

    public function test_is_up_is_true_only_for_up_status(): void
    {
        $website = $this->website();

        foreach (['down', 'pending', null] as $status) {
            $website->status = $status;
            $this->assertFalse($website->isUp(), "Expected isUp() false for status '{$status}'.");
        }

        $website->status = 'up';
        $this->assertTrue($website->isUp());
    }

    public function test_is_down_is_true_only_for_down_status(): void
    {
        $website = $this->website();

        foreach (['up', 'pending', null] as $status) {
            $website->status = $status;
            $this->assertFalse($website->isDown(), "Expected isDown() false for status '{$status}'.");
        }

        $website->status = 'down';
        $this->assertTrue($website->isDown());
    }

    public function test_is_pending_covers_pending_status_and_null(): void
    {
        $website = $this->website();

        foreach (['up', 'down'] as $status) {
            $website->status = $status;
            $this->assertFalse($website->isPending(), "Expected isPending() false for status '{$status}'.");
        }

        $website->status = 'pending';
        $this->assertTrue($website->isPending());

        $website->status = null;
        $this->assertTrue($website->isPending());
    }

    // ── isDue() scheduling logic ─────────────────────────────────────────────

    public function test_is_due_returns_true_when_never_checked(): void
    {
        $website = $this->website(['interval_minutes' => 5]);
        $website->checked_at = null;

        $this->assertTrue($website->isDue());
    }

    public function test_is_due_returns_false_when_checked_within_interval(): void
    {
        $website = $this->website(['interval_minutes' => 10]);
        $website->checked_at = now()->subMinutes(5);

        $this->assertFalse($website->isDue());
    }

    public function test_is_due_returns_true_when_interval_has_elapsed(): void
    {
        $website = $this->website(['interval_minutes' => 5]);
        $website->checked_at = now()->subMinutes(6);

        $this->assertTrue($website->isDue());
    }

    public function test_is_due_returns_true_at_exact_interval_boundary(): void
    {
        $website = $this->website(['interval_minutes' => 15]);
        // Exactly 15 minutes ago means isPast() returns true (at or beyond boundary)
        $website->checked_at = now()->subMinutes(15)->subSecond();

        $this->assertTrue($website->isDue());
    }

    public function test_is_due_respects_longer_intervals(): void
    {
        $website = $this->website();

        foreach ([1, 5, 10, 15, 30, 60] as $interval) {
            $website->interval_minutes = $interval;

            $website->checked_at = now()->subMinutes($interval - 1);
            $this->assertFalse($website->isDue(), "Expected not due {$interval} minutes before interval.");

            $website->checked_at = now()->subMinutes($interval + 1);
            $this->assertTrue($website->isDue(), "Expected due {$interval} minutes after interval.");
        }
    }

    // ── Website query scopes ─────────────────────────────────────────────────

    public function test_up_scope_returns_only_up_websites(): void
    {
        $up = $this->website(['url' => 'https://up.example.com']);
        $up->forceFill(['status' => 'up'])->save();

        $down = $this->website(['url' => 'https://down.example.com']);
        $down->forceFill(['status' => 'down'])->save();

        $results = Website::up()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($up));
    }

    public function test_down_scope_returns_only_down_websites(): void
    {
        $up = $this->website(['url' => 'https://up.example.com']);
        $up->forceFill(['status' => 'up'])->save();

        $down = $this->website(['url' => 'https://down.example.com']);
        $down->forceFill(['status' => 'down'])->save();

        $results = Website::down()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($down));
    }

    public function test_pending_scope_returns_websites_with_pending_or_null_status(): void
    {
        $pending = $this->website(['url' => 'https://pending.example.com']);
        // Status defaults to pending via migration
        $pending->forceFill(['status' => 'pending'])->save();

        $up = $this->website(['url' => 'https://up.example.com']);
        $up->forceFill(['status' => 'up'])->save();

        $results = Website::pending()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($pending));
    }

    // ── Check model helpers ──────────────────────────────────────────────────

    public function test_check_is_up_and_is_down_helpers(): void
    {
        $website = $this->website();
        $upCheck = $website->checks()->create([
            'checked_url'      => $website->url,
            'status'           => 'up',
            'http_status_code' => 200,
            'response_time_ms' => 50,
            'checked_at'       => now(),
        ]);
        $downCheck = $website->checks()->create([
            'checked_url'      => $website->url,
            'status'           => 'down',
            'http_status_code' => 503,
            'response_time_ms' => 0,
            'checked_at'       => now(),
        ]);

        $this->assertTrue($upCheck->isUp());
        $this->assertFalse($upCheck->isDown());

        $this->assertTrue($downCheck->isDown());
        $this->assertFalse($downCheck->isUp());
    }

    public function test_check_up_and_down_scopes(): void
    {
        $website = $this->website();
        $website->checks()->create([
            'checked_url' => $website->url, 'status' => 'up',
            'http_status_code' => 200, 'response_time_ms' => 10, 'checked_at' => now(),
        ]);
        $website->checks()->create([
            'checked_url' => $website->url, 'status' => 'down',
            'http_status_code' => 500, 'response_time_ms' => 0, 'checked_at' => now(),
        ]);

        $this->assertCount(1, Check::up()->get());
        $this->assertCount(1, Check::down()->get());
    }
}
