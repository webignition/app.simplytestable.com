<?php

namespace Tests\ApiBundle\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\User;
use SimplyTestable\ApiBundle\Repository\JobRepository;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\JobTypeService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\WebSiteService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

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

        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $this->jobRepository = $entityManager->getRepository(Job::class);

        $this->jobFactory = new JobFactory($this->container);
        $this->userFactory = new UserFactory($this->container);
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
        $stateService = $this->container->get(StateService::class);

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
                    JobService::IN_PROGRESS_STATE,
                ],
                'taskStateNames' => [],
                'expectedJobIndices' => [],
            ],
            'job-states[none specified], task-states[in-progress]' => [
                'jobStateNames' => [],
                'taskStateNames' => [
                    TaskService::IN_PROGRESS_STATE,
                ],
                'expectedJobIndices' => [],
            ],
            'job-states[in-progress], task-states[cancelled]' => [
                'jobStateNames' => [
                    JobService::IN_PROGRESS_STATE,
                ],
                'taskStateNames' => [
                    TaskService::CANCELLED_STATE,
                ],
                'expectedJobIndices' => [],
            ],
            'job-states[in-progress], task-states[completed]' => [
                'jobStateNames' => [
                    JobService::IN_PROGRESS_STATE,
                ],
                'taskStateNames' => [
                    TaskService::IN_PROGRESS_STATE,
                ],
                'expectedJobIndices' => [7],
            ],
            'job-states[in-progress, cancelled], task-states[completed]' => [
                'jobStateNames' => [
                    JobService::IN_PROGRESS_STATE,
                    JobService::CANCELLED_STATE,
                ],
                'taskStateNames' => [
                    TaskService::COMPLETED_STATE,
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
        $stateService = $this->container->get(StateService::class);
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
                'stateName' => JobService::CANCELLED_STATE,
                'expectedJobIndices' => [],
            ],
            'cancelled' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://0.example.com/',
                        JobFactory::KEY_STATE => JobService::CANCELLED_STATE,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://1.example.com/',
                        JobFactory::KEY_STATE => JobService::REJECTED_STATE,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://2.example.com/',
                        JobFactory::KEY_STATE => JobService::CANCELLED_STATE,
                    ],
                ],
                'stateName' => JobService::CANCELLED_STATE,
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
        $stateService = $this->container->get(StateService::class);
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
                'stateName' => JobService::CANCELLED_STATE,
                'expectedCount' => 0,
            ],
            'no matches' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://0.example.com/',
                        JobFactory::KEY_STATE => JobService::CANCELLED_STATE,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://1.example.com/',
                        JobFactory::KEY_STATE => JobService::REJECTED_STATE,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://2.example.com/',
                        JobFactory::KEY_STATE => JobService::CANCELLED_STATE,
                    ],
                ],
                'stateName' => JobService::COMPLETED_STATE,
                'expectedCount' => 0,
            ],
            'one match' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://0.example.com/',
                        JobFactory::KEY_STATE => JobService::CANCELLED_STATE,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://1.example.com/',
                        JobFactory::KEY_STATE => JobService::REJECTED_STATE,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://2.example.com/',
                        JobFactory::KEY_STATE => JobService::CANCELLED_STATE,
                    ],
                ],
                'stateName' => JobService::REJECTED_STATE,
                'expectedCount' => 1,
            ],
            'two matches' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://0.example.com/',
                        JobFactory::KEY_STATE => JobService::CANCELLED_STATE,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://1.example.com/',
                        JobFactory::KEY_STATE => JobService::REJECTED_STATE,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://2.example.com/',
                        JobFactory::KEY_STATE => JobService::CANCELLED_STATE,
                    ],
                ],
                'stateName' => JobService::CANCELLED_STATE,
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

        $websiteService = $this->container->get(WebSiteService::class);
        $jobTypeService = $this->container->get('simplytestable.services.jobtypeservice');

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
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-02-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-02-01 23:59:59',
                        JobFactory::KEY_STATE => JobService::CANCELLED_STATE,
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
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-02-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-02-01 23:59:59',
                        JobFactory::KEY_STATE => JobService::CANCELLED_STATE,
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
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-02-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-02-01 23:59:59',
                        JobFactory::KEY_STATE => JobService::CANCELLED_STATE,
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
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-02-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-02-01 23:59:59',
                        JobFactory::KEY_STATE => JobService::CANCELLED_STATE,
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
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-02-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-02-01 23:59:59',
                        JobFactory::KEY_STATE => JobService::CANCELLED_STATE,
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
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-02-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-02-01 23:59:59',
                        JobFactory::KEY_STATE => JobService::CANCELLED_STATE,
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
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TIME_PERIOD_START => '2017-02-01',
                        JobFactory::KEY_TIME_PERIOD_END => '2017-02-01 23:59:59',
                        JobFactory::KEY_STATE => JobService::CANCELLED_STATE,
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
        $entityManager = $this->container->get('doctrine.orm.entity_manager');


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
            'private job made public is not public' => [
                'jobValuesCollection' => [
                    JobFactory::KEY_USER => 'private',
                ],
                'callSetPublic' => true,
                'expectedIsPublic' => true,
            ],
        ];
    }

    /**
     * @return Job[]
     */
    private function createJobsForAllJobStatesWithTasksForAllTaskStates()
    {
        $jobService = $this->container->get('simplytestable.services.jobservice');
        $taskService = $this->container->get(TaskService::class);
        $stateService = $this->container->get(StateService::class);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

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
    public function createJobs($jobValuesCollection, $users = [])
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
