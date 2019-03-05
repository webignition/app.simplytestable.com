<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Services;

use App\Entity\Job\Job;
use App\Entity\Task\Task;
use App\Entity\User;
use App\Repository\JobRepository;
use App\Services\JobService;
use App\Services\JobTypeService;
use App\Services\StateService;
use App\Services\TaskService;
use App\Services\WebSiteService;
use App\Tests\Factory\JobFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;

class JobRepositoryTest extends AbstractBaseTestCase
{
    /**
     * @var JobRepository
     */
    private $jobRepository;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var UserFactory
     */
    private $userFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $this->jobRepository = $entityManager->getRepository(Job::class);

        $this->jobFactory = new JobFactory(self::$container);
        $this->userFactory = new UserFactory(self::$container);
    }

    /**
     * @dataProvider getByStatesAndTaskStatesDataProvider
     *
     * @param string[] $jobStateNames
     * @param string[] $taskStateNames
     * @param int[] $expectedJobIndices
     */
    public function testGetByStatesAndTaskStates($jobStateNames, $taskStateNames, $expectedJobIndices)
    {
        $stateService = self::$container->get(StateService::class);

        $jobs = $this->createJobsForAllJobStatesWithTasksForAllTaskStates();

        $expectedJobIds = $this->createExpectedJobIdsFromExpectedJobIndices($jobs, $expectedJobIndices);

        $jobStates = $stateService->getCollection($jobStateNames);
        $taskStates = $stateService->getCollection($taskStateNames);

        $retrievedJobs = $this->jobRepository->getByStatesAndTaskStates($jobStates, $taskStates);

        $this->assertEquals($expectedJobIds, $this->getJobIds($retrievedJobs));
    }

    /**
     * @return array
     */
    public function getByStatesAndTaskStatesDataProvider()
    {
        return [
            'job-states[none specified], task-states[none specified]' => [
                'jobStateNames' => [],
                'taskStateNames' => [],
                'expectedJobIndices' => [],
            ],
            'job-states[in-progress], task-states[none specified]' => [
                'jobStateNames' => [
                    Job::STATE_IN_PROGRESS,
                ],
                'taskStateNames' => [],
                'expectedJobIndices' => [],
            ],
            'job-states[none specified], task-states[in-progress]' => [
                'jobStateNames' => [],
                'taskStateNames' => [
                    Task::STATE_IN_PROGRESS,
                ],
                'expectedJobIndices' => [],
            ],
            'job-states[in-progress], task-states[cancelled]' => [
                'jobStateNames' => [
                    Job::STATE_IN_PROGRESS,
                ],
                'taskStateNames' => [
                    Task::STATE_CANCELLED,
                ],
                'expectedJobIndices' => [],
            ],
            'job-states[in-progress], task-states[completed]' => [
                'jobStateNames' => [
                    Job::STATE_IN_PROGRESS,
                ],
                'taskStateNames' => [
                    Task::STATE_IN_PROGRESS,
                ],
                'expectedJobIndices' => [7],
            ],
            'job-states[in-progress, cancelled], task-states[completed]' => [
                'jobStateNames' => [
                    Job::STATE_IN_PROGRESS,
                    Job::STATE_CANCELLED,
                ],
                'taskStateNames' => [
                    Task::STATE_COMPLETED,
                ],
                'expectedJobIndices' => [1, 7],
            ],
        ];
    }

    /**
     * @dataProvider getIdsByStateDataProvider
     *
     * @param array $jobValuesCollection
     * @param string $stateName
     * @param int[] $expectedJobIndices
     */
    public function testGetIdsByState($jobValuesCollection, $stateName, $expectedJobIndices)
    {
        $stateService = self::$container->get(StateService::class);
        $state = $stateService->get($stateName);

        $jobs = $this->createJobs($jobValuesCollection);

        $expectedJobIds = $this->createExpectedJobIdsFromExpectedJobIndices($jobs, $expectedJobIndices);
        $retrievedIds = $this->jobRepository->getIdsByState($state);

        $this->assertEquals($expectedJobIds, $retrievedIds);
    }

    /**
     * @return array
     */
    public function getIdsByStateDataProvider()
    {
        return [
            'no jobs' => [
                'jobValuesCollection' => [],
                'stateName' => Job::STATE_CANCELLED,
                'expectedJobIndices' => [],
            ],
            'cancelled' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://0.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://1.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_REJECTED,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://2.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                ],
                'stateName' => Job::STATE_CANCELLED,
                'expectedJobIndices' => [0, 2],
            ],
        ];
    }

    /**
     * @dataProvider getCountByStateDataProvider
     *
     * @param array $jobValuesCollection
     * @param string $stateName
     * @param int[] $expectedCount
     */
    public function testGetCountByState($jobValuesCollection, $stateName, $expectedCount)
    {
        $stateService = self::$container->get(StateService::class);
        $state = $stateService->get($stateName);

        $this->createJobs($jobValuesCollection);

        $count = $this->jobRepository->getCountByState($state);

        $this->assertEquals($expectedCount, $count);
    }

    /**
     * @return array
     */
    public function getCountByStateDataProvider()
    {
        return [
            'no jobs' => [
                'jobValuesCollection' => [],
                'stateName' => Job::STATE_CANCELLED,
                'expectedCount' => 0,
            ],
            'no matches' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://0.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://1.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_REJECTED,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://2.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                ],
                'stateName' => Job::STATE_COMPLETED,
                'expectedCount' => 0,
            ],
            'one match' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://0.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://1.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_REJECTED,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://2.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                ],
                'stateName' => Job::STATE_REJECTED,
                'expectedCount' => 1,
            ],
            'two matches' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://0.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://1.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_REJECTED,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://2.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                ],
                'stateName' => Job::STATE_CANCELLED,
                'expectedCount' => 2,
            ],
        ];
    }

    /**
     * @dataProvider getJobCountByUserAndJobTypeAndWebsiteForPeriodDataProvider
     *
     * @param array $jobValuesCollection
     * @param string $userName
     * @param string $jobTypeName
     * @param string $websiteUrl
     * @param string $periodStart
     * @param string $periodEnd
     * @param int $expectedCount
     */
    public function testGetJobCountByUserAndJobTypeAndWebsiteForPeriod(
        $jobValuesCollection,
        $userName,
        $jobTypeName,
        $websiteUrl,
        $periodStart,
        $periodEnd,
        $expectedCount
    ) {
        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();
        $user = $users[$userName];

        $websiteService = self::$container->get(WebSiteService::class);
        $jobTypeService = self::$container->get(JobTypeService::class);

        $jobType = $jobTypeService->get($jobTypeName);
        $website = $websiteService->get($websiteUrl);

        $this->createJobs($jobValuesCollection, $users);

        $count = $this->jobRepository->getJobCountByUserAndJobTypeAndWebsiteForPeriod(
            $user,
            $jobType,
            $website,
            $periodStart,
            $periodEnd
        );

        $this->assertEquals($expectedCount, $count);
    }

    /**
     * @return array
     */
    public function getJobCountByUserAndJobTypeAndWebsiteForPeriodDataProvider()
    {
        return [
            'no jobs' => [
                'jobValuesCollection' => [],
                'userName' => 'public',
                'jobTypeName' => JobTypeService::FULL_SITE_NAME,
                'websiteUrl' => 'http://example.com/',
                'periodStart' => '2017-01-01',
                'periodEnd' => '2017-01-01 23:59:59',
                'expectedCount' => 0,
            ],
            'no matching jobs' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-01-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-01-01 23:59:59',
                        JobFactory::KEY_STATE => Job::STATE_COMPLETED,
                    ],
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-02-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-02-01 23:59:59',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                ],
                'userName' => 'public',
                'jobTypeName' => JobTypeService::FULL_SITE_NAME,
                'websiteUrl' => 'http://example.com/',
                'periodStart' => '2017-03-01',
                'periodEnd' => '2017-03-01',
                'expectedCount' => 0,
            ],
            'no matching jobs for user' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-01-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-01-01 23:59:59',
                        JobFactory::KEY_STATE => Job::STATE_COMPLETED,
                    ],
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-02-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-02-01 23:59:59',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                ],
                'userName' => 'private',
                'jobTypeName' => JobTypeService::FULL_SITE_NAME,
                'websiteUrl' => 'http://example.com/',
                'periodStart' => '2017-01-01',
                'periodEnd' => '2017-02-01 23:59:59',
                'expectedCount' => 0,
            ],
            'no matching jobs for job type' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-01-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-01-01 23:59:59',
                        JobFactory::KEY_STATE => Job::STATE_COMPLETED,
                    ],
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-02-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-02-01 23:59:59',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                ],
                'userName' => 'public',
                'jobTypeName' => JobTypeService::SINGLE_URL_NAME,
                'websiteUrl' => 'http://example.com/',
                'periodStart' => '2017-01-01',
                'periodEnd' => '2017-02-01 23:59:59',
                'expectedCount' => 0,
            ],
            'no matching jobs for website' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-01-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-01-01 23:59:59',
                        JobFactory::KEY_STATE => Job::STATE_COMPLETED,
                    ],
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-02-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-02-01 23:59:59',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                ],
                'userName' => 'public',
                'jobTypeName' => JobTypeService::FULL_SITE_NAME,
                'websiteUrl' => 'http://foo.example.com/',
                'periodStart' => '2017-01-01',
                'periodEnd' => '2017-02-01 23:59:59',
                'expectedCount' => 0,
            ],
            'single match: first job' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-01-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-01-01 23:59:59',
                        JobFactory::KEY_STATE => Job::STATE_COMPLETED,
                    ],
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-02-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-02-01 23:59:59',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                ],
                'userName' => 'public',
                'jobTypeName' => JobTypeService::FULL_SITE_NAME,
                'websiteUrl' => 'http://example.com/',
                'periodStart' => '2017-01-01',
                'periodEnd' => '2017-01-01 23:59:59',
                'expectedCount' => 1,
            ],
            'single match: second job' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-01-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-01-01 23:59:59',
                        JobFactory::KEY_STATE => Job::STATE_COMPLETED,
                    ],
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-02-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-02-01 23:59:59',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                ],
                'userName' => 'public',
                'jobTypeName' => JobTypeService::FULL_SITE_NAME,
                'websiteUrl' => 'http://example.com/',
                'periodStart' => '2017-02-01',
                'periodEnd' => '2017-02-01 23:59:59',
                'expectedCount' => 1,
            ],
            'two matches' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-01-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-01-01 23:59:59',
                        JobFactory::KEY_STATE => Job::STATE_COMPLETED,
                    ],
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-02-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-02-01 23:59:59',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                ],
                'userName' => 'public',
                'jobTypeName' => JobTypeService::FULL_SITE_NAME,
                'websiteUrl' => 'http://example.com/',
                'periodStart' => '2017-01-01',
                'periodEnd' => '2017-02-01 23:59:59',
                'expectedCount' => 2,
            ],
        ];
    }

    /**
     * @dataProvider getIsPublicByJobIdDataProvider
     *
     * @param array $jobValues
     * @param bool $callSetPublic
     * @param bool $expectedIsPublic
     */
    public function testGetIsPublicByJobId($jobValues, $callSetPublic, $expectedIsPublic)
    {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');


        $users = $this->userFactory->createPublicPrivateAndTeamUserSet();

        if (empty($jobValues)) {
            $jobId = 1;
        } else {
            $jobValues[JobFactory::KEY_USER] = $users[$jobValues[JobFactory::KEY_USER]];
            $job = $this->jobFactory->create($jobValues);
            $jobId = $job->getId();

            if ($callSetPublic) {
                $job->setIsPublic(true);

                $entityManager->persist($job);
                $entityManager->flush();
            }
        }

        $isPublic = $this->jobRepository->getIsPublicByJobId($jobId);

        $this->assertEquals($expectedIsPublic, $isPublic);
    }

    /**
     * @return array
     */
    public function getIsPublicByJobIdDataProvider()
    {
        return [
            'no jobs' => [
                'jobValues' => [],
                'callSetPublic' => false,
                'expectedIsPublic' => false,
            ],
            'public job is public' => [
                'jobValues' => [
                    JobFactory::KEY_USER => 'public',
                ],
                'callSetPublic' => false,
                'expectedIsPublic' => true,
            ],
            'private job is not public' => [
                'jobValuesCollection' => [
                    JobFactory::KEY_USER => 'private',
                ],
                'callSetPublic' => false,
                'expectedIsPublic' => false,
            ],
            'private job made public is public' => [
                'jobValuesCollection' => [
                    JobFactory::KEY_USER => 'private',
                ],
                'callSetPublic' => true,
                'expectedIsPublic' => true,
            ],
        ];
    }

    public function testExists()
    {
        $job = $this->jobFactory->create();
        $jobId = $job->getId();
        $nonExistentJobId = $jobId + 1;

        $this->assertTrue($this->jobRepository->exists($jobId));
        $this->assertFalse($this->jobRepository->exists($nonExistentJobId));
    }

    public function testIsOwnedByUser()
    {
        $user1 = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'user1@example.com',
        ]);

        $user2 = $this->userFactory->create([
            UserFactory::KEY_EMAIL => 'user2@example.com',
        ]);

        $jobOwnedByUser1 = $this->jobFactory->create([
            JobFactory::KEY_USER => $user1,
        ]);

        $jobOwnedByUser2 = $this->jobFactory->create([
            JobFactory::KEY_USER => $user2,
        ]);

        $this->assertTrue($this->jobRepository->isOwnedByUser($user1, $jobOwnedByUser1->getId()));
        $this->assertFalse($this->jobRepository->isOwnedByUser($user2, $jobOwnedByUser1->getId()));
        $this->assertTrue($this->jobRepository->isOwnedByUser($user2, $jobOwnedByUser2->getId()));
        $this->assertFalse($this->jobRepository->isOwnedByUser($user1, $jobOwnedByUser2->getId()));
    }

    /**
     * @dataProvider isOwnedByUsersDataProvider
     */
    public function testIsOwnedByUsers(
        string $ownerName,
        array $userNames,
        bool $expectedIsOwnedByUsers
    ) {
        $allUsers = $this->userFactory->createPublicPrivateAndTeamUserSet();
        $owner = $allUsers[$ownerName];
        $users = [];

        foreach ($userNames as $userName) {
            $users[] = $allUsers[$userName];
        }

        $jobValues[JobFactory::KEY_USER] = $owner;

        $job = $this->jobFactory->create($jobValues);
        $jobId = $job->getId();

        $this->assertEquals($expectedIsOwnedByUsers, $this->jobRepository->isOwnedByUsers($users, $jobId));
    }

    public function isOwnedByUsersDataProvider(): array
    {
        return [
            'owner=public; users=public' => [
                'ownerName' => 'public',
                'userNames' => [
                    'public',
                ],
                'expectedIsOwnedByUsers' => true,
            ],
            'owner=public; users=leader' => [
                'ownerName' => 'public',
                'userNames' => [
                    'leader',
                ],
                'expectedIsOwnedByUsers' => false,
            ],
            'owner=public; users=public,leader' => [
                'ownerName' => 'public',
                'userNames' => [
                    'public',
                    'leader',
                ],
                'expectedIsOwnedByUsers' => true,
            ],
            'owner=leader; users=leader,member1,member2' => [
                'ownerName' => 'leader',
                'userNames' => [
                    'leader',
                    'member1',
                    'member2',
                ],
                'expectedIsOwnedByUsers' => true,
            ],
            'owner=member1; users=leader,member1,member2' => [
                'ownerName' => 'member1',
                'userNames' => [
                    'leader',
                    'member1',
                    'member2',
                ],
                'expectedIsOwnedByUsers' => true,
            ],
            'owner=member2; users=leader,member1,member2' => [
                'ownerName' => 'member2',
                'userNames' => [
                    'leader',
                    'member1',
                    'member2',
                ],
                'expectedIsOwnedByUsers' => true,
            ],
        ];
    }

    /**
     * @return Job[]
     */
    private function createJobsForAllJobStatesWithTasksForAllTaskStates()
    {
        $jobService = self::$container->get(JobService::class);
        $taskService = self::$container->get(TaskService::class);
        $stateService = self::$container->get(StateService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $jobStateNames = array_merge($jobService->getFinishedStateNames(), $jobService->getIncompleteStateNames());

        $taskStateNames = $taskService->getAvailableStateNames();

        /* @var Job[] $jobs */
        $jobs = [];

        /* @var Task[] $tasks */
        $tasks = [];

        foreach ($jobStateNames as $jobStateNameIndex => $jobStateName) {
            $domain = $jobStateName . '.example.com';

            $job = $this->jobFactory->createResolveAndPrepare(
                [
                    JobFactory::KEY_SITE_ROOT_URL => 'http://' . $domain . '/',
                    JobFactory::KEY_STATE => $jobStateName,
                ],
                [],
                $domain
            );

            $tasks = array_merge($tasks, $job->getTasks()->toArray());
            $jobs[] = $job;
        }

        $taskStateCount = count($taskStateNames);

        foreach ($tasks as $taskIndex => $task) {
            $taskState = $stateService->get($taskStateNames[$taskIndex % $taskStateCount]);
            $task->setState($taskState);
            $entityManager->persist($task);
        }

        $entityManager->flush();

        return $jobs;
    }

    /**
     * @param Job[] $jobs
     *
     * @return int[] array
     */
    private function getJobIds($jobs)
    {
        $jobIds = [];

        foreach ($jobs as $job) {
            $jobIds[] = $job->getId();
        }

        return $jobIds;
    }

    /**
     * @param Job[] $jobs
     * @param int[] $expectedJobIndices
     *
     * @return int[]
     */
    private function createExpectedJobIdsFromExpectedJobIndices($jobs, $expectedJobIndices)
    {
        $expectedJobIds = [];

        foreach ($jobs as $jobIndex => $job) {
            if (in_array($jobIndex, $expectedJobIndices)) {
                $expectedJobIds[] = $job->getId();
            }
        }

        return $expectedJobIds;
    }

    /**
     * @param array $jobValuesCollection
     * @param User[] $users
     *
     * @return Job[]
     */
    private function createJobs($jobValuesCollection, $users = [])
    {
        $jobs = [];

        foreach ($jobValuesCollection as $jobValues) {
            if (isset($jobValues[JobFactory::KEY_USER])) {
                $jobValues[JobFactory::KEY_USER] = $users[$jobValues[JobFactory::KEY_USER]];
            }

            $jobs[] = $this->jobFactory->create($jobValues);
        }

        return $jobs;
    }
}
