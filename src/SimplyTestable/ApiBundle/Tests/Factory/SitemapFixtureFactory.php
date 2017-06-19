<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

class SitemapFixtureFactory
{
    /**
     * @param string $name
     * @param string $domain
     *
     * @return string
     */
    public static function load($name, $domain = 'example.com')
    {
        $content = file_get_contents(__DIR__ . '/../Fixtures/Data/Sitemaps/' . $name . '.xml');

        if ($domain != 'example.com') {
            $content = str_replace('//example.com/', '//' . $domain . '/', $content);
        }

        return $content;
    }
}
