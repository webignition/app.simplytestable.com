<?php

namespace Tests\AppBundle\Functional\Services;

use AppBundle\Services\CrawlJobContainerService;
use AppBundle\Services\JobService;
use AppBundle\Services\JobSummaryFactory;
use AppBundle\Services\UserAccountPlanService;
use Tests\AppBundle\Factory\JobFactory;
use Tests\AppBundle\Factory\UserFactory;
use Tests\AppBundle\Functional\AbstractBaseTestCase;

class JobSummaryFactoryTest extends AbstractBaseTestCase
{
    /**
     * @var JobSummaryFactory
     */
    private $jobSummaryFactory;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * @var array
     */
    private $expectedTaskCountByState = [
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
    ];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobSummaryFactory = self::$container->get(JobSummaryFactory::class);
        $this->jobFactory = new JobFactory(self::$container);

        $this->userFactory = new UserFactory(self::$container);
    }

    /**
     * @dataProvider createForRegularJobDataProvider
     *
     * @param string $userName
     * @param array $jobValues
     * @param bool $expectedIsPublic
     * @param array $expectedOwners
     */
    public function testCreateForRegularJob($userName, $jobValues, $expectedIsPublic, $expectedOwners)
    {
        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();
        $user = $users[$userName];

        $jobValues[JobFactory::KEY_USER] = $user;

        $job = $this->jobFactory->create($jobValues);

        $summary = $this->jobSummaryFactory->create($job);
        $serialisedJobSummary = $summary->jsonSerialize();

        $this->assertArrayHasKey('id', $serialisedJobSummary);
        $this->assertInternalType('int', $serialisedJobSummary['id']);
        unset($serialisedJobSummary['id']);

        $this->assertNotNull($serialisedJobSummary['url_count']);
        $this->assertEquals($this->expectedTaskCountByState, $serialisedJobSummary['task_count_by_state']);

        $this->assertEquals($expectedIsPublic, $serialisedJobSummary['is_public']);
        $this->assertEquals($expectedOwners, $serialisedJobSummary['owners']);
    }

    /**
     * @return array
     */
    public function createForRegularJobDataProvider()
    {
        return [
            'public user' => [
                'userName' => 'public',
                'jobValues' => [],
                'expectedIsPublic' => true,
                'expectedOwners' => [
                    'public',
                ],
            ],
            'private user' => [
                'userName' => 'private',
                'jobValues' => [],
                'expectedIsPublic' => false,
                'expectedOwners' => [
                    'private@example.com',
                ],
            ],
            'private user, is public' => [
                'userName' => 'private',
                'jobValues' => [
                    JobFactory::KEY_SET_PUBLIC => true,
                ],
                'expectedIsPublic' => true,
                'expectedOwners' => [
                    'private@example.com',
                ],
            ],
            'team leader' => [
                'userName' => 'leader',
                'jobValues' => [],
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
     * @dataProvider createForRejectedJobDataProvider
     *
     * @param string $reason
     * @param string $constraintName
     * @param array $expectedSerializedRejection
     */
    public function testCreateForRejectedJob($reason, $constraintName, $expectedSerializedRejection)
    {
        $userAccountPlanService = self::$container->get(UserAccountPlanService::class);

        $job = $this->jobFactory->create();
        $user = $job->getUser();

        $constraint = null;

        if (!empty($constraintName)) {
            $userAccountPlan = $userAccountPlanService->getForUser($user);
            $constraint = $userAccountPlan->getPlan()->getConstraintNamed($constraintName);
        }

        $this->jobFactory->reject($job, $reason, $constraint);

        $summary = $this->jobSummaryFactory->create($job);
        $serialisedJobSummary = $summary->jsonSerialize();

        $this->assertArrayHasKey('rejection', $serialisedJobSummary);
        $this->assertInternalType('array', $serialisedJobSummary['rejection']);

        $this->assertEquals($expectedSerializedRejection, $serialisedJobSummary['rejection']);
    }

    /**
     * @return array
     */
    public function createForRejectedJobDataProvider()
    {
        return [
            'full_site_jobs_per_site limit reached' => [
                'reason' => 'plan-constraint-limit-reached',
                'constraintName' => 'full_site_jobs_per_site',
                'expectedSerializedRejection' => [
                    'reason' => 'plan-constraint-limit-reached',
                    'constraint' => [
                        'name' => 'full_site_jobs_per_site',
                        'limit' => 1,
                        'is_available' => true,
                    ],
                ],
            ],
            'single_url_jobs_per_url limit reached' => [
                'reason' => 'plan-constraint-limit-reached',
                'constraintName' => 'single_url_jobs_per_url',
                'expectedSerializedRejection' => [
                    'reason' => 'plan-constraint-limit-reached',
                    'constraint' => [
                        'name' => 'single_url_jobs_per_url',
                        'limit' => 1,
                        'is_available' => true,
                    ],
                ],
            ],
            'job resolution curl error' => [
                'reason' => 'curl-28',
                'constraintName' => null,
                'expectedSerializedRejection' => [
                    'reason' => 'curl-28',
                ],
            ],
        ];
    }

    /**
     * @dataProvider createForJobWithAmmendmentDataProvider
     *
     * @param string $reason
     * @param string $constraintName
     * @param array $expectedSerializedAmmendments
     */
    public function testCreateForJobWithAmmendment($reason, $constraintName, $expectedSerializedAmmendments)
    {
        $userAccountPlanService = self::$container->get(UserAccountPlanService::class);
        $jobService = self::$container->get(JobService::class);

        $user = $this->userFactory->create();

        $job = $this->jobFactory->create([
            JobFactory::KEY_USER => $user,
        ]);

        $userAccountPlan = $userAccountPlanService->getForUser($user);
        $constraint = $userAccountPlan->getPlan()->getConstraintNamed($constraintName);

        $jobService->addAmmendment($job, $reason, $constraint);

        $summary = $this->jobSummaryFactory->create($job);
        $serialisedJobSummary = $summary->jsonSerialize();

        $this->assertArrayHasKey('ammendments', $serialisedJobSummary);
        $this->assertInternalType('array', $serialisedJobSummary['ammendments']);

        $this->assertEquals($expectedSerializedAmmendments, $serialisedJobSummary['ammendments']);
    }

    /**
     * @return array
     */
    public function createForJobWithAmmendmentDataProvider()
    {
        return [
            'urls_per_job limit reached' => [
                'reason' => 'plan-url-limit-reached:discovered-url-count-120',
                'constraintName' => 'urls_per_job',
                'expectedSerializedAmmendments' => [
                    [
                        'reason' => 'plan-url-limit-reached:discovered-url-count-120',
                        'constraint' => [
                            'name' => 'urls_per_job',
                            'limit' => 10,
                            'is_available' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider createForCrawlJobDataProvider
     *
     * @param $expectedSerializedCrawl
     */
    public function testCreateForCrawlJob($userValues, $expectedSerializedCrawl)
    {
        $crawlJobContainerService = self::$container->get(CrawlJobContainerService::class);

        $user = $this->userFactory->create($userValues);
        $job = $this->jobFactory->createResolveAndPrepareStandardCrawlJob([
            JobFactory::KEY_USER => $user,
        ]);

        $crawlJobContainer = $crawlJobContainerService->getForJob($job);
        $crawlJob = $crawlJobContainer->getCrawlJob();

        $summary = $this->jobSummaryFactory->create($job);
        $serialisedJobSummary = $summary->jsonSerialize();

        $this->assertArrayHasKey('crawl', $serialisedJobSummary);
        $this->assertInternalType('array', $serialisedJobSummary['crawl']);

        $crawlData = $serialisedJobSummary['crawl'];

        $this->assertEquals($crawlJob->getId(), $crawlData['id']);
        unset($crawlData['id']);

        $this->assertEquals($expectedSerializedCrawl, $crawlData);
    }

    /**
     * @return array
     */
    public function createForCrawlJobDataProvider()
    {
        return [
            'basic user' => [
                'userValues' => [
                    UserFactory::KEY_PLAN_NAME => 'basic',
                ],
                'expectedSerializedCrawl' => [
                    'state' => 'queued',
                    'processed_url_count' => 0,
                    'discovered_url_count' => 1,
                    'limit' => 10,
                ],
            ],
            'personal user' => [
                'userValues' => [
                    UserFactory::KEY_PLAN_NAME => 'personal',
                ],
                'expectedSerializedCrawl' => [
                    'state' => 'queued',
                    'processed_url_count' => 0,
                    'discovered_url_count' => 1,
                    'limit' => 50,
                ],
            ],
        ];
    }
}
