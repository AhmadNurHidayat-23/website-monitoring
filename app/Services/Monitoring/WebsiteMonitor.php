<?php

namespace App\Services\Monitoring;

use App\Models\Check;
use App\Models\Website;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Throwable;

class WebsiteMonitor
{
    public function __construct(private PublicTarget $targets, private SafeHttpClient $http) {}

    public function check(Website $website, bool $onlyIfDue = false): ?Check
    {
        $lock = Cache::lock('monitor:website:'.$website->id, 90);
        if (! $lock->get()) {
            return null;
        }
        try {
            $website = $website->fresh();
            if (! $website || ($onlyIfDue && ! $website->isDue())) {
                return null;
            }

            return $this->perform($website);
        } finally {
            $lock->release();
        }
    }

    private function perform(Website $website): Check
    {
        $started = hrtime(true);
        $code = null;
        $status = 'down';
        $error = null;
        try {
            $url = $website->url;
            for ($redirects = 0; ; $redirects++) {
                // Resolve every hop and pin cURL to the validated address: no DNS rebinding window.
                $response = $this->http->get($this->targets->validate($url));
                $code = $response->status();
                if (! in_array($code, [301, 302, 303, 307, 308], true)) {
                    $status = $response->successful() ? 'up' : 'down';
                    break;
                }
                if ($redirects >= 3 || ! $response->header('Location')) {
                    throw new UnsafeTarget('Redirect tidak valid atau melebihi batas tiga kali.');
                }
                $url = (string) UriResolver::resolve(new Uri($url), new Uri($response->header('Location')));
                $response->close();
                $code = null;
            }
            $response->close();
        } catch (UnsafeTarget $exception) {
            $error = $exception->getMessage();
        } catch (ConnectionException $exception) {
            $error = preg_match('/cURL error 28\b/', $exception->getMessage())
                ? 'Batas waktu koneksi terlampaui.' : 'Koneksi gagal atau sertifikat TLS tidak valid.';
        } catch (Throwable $exception) {
            $error = $this->isOversized($exception)
                ? 'Respons melebihi batas 1 MiB.' : 'Pemeriksaan gagal. Silakan coba kembali.';
        }
        $data = [
            'status' => $status, 'http_status_code' => $code,
            'response_time_ms' => max(1, (int) round((hrtime(true) - $started) / 1e6)),
            'checked_at' => now(),
        ];

        return DB::transaction(function () use ($website, $data, $error): Check {
            $check = $website->checks()->create([...$data, 'checked_url' => $website->url, 'error' => $error]);
            $website->forceFill([...$data, 'last_error' => $error])->save();

            return $check;
        });
    }

    private function isOversized(Throwable $exception): bool
    {
        do {
            if ($exception instanceof ResponseTooLarge || str_contains($exception->getMessage(), 'cURL error 63')) {
                return true;
            }
        } while ($exception = $exception->getPrevious());

        return false;
    }
}
