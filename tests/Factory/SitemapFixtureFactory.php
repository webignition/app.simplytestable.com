<?php

namespace App\Tests\Factory;

class SitemapFixtureFactory
{
    const DEFAULT_DOMAIN = 'example.com';

    /**
     * @param string $name
     * @param string $domain
     *
     * @return string
     */
    public static function load($name, $domain = self::DEFAULT_DOMAIN)
    {
        if (empty($domain)) {
            $domain = self::DEFAULT_DOMAIN;
        }

        $content = file_get_contents(__DIR__ . '/../Fixtures/Data/Sitemaps/' . $name . '.xml');

        if ($domain != 'example.com') {
            $content = str_replace('//example.com/', '//' . $domain . '/', $content);
        }

        return $content;
    }

    /**
     * @param string[] $urls
     *
     * @return string
     */
    public static function generate($urls)
    {
        $lines = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
        ];

        foreach ($urls as $url) {
            $lines[] = '<url><loc>' . $url . '</loc></url>';
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines);
    }
}
