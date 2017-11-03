<?php

namespace Tests\ApiBundle\Factory;

class StripeEventFixtureFactory
{
    /**
     * @param string $name
     * @param array $modifications
     *
     * @return array
     */
    public static function load($name, $modifications = [])
    {
        $content = file_get_contents(__DIR__ . '/../Fixtures/Data/StripeEvent/' . $name . '.json');
        $object = json_decode($content, true);

        return array_replace_recursive($object, $modifications);
    }
}
