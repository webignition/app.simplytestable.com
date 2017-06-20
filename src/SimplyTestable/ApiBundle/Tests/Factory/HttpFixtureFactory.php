<?php

namespace SimplyTestable\ApiBundle\Tests\Factory;

use Guzzle\Http\Message\Response as GuzzleResponse;

class HttpFixtureFactory
{
    /**
     * @return GuzzleResponse[]
     */
    public static function createStandardJobResolveAndPrepareFixtures()
    {
        return [
            static::createStandardResolveResponse(),
            static::createStandardRobotsTxtResponse(),
            static::createStandardSitemapResponse(),
        ];
    }

    /**
     * @param string $contentType
     * @param string $body
     *
     * @return GuzzleResponse
     */
    public static function createSuccessResponse($contentType = null, $body = '')
    {
        $headerLines = [
            'HTTP/1.1 200 OK'
        ];

        if (!empty($contentType)) {
            $headerLines[] = 'Content-type: ' . $contentType;
        }

        $message = implode("\n", $headerLines);

        if (!empty($body)) {
            $message .= "\n\n" . $body;
        }

        return GuzzleResponse::fromMessage($message);
    }

    /**
     * @return GuzzleResponse
     */
    public static function createNotFoundResponse()
    {
        return GuzzleResponse::fromMessage('HTTP/1.1 404 Not Found');
    }

    /**
     * @return GuzzleResponse
     */
    public static function createStandardResolveResponse()
    {
        return static::createSuccessResponse();
    }

    /**
     * @return GuzzleResponse
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
     * @return GuzzleResponse
     */
    public static function createRobotsTxtResponse($sitemapUrls)
    {
        $sitemapLines = [];

        foreach ($sitemapUrls as $sitemapUrl) {
            $sitemapLines[] = 'sitemap: ' . $sitemapUrl;
        }

        return static::createSuccessResponse(
            'text/plain',
            implode("\n", $sitemapLines)
        );
    }

    /**
     * @param string $domain
     *
     * @return GuzzleResponse
     */
    public static function createStandardSitemapResponse($domain = 'example.com')
    {
        return static::createSuccessResponse(
            'text/plain',
            SitemapFixtureFactory::load('example.com-three-urls', $domain)
        );
    }

    /**
     * @return GuzzleResponse[]
     */
    public static function createStandardCrawlPrepareResponses()
    {
        return [
            GuzzleResponse::fromMessage("HTTP/1.0 200 OK\nContent-Type: text/plain\n\nUser-Agent: *"),
            GuzzleResponse::fromMessage('HTTP/1.1 404'),
            GuzzleResponse::fromMessage('HTTP/1.1 404'),
            GuzzleResponse::fromMessage('HTTP/1.1 404'),
        ];
    }
}
