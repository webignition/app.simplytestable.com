<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services;

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
     * @dataProvider findUrlCountByJobDataProvider
     *
     * @param array $jobValues
     * @param array $prepareHttpFixtures
     * @param int $expectedUrlCount
     */
    public function testFindUrlCountByJob($jobValues, $prepareHttpFixtures, $expectedUrlCount)
    {
        $fixtures = [];

        if (!empty($prepareHttpFixtures)) {
            $fixtures['prepare'] = $prepareHttpFixtures;
        }

        $job = $this->jobFactory->createResolveAndPrepare($jobValues, $fixtures);

        $this->assertEquals(
            $expectedUrlCount,
            $this->taskRepository->findUrlCountByJob($job)
        );
    }

    /**
     * @return array
     */
    public function findUrlCountByJobDataProvider()
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
            ],
        ];
    }

    /**
     * @dataProvider findUrlsByJobDataProvider
     *
     * @param array $jobValues
     * @param array $prepareHttpFixtures
     * @param int $expectedUrls
     */
    public function testFindUrlsByJob($jobValues, $prepareHttpFixtures, $expectedUrls)
    {
        $fixtures = [];

        if (!empty($prepareHttpFixtures)) {
            $fixtures['prepare'] = $prepareHttpFixtures;
        }

        $job = $this->jobFactory->createResolveAndPrepare($jobValues, $fixtures);

        $this->assertEquals(
            $expectedUrls,
            $this->taskRepository->findUrlsByJob($job)
        );
    }

    /**
     * @return array
     */
    public function findUrlsByJobDataProvider()
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
}
