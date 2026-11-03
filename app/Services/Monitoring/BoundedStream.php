<?php

namespace App\Services\Monitoring;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;

class BoundedStream implements StreamInterface
{
    use StreamDecoratorTrait;

    public const LIMIT = 1048576;

    public function write(string $string): int
    {
        if ($this->tell() + strlen($string) > self::LIMIT) {
            throw new ResponseTooLarge('Respons melebihi batas 1 MiB.');
        }

        return $this->stream->write($string);
    }
}
