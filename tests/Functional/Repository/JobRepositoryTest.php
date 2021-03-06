<?php
/** @noinspection PhpDocSignatureInspection */

namespace App\Tests\Functional\Repository;

use App\Entity\Job\Job;
use App\Entity\Task\Task;
use App\Entity\User;
use App\Repository\JobRepository;
use App\Services\JobService;
use App\Services\JobTypeService;
use App\Services\StateService;
use App\Services\TaskService;
use App\Services\UserService;
use App\Services\WebSiteService;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\JobFactory;

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

        $this->jobRepository = self::$container->get(JobRepository::class);

        $this->jobFactory = self::$container->get(JobFactory::class);
        $this->userFactory = self::$container->get(UserFactory::class);
    }

    /**
     * @dataProvider getByStatesAndTaskStatesDataProvider
     */
    public function testGetByStatesAndTaskStates(array $jobStateNames, array $taskStateNames, array $expectedJobIndices)
    {
        $stateService = self::$container->get(StateService::class);

        $jobs = $this->createJobsForAllJobStatesWithTasksForAllTaskStates();

        $expectedJobIds = $this->createExpectedJobIdsFromExpectedJobIndices($jobs, $expectedJobIndices);

        $jobStates = $stateService->getCollection($jobStateNames);
        $taskStates = $stateService->getCollection($taskStateNames);

        $retrievedJobs = $this->jobRepository->getByStatesAndTaskStates($jobStates, $taskStates);

        $this->assertEquals($expectedJobIds, $this->getJobIds($retrievedJobs));
    }

    public function getByStatesAndTaskStatesDataProvider(): array
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
            'job-states[in-progress], task-states[failed-no-retry-available]' => [
                'jobStateNames' => [
                    Job::STATE_IN_PROGRESS,
                ],
                'taskStateNames' => [
                    Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                ],
                'expectedJobIndices' => [8],
            ],
            'job-states[in-progress, completed], task-states[failed-no-retry-available]' => [
                'jobStateNames' => [
                    Job::STATE_IN_PROGRESS,
                    Job::STATE_COMPLETED,
                ],
                'taskStateNames' => [
                    Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                ],
                'expectedJobIndices' => [2, 8],
            ],
        ];
    }

    /**
     * @dataProvider getIdsByStateDataProvider
     */
    public function testGetIdsByState(array $jobValuesCollection, string $stateName, array $expectedJobIndices)
    {
        $stateService = self::$container->get(StateService::class);
        $state = $stateService->get($stateName);

        $jobs = $this->createJobs($jobValuesCollection);

        $expectedJobIds = $this->createExpectedJobIdsFromExpectedJobIndices($jobs, $expectedJobIndices);
        $retrievedIds = $this->jobRepository->getIdsByState($state);

        $this->assertEquals($expectedJobIds, $retrievedIds);
    }

    public function getIdsByStateDataProvider(): array
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
                        JobFactory::KEY_URL => 'http://0.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                    [
                        JobFactory::KEY_URL => 'http://1.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_REJECTED,
                    ],
                    [
                        JobFactory::KEY_URL => 'http://2.example.com/',
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
     */
    public function testGetCountByState(array $jobValuesCollection, string $stateName, int $expectedCount)
    {
        $stateService = self::$container->get(StateService::class);
        $state = $stateService->get($stateName);

        $this->createJobs($jobValuesCollection);

        $count = $this->jobRepository->getCountByState($state);

        $this->assertEquals($expectedCount, $count);
    }

    public function getCountByStateDataProvider(): array
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
                        JobFactory::KEY_URL => 'http://0.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                    [
                        JobFactory::KEY_URL => 'http://1.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_REJECTED,
                    ],
                    [
                        JobFactory::KEY_URL => 'http://2.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                ],
                'stateName' => Job::STATE_COMPLETED,
                'expectedCount' => 0,
            ],
            'one match' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_URL => 'http://0.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                    [
                        JobFactory::KEY_URL => 'http://1.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_REJECTED,
                    ],
                    [
                        JobFactory::KEY_URL => 'http://2.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                ],
                'stateName' => Job::STATE_REJECTED,
                'expectedCount' => 1,
            ],
            'two matches' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_URL => 'http://0.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                    [
                        JobFactory::KEY_URL => 'http://1.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_REJECTED,
                    ],
                    [
                        JobFactory::KEY_URL => 'http://2.example.com/',
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
     */
    public function testGetJobCountByUserAndJobTypeAndWebsiteForPeriod(
        array $jobValuesCollection,
        string $userName,
        string $jobTypeName,
        string $websiteUrl,
        string $periodStart,
        string $periodEnd,
        int $expectedCount
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

    public function getJobCountByUserAndJobTypeAndWebsiteForPeriodDataProvider(): array
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
     * @dataProvider isPublicDataProvider
     */
    public function testIsPublic(callable $jobCreator, bool $expectedIsPublic)
    {
        /* @var Job $job */
        $job = $jobCreator();

        $this->assertEquals($expectedIsPublic, $this->jobRepository->isPublic($job->getId()));
    }

    public function isPublicDataProvider(): array
    {
        return [
            'public user, public job' => [
                'jobCreator' => function () {
                    $userService = self::$container->get(UserService::class);
                    $jobFactory = self::$container->get(JobFactory::class);

                    return $jobFactory->create([
                        JobFactory::KEY_USER => $userService->getPublicUser(),
                        JobFactory::KEY_SET_PUBLIC => true,
                    ]);
                },
                'expectedIsPublic' => true,
            ],
            'private user, public job' => [
                'jobCreator' => function () {
                    $jobFactory = self::$container->get(JobFactory::class);
                    $userFactory = self::$container->get(UserFactory::class);

                    return $jobFactory->create([
                        JobFactory::KEY_USER => $userFactory->createAndActivateUser(),
                        JobFactory::KEY_SET_PUBLIC => true,
                    ]);
                },
                'expectedIsPublic' => true,
            ],
            'private user, private job' => [
                'jobCreator' => function () {
                    $jobFactory = self::$container->get(JobFactory::class);
                    $userFactory = self::$container->get(UserFactory::class);

                    return $jobFactory->create([
                        JobFactory::KEY_USER => $userFactory->createAndActivateUser(),
                    ]);
                },
                'expectedIsPublic' => false,
            ],
        ];
    }

    /**
     * @dataProvider findJobsForUserOlderThanMaxAgeWithStatesDataProvider
     */
    public function testFindJobsForUserOlderThanMaxAgeWithStates(
        array $jobValuesCollection,
        string $userName,
        string $maximumAge,
        array $stateNames,
        array $expectedJobIndices
    ) {
        $stateService = self::$container->get(StateService::class);

        $users = $this->userFactory->createPublicAndPrivateUserSet();

        $jobs = $this->createJobs($jobValuesCollection, $users);
        $expectedJobIds = $this->createExpectedJobIdsFromExpectedJobIndices($jobs, $expectedJobIndices);

        $user = $users[$userName];

        $states = array_values($stateService->getCollection($stateNames));

        /* @var Job[] $retrievedJobs */
        $retrievedJobs = $this->jobRepository->findJobsForUserOlderThanMaxAgeWithStates(
            $user,
            $maximumAge,
            $states
        );

        $this->assertEquals($expectedJobIds, $this->getJobIds($retrievedJobs));
    }

    public function findJobsForUserOlderThanMaxAgeWithStatesDataProvider(): array
    {
        $defaultJobValuesCollection = [
            [
                JobFactory::KEY_URL => 'http://0.example.com/',
                JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                JobFactory::KEY_TIME_PERIOD_START => (new \DateTime('- 48 HOUR'))->format('Y-m-d H:i:s'),
                JobFactory::KEY_TIME_PERIOD_END => (new \DateTime('- 47 HOUR'))->format('Y-m-d H:i:s'),
                JobFactory::KEY_USER => 'private',
            ],
            [
                JobFactory::KEY_URL => 'http://1.example.com/',
                JobFactory::KEY_STATE => Job::STATE_COMPLETED,
                JobFactory::KEY_TIME_PERIOD_START => (new \DateTime('- 48 HOUR'))->format('Y-m-d H:i:s'),
                JobFactory::KEY_TIME_PERIOD_END => (new \DateTime('- 47 HOUR'))->format('Y-m-d H:i:s'),
                JobFactory::KEY_USER => 'public',
            ],
        ];

        return [
            'no jobs' => [
                'jobValuesCollection' => [],
                'username' => 'public',
                'maximumAge' => '24 HOUR',
                'stateNames' => [],
                'expectedJobIndices' => [],
            ],
            'no match on user, match on age' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_URL => 'http://0.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                        JobFactory::KEY_TIME_PERIOD_START => (new \DateTime('- 48 HOUR'))->format('Y-m-d H:i:s'),
                        JobFactory::KEY_TIME_PERIOD_END => (new \DateTime('- 47 HOUR'))->format('Y-m-d H:i:s'),
                        JobFactory::KEY_USER => 'private',
                    ],
                ],
                'username' => 'public',
                'maximumAge' => '24 HOUR',
                'stateNames' => [],
                'expectedJobIndices' => [],
            ],
            'match on public user, match on age, no match on states' => [
                'jobValuesCollection' => $defaultJobValuesCollection,
                'username' => 'public',
                'maximumAge' => '24 HOUR',
                'stateNames' => [
                    Job::STATE_REJECTED,
                ],
                'expectedJobIndices' => [],
            ],
            'match on public user, match on age, match on states' => [
                'jobValuesCollection' => $defaultJobValuesCollection,
                'username' => 'public',
                'maximumAge' => '24 HOUR',
                'stateNames' => [
                    Job::STATE_COMPLETED,
                    Job::STATE_REJECTED,
                ],
                'expectedJobIndices' => [1],
            ],
            'match on private user, match on age, match on states' => [
                'jobValuesCollection' => $defaultJobValuesCollection,
                'username' => 'private',
                'maximumAge' => '24 HOUR',
                'stateNames' => [
                    Job::STATE_CANCELLED,
                    Job::STATE_REJECTED,
                ],
                'expectedJobIndices' => [0],
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
                    JobFactory::KEY_URL => 'http://' . $domain . '/',
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
