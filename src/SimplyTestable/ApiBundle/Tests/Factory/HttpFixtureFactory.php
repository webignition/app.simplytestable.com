<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use Guzzle\Http\Message\Response as GuzzleResponse;

class HttpFixtureFactory
{
    public static function createStandardJobResolveAndPrepareFixtures()
    {
        return [
            static::createStandardResolveResponse(),
            static::createStandardRobotsTxtResponse(),
            static::createStandardSitemapResponse(),
        ];
    }

    public static function createStandardResolveResponse()
    {
        return GuzzleResponse::fromMessage('HTTP/1.1 200 OK');
    }

    public static function createStandardRobotsTxtResponse()
    {
        return GuzzleResponse::fromMessage("HTTP/1.1 200 OK\nContent-type:text/plain\n\nsitemap: sitemap.xml");
    }

    public static function createStandardSitemapResponse($domain = 'example.com')
    {
        return GuzzleResponse::fromMessage(sprintf(
            "HTTP/1.1 200 OK\nContent-type:text/plain\n\n%s",
            SitemapFixtureFactory::load('example.com-three-urls', $domain)
        ));
    }
}
