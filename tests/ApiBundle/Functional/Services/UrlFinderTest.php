<?php

namespace Tests\ApiBundle\Command;

use GuzzleHttp\Message\RequestInterface;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\UrlFinder;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use Tests\ApiBundle\Factory\AtomFeedFactory;
use Tests\ApiBundle\Factory\HtmlDocumentFactory;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Factory\RssFeedFactory;
use Tests\ApiBundle\Factory\SitemapFixtureFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use webignition\WebResource\Service\Configuration as WebResourceServiceConfiguration;
use webignition\WebResource\Service\Service as WebResourceService;

class UrlFinderTest extends AbstractBaseTestCase
{
    /**
     * @dataProvider getUrlsDataProvider
     *
     * @param string $websiteUrl
     * @param array $parameters
     * @param array $httpFixtures
     * @param string[] $expectedUrlSet
     * @param string[] $expectedRequestPropertiesCollection
     * @param float|null $sitemapRetrieverTimeout
     */
    public function testGetUrls(
        $websiteUrl,
        $parameters,
        $httpFixtures,
        $expectedUrlSet,
        $expectedRequestPropertiesCollection,
        $sitemapRetrieverTimeout = null
    ) {
        $this->queueHttpFixtures($httpFixtures);

        $webResourceService = $this->container->get(WebResourceService::class);
        $httpClientService = $this->container->get(HttpClientService::class);

        $webResourceServiceConfiguration = $this->container->get(WebResourceServiceConfiguration::class);

        $updatedWebResourceServiceConfiguration = $webResourceServiceConfiguration->createFromCurrent([
            WebResourceServiceConfiguration::CONFIG_RETRY_WITH_URL_ENCODING_DISABLED => false,
        ]);

        $webResourceService->setConfiguration($updatedWebResourceServiceConfiguration);

        $websiteService = $this->container->get(WebSiteService::class);
        $website = $websiteService->get($websiteUrl);

        $urlFinder = new UrlFinder(
            $this->container->get(HttpClientService::class),
            $webResourceService,
            $this->container->get('simplytestable.services.sitemapfactory'),
            $sitemapRetrieverTimeout
        );

        $urls = $urlFinder->getUrls($website, 10, $parameters);

        $this->assertEquals($expectedUrlSet, $urls);

        $requestPropertiesCollection = [];

        foreach ($httpClientService->getHistory() as $httpTransaction) {
            /* @var RequestInterface $request */
            $request = $httpTransaction['request'];

            $requestProperties = [];

            foreach (['user-agent', 'cookie', 'authorization'] as $headerKey) {
                $requestProperties[$headerKey] = $request->getHeader($headerKey);
            }

            $requestPropertiesCollection[] = $requestProperties;
        }

        $this->assertEquals($expectedRequestPropertiesCollection, $requestPropertiesCollection);
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
                    HttpFixtureFactory::createNotFoundResponse(),
                ],
                'expectedUrlSet' => [],
                'expectedRequestPropertiesCollection' => [
                    [
                        'user-agent' => UrlFinder::SITEMAP_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::FEED_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::FEED_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                ],
            ],
            'no urls; no sitemap, no rss need, no atom feed' => [
                'websiteUrl' => 'http://example.com',
                'parameters' => [],
                'httpFixtures' => [
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/html',
                        HtmlDocumentFactory::load('minimal')
                    ),
                ],
                'expectedUrlSet' => [],
                'expectedRequestPropertiesCollection' => [
                    [
                        'user-agent' => UrlFinder::SITEMAP_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::FEED_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                ],
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
                'expectedRequestPropertiesCollection' => [
                    [
                        'user-agent' => UrlFinder::SITEMAP_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                ],
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
                'expectedRequestPropertiesCollection' => [
                    [
                        'user-agent' => UrlFinder::SITEMAP_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::FEED_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                ],
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
                'expectedRequestPropertiesCollection' => [
                    [
                        'user-agent' => UrlFinder::SITEMAP_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::FEED_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::FEED_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                ],
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
                'expectedRequestPropertiesCollection' => [
                    [
                        'user-agent' => UrlFinder::SITEMAP_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
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
                'expectedRequestPropertiesCollection' => [
                    [
                        'user-agent' => UrlFinder::SITEMAP_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
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
                'expectedRequestPropertiesCollection' => [
                    [
                        'user-agent' => UrlFinder::SITEMAP_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
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
                'expectedRequestPropertiesCollection' => [
                    [
                        'user-agent' => UrlFinder::SITEMAP_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
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
                'expectedRequestPropertiesCollection' => [
                    [
                        'user-agent' => UrlFinder::SITEMAP_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                ],
                'sitemapRetrieverTimeout' => 0.0001,
            ],
            'from multiple sitemaps; url soft limit reached' => [
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
                'expectedRequestPropertiesCollection' => [
                    [
                        'user-agent' => UrlFinder::SITEMAP_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                ],
            ],
            'from atom feed with cookies and authorization' => [
                'websiteUrl' => 'http://example.com',
                'parameters' => [
                    HttpClientService::PARAMETER_KEY_COOKIES => [
                        [
                            'Name' => 'foo',
                            'Value' => 'bar',
                            'Domain' => '.example.com',
                        ],
                    ],
                    HttpClientService::PARAMETER_KEY_HTTP_AUTH_USERNAME => 'user',
                    HttpClientService::PARAMETER_KEY_HTTP_AUTH_PASSWORD => 'password',
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
                'expectedRequestPropertiesCollection' => [
                    [
                        'user-agent' => UrlFinder::SITEMAP_FINDER_USER_AGENT,
                        'cookie' => 'foo=bar',
                        'authorization' => 'Basic dXNlcjpwYXNzd29yZA==',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => 'foo=bar',
                        'authorization' => 'Basic dXNlcjpwYXNzd29yZA==',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => 'foo=bar',
                        'authorization' => 'Basic dXNlcjpwYXNzd29yZA==',
                    ],
                    [
                        'user-agent' => UrlFinder::FEED_FINDER_USER_AGENT,
                        'cookie' => 'foo=bar',
                        'authorization' => 'Basic dXNlcjpwYXNzd29yZA==',
                    ],
                    [
                        'user-agent' => UrlFinder::FEED_FINDER_USER_AGENT,
                        'cookie' => 'foo=bar',
                        'authorization' => 'Basic dXNlcjpwYXNzd29yZA==',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => 'foo=bar',
                        'authorization' => 'Basic dXNlcjpwYXNzd29yZA==',
                    ],
                ],
            ],
            'from rss feed with cookies and authorization' => [
                'websiteUrl' => 'http://example.com',
                'parameters' => [
                    'softLimit' => 10,
                    HttpClientService::PARAMETER_KEY_COOKIES => [
                        [
                            'Name' => 'foo',
                            'Value' => 'bar',
                            'Domain' => '.example.com',
                        ],
                    ],
                    HttpClientService::PARAMETER_KEY_HTTP_AUTH_USERNAME => 'user',
                    HttpClientService::PARAMETER_KEY_HTTP_AUTH_PASSWORD => 'password',
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
                'expectedRequestPropertiesCollection' => [
                    [
                        'user-agent' => UrlFinder::SITEMAP_FINDER_USER_AGENT,
                        'cookie' => 'foo=bar',
                        'authorization' => 'Basic dXNlcjpwYXNzd29yZA==',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => 'foo=bar',
                        'authorization' => 'Basic dXNlcjpwYXNzd29yZA==',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => 'foo=bar',
                        'authorization' => 'Basic dXNlcjpwYXNzd29yZA==',
                    ],
                    [
                        'user-agent' => UrlFinder::FEED_FINDER_USER_AGENT,
                        'cookie' => 'foo=bar',
                        'authorization' => 'Basic dXNlcjpwYXNzd29yZA==',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => 'foo=bar',
                        'authorization' => 'Basic dXNlcjpwYXNzd29yZA==',
                    ],
                ],
            ],
            'from single sitemap.xml with cookies and authorization' => [
                'websiteUrl' => 'http://example.com',
                'parameters' => [
                    HttpClientService::PARAMETER_KEY_COOKIES => [
                        [
                            'Name' => 'foo',
                            'Value' => 'bar',
                            'Domain' => '.example.com',
                        ],
                    ],
                    HttpClientService::PARAMETER_KEY_HTTP_AUTH_USERNAME => 'user',
                    HttpClientService::PARAMETER_KEY_HTTP_AUTH_PASSWORD => 'password',
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
                'expectedRequestPropertiesCollection' => [
                    [
                        'user-agent' => UrlFinder::SITEMAP_FINDER_USER_AGENT,
                        'cookie' => 'foo=bar',
                        'authorization' => 'Basic dXNlcjpwYXNzd29yZA==',
                    ],
                    [
                        'user-agent' => UrlFinder::SITEMAP_RETRIEVER_USER_AGENT,
                        'cookie' => 'foo=bar',
                        'authorization' => 'Basic dXNlcjpwYXNzd29yZA==',
                    ],
                ],
            ],
        ];
    }
}
