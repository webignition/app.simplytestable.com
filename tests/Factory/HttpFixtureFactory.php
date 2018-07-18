<?php

namespace App\Tests\Factory;

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
        $notFoundResponse = new Response(404);

        return [
            new Response(200, ['content-type' => 'text/plain'], 'User-Agent: *'),
            $notFoundResponse,
            $notFoundResponse,
            $notFoundResponse,
            $notFoundResponse,
            $notFoundResponse,
            $notFoundResponse,
        ];
    }

    /**
     * @param int $statusCode
     * @param array $responseData
     *
     * @return Response
     */
    public static function createPostmarkResponse($statusCode, array $responseData)
    {
        return new Response(
            $statusCode,
            ['Content-Type' => 'application/json'],
            json_encode($responseData)
        );
    }
}
