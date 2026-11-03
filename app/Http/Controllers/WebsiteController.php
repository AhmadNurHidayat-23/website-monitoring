<?php

namespace App\Http\Controllers;

use App\Http\Requests\WebsiteRequest;
use App\Models\Website;
use Illuminate\Support\Facades\Cache;

class WebsiteController extends Controller
{
    public function index()
    {
        $websites = Website::orderBy('name')->get();

        return view('websites.index', compact('websites'));
    }

    public function create()
    {
        return view('websites.form', ['website' => new Website(['interval_minutes' => 5])]);
    }

    public function store(WebsiteRequest $request)
    {
        $website = Website::create($request->validated());

        return to_route('websites.show', $website)->with('notice', 'Website ditambahkan. Jalankan pemeriksaan pertama atau tunggu scheduler.');
    }

    public function show(Website $website)
    {
        $query = $website->checks()->where('checked_url', $website->url);
        $total = (clone $query)->count();
        $successful = (clone $query)->where('status', 'up')->count();
        $successRate = $total ? round($successful / $total * 100, 1) : null;
        $checks = (clone $query)->orderByDesc('id')->paginate(20);
        $chart = (clone $query)->orderByDesc('id')->limit(40)->get()->reverse()->values();

        return view('websites.show', compact('website', 'total', 'successful', 'successRate', 'checks', 'chart'));
    }

    public function edit(Website $website)
    {
        return view('websites.form', compact('website'));
    }

    public function update(WebsiteRequest $request, Website $website)
    {
        $result = Cache::lock('monitor:website:'.$website->id, 90)->get(function () use ($website, $request) {
            $website->refresh()->fill($request->validated());
            if ($website->isDirty('url')) {
                $website->forceFill(['status' => 'pending', 'checked_at' => null, 'http_status_code' => null,
                    'response_time_ms' => null, 'last_error' => null]);
            }
            $website->save();

            return true;
        });

        return $result ? to_route('websites.show', $website)->with('notice', 'Perubahan website disimpan.')
            : back()->with('notice', 'Website sedang diperiksa. Coba kembali sebentar lagi.');
    }

    public function destroy(Website $website)
    {
        $deleted = Cache::lock('monitor:website:'.$website->id, 90)->get(fn () => $website->delete());

        return $deleted ? to_route('websites.index')->with('notice', 'Website dan riwayat pemeriksaannya dihapus.')
            : back()->with('notice', 'Website sedang diperiksa. Coba kembali sebentar lagi.');
    }
}
