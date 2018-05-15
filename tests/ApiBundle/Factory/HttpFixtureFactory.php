<?php

namespace Tests\ApiBundle\Factory;

use GuzzleHttp\Psr7\Response;

class HttpFixtureFactory
{
    /**
     * @return string
     */
    public static function createStandardRobotsTxtResponse()
    {
        return static::createRobotsTxtResponse([
            'http://example.com/sitemap.xml',
        ]);
    }

    /**
     * @param string[] $sitemapUrls
     *
     * @return string
     */
    public static function createRobotsTxtResponse($sitemapUrls)
    {
        $sitemapLines = [];

        foreach ($sitemapUrls as $sitemapUrl) {
            $sitemapLines[] = 'sitemap: ' . $sitemapUrl;
        }

        return new Response(200, ['content-type' => 'text/plain'], implode("\n", $sitemapLines));
    }

    /**
     * @return string[]
     */
    public static function createStandardCrawlPrepareResponses()
    {
        return [
            "HTTP/1.0 200 OK\nContent-Type: text/plain\n\nUser-Agent: *",
            'HTTP/1.1 404',
            'HTTP/1.1 404',
            'HTTP/1.1 404',
            'HTTP/1.1 404',
            'HTTP/1.1 404',
            'HTTP/1.1 404',
        ];
    }
}
