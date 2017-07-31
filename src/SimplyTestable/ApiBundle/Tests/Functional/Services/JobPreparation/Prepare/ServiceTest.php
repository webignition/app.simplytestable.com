<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\JobPreparation\Prepare;

use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Exception\Services\JobPreparation\Exception as JobPreparationException;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
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
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = new JobFactory($this->container);
    }

    public function testJobInWrongState()
    {
        $this->setExpectedException(
            JobPreparationException::class
        );

        $job = $this->jobFactory->create();
        $this->getJobPreparationService()->prepare($job);
    }

    /**
     * @dataProvider prepareDataProvider
     *
     * @param array $jobValues
     * @param array $httpFixtures
     * @param int $expectedTaskCount
     * @param string $expectedJobState
     * @param string[] $expectedTaskUrls
     * @param array $expectedTaskParameters
     * @param float $sitemapRetrieverTimeout
     */
    public function testPrepare(
        $jobValues,
        $httpFixtures,
        $expectedTaskCount,
        $expectedJobState,
        $expectedTaskUrls,
        $expectedTaskParameters,
        $sitemapRetrieverTimeout = null
    ) {
        $user = $this->getUserService()->getPublicUser();
        $this->getUserService()->setUser($user);

        $job = $this->jobFactory->create($jobValues);
        $this->jobFactory->resolve($job);

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

        foreach ($job->getTasks() as $taskIndex => $task) {
            /* @var Task $task */
            $this->assertEquals($expectedTaskUrls[$taskIndex], $task->getUrl());
            $this->assertEquals($expectedTaskParameters, $task->getParametersArray());
        }
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
                'expectedTaskParameters' => [],
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
                'expectedTaskParameters' => [],
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
                'expectedTaskParameters' => [],
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
                'expectedTaskParameters' => [],
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
                'expectedTaskParameters' => [],
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
                'expectedTaskParameters' => [],
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
                'expectedTaskParameters' => [],
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
                'expectedTaskParameters' => [],
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
                'expectedTaskParameters' => [],
            ],
            'task parameters' => [
                'jobValues' => [
                    JobFactory::KEY_PARAMETERS => [
                        'http-auth-username' => 'foo',
                        'http-auth-password'=> 'bar',
                    ],
                ],
                'httpFixtures' => [
                    HttpFixtureFactory::createStandardRobotsTxtResponse(),
                    HttpFixtureFactory::createSuccessResponse(
                        'text/xml',
                        SitemapFixtureFactory::generate([
                            'http://example.com/one',
                        ])
                    ),
                ],
                'expectedTaskCount' => 1,
                'expectedJobState' => JobService::QUEUED_STATE,
                'expectedTaskUrls' => [
                    'http://example.com/one',
                ],
                'expectedTaskParameters' => [
                    'http-auth-username' => 'foo',
                    'http-auth-password'=> 'bar',
                ],
            ],
        ];
    }

    public function testCrawlJobTakesParametersOfParentJob()
    {
        $userFactory = new UserFactory($this->container);

        $user = $userFactory->create();
        $this->getUserService()->setUser($user);

        $job = $this->jobFactory->create([
            JobFactory::KEY_USER => $user,
            JobFactory::KEY_PARAMETERS => [
                'http-auth-username' => 'example',
                'http-auth-password' => 'password'
            ],
        ]);
        $this->jobFactory->resolve($job);

        $this->queuePrepareHttpFixturesForCrawlJob($job->getWebsite()->getCanonicalUrl());
        $this->getJobPreparationService()->prepare($job);

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->assertEquals(
            $crawlJobContainer->getParentJob()->getParameters(),
            $crawlJobContainer->getCrawlJob()->getParameters()
        );
    }
}
