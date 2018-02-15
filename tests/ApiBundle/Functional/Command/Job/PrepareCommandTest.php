<?php

namespace Tests\ApiBundle\Functional\Command\Job;

use SimplyTestable\ApiBundle\Command\Job\PrepareCommand;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\Resque\QueueService;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\SitemapFixtureFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use webignition\WebResource\Service\Configuration as WebResourceServiceConfiguration;
use webignition\WebResource\Service\Service as WebResourceService;

class PrepareCommandTest extends AbstractBaseTestCase
{
    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var PrepareCommand
     */
    private $prepareCommand;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = new JobFactory($this->container);

        $this->prepareCommand = $this->container->get(PrepareCommand::class);
    }

    public function testRunWithJobInWrongState()
    {
        $job = $this->jobFactory->create();

        $returnCode = $this->prepareCommand->run(new ArrayInput([
            'id' => $job->getId(),
        ]), new BufferedOutput());

        $this->assertEquals(PrepareCommand::RETURN_CODE_CANNOT_PREPARE_IN_WRONG_STATE, $returnCode);
    }

    /**
     * @dataProvider runDataProvider
     *
     * @param string $user
     * @param array $jobValues
     * @param array $httpFixtures
     * @param int $expectedReturnCode
     * @param string $expectedJobState
     * @param bool $expectedHasCrawlJob
     * @param int $expectedTaskCount
     */
    public function testRun(
        $user,
        $jobValues,
        $httpFixtures,
        $expectedReturnCode,
        $expectedJobState,
        $expectedHasCrawlJob,
        $expectedTaskCount
    ) {
        $webResourceServiceConfiguration = $this->container->get(WebResourceServiceConfiguration::class);
        $updatedWebResourceServiceConfiguration = $webResourceServiceConfiguration->createFromCurrent([
            WebResourceServiceConfiguration::CONFIG_RETRY_WITH_URL_ENCODING_DISABLED => false,
        ]);

        $webResourceService = $this->container->get(WebResourceService::class);
        $webResourceService->setConfiguration($updatedWebResourceServiceConfiguration);

        $crawlJobContainerService = $this->container->get(CrawlJobContainerService::class);
        $resqueQueueService = $this->container->get(QueueService::class);
        $resqueQueueService->getResque()->getQueue('tasks-notify')->clear();
        $resqueQueueService->getResque()->getQueue('task-assign-collection')->clear();

        $userFactory = new UserFactory($this->container);
        $users = $userFactory->createPublicAndPrivateUserSet();
        $jobValues = array_merge($jobValues, [
            JobFactory::KEY_USER => $users[$user],
        ]);

        $job = $this->jobFactory->create($jobValues);
        $this->jobFactory->resolve($job);

        $this->queueHttpFixtures($httpFixtures);

        $returnCode = $this->prepareCommand->run(new ArrayInput([
            'id' => $job->getId(),
        ]), new BufferedOutput());

        $this->assertEquals($expectedReturnCode, $returnCode);
        $this->assertEquals($expectedJobState, $job->getState()->getName());
        $this->assertCount($expectedTaskCount, $job->getTasks());
        $this->assertEquals($expectedHasCrawlJob, $crawlJobContainerService->hasForJob($job));

        if ($expectedHasCrawlJob) {
            $crawlJob = $crawlJobContainerService->getForJob($job)->getCrawlJob();

            $this->assertTrue($resqueQueueService->contains(
                'task-assign-collection',
                ['ids' => $crawlJob->getTasks()->first()->getId()]
            ));
        }

        if (empty($expectedTaskCount)) {
            $this->assertTrue($resqueQueueService->isEmpty(
                'tasks-notify'
            ));
        } else {
            $this->assertTrue($resqueQueueService->contains(
                'tasks-notify'
            ));
        }
    }

    /**
     * @return array
     */
    public function runDataProvider()
    {
        return [
            'job in wrong state' => [
                'user' => 'public',
                'jobValues' => [],
                'httpFixtures' => [
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                ],
                'expectedReturnCode' => PrepareCommand::RETURN_CODE_OK,
                'expectedJobState' => Job::STATE_FAILED_NO_SITEMAP,
                'expectedHasCrawlJob' => false,
                'expectedTasksCount' => 0,
            ],
            'no urls discovered, public user' => [
                'user' => 'public',
                'jobValues' => [],
                'httpFixtures' => [
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                ],
                'expectedReturnCode' => PrepareCommand::RETURN_CODE_OK,
                'expectedJobState' => Job::STATE_FAILED_NO_SITEMAP,
                'expectedHasCrawlJob' => false,
                'expectedTasksCount' => 0,
            ],
            'no urls discovered, private user' => [
                'user' => 'private',
                'jobValues' => [],
                'httpFixtures' => [
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                    HttpFixtureFactory::createNotFoundResponse(),
                ],
                'expectedReturnCode' => PrepareCommand::RETURN_CODE_OK,
                'expectedJobState' => Job::STATE_FAILED_NO_SITEMAP,
                'expectedHasCrawlJob' => true,
                'expectedTasksCount' => 0,
            ],
            'urls discovered' => [
                'user' => 'private',
                'jobValues' => [
                    JobFactory::KEY_TEST_TYPES => ['css validation'],
                    JobFactory::KEY_TEST_TYPE_OPTIONS => [
                        'css validation' => [
                            'domains-to-ignore' => ['foo',],
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
                        ])
                    ),
                ],
                'expectedReturnCode' => PrepareCommand::RETURN_CODE_OK,
                'expectedJobState' => Job::STATE_QUEUED,
                'expectedHasCrawlJob' => false,
                'expectedTasksCount' => 3,
            ],
        ];
    }
}
