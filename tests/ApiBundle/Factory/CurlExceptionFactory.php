<?php

namespace Tests\ApiBundle\Factory;

use Guzzle\Http\Exception\CurlException;

class CurlExceptionFactory
{
    /**
     * @param string $message
     * @param int $curlCode
     *
     * @return CurlException
     */
    public static function create($message, $curlCode)
    {
        $curlException = new CurlException();

        $curlException->setError($message, $curlCode);

        return $curlException;
    }
}
