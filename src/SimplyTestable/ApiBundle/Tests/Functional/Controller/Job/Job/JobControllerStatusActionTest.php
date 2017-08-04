<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Job\Job;

use SimplyTestable\ApiBundle\Command\Task\Assign\CollectionCommand;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\SitemapFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\TaskControllerCompleteActionRequestFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Guzzle\Http\Message\Response as GuzzleResponse;

class JobControllerStatusActionTest extends AbstractJobControllerTest
{
    const CANONICAL_URL = 'http://example.com/';

    /**
     * @var UserFactory
     */
    private $userFactory;

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

        $this->userFactory = new UserFactory($this->container);
        $this->jobFactory = new JobFactory($this->container);
    }

    public function testRequest()
    {
        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://example.com',
        ]);

        $this->getCrawler([
            'url' => $this->container->get('router')->generate('job_job_status', [
                'test_id' => $job->getId(),
                'site_root_url' => $job->getWebsite()->getCanonicalUrl(),
            ])
        ]);

        /* @var RedirectResponse $response */
        $response = $this->getClientResponse();

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testResponseContentRegularJob()
    {
        $canonicalUrl = 'http://example.com/';

        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
            JobFactory::KEY_PARAMETERS => [
                'http-auth-username' => 'example',
                'http-auth-password' => 'password',
            ],
        ]);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $response = $this->jobController->statusAction($canonicalUrl, $job->getId());
        $responseJobData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertEquals([
            'id' => $job->getId(),
            'user' => 'public',
            'website' => 'http://example.com/',
            'state' => 'new',
            'url_count' => 0,
            'task_count' => 0,
            'task_count_by_state' => [
                'cancelled' => 0,
                'queued' => 0,
                'in-progress' => 0,
                'completed' => 0,
                'awaiting-cancellation' => 0,
                'queued-for-assignment' => 0,
                'failed-no-retry-available' => 0,
                'failed-retry-available' => 0,
                'failed-retry-limit-reached' => 0,
                'skipped' => 0,
            ],
            'task_types' => [
                [
                    'name' => 'HTML validation',
                ],
            ],
            'errored_task_count' => 0,
            'cancelled_task_count' => 0,
            'skipped_task_count' => 0,
            'warninged_task_count' => 0,
            'task_type_options' => [],
            'type' => 'Full site',
            'is_public' => true,
            'parameters' => '{"http-auth-username":"example","http-auth-password":"password"}',
            'error_count' => 0,
            'warning_count' => 0,
            'owners' => [
                'public',
            ],
        ], $responseJobData);
    }

    public function testResponseContentQueuedCrawlJob()
    {
        $crawlJobContainerService = $this->container->get('simplytestable.services.crawljobcontainerservice');

        $user = $this->userFactory->createAndActivateUser();
        $this->getUserService()->setUser($user);

        $parentJob = $this->jobFactory->createResolveAndPrepareStandardCrawlJob([
            JobFactory::KEY_USER => $user,
        ]);

        $crawlJobContainer = $crawlJobContainerService->getForJob($parentJob);
        $crawlJob = $crawlJobContainer->getCrawlJob();

        $response = $this->jobController->statusAction(
            $parentJob->getWebsite()->getCanonicalUrl(),
            $parentJob->getId()
        );

        $responseJobData = json_decode($response->getContent(), true);

        $this->assertArraySubset([
            'crawl' => [
                'state' => 'queued',
                'limit' => 10,
                'id' => $crawlJob->getId(),
            ],
        ], $responseJobData);
    }

    public function testResponseContentInProgressCrawlJob()
    {
        $crawlJobContainerService = $this->container->get('simplytestable.services.crawljobcontainerservice');
        $userAccountPlanService = $this->container->get('simplytestable.services.useraccountplanservice');

        $user = $this->userFactory->createAndActivateUser();
        $this->getUserService()->setUser($user);

        $job = $this->jobFactory->createResolveAndPrepareStandardCrawlJob([
            JobFactory::KEY_USER => $user,
        ]);

        $workerFactory = new WorkerFactory($this->container);
        $workerFactory->create();

        $crawlJobContainer = $crawlJobContainerService->getForJob($job);
        $crawlJobContainerService->prepare($crawlJobContainer);

        /* @var Task $task */
        $task = $crawlJobContainer->getCrawlJob()->getTasks()->first();

        $this->queueHttpFixtures([
            HttpFixtureFactory::createSuccessResponse(
                'application/json',
                json_encode([
                    [
                        'id' => 1,
                        'url' => $task->getUrl(),
                        'type' => $task->getType()->getName(),
                    ],
                ])
            ),
        ]);

        $taskAssignCollectionCommand = new CollectionCommand();
        $taskAssignCollectionCommand->setContainer($this->container);

        $taskAssignCollectionCommand->run(new ArrayInput([
            'ids' => $task->getId()
        ]), new BufferedOutput());

        $user = $task->getJob()->getUser();
        $userAccountPlan = $userAccountPlanService->getForUser($user);

        $urlCountToDiscover = (int)round(
            $userAccountPlan->getPlan()->getConstraintNamed('urls_per_job')->getLimit() / 2
        );

        $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
            'end_date_time' => '2012-03-08 17:03:00',
            'output' => json_encode($this->createUrlResultSet(
                $job->getWebsite()->getCanonicalUrl(),
                $urlCountToDiscover
            )),
            'contentType' => 'application/json',
            'state' => 'completed',
            'errorCount' => 0,
            'warningCount' => 0
        ], [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $task->getType(),
            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $task->getUrl(),
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $task->getParametersHash(),
        ]);

        $taskController = $this->createControllerFactory()->createTaskController($taskCompleteRequest);
        $taskController->completeAction();

        $responseJobData = json_decode(
            $this->jobController->statusAction($job->getWebsite()->getCanonicalUrl(), $job->getId())->getContent(),
            true
        );

        $this->assertArraySubset([
            'crawl' => [
                'state' => 'in-progress',
                'processed_url_count' => 1,
                'discovered_url_count' => 6,
                'limit' => 10,
            ],
        ], $responseJobData);
    }

    public function testAccessForbidden()
    {
        $users = $this->userFactory->createPublicAndPrivateUserSet();

        $ownerUser = $users['private'];
        $requesterUser = $users['public'];

        $this->getUserService()->setUser($ownerUser);
        $canonicalUrl = 'http://example.com/';

        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
            JobFactory::KEY_USER => $ownerUser,
        ]);

        $this->getUserService()->setUser($requesterUser);

        $response = $this->jobController->statusAction($canonicalUrl, $job->getId());

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testForRejectedDueToPlanFullSiteConstraint()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $canonicalUrl = 'http://example.com/';

        $user = $this->getUserService()->getPublicUser();
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);

        $fullSiteJobsPerSiteConstraint = $userAccountPlan->getPlan()->getConstraintNamed('full_site_jobs_per_site');

        $jobFactory = $this->jobFactory;

        $rejectedJob = $jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
        ]);

        $jobFactory->reject($rejectedJob, 'plan-constraint-limit-reached', $fullSiteJobsPerSiteConstraint);

        $responseJobData = json_decode(
            $this->jobController->statusAction($canonicalUrl, $rejectedJob->getId())->getContent(),
            true
        );

        $this->assertArraySubset([
            'rejection' => [
                'reason' => 'plan-constraint-limit-reached',
                'constraint' => [
                    'name' => 'full_site_jobs_per_site',
                ],
            ],
        ], $responseJobData);
    }

    public function testStatusForRejectedDueToPlanSingleUrlConstraint()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $canonicalUrl = 'http://example.com/';

        $user = $this->getUserService()->getPublicUser();
        $userAccountPlan = $this->getUserAccountPlanService()->getForUser($user);

        $singleJobsPerUrlConstraint = $userAccountPlan->getPlan()->getConstraintNamed('single_url_jobs_per_url');

        $jobFactory = $this->jobFactory;

        $rejectedJob = $jobFactory->create([
            JobFactory::KEY_TYPE => JobTypeService::SINGLE_URL_NAME,
            JobFactory::KEY_SITE_ROOT_URL => $canonicalUrl,
        ]);

        $jobFactory->reject($rejectedJob, 'plan-constraint-limit-reached', $singleJobsPerUrlConstraint);

        $responseJobData = json_decode(
            $this->jobController->statusAction($canonicalUrl, $rejectedJob->getId())->getContent(),
            true
        );

        $this->assertArraySubset([
            'rejection' => [
                'reason' => 'plan-constraint-limit-reached',
                'constraint' => [
                    'name' => 'single_url_jobs_per_url',
                ],
            ],
        ], $responseJobData);
    }

    public function testForJobUrlLimitAmmendment()
    {
        $this->jobFactory;

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $job = $this->jobFactory->createResolveAndPrepare([], [
            'prepare' => [
                GuzzleResponse::fromMessage("HTTP/1.1 200 OK\nContent-type:text/plain\n\nsitemap: sitemap.xml"),
                GuzzleResponse::fromMessage(sprintf(
                    "HTTP/1.1 200 OK\nContent-type:text/plain\n\n%s",
                    SitemapFixtureFactory::load('example.com-eleven-urls')
                )),
            ],
        ]);

        $statusResponse = $this->jobController->statusAction(
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );
        $responseJobData = json_decode($statusResponse->getContent(), true);

        $this->assertArraySubset([
            'ammendments' => [
                [
                    'reason' => 'plan-url-limit-reached:discovered-url-count-11',
                    'constraint' => [
                        'name' => 'urls_per_job',
                    ],
                ],
            ],
        ], $responseJobData);
    }

    /**
     * @dataProvider ownershipDataProvider
     *
     * @param string $owner
     * @param bool $expectedIsPublic
     * @param string[] $expectedOwners
     */
    public function testOwnership($owner, $expectedIsPublic, $expectedOwners)
    {
        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();

        $ownerUser = $users[$owner];

        $this->getUserService()->setUser($ownerUser);

        $job = $this->jobFactory->create([
            JobFactory::KEY_SITE_ROOT_URL => 'http://example.com',
            JobFactory::KEY_USER => $ownerUser,
        ]);

        $statusResponse = $this->jobController->statusAction(
            $job->getWebsite()->getCanonicalUrl(),
            $job->getId()
        );

        $responseJobData = json_decode($statusResponse->getContent(), true);

        $this->assertEquals(200, $statusResponse->getStatusCode());

        $this->assertEquals([
            'user' => $ownerUser->getUsername(),
            'is_public' => $expectedIsPublic,
            'owners' => $expectedOwners,
        ], [
            'user' => $responseJobData['user'],
            'is_public' => $responseJobData['is_public'],
            'owners' => $responseJobData['owners'],
        ]);
    }

    /**
     * @return array
     */
    public function ownershipDataProvider()
    {
        return [
            'public' => [
                'owner' => 'public',
                'expectedIsPublic' => true,
                'expectedOwners' => [
                    'public',
                ],
            ],
            'private' => [
                'owner' => 'private',
                'expectedIsPublic' => false,
                'expectedOwners' => [
                    'private@example.com',
                ],
            ],
            'team with leader as owner' => [
                'owner' => 'leader',
                'expectedIsPublic' => false,
                'expectedOwners' => [
                    'leader@example.com',
                    'member1@example.com',
                    'member2@example.com',
                ],
            ],
            'team with member1 as owner' => [
                'owner' => 'member1',
                'expectedIsPublic' => false,
                'expectedOwners' => [
                    'leader@example.com',
                    'member1@example.com',
                    'member2@example.com',
                ],
            ],
            'team with member2 as owner' => [
                'owner' => 'member2',
                'expectedIsPublic' => false,
                'expectedOwners' => [
                    'leader@example.com',
                    'member1@example.com',
                    'member2@example.com',
                ],
            ],
        ];
    }

    /**
     * @dataProvider issueCountDataProvider
     *
     * @param int $reportedErrorCount
     * @param int $reportedWarningCount
     */
    public function testIssueCount($reportedErrorCount, $reportedWarningCount)
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->jobFactory->createResolveAndPrepare();
        foreach ($job->getTasks() as $task) {
            $taskCompleteRequest = TaskControllerCompleteActionRequestFactory::create([
                'end_date_time' => '2012-03-08 17:03:00',
                'output' => '[]',
                'contentType' => 'application/json',
                'state' => 'completed',
                'errorCount' => $reportedErrorCount,
                'warningCount' => $reportedWarningCount,
            ], [
                CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => $task->getType(),
                CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => $task->getUrl(),
                CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => $task->getParametersHash(),
            ]);

            $taskController = $this->createControllerFactory()->createTaskController($taskCompleteRequest);
            $taskController->completeAction();
        }

        $response = $this->jobController->statusAction(self::CANONICAL_URL, $job->getId());
        $responseJobData = json_decode($response->getContent(), true);

        $taskCount = $responseJobData['task_count'];
        $expectedErrorCount = $taskCount * $reportedErrorCount;
        $expectedWarningCount = $taskCount * $reportedWarningCount;

        $this->assertArraySubset([
            'error_count' => $expectedErrorCount,
            'warning_count' => $expectedWarningCount,
        ], $responseJobData);
    }

    /**
     * @return array
     */
    public function issueCountDataProvider()
    {
        return [
            'error count: 0, warning count: 0' => [
                'reportedErrorCount' => 0,
                'reportedWarningCount' => 0,
            ],
            'error count: 0, warning count: 1' => [
                'reportedErrorCount' => 0,
                'reportedWarningCount' => 1,
            ],
            'error count: 1, warning count: 0' => [
                'reportedErrorCount' => 1,
                'reportedWarningCount' => 0,
            ],
            'error count: 1, warning count: 1' => [
                'reportedErrorCount' => 1,
                'reportedWarningCount' => 1,
            ],
            'error count: 2, warning count: 2' => [
                'reportedErrorCount' => 2,
                'reportedWarningCount' => 2,
            ],
        ];
    }
}
