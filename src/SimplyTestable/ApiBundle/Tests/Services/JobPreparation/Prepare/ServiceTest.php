<?php

namespace SimplyTestable\ApiBundle\Tests\Services\JobPreparation\Prepare;

use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Tests\BaseSimplyTestableTestCase;
use SimplyTestable\ApiBundle\Tests\Factory\AtomFeedFactory;
use SimplyTestable\ApiBundle\Tests\Factory\HtmlDocumentFactory;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\RssFeedFactory;
use SimplyTestable\ApiBundle\Tests\Factory\SitemapFixtureFactory;

class ServiceTest extends BaseSimplyTestableTestCase
{
    const EXPECTED_TASK_TYPE_COUNT = 1;

    /**
     * @dataProvider prepareDataProvider
     *
     * @param array $jobValues
     * @param array $httpFixtures
     * @param int $expectedTaskCount
     * @param string $expectedJobState
     * @param string[] $expectedTaskUrls
     * @param float $sitemapRetrieverTimeout
     */
    public function testPrepare(
        $jobValues,
        $httpFixtures,
        $expectedTaskCount,
        $expectedJobState,
        $expectedTaskUrls,
        $sitemapRetrieverTimeout = null
    ) {
        $user = $this->getUserService()->getPublicUser();
        $this->getUserService()->setUser($user);

        $jobFactory = $this->createJobFactory();
        $job = $jobFactory->create($jobValues);
        $jobFactory->resolve($job);

        if (!empty($sitemapRetrieverTimeout)) {
            $this
                ->getWebSiteService()
                ->getSitemapFinder()
                ->getSitemapRetriever()
                ->getConfiguration()
                ->setTotalTransferTimeout(0.00001);
        }

        $this->queueHttpFixtures($httpFixtures);
        $this->getJobPreparationService()->prepare($job);

        $this->assertCount($expectedTaskCount, $job->getTasks());
        $this->assertEquals($expectedJobState, $job->getState());

        $taskUrls = [];
        foreach ($job->getTasks() as $task) {
            $taskUrls[] = $task->getUrl();
        }

        $this->assertEquals($expectedTaskUrls, $taskUrls);
    }

    /**
     * @return array
     */
    public function prepareDataProvider()
    {
        return [
            'no urls' => [
                'jobValues' => [],
                'httpFixtures' => [
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/html',
                        HtmlDocumentFactory::load('minimal')
                    ),
                ],
                'expectedTaskCount' => 0,
                'expectedJobState' => JobService::FAILED_NO_SITEMAP_STATE,
                'expectedTaskUrls' => [],
            ],
            'sitemap containing only schemeless urls' => [
                'jobValues' => [],
                'httpFixtures' => [
                    HttpFixtureFactory::createStandardRobotsTxtResponse(),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/xml',
                        SitemapFixtureFactory::generate([
                            'example.com/one',
                            'example.com/two'
                        ])
                    ),
                ],
                'expectedTaskCount' => 0,
                'expectedJobState' => JobService::FAILED_NO_SITEMAP_STATE,
                'expectedTaskUrls' => [],
            ],
            'urls in multiple sitemaps' => [
                'jobValues' => [],
                'httpFixtures' => [
                    HttpFixtureFactory::createRobotsTxtResponse([
                        'http://example.com/sitemap1.xml',
                        'http://example.com/sitemap2.xml',
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
                ],
                'expectedTaskCount' => 10,
                'expectedJobState' => JobService::QUEUED_STATE,
                'expectedTaskUrls' => [
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
            ],
            'malformed rss url' => [
                'jobValues' => [],
                'httpFixtures' => [
                    HttpFixtureFactory::createStandardRobotsTxtResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/html',
                        HtmlDocumentFactory::load('malformed-rss-url')
                    ),
                ],
                'expectedTaskCount' => 0,
                'expectedJobState' => JobService::FAILED_NO_SITEMAP_STATE,
                'expectedTaskUrls' => [],
            ],
            'large sitemap collection times out retrieving all' => [
                'jobValues' => [],
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
                'expectedTaskCount' => 10,
                'expectedJobState' => JobService::QUEUED_STATE,
                'expectedTaskUrls' => [
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
                'sitemapRetrieverTimeout' => 0.00001,
            ],
            'sitemap txt' => [
                'jobValues' => [],
                'httpFixtures' => [
                    HttpFixtureFactory::createRobotsTxtResponse([
                        'http://example.com/sitemap.txt',
                    ]),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/plain',
                        'http://example.com/from-sitemap-txt/'
                    ),
                ],
                'expectedTaskCount' => 1,
                'expectedJobState' => JobService::QUEUED_STATE,
                'expectedTaskUrls' => [
                    'http://example.com/from-sitemap-txt/',
                ],
            ],
            'atom feed urls' => [
                'jobValues' => [],
                'httpFixtures' => [
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
                'expectedTaskCount' => 1,
                'expectedJobState' => JobService::QUEUED_STATE,
                'expectedTaskUrls' => [
                    'http://example.com/from-atom-feed/',
                ],
            ],
            'rss feed urls' => [
                'jobValues' => [],
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
                'expectedTaskCount' => 1,
                'expectedJobState' => JobService::QUEUED_STATE,
                'expectedTaskUrls' => [
                    'http://example.com/from-rss-feed/',
                ],
            ],
            'sitemap xml and txt' => [
                'jobValues' => [],
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
                'expectedTaskCount' => 2,
                'expectedJobState' => JobService::QUEUED_STATE,
                'expectedTaskUrls' => [
                    'http://example.com/from-sitemap-xml/',
                    'http://example.com/from-sitemap-txt/',
                ],
            ],
        ];
    }

    public function testCrawlJobTakesParametersOfParentJob()
    {
        $user = $this->getTestUser();
        $this->getUserService()->setUser($user);

        $jobFactory = $this->createJobFactory();
        $job = $jobFactory->create([
            JobFactory::KEY_USER => $user,
            JobFactory::KEY_PARAMETERS => [
                'http-auth-username' => 'example',
                'http-auth-password' => 'password'
            ],
        ]);
        $jobFactory->resolve($job);

        $this->queuePrepareHttpFixturesForCrawlJob($job->getWebsite()->getCanonicalUrl());
        $this->getJobPreparationService()->prepare($job);

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->assertEquals(
            $crawlJobContainer->getParentJob()->getParameters(),
            $crawlJobContainer->getCrawlJob()->getParameters()
        );
    }
}
