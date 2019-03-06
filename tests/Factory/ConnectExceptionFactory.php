<?php

namespace App\Tests\Factory;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;

class ConnectExceptionFactory
{
    public static function create(int $code, string $message): ConnectException
    {
        return new ConnectException(
            'cURL error ' . $code . ': ' . $message,
            new Request('GET', 'http://example.com/')
        );
    }
}
