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
     * @param int $statusCode
     * @param string $statusMessage
     * @param array $headerLines
     * @param string $contentType
     * @param string $body
     *
     * @return GuzzleResponse
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

        return GuzzleResponse::fromMessage($message);
    }

    /**
     * @param string $contentType
     * @param string $body
     *
     * @return GuzzleResponse
     */
    public static function createSuccessResponse($contentType = null, $body = '')
    {
        return static::createResponse(200, 'OK', [], $contentType, $body);
    }

    /**
     * @return GuzzleResponse
     */
    public static function createNotFoundResponse()
    {
        return static::createResponse(404, 'Not Found');
    }

    /**
     * @return GuzzleResponse
     */
    public static function createInternalServerErrorResponse()
    {
        return static::createResponse(500, 'Internal Server Error');
    }

    /**
     * @return GuzzleResponse
     */
    public static function createServiceUnavailableResponse()
    {
        return static::createResponse(503, 'Service Unavailable');
    }

    /**
     * @return GuzzleResponse
     */
    public static function createBadRequestResponse()
    {
        return static::createResponse(400, 'Bad Request');
    }

    /**
     * @param string $location
     *
     * @return GuzzleResponse
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
     * @return GuzzleResponse
     */
    public static function createFoundRedirectResponse($location)
    {
        return static::createResponse(302, 'Found', [
            'Location: ' . $location,
        ]);
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
            GuzzleResponse::fromMessage('HTTP/1.1 404'),
        ];
    }
}
