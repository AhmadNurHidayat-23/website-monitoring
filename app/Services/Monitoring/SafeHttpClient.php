<?php

namespace App\Services\Monitoring;

use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Psr7\Utils;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class SafeHttpClient
{
    /** @param array{url: string, host: string, port: int, ip: string} $target */
    public function get(array $target): Response
    {
        if (! extension_loaded('curl')) {
            throw new RuntimeException('Ekstensi cURL diperlukan untuk koneksi aman.');
        }
        $ip = str_contains($target['ip'], ':') ? '['.$target['ip'].']' : $target['ip'];
        $curl = [CURLOPT_FRESH_CONNECT => true, CURLOPT_FORBID_REUSE => true];
        if (! filter_var($target['host'], FILTER_VALIDATE_IP)) {
            $curl[CURLOPT_RESOLVE] = [$target['host'].':'.$target['port'].':'.$ip];
        }
        $sink = new BoundedStream(Utils::streamFor(fopen('php://temp/maxmemory:1048576', 'w+')));

        return Http::withUserAgent('WebsiteMonitoring/1.0 (local availability monitor)')
            ->setHandler(new CurlHandler)
            ->connectTimeout(3)->timeout(8)->withoutRedirecting()
            ->withOptions([
                'verify' => true,
                'proxy' => '',
                'protocols' => ['http', 'https'],
                'cookies' => false,
                'decode_content' => false,
                'sink' => $sink,
                'on_headers' => function (ResponseInterface $response): void {
                    if ((int) $response->getHeaderLine('Content-Length') > BoundedStream::LIMIT) {
                        throw new ResponseTooLarge('Respons melebihi batas 1 MiB.');
                    }
                },
                'curl' => $curl,
            ])->get($target['url']);
    }
}
