<?php

namespace Tests\Feature;

use App\Models\Check;
use App\Models\Website;
use Illuminate\Support\Facades\Cache;
use Tests\MonitoringTestCase;

class WebsiteCrudTest extends MonitoringTestCase
{
    public function test_empty_home_and_form_render(): void
    {
        $this->get('/')->assertOk()->assertSee('Pantau Website dalam Satu Tempat.')->assertSee('Mulai dari website pertama Anda.');
        $this->get('/websites/create')->assertOk()->assertSee('Simpan Website');
    }

    public function test_create_edit_and_delete_website_with_history(): void
    {
        $this->post('/websites', ['name' => 'Contoh', 'url' => 'https://example.com', 'interval_minutes' => 5])->assertRedirect();
        $website = Website::firstOrFail();
        $this->get(route('websites.edit', $website))->assertOk()->assertSee('Contoh');
        $this->put(route('websites.update', $website), ['name' => 'Diperbarui', 'url' => 'https://example.com', 'interval_minutes' => 10])->assertRedirect();
        $this->assertDatabaseHas('websites', ['name' => 'Diperbarui', 'interval_minutes' => 10]);
        $website->checks()->create(['checked_url' => $website->url, 'status' => 'up', 'http_status_code' => 200, 'response_time_ms' => 20, 'checked_at' => now()]);
        $this->delete(route('websites.destroy', $website))->assertRedirect('/');
        $this->assertDatabaseCount('websites', 0);
        $this->assertDatabaseCount('checks', 0);
    }

    public function test_validation_is_in_indonesian_and_rejects_internal_targets(): void
    {
        $this->post('/websites', ['name' => '', 'url' => 'http://127.0.0.1', 'interval_minutes' => 2])
            ->assertSessionHasErrors(['name', 'url', 'interval_minutes']);
        $this->assertDatabaseCount('websites', 0);
    }

    public function test_duplicates_are_rejected_but_unchanged_url_can_be_updated(): void
    {
        $website = $this->website();
        $data = ['name' => 'Contoh', 'url' => $website->url, 'interval_minutes' => 5];
        $this->post('/websites', $data)->assertSessionHasErrors('url');
        $this->put(route('websites.update', $website), $data)->assertSessionHasNoErrors();
    }

    public function test_statistics_and_chart_use_real_history_for_current_url(): void
    {
        $website = $this->website();
        foreach (['up', 'up', 'down'] as $status) {
            $website->checks()->create(['checked_url' => $website->url, 'status' => $status, 'http_status_code' => $status === 'up' ? 200 : 503,
                'response_time_ms' => 123, 'checked_at' => now()]);
        }
        $website->checks()->create(['checked_url' => 'https://old.example.com', 'status' => 'up', 'response_time_ms' => 999, 'checked_at' => now()]);
        $this->get(route('websites.show', $website))->assertOk()->assertViewHas('total', 3)
            ->assertViewHas('successRate', 66.7)->assertSee('66,7')->assertSee('data-points=', false)
            ->assertViewHas('chart', fn ($chart) => $chart->count() === 3 && $chart->every(fn ($check) => $check->response_time_ms === 123));
    }

    public function test_changing_url_resets_current_summary_without_deleting_history(): void
    {
        $website = $this->website();
        $website->forceFill(['status' => 'up', 'checked_at' => now(), 'http_status_code' => 200])->save();
        $website->checks()->create(['checked_url' => $website->url, 'status' => 'up', 'response_time_ms' => 20, 'checked_at' => now()]);
        $this->put(route('websites.update', $website), ['name' => 'Baru', 'url' => 'https://laravel.com', 'interval_minutes' => 5])->assertRedirect();
        $this->assertSame('pending', $website->fresh()->status);
        $this->assertNull($website->fresh()->checked_at);
        $this->assertDatabaseCount('checks', 1);
        $this->get(route('websites.show', $website))->assertViewHas('total', 0)->assertViewHas('successRate', null);
    }

    public function test_edit_and_delete_cannot_race_with_active_check(): void
    {
        $website = $this->website();
        $lock = Cache::lock('monitor:website:'.$website->id, 90);
        $lock->get();
        try {
            $this->put(route('websites.update', $website), ['name' => 'Baru', 'url' => $website->url, 'interval_minutes' => 5])->assertRedirect();
            $this->delete(route('websites.destroy', $website))->assertRedirect();
            $this->assertSame('Contoh', $website->fresh()->name);
        } finally {
            $lock->release();
        }
    }

    public function test_history_is_paginated_and_names_are_escaped(): void
    {
        $website = $this->website(['name' => '<script>alert(1)</script>']);
        for ($i = 0; $i < 21; $i++) {
            $website->checks()->create(['checked_url' => $website->url, 'status' => 'up', 'response_time_ms' => 12, 'checked_at' => now()]);
        }
        $this->get('/')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->get(route('websites.show', $website))->assertSee('Berikutnya')->assertViewHas('checks', fn ($checks) => $checks->count() === 20);
    }

    public function test_website_and_check_status_helpers_and_scopes(): void
    {
        $upSite = $this->website(['name' => 'Site Up', 'url' => 'https://up.example.com']);
        $upSite->forceFill(['status' => 'up'])->save();

        $downSite = $this->website(['name' => 'Site Down', 'url' => 'https://down.example.com']);
        $downSite->forceFill(['status' => 'down'])->save();

        $pendingSite = $this->website(['name' => 'Site Pending', 'url' => 'https://pending.example.com']);
        $pendingSite->forceFill(['status' => 'pending'])->save();

        $this->assertTrue($upSite->isUp());
        $this->assertFalse($upSite->isDown());
        $this->assertFalse($upSite->isPending());

        $this->assertTrue($downSite->isDown());
        $this->assertFalse($downSite->isUp());

        $this->assertTrue($pendingSite->isPending());
        $this->assertFalse($pendingSite->isUp());

        $this->assertCount(1, Website::up()->where('id', $upSite->id)->get());
        $this->assertCount(1, Website::down()->where('id', $downSite->id)->get());
        $this->assertCount(1, Website::pending()->where('id', $pendingSite->id)->get());

        $upCheck = $upSite->checks()->create([
            'checked_url' => $upSite->url,
            'status' => 'up',
            'response_time_ms' => 50,
            'checked_at' => now(),
        ]);
        $downCheck = $downSite->checks()->create([
            'checked_url' => $downSite->url,
            'status' => 'down',
            'response_time_ms' => 0,
            'checked_at' => now(),
        ]);

        $this->assertTrue($upCheck->isUp());
        $this->assertFalse($upCheck->isDown());
        $this->assertTrue($downCheck->isDown());
        $this->assertFalse($downCheck->isUp());

        $this->assertCount(1, Check::up()->where('id', $upCheck->id)->get());
        $this->assertCount(1, Check::down()->where('id', $downCheck->id)->get());
    }
}
