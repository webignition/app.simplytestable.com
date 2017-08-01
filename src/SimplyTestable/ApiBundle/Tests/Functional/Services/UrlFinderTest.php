<?php

namespace SimplyTestable\ApiBundle\Tests\Command;

use SimplyTestable\ApiBundle\Services\UrlFinder;
use SimplyTestable\ApiBundle\Tests\Factory\AtomFeedFactory;
use SimplyTestable\ApiBundle\Tests\Factory\HtmlDocumentFactory;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\RssFeedFactory;
use SimplyTestable\ApiBundle\Tests\Factory\SitemapFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class UrlFinderTest extends BaseSimplyTestableTestCase
{
    /**
     * @dataProvider getUrlsDataProvider
     *
     * @param string $websiteUrl
     * @param array $parameters
     * @param array $httpFixtures
     * @param string[] $expectedUrlSet
     * @param float|null $sitemapRetrieverTimeout
     */
    public function testGetUrls(
        $websiteUrl,
        $parameters,
        $httpFixtures,
        $expectedUrlSet,
        $sitemapRetrieverTimeout = null
    ) {
        $this->queueHttpFixtures($httpFixtures);

        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $website = $websiteService->fetch($websiteUrl);

        $urlFinder = new UrlFinder(
            $this->container->get('simplytestable.services.httpclientservice'),
            $sitemapRetrieverTimeout
        );

        $urls = $urlFinder->getUrls($website, 10, $parameters);

        $this->assertEquals($expectedUrlSet, $urls);
    }

    /**
     * @return array
     */
    public function getUrlsDataProvider()
    {
        return [
            'no urls; no sitemap, no rss need, no atom feed, no web page' => [
                'websiteUrl' => 'http://example.com',
                'parameters' => [],
                'httpFixtures' => [
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                ],
                'expectedUrlSet' => [],
            ],
            'no urls; no sitemap, no rss need, no atom feed' => [
                'websiteUrl' => 'http://example.com',
                'parameters' => [],
                'httpFixtures' => [
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/html',
                        HtmlDocumentFactory::load('minimal')
                    ),
                ],
                'expectedUrlSet' => [],
            ],
            'no urls; sitemap contains only schemeless urls' => [
                'websiteUrl' => 'http://example.com',
                'parameters' => [],
                'httpFixtures' => [
                    HttpFixtureFactory::createStandardRobotsTxtResponse(),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/xml',
                        SitemapFixtureFactory::generate([
                            'example.com/one',
                            'example.com/two'
                        ])
                    ),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                ],
                'expectedUrlSet' => [],
            ],
            'no urls; malformed rss url' => [
                'websiteUrl' => 'http://example.com',
                'parameters' => [],
                'httpFixtures' => [
                    HttpFixtureFactory::createStandardRobotsTxtResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/html',
                        HtmlDocumentFactory::load('malformed-rss-url')
                    ),
                    HttpFixtureFactory::createNotFoundResponse(),
                ],
                'expectedUrlSet' => [],
            ],
            'no urls; request exception retrieving atom feed' => [
                'websiteUrl' => 'http://example.com',
                'parameters' => [],
                'httpFixtures' => [
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/html',
                        HtmlDocumentFactory::load('atom-feed')
                    ),
                    HttpFixtureFactory::createNotFoundResponse(),
                ],
                'expectedUrlSet' => [],
            ],
            'from single sitemap.txt' => [
                'websiteUrl' => 'http://example.com',
                'parameters' => [],
                'httpFixtures' => [
                    HttpFixtureFactory::createRobotsTxtResponse([
                        'http://example.com/sitemap.txt',
                    ]),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/plain',
                        'http://example.com/from-sitemap-txt/'
                    ),
                ],
                'expectedUrlSet' => [
                    'http://example.com/from-sitemap-txt/',
                ],
            ],
            'from single sitemap.xml' => [
                'websiteUrl' => 'http://example.com',
                'parameters' => [],
                'httpFixtures' => [
                    HttpFixtureFactory::createRobotsTxtResponse([
                        'http://example.com/sitemap.xml',
                    ]),
                    HttpFixtureFactory::createSuccessResponse(
                        'application/xml',
                        SitemapFixtureFactory::generate([
                            'http://example.com/one',
                            'http://example.com/two',
                            'http://example.com/three',
                            'http://foo.example.com/one',
                            'http://bar.example.com/one',
                        ])
                    ),
                ],
                'expectedUrlSet' => [
                    'http://example.com/one',
                    'http://example.com/two',
                    'http://example.com/three',
                ],
            ],
            'from sitemap.txt and sitemap.xml' => [
                'websiteUrl' => 'http://example.com',
                'parameters' => [],
                'httpFixtures' => [
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/xml',
                        SitemapFixtureFactory::generate([
                            'http://example.com/from-sitemap-xml/',
                        ])
                    ),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/plain',
                        'http://example.com/from-sitemap-txt/'
                    ),
                ],
                'expectedUrlSet' => [
                    'http://example.com/from-sitemap-xml/',
                    'http://example.com/from-sitemap-txt/',
                ],
            ],
            'from multiple sitemaps' => [
                'websiteUrl' => 'http://example.com',
                'parameters' => [],
                'httpFixtures' => [
                    HttpFixtureFactory::createRobotsTxtResponse([
                        'http://example.com/sitemap1.xml',
                        'http://example.com/sitemap2.xml',
                        'http://example.com/sitemap3.xml',
                    ]),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/xml',
                        SitemapFixtureFactory::generate([
                            'http://example.com/one',
                            'http://example.com/two',
                            'http://example.com/three',
                            'http://example.com/four',
                            'http://example.com/five',
                            'http://example.com/six',
                        ])
                    ),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/xml',
                        SitemapFixtureFactory::generate([
                            'http://example.com/seven',
                            'http://example.com/eight',
                            'http://example.com/nine',
                            'http://example.com/ten',
                            'http://example.com/eleven',
                            'http://example.com/twelve',
                        ])
                    ),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/xml',
                        SitemapFixtureFactory::generate([
                            'http://example.com/thirteen',
                        ])
                    ),
                ],
                'expectedUrlSet' => [
                    'http://example.com/one',
                    'http://example.com/two',
                    'http://example.com/three',
                    'http://example.com/four',
                    'http://example.com/five',
                    'http://example.com/six',
                    'http://example.com/seven',
                    'http://example.com/eight',
                    'http://example.com/nine',
                    'http://example.com/ten',
                    'http://example.com/eleven',
                    'http://example.com/twelve',
                ],
            ],
            'from multiple sitemaps; timeout during transfer' => [
                'websiteUrl' => 'http://example.com',
                'parameters' => [],
                'httpFixtures' => [
                    HttpFixtureFactory::createStandardRobotsTxtResponse(),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/xml',
                        SitemapFixtureFactory::load('example.com-index-50-sitemaps')
                    ),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/xml',
                        SitemapFixtureFactory::generate([
                            'http://example.com/one',
                            'http://example.com/two',
                            'http://example.com/three',
                            'http://example.com/four',
                            'http://example.com/five',
                            'http://example.com/six',
                            'http://example.com/seven',
                            'http://example.com/eight',
                            'http://example.com/nine',
                            'http://example.com/ten',
                            'http://example.com/eleven',
                            'http://example.com/twelve',
                        ])
                    ),
                ],
                'expectedUrlSet' => [
                    'http://example.com/one',
                    'http://example.com/two',
                    'http://example.com/three',
                    'http://example.com/four',
                    'http://example.com/five',
                    'http://example.com/six',
                    'http://example.com/seven',
                    'http://example.com/eight',
                    'http://example.com/nine',
                    'http://example.com/ten',
                    'http://example.com/eleven',
                    'http://example.com/twelve',
                ],
                'sitemapRetrieverTimeout' => 0.00001,
            ],
            'from atom feed with cookies' => [
                'websiteUrl' => 'http://example.com',
                'parameters' => [
                    'cookies' => [
                        [
                            'Name' => 'foo',
                            'Value' => 'bar',
                        ],
                    ],
                ],
                'httpFixtures' => [
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/html',
                        HtmlDocumentFactory::load('atom-feed')
                    ),
                    HttpFixtureFactory::createSuccessResponse(
                        'application/atom+xml',
                        AtomFeedFactory::load('example')
                    ),
                ],
                'expectedUrlSet' => [
                    'http://example.com/from-atom-feed/',
                ],
            ],
            'from rss feed' => [
                'websiteUrl' => 'http://example.com',
                'parameters' => [
                    'softLimit' => 10,
                ],
                'httpFixtures' => [
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/html',
                        HtmlDocumentFactory::load('rss-feed')
                    ),
                    HttpFixtureFactory::createSuccessResponse(
                        'application/rss+xml',
                        RssFeedFactory::load('example')
                    ),
                ],
                'expectedUrlSet' => [
                    'http://example.com/from-rss-feed/',
                ],
            ],
            'from single sitemap.xml with cookies' => [
                'websiteUrl' => 'http://example.com',
                'parameters' => [
                    'cookies' => [
                        [
                            'Name' => 'foo',
                            'Value' => 'bar',
                        ],
                    ],
                ],
                'httpFixtures' => [
                    HttpFixtureFactory::createRobotsTxtResponse([
                        'http://example.com/sitemap.xml',
                    ]),
                    HttpFixtureFactory::createSuccessResponse(
                        'application/xml',
                        SitemapFixtureFactory::generate([
                            'http://example.com/one',
                            'http://example.com/two',
                            'http://example.com/three',
                            'http://foo.example.com/one',
                            'http://bar.example.com/one',
                        ])
                    ),
                ],
                'expectedUrlSet' => [
                    'http://example.com/one',
                    'http://example.com/two',
                    'http://example.com/three',
                ],
            ],
        ];
    }
}
