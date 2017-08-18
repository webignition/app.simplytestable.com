<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Repository\TaskRepository;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\SitemapFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class TaskRepositoryTest extends BaseSimplyTestableTestCase
{
    /**
     * @var TaskRepository
     */
    private $taskRepository;

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

        $this->taskRepository = $this->getManager()->getRepository(Task::class);
        $this->jobFactory = new JobFactory($this->container);
        $this->userFactory = new UserFactory($this->container);
    }

    /**
     * @dataProvider findUrlCountByJobFindUrlsByJobGetCountByJobDataProvider
     *
     * @param array $jobValues
     * @param array $prepareHttpFixtures
     * @param int $expectedUrlCount
     * @param array $expectedUrls
     * @param int $expectedTaskCount
     */
    public function testFindUrlCountByJobFindUrlsByJobGetCountByJob(
        $jobValues,
        $prepareHttpFixtures,
        $expectedUrlCount,
        $expectedUrls,
        $expectedTaskCount
    ) {
        $fixtures = [];

        if (!empty($prepareHttpFixtures)) {
            $fixtures['prepare'] = $prepareHttpFixtures;
        }

        $job = $this->jobFactory->createResolveAndPrepare($jobValues, $fixtures);

        $this->assertEquals(
            $expectedUrlCount,
            $this->taskRepository->findUrlCountByJob($job)
        );

        $this->assertEquals(
            $expectedUrls,
            $this->taskRepository->findUrlsByJob($job)
        );

        $this->assertEquals(
            $expectedTaskCount,
            $this->taskRepository->getCountByJob($job)
        );
    }

    /**
     * @return array
     */
    public function findUrlCountByJobFindUrlsByJobGetCountByJobDataProvider()
    {
        return [
            'three' => [
                'jobValues' => [
                    JobFactory::KEY_TEST_TYPES => [
                        TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                ],
                'prepareHttpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse('text/plain', 'sitemap: sitemap.xml'),
                    HttpFixtureFactory::createSuccessResponse(
                        'application/xml',
                        SitemapFixtureFactory::generate([
                            'http://example.com/1',
                            'http://example.com/2',
                            'http://example.com/3',
                        ])
                    ),
                ],
                'expectedUrlCount' => 3,
                'expectedUrls' => [
                    [
                        'url' => 'http://example.com/1',
                    ],
                    [
                        'url' => 'http://example.com/2',
                    ],
                    [
                        'url' => 'http://example.com/3',
                    ],
                ],
                'expectedTaskCount' => 3,
            ],
            'five' => [
                'jobValues' => [
                    JobFactory::KEY_TEST_TYPES => [
                        TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                ],
                'prepareHttpFixtures' => [
                    HttpFixtureFactory::createSuccessResponse('text/plain', 'sitemap: sitemap.xml'),
                    HttpFixtureFactory::createSuccessResponse(
                        'application/xml',
                        SitemapFixtureFactory::generate([
                            'http://example.com/1',
                            'http://example.com/2',
                            'http://example.com/3',
                            'http://example.com/4',
                            'http://example.com/5',
                        ])
                    ),
                ],
                'expectedUrlCount' => 5,
                'expectedUrls' => [
                    [
                        'url' => 'http://example.com/1',
                    ],
                    [
                        'url' => 'http://example.com/2',
                    ],
                    [
                        'url' => 'http://example.com/3',
                    ],
                    [
                        'url' => 'http://example.com/4',
                    ],
                    [
                        'url' => 'http://example.com/5',
                    ],
                ],
                'expectedTaskCount' => 5,
            ],
        ];
    }

    /**
     * @dataProvider findUrlsByJobAndStateDataProvider
     *
     * @param array $jobValues
     * @param string[] $taskStateNames
     * @param string $taskStateName
     * @param string[] $expectedUrls
     */
    public function testFindUrlsByJobAndState($jobValues, $taskStateNames, $taskStateName, $expectedUrls)
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $job = $this->jobFactory->createResolveAndPrepare($jobValues);
        $tasks = $job->getTasks();

        foreach ($taskStateNames as $taskStateIndex => $stateName) {
            /* @var Task $task */
            $task = $tasks->get($taskStateIndex);
            $task->setState($stateService->fetch($stateName));

            $entityManager->persist($task);
            $entityManager->flush($task);
        }

        $urls = $this->taskRepository->findUrlsByJobAndState($job, $stateService->fetch($taskStateName));

        $this->assertEquals(
            $expectedUrls,
            $urls
        );
    }

    /**
     * @return array
     */
    public function findUrlsByJobAndStateDataProvider()
    {
        return [
            'none found' => [
                'jobValues' => [],
                'taskStateNames' => [],
                'taskStateName' => TaskService::COMPLETED_STATE,
                'expectedUrls' => [],
            ],
            'found' => [
                'jobValues' => [
                    JobFactory::KEY_TEST_TYPES => [
                        TaskTypeService::HTML_VALIDATION_TYPE,
                    ],
                ],
                'taskStateNames' => [
                    TaskService::COMPLETED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::COMPLETED_STATE,
                ],
                'taskStateName' => TaskService::COMPLETED_STATE,
                'expectedUrls' => [
                    'http://example.com/one',
                    'http://example.com/foo bar',
                ],
            ],
        ];
    }

    /**
     * @dataProvider findUrlExistsByJobAndUrlDataProvider
     *
     * @param string $url
     * @param bool $expectedExists
     */
    public function testFindUrlExistsByJobAndUrl($url, $expectedExists)
    {
        $job = $this->jobFactory->createResolveAndPrepare();

        $this->assertEquals(
            $expectedExists,
            $this->taskRepository->findUrlExistsByJobAndUrl($job, $url)
        );
    }

    /**
     * @return array
     */
    public function findUrlExistsByJobAndUrlDataProvider()
    {
        return [
            'exists' => [
                'url' => 'http://example.com/one',
                'expectedExists' => true,
            ],
            'does not exist' => [
                'url' => 'http://example.com/foo',
                'expectedExists' => false,
            ],
        ];
    }

    /**
     * @dataProvider getCountByTaskTypeAndStateDataProvider
     *
     * @param array $jobValuesCollection
     * @param string[] $taskStateNames
     * @param string $taskTypeName
     * @param string $taskStateName
     * @param int $expectedCount
     */
    public function testGetCountByTaskTypeAndState(
        $jobValuesCollection,
        $taskStateNames,
        $taskTypeName,
        $taskStateName,
        $expectedCount
    ) {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');

        $users = $this->userFactory->createPublicAndPrivateUserSet();
        $tasks = [];

        foreach ($jobValuesCollection as $jobValues) {
            $jobValues[JobFactory::KEY_USER] = $users[$jobValues[JobFactory::KEY_USER]];

            $job = $this->jobFactory->createResolveAndPrepare($jobValues);
            $tasks = array_merge($tasks, $job->getTasks()->toArray());
        }

        foreach ($taskStateNames as $taskStateIndex => $stateName) {
            $task = $tasks[$taskStateIndex];
            $task->setState($stateService->fetch($stateName));

            $entityManager->persist($task);
            $entityManager->flush($task);
        }

        $taskType = $taskTypeService->getByName($taskTypeName);
        $state = $stateService->fetch($taskStateName);

        $this->assertEquals(
            $expectedCount,
            $this->taskRepository->getCountByTaskTypeAndState($taskType, $state)
        );
    }

    /**
     * @return array
     */
    public function getCountByTaskTypeAndStateDataProvider()
    {
        return [
            'none' => [
                'jobValuesCollection' => [],
                'taskStateNames' => [],
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'taskStateName' => TaskService::COMPLETED_STATE,
                'expectedCount' => 0,
            ],
            'many' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => JobService::CANCELLED_STATE,
                    ],
                ],
                'taskStateNames' => [
                    TaskService::COMPLETED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::COMPLETED_STATE,
                ],
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'taskStateName' => TaskService::COMPLETED_STATE,
                'expectedCount' => 6,
            ],
        ];
    }

    /**
     * @dataProvider getCountByJobAndStateDataProvider
     *
     * @param array $jobValuesCollection
     * @param string[] $taskStateNames
     * @param int $jobIndex
     * @param string $taskStateName
     * @param int $expectedCount
     */
    public function testGetCountByJobAndState(
        $jobValuesCollection,
        $taskStateNames,
        $jobIndex,
        $taskStateName,
        $expectedCount
    ) {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $users = $this->userFactory->createPublicAndPrivateUserSet();
        $jobs = [];
        $tasks = [];

        foreach ($jobValuesCollection as $jobValues) {
            $jobValues[JobFactory::KEY_USER] = $users[$jobValues[JobFactory::KEY_USER]];

            $job = $this->jobFactory->createResolveAndPrepare($jobValues);
            $tasks = array_merge($tasks, $job->getTasks()->toArray());
            $jobs[] = $job;
        }

        foreach ($taskStateNames as $taskStateIndex => $stateName) {
            $task = $tasks[$taskStateIndex];
            $task->setState($stateService->fetch($stateName));

            $entityManager->persist($task);
            $entityManager->flush($task);
        }

        $job = $jobs[$jobIndex];
        $state = $stateService->fetch($taskStateName);

        $this->assertEquals(
            $expectedCount,
            $this->taskRepository->getCountByJobAndState($job, $state)
        );
    }

    /**
     * @return array
     */
    public function getCountByJobAndStateDataProvider()
    {
        return [
            'first job' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                ],
                'taskStateNames' => [
                    TaskService::COMPLETED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::CANCELLED_STATE,
                ],
                'jobIndex' => 0,
                'taskStateName' => TaskService::COMPLETED_STATE,
                'expectedCount' => 3,
            ],
            'second job' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                ],
                'taskStateNames' => [
                    TaskService::COMPLETED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::CANCELLED_STATE,
                ],
                'jobIndex' => 1,
                'taskStateName' => TaskService::COMPLETED_STATE,
                'expectedCount' => 2,
            ],
        ];
    }

    /**
     * @dataProvider getIdsByStateDataProvider
     *
     * @param array $jobValuesCollection
     * @param string[] $taskStateNames
     * @param string $taskStateName
     * @param int[] $expectedTaskIndices
     */
    public function testGetIdsByState(
        $jobValuesCollection,
        $taskStateNames,
        $taskStateName,
        $expectedTaskIndices
    ) {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $users = $this->userFactory->createPublicAndPrivateUserSet();
        $jobs = [];
        $tasks = [];

        foreach ($jobValuesCollection as $jobValues) {
            $jobValues[JobFactory::KEY_USER] = $users[$jobValues[JobFactory::KEY_USER]];

            $job = $this->jobFactory->createResolveAndPrepare($jobValues);
            $tasks = array_merge($tasks, $job->getTasks()->toArray());
            $jobs[] = $job;
        }

        foreach ($taskStateNames as $taskStateIndex => $stateName) {
            $task = $tasks[$taskStateIndex];
            $task->setState($stateService->fetch($stateName));

            $entityManager->persist($task);
            $entityManager->flush($task);
        }

        $expectedTaskIds = [];

        foreach ($tasks as $taskIndex => $task) {
            if (in_array($taskIndex, $expectedTaskIndices)) {
                $expectedTaskIds[] = $task->getId();
            }
        }

        $state = $stateService->fetch($taskStateName);

        $this->assertEquals(
            $expectedTaskIds,
            $this->taskRepository->getIdsByState($state)
        );
    }

    /**
     * @return array
     */
    public function getIdsByStateDataProvider()
    {
        return [
            'completed' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                ],
                'taskStateNames' => [
                    TaskService::COMPLETED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                    TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                    TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                ],
                'taskStateName' => TaskService::COMPLETED_STATE,
                'expectedTaskIndices' => [0, 1],
            ],
            'cancelled' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                        ],
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                ],
                'taskStateNames' => [
                    TaskService::COMPLETED_STATE,
                    TaskService::COMPLETED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                    TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                    TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                ],
                'taskStateName' => TaskService::CANCELLED_STATE,
                'expectedTaskIndices' => [3, 4, 5],
            ],
        ];
    }

    /**
     * @dataProvider getCollectionByUrlSetAndTaskTypeAndStatesDataProvider
     *
     * @param array $jobValuesCollection
     * @param string[] $taskStateNamesToSet
     * @param array $prepareHttpFixturesCollection
     * @param string[] $urlSet
     * @param string $taskTypeName
     * @param string[] $stateNames
     * @param int[] $expectedTaskIndices
     */
    public function testGetCollectionByUrlSetAndTaskTypeAndStates(
        $jobValuesCollection,
        $taskStateNamesToSet,
        $prepareHttpFixturesCollection,
        $urlSet,
        $taskTypeName,
        $stateNames,
        $expectedTaskIndices
    ) {
        $users = $this->userFactory->createPublicAndPrivateUserSet();

        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $taskType = $taskTypeService->getByName($taskTypeName);
        $taskStatesToSet = $stateService->fetchCollection($taskStateNamesToSet);
        $states = $stateService->fetchCollection($stateNames);

        /* @var Job[] $jobs */
        $jobs = [];

        /* @var Task[] $tasks */
        $tasks = [];

        foreach ($jobValuesCollection as $jobIndex => $jobValues) {
            $prepareHttpFixtures = isset($prepareHttpFixturesCollection[$jobIndex])
                ? $prepareHttpFixturesCollection[$jobIndex]
                : null;

            $jobValues[JobFactory::KEY_USER] = $users[$jobValues[JobFactory::KEY_USER]];
            $job = $this->jobFactory->createResolveAndPrepare($jobValues, [
                'prepare' => $prepareHttpFixtures,
            ]);

            $tasks = array_merge($tasks, $job->getTasks()->toArray());
            $jobs[] = $job;
        }

        $expectedTaskIds = [];

        foreach ($tasks as $taskIndex => $task) {
            if (in_array($taskIndex, $expectedTaskIndices)) {
                $expectedTaskIds[] = $task->getId();
            }

            if (isset($taskStateNamesToSet[$taskIndex])) {
                $task->setState($taskStatesToSet[$taskStateNamesToSet[$taskIndex]]);

                $entityManager->persist($task);
                $entityManager->flush($task);
            }
        }

        $retrievedTasks = $this->taskRepository->getCollectionByUrlSetAndTaskTypeAndStates($urlSet, $taskType, $states);

        $taskIds = [];

        foreach ($retrievedTasks as $retrievedTask) {
            $taskIds[] = $retrievedTask->getId();
        }

        $this->assertEquals($expectedTaskIds, $taskIds);
    }

    /**
     * @return array
     */
    public function getCollectionByUrlSetAndTaskTypeAndStatesDataProvider()
    {
        return [
            'multiple jobs, single url, html validation task type, queued state' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                    ],
                ],
                'taskStateNamesToSet' => [
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::QUEUED_STATE,
                ],
                'prepareHttpFixturesCollection' => [
                    [
                        HttpFixtureFactory::createSuccessResponse('text/plain', 'sitemap: sitemap.xml'),
                        HttpFixtureFactory::createSuccessResponse(
                            'application/xml',
                            SitemapFixtureFactory::generate([
                                'http://example.com/foo bar',
                                'http://example.com/foo%20bar',
                                'http://example.com/foo%2520bar',
                            ])
                        ),
                    ],
                    [
                        HttpFixtureFactory::createSuccessResponse('text/plain', 'sitemap: sitemap.xml'),
                        HttpFixtureFactory::createSuccessResponse(
                            'application/xml',
                            SitemapFixtureFactory::generate([
                                'http://example.com/foo bar',
                                'http://example.com/foo%20bar',
                                'http://example.com/foo%2520bar',
                            ])
                        ),
                    ],
                ],
                'urlSet' => [
                    'http://example.com/foo bar',
                ],
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'taskStateNames' => [
                    TaskService::QUEUED_STATE,
                ],
                'expectedTaskIndices' => [0, 6],
            ],
            'multiple jobs, single url, css validation task type, queued state and cancelled state' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                    ],
                ],
                'taskStateNamesToSet' => [
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                ],
                'prepareHttpFixturesCollection' => [
                    [
                        HttpFixtureFactory::createSuccessResponse('text/plain', 'sitemap: sitemap.xml'),
                        HttpFixtureFactory::createSuccessResponse(
                            'application/xml',
                            SitemapFixtureFactory::generate([
                                'http://example.com/foo bar',
                                'http://example.com/foo%20bar',
                                'http://example.com/foo%2520bar',
                            ])
                        ),
                    ],
                    [
                        HttpFixtureFactory::createSuccessResponse('text/plain', 'sitemap: sitemap.xml'),
                        HttpFixtureFactory::createSuccessResponse(
                            'application/xml',
                            SitemapFixtureFactory::generate([
                                'http://example.com/foo bar',
                                'http://example.com/foo%20bar',
                                'http://example.com/foo%2520bar',
                            ])
                        ),
                    ],
                ],
                'urlSet' => [
                    'http://example.com/foo bar',
                ],
                'taskTypeName' => TaskTypeService::CSS_VALIDATION_TYPE,
                'taskStateNames' => [
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                ],
                'expectedTaskIndices' => [1, 7],
            ],
            'multiple jobs, two urls, html validation task type, queued state and cancelled state' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_USER => 'public',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                    ],
                    [
                        JobFactory::KEY_USER => 'private',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                        ],
                    ],
                ],
                'taskStateNamesToSet' => [
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                ],
                'prepareHttpFixturesCollection' => [
                    [
                        HttpFixtureFactory::createSuccessResponse('text/plain', 'sitemap: sitemap.xml'),
                        HttpFixtureFactory::createSuccessResponse(
                            'application/xml',
                            SitemapFixtureFactory::generate([
                                'http://example.com/foo bar',
                                'http://example.com/foo%20bar',
                                'http://example.com/foo%2520bar',
                            ])
                        ),
                    ],
                    [
                        HttpFixtureFactory::createSuccessResponse('text/plain', 'sitemap: sitemap.xml'),
                        HttpFixtureFactory::createSuccessResponse(
                            'application/xml',
                            SitemapFixtureFactory::generate([
                                'http://example.com/foo bar',
                                'http://example.com/foo%20bar',
                                'http://example.com/foo%2520bar',
                            ])
                        ),
                    ],
                ],
                'urlSet' => [
                    'http://example.com/foo bar',
                    'http://example.com/foo%20bar',
                ],
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'taskStateNames' => [
                    TaskService::QUEUED_STATE,
                    TaskService::CANCELLED_STATE,
                ],
                'expectedTaskIndices' => [0, 2, 6, 8],
            ],
        ];
    }
}
