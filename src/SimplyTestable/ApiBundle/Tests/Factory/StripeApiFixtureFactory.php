<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use phpmock\mockery\PHPMockery;

class StripeApiFixtureFactory
{
    /**
     * @param string[] $fixtures
     */
    public static function set($fixtures)
    {
        PHPMockery::mock(
            'Stripe',
            'curl_exec'
        )->andReturnValues(
            $fixtures
        );

        $fixtureCount = count($fixtures);
        $httpStatusCodes = array_fill(0, $fixtureCount, 200);

        PHPMockery::mock(
            'Stripe',
            'curl_getinfo'
        )->andReturnValues(
            $httpStatusCodes
        );
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function load($name)
    {
        return file_get_contents(__DIR__ . '/../Fixtures/Data/Stripe/' . $name . '.json');
    }
}
