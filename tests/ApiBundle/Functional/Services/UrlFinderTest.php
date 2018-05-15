<?php

namespace Tests\ApiBundle\Command;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\WebSite;
use SimplyTestable\ApiBundle\Services\HttpClientService;
use SimplyTestable\ApiBundle\Services\UrlFinder;
use Tests\ApiBundle\Factory\AtomFeedFactory;
use Tests\ApiBundle\Factory\HtmlDocumentFactory;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Factory\RssFeedFactory;
use Tests\ApiBundle\Factory\SitemapFixtureFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Tests\ApiBundle\Services\TestHttpClientService;
use webignition\WebResource\Retriever as WebResourceRetriever;
use webignition\WebResource\Sitemap\Factory as SitemapFactory;
use webignition\WebsiteRssFeedFinder\WebsiteRssFeedFinder;
use webignition\WebsiteSitemapFinder\WebsiteSitemapFinder;

class UrlFinderTest extends AbstractBaseTestCase
{
    /**
     * @dataProvider getUrlsDataProvider
     *
     * @param Job $job
     * @param array $httpFixtures
     * @param string[] $expectedUrlSet
     * @param string[] $expectedRequestPropertiesCollection
     * @param float|null $sitemapRetrieverTimeout
     */
    public function testGetUrls(
        Job $job,
        $httpFixtures,
        $expectedUrlSet,
        $expectedRequestPropertiesCollection,
        $sitemapRetrieverTimeout = null
    ) {
        /* @var TestHttpClientService $httpClientService */
        $httpClientService = $this->container->get(HttpClientService::class);
        $webResourceRetriever = $this->container->get(WebResourceRetriever::class);
        $sitemapFactory = $this->container->get(SitemapFactory::class);
        $websiteSitemapFinder = $this->container->get(WebsiteSitemapFinder::class);
        $websiteRssFeedFinder = $this->container->get(WebsiteRssFeedFinder::class);

        $httpClientService->appendFixtures($httpFixtures);

        $urlFinder = new UrlFinder(
            $httpClientService,
            $webResourceRetriever,
            $sitemapFactory,
            $websiteSitemapFinder,
            $websiteRssFeedFinder,
            $sitemapRetrieverTimeout
        );

        $urls = $urlFinder->getUrls($job, 10);

        $this->assertEquals($expectedUrlSet, $urls);

        $requestPropertiesCollection = [];

        foreach ($httpClientService->getHistory() as $httpTransaction) {
            /* @var RequestInterface $request */
            $request = $httpTransaction['request'];

            $requestProperties = [];

            foreach (['user-agent', 'cookie', 'authorization'] as $headerKey) {
                $requestProperties[$headerKey] = $request->getHeaderLine($headerKey);
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
        $notFoundResponse = new Response(404);

        return [
            'no urls; no sitemap, no rss need, no atom feed, no web page' => [
                'job' => $this->createJob('http://example.com/', []),
                'httpFixtures' => [
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
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
                        'user-agent' => UrlFinder::FEED_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                ],
            ],
            'no urls; no sitemap, no rss need, no atom feed' => [
                'job' => $this->createJob('http://example.com/', []),
                'httpFixtures' => [
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
                    new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('minimal')),
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
                'job' => $this->createJob('http://example.com/', []),
                'httpFixtures' => [
                    HttpFixtureFactory::createStandardRobotsTxtResponse(),
                    new Response(
                        200,
                        ['content-type' => 'text/xml'],
                        SitemapFixtureFactory::generate([
                            'example.com/one',
                            'example.com/two'
                        ])
                    ),
                    $notFoundResponse,
                    $notFoundResponse,
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
                'job' => $this->createJob('http://example.com/', []),
                'httpFixtures' => [
                    HttpFixtureFactory::createStandardRobotsTxtResponse(),
                    $notFoundResponse,
                    new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('malformed-rss-url')),
                    $notFoundResponse,
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
                    [
                        'user-agent' => UrlFinder::FEED_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                ],
            ],
            'no urls; request exception retrieving atom feed' => [
                'job' => $this->createJob('http://example.com/', []),
                'httpFixtures' => [
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
                    new Response(200, ['content-type' => 'text/html'], HtmlDocumentFactory::load('atom-feed')),
                    $notFoundResponse,
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
                        'user-agent' => UrlFinder::FEED_FINDER_USER_AGENT,
                        'cookie' => '',
                        'authorization' => '',
                    ],
                ],
            ],
            'from single sitemap.txt' => [
                'job' => $this->createJob('http://example.com/', []),
                'httpFixtures' => [
                    HttpFixtureFactory::createRobotsTxtResponse([
                        'http://example.com/sitemap.txt',
                    ]),
                    new Response(200, ['content-type' => 'text/plain'], 'http://example.com/from-sitemap-txt/'),
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
                'job' => $this->createJob('http://example.com/', []),
                'httpFixtures' => [
                    HttpFixtureFactory::createRobotsTxtResponse([
                        'http://example.com/sitemap.xml',
                    ]),
                    new Response(
                        200,
                        ['content-type' => 'application/xml'],
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
                'job' => $this->createJob('http://example.com/', []),
                'httpFixtures' => [
                    $notFoundResponse,
                    new Response(
                        200,
                        ['content-type' => 'text/xml'],
                        SitemapFixtureFactory::generate([
                            'http://example.com/from-sitemap-xml/',
                        ])
                    ),
                    new Response(200, ['content-type' => 'text/plain'], 'http://example.com/from-sitemap-txt/'),
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
                'job' => $this->createJob('http://example.com/', []),
                'httpFixtures' => [
                    HttpFixtureFactory::createRobotsTxtResponse([
                        'http://example.com/sitemap1.xml',
                        'http://example.com/sitemap2.xml',
                        'http://example.com/sitemap3.xml',
                    ]),
                    new Response(
                        200,
                        ['content-type' => 'text/xml'],
                        SitemapFixtureFactory::generate([
                            'http://example.com/one',
                            'http://example.com/two',
                            'http://example.com/three',
                            'http://example.com/four',
                            'http://example.com/five',
                            'http://example.com/six',
                        ])
                    ),
                    new Response(
                        200,
                        ['content-type' => 'text/xml'],
                        SitemapFixtureFactory::generate([
                            'http://example.com/seven',
                            'http://example.com/eight',
                            'http://example.com/nine',
                            'http://example.com/ten',
                            'http://example.com/eleven',
                            'http://example.com/twelve',
                        ])
                    ),
                    new Response(
                        200,
                        ['content-type' => 'text/xml'],
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
                'job' => $this->createJob('http://example.com/', []),
                'httpFixtures' => [
                    HttpFixtureFactory::createStandardRobotsTxtResponse(),
                    new Response(
                        200,
                        ['content-type' => 'text/xml'],
                        SitemapFixtureFactory::load('example.com-index-50-sitemaps')
                    ),
                    new Response(
                        200,
                        ['content-type' => 'text/xml'],
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
                'job' => $this->createJob('http://example.com/', []),
                'httpFixtures' => [
                    HttpFixtureFactory::createStandardRobotsTxtResponse(),
                    new Response(
                        200,
                        ['content-type' => 'text/xml'],
                        SitemapFixtureFactory::load('example.com-index-50-sitemaps')
                    ),
                    new Response(
                        200,
                        ['content-type' => 'text/xml'],
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
                'job' => $this->createJob('http://example.com/', [
                    'cookies' => [
                        [
                            'Name' => 'foo',
                            'Value' => 'bar',
                            'Domain' => '.example.com',
                        ],
                    ],
                    'http-auth-username' => 'user',
                    'http-auth-password' => 'password',
                ]),
                'httpFixtures' => [
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::load('atom-feed')
                    ),
                    new Response(
                        200,
                        ['content-type' => 'application/atom+xml'],
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
                        'user-agent' => UrlFinder::FEED_FINDER_USER_AGENT,
                        'cookie' => 'foo=bar',
                        'authorization' => 'Basic dXNlcjpwYXNzd29yZA==',
                    ],
                ],
            ],
            'from rss feed with cookies and authorization' => [
                'job' => $this->createJob('http://example.com/', [
                    'cookies' => [
                        [
                            'Name' => 'foo',
                            'Value' => 'bar',
                            'Domain' => '.example.com',
                        ],
                    ],
                    'http-auth-username' => 'user',
                    'http-auth-password' => 'password',
                ]),
                'httpFixtures' => [
                    $notFoundResponse,
                    $notFoundResponse,
                    $notFoundResponse,
                    new Response(
                        200,
                        ['content-type' => 'text/html']
                    ),
                    new Response(
                        200,
                        ['content-type' => 'text/html'],
                        HtmlDocumentFactory::load('rss-feed')
                    ),
                    new Response(
                        200,
                        ['content-type' => 'application/rss+xml'],
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
                        'user-agent' => UrlFinder::FEED_FINDER_USER_AGENT,
                        'cookie' => 'foo=bar',
                        'authorization' => 'Basic dXNlcjpwYXNzd29yZA==',
                    ],
                    [
                        'user-agent' => UrlFinder::FEED_FINDER_USER_AGENT,
                        'cookie' => 'foo=bar',
                        'authorization' => 'Basic dXNlcjpwYXNzd29yZA==',
                    ],
                ],
            ],
            'from single sitemap.xml with cookies and authorization' => [
                'job' => $this->createJob('http://example.com/', [
                    'cookies' => [
                        [
                            'Name' => 'foo',
                            'Value' => 'bar',
                            'Domain' => '.example.com',
                        ],
                    ],
                    'http-auth-username' => 'user',
                    'http-auth-password' => 'password',
                ]),
                'httpFixtures' => [
                    HttpFixtureFactory::createRobotsTxtResponse([
                        'http://example.com/sitemap.xml',
                    ]),
                    new Response(
                        200,
                        ['content-type' => 'application/xml'],
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
            'from multiple sitemaps; transfer error on first sitemap' => [
                $this->createJob('http://example.com/', []),
                'httpFixtures' => [
                    HttpFixtureFactory::createStandardRobotsTxtResponse(),
                    new Response(
                        200,
                        ['content-type' => 'text/xml'],
                        SitemapFixtureFactory::load('example.com-index-50-sitemaps')
                    ),
                    $notFoundResponse,
                    new Response(
                        200,
                        ['content-type' => 'text/xml'],
                        SitemapFixtureFactory::generate([
                            'http://example.com/ten',
                            'http://example.com/eleven',
                            'http://example.com/twelve',
                            'http://example.com/thirteen',
                            'http://example.com/fourteen',
                            'http://example.com/fifteen',
                            'http://example.com/sixteen',
                            'http://example.com/seventeen',
                            'http://example.com/eighteen',
                            'http://example.com/nineteen',
                            'http://example.com/twenty',
                            'http://example.com/twenty-one',
                        ])
                    ),
                ],
                'expectedUrlSet' => [
                    'http://example.com/ten',
                    'http://example.com/eleven',
                    'http://example.com/twelve',
                    'http://example.com/thirteen',
                    'http://example.com/fourteen',
                    'http://example.com/fifteen',
                    'http://example.com/sixteen',
                    'http://example.com/seventeen',
                    'http://example.com/eighteen',
                    'http://example.com/nineteen',
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
        ];
    }

    /**
     * @param string $canonicalUrl
     * @param array $parameters
     *
     * @return Job
     */
    private function createJob($canonicalUrl, array $parameters)
    {
        $website = new WebSite();
        $website->setCanonicalUrl($canonicalUrl);

        $job = new Job();
        $job->setWebsite($website);
        $job->setParameters(json_encode($parameters));

        return $job;
    }
}
