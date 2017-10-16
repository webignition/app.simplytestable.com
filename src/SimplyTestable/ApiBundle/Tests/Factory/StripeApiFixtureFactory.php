<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use phpmock\mockery\PHPMockery;

class StripeApiFixtureFactory
{
    /**
     * @param string $fixture
     */
    public static function set($fixture)
    {
        PHPMockery::mock(
            'Stripe',
            'curl_exec'
        )->andReturn(
            $fixture
        );

        PHPMockery::mock(
            'Stripe',
            'curl_getinfo'
        )->andReturn(
            200
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
