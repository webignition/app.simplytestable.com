<?php

namespace Tests\ApiBundle\Factory;

use GuzzleHttp\Message\ResponseInterface;

class HttpFixtureFactory
{
    /**
     * @return ResponseInterface[]
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
     * @param int $statusCode
     * @param string $statusMessage
     * @param array $headerLines
     * @param string $contentType
     * @param string $body
     *
     * @return string
     */
    public static function createResponse(
        $statusCode,
        $statusMessage,
        $headerLines = [],
        $contentType = null,
        $body = ''
    ) {
        $headerLines = array_merge(
            [
                sprintf(
                    'HTTP/1.1 %s %s',
                    $statusCode,
                    $statusMessage
                ),
            ],
            $headerLines
        );

        if (!empty($contentType)) {
            $headerLines[] = 'Content-type: ' . $contentType;
        }

        $message = implode("\n", $headerLines);

        if (!empty($body)) {
            $message .= "\n\n" . $body;
        }

        return $message;
    }

    /**
     * @param string $contentType
     * @param string $body
     *
     * @return string
     */
    public static function createSuccessResponse($contentType = null, $body = '')
    {
        return static::createResponse(200, 'OK', [], $contentType, $body);
    }

    /**
     * @return string
     */
    public static function createNotFoundResponse()
    {
        return static::createResponse(404, 'Not Found');
    }

    /**
     * @return string
     */
    public static function createInternalServerErrorResponse()
    {
        return static::createResponse(500, 'Internal Server Error');
    }

    /**
     * @return string
     */
    public static function createServiceUnavailableResponse()
    {
        return static::createResponse(503, 'Service Unavailable');
    }

    /**
     * @return string
     */
    public static function createBadRequestResponse()
    {
        return static::createResponse(400, 'Bad Request');
    }

    /**
     * @param string $location
     *
     * @return string
     */
    public static function createMovedPermanentlyRedirectResponse($location)
    {
        return static::createResponse(301, 'Moved Permanently', [
            'Location: ' . $location,
        ]);
    }

    /**
     * @param string $location
     *
     * @return string
     */
    public static function createFoundRedirectResponse($location)
    {
        return static::createResponse(302, 'Found', [
            'Location: ' . $location,
        ]);
    }

    /**
     * @return string
     */
    public static function createStandardResolveResponse()
    {
        return static::createSuccessResponse();
    }

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

        return static::createSuccessResponse(
            'text/plain',
            implode("\n", $sitemapLines)
        );
    }

    /**
     * @param string $domain
     *
     * @return string
     */
    public static function createStandardSitemapResponse($domain = 'example.com')
    {
        return static::createSuccessResponse(
            'text/plain',
            SitemapFixtureFactory::load('example.com-three-urls', $domain)
        );
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
