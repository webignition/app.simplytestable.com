<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

class SitemapFixtureFactory
{
    /**
     * @param string $name
     * @return string
     */
    public static function load($name)
    {
        return file_get_contents(__DIR__ . '/../Fixtures/Data/Sitemaps/' . $name . '.xml');
    }
}
