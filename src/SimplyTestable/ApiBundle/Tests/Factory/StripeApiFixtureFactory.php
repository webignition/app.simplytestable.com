<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use phpmock\mockery\PHPMockery;

class StripeApiFixtureFactory
{
    /**
     * @param string[] $fixtures
     * @param int[] $httpStatusCodes
     */
    public static function set($fixtures, $httpStatusCodes = [])
    {
        PHPMockery::mock(
            'Stripe',
            'curl_exec'
        )->andReturnValues(
            $fixtures
        );

        if (empty($httpStatusCodes)) {
            $fixtureCount = count($fixtures);
            $httpStatusCodes = array_fill(0, $fixtureCount, 200);
        }

        PHPMockery::mock(
            'Stripe',
            'curl_getinfo'
        )->andReturnValues(
            $httpStatusCodes
        );

        PHPMockery::mock(
            'Stripe',
            'stream_socket_client'
        )->andReturn('not relevant');

        $pem = file_get_contents(realpath(__DIR__ . '/../Fixtures/Data/Certs/512b-rsa-example-cert.pem'));

        PHPMockery::mock(
            'Stripe',
            'stream_context_get_params'
        )->andReturn(
            [
                'options' => [
                    'ssl' => [
                        'peer_certificate' => $pem,
                    ],
                ],
            ]
        );
    }

    /**
     * @param string $name
     * @param array $replacements
     * @param array $modifications
     * @return string
     */
    public static function load($name, $replacements = [], $modifications = [])
    {
        $content = file_get_contents(__DIR__ . '/../Fixtures/Data/Stripe/' . $name . '.json');

        foreach ($replacements as $key => $value) {
            $content = str_replace($key, $value, $content);
        }

        $object = json_decode($content, true);
        $object = array_replace_recursive($object, $modifications);

        return json_encode($object, JSON_PRETTY_PRINT);
    }
}
