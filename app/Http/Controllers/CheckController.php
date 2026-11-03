<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Services\Monitoring\WebsiteMonitor;

class CheckController extends Controller
{
    public function __invoke(Website $website, WebsiteMonitor $monitor)
    {
        $check = $monitor->check($website);

        return back()->with('notice', $check ? 'Pemeriksaan selesai. Hasil terbaru sudah tersimpan.'
            : 'Pemeriksaan website ini sedang berlangsung. Silakan tunggu.');
    }
}
