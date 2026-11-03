<?php

namespace App\Console\Commands;

use App\Models\Website;
use App\Services\Monitoring\WebsiteMonitor;
use Illuminate\Console\Command;

class CheckWebsites extends Command
{
    protected $signature = 'monitor:check {--force : Periksa juga website yang belum jatuh tempo}';

    protected $description = 'Memeriksa website publik sesuai interval masing-masing';

    public function handle(WebsiteMonitor $monitor): int
    {
        $count = 0;
        foreach (Website::lazyById() as $website) {
            if ($monitor->check($website, ! $this->option('force'))) {
                $count++;
            }
        }
        $this->info("{$count} website diperiksa.");

        return self::SUCCESS;
    }
}
