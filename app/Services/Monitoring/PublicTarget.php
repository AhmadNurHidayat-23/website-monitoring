<?php

namespace App\Services\Monitoring;

class PublicTarget
{
    public function __construct(private DnsResolver $dns) {}

    /** @return array{url: string, host: string, port: int, ip: string} */
    public function validate(string $url): array
    {
        $parts = parse_url($url);
        if (! $parts || strlen($url) > 2048 || preg_match('/[\\x00-\\x20\\x7f\\\\\\\\]/', $url)) {
            throw new UnsafeTarget('Masukkan URL HTTP atau HTTPS yang valid.');
        }
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower(trim($parts['host'] ?? '', '[]'));
        $port = $parts['port'] ?? ($scheme === 'https' ? 443 : 80);
        if (! in_array($scheme, ['http', 'https'], true) || ! $host
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
            || $port !== ($scheme === 'https' ? 443 : 80)) {
            throw new UnsafeTarget('Gunakan HTTP port 80 atau HTTPS port 443 tanpa kredensial atau fragmen.');
        }
        $literal = filter_var($host, FILTER_VALIDATE_IP);
        if (! $literal && (! preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\\.)+[a-z]{2,63}$/D', $host)
            || preg_match('/\\.(localhost|local|internal|test|invalid|example|onion)$/', $host))) {
            throw new UnsafeTarget('Hanya domain publik atau alamat IP publik yang diizinkan.');
        }
        $addresses = $literal ? [$host] : $this->dns->resolve($host);
        if ($addresses === []) {
            throw new UnsafeTarget('DNS tidak dapat menemukan alamat publik untuk domain ini.');
        }
        foreach ($addresses as $ip) {
            if (! $this->isPublic($ip)) {
                throw new UnsafeTarget('Alamat lokal, privat, khusus, dan internal tidak diizinkan.');
            }
        }

        return ['url' => $url, 'host' => $host, 'port' => $port, 'ip' => $addresses[0]];
    }

    public function isPublic(string $ip): bool
    {
        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE)) {
            return false;
        }
        // Reject IPv6 translation/tunnelling ranges, including IPv4-mapped addresses.
        if (str_contains($ip, ':')) {
            $packed = inet_pton($ip);

            return $packed !== false && (ord($packed[0]) & 0xE0) === 0x20
                && ! str_starts_with(bin2hex($packed), '20010000')
                && ! str_starts_with(bin2hex($packed), '2002');
        }

        return (int) explode('.', $ip)[0] < 224;
    }
}
