<?php

namespace App\Tests\Functional\Services\TaskPostProcessor;

use App\Entity\Job\Ammendment;
use App\Entity\Task\Task;
use App\Entity\Task\TaskType;
use App\Services\CrawlJobContainerService;
use App\Services\StateService;
use App\Services\TaskPostProcessor\UrlDiscoveryTaskPostProcessor;
use App\Services\TaskTypeService;
use App\Tests\Services\TaskOutputFactory;
use App\Tests\Services\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;
use App\Tests\Services\JobFactory;

class UrlDiscoveryTaskPostProcessorTest extends AbstractBaseTestCase
{
    /**
     * @var UrlDiscoveryTaskPostProcessor
     */
    private $urlDiscoveryTaskPostProcessor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->urlDiscoveryTaskPostProcessor = self::$container->get(UrlDiscoveryTaskPostProcessor::class);
    }

    /**
     * @dataProvider handlesDataProvider
     *
     * @param string $taskTypeName
     * @param bool $expectedHandles
     */
    public function testHandles($taskTypeName, $expectedHandles)
    {
        $taskType = new TaskType();
        $taskType->setName($taskTypeName);

        $this->assertEquals(
            $expectedHandles,
            $this->urlDiscoveryTaskPostProcessor->handles($taskType)
        );
    }

    /**
     * @return array
     */
    public function handlesDataProvider()
    {
        return [
            TaskTypeService::HTML_VALIDATION_TYPE => [
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
                'expectedHandles' => false,
            ],
            TaskTypeService::CSS_VALIDATION_TYPE => [
                'taskTypeName' => TaskTypeService::CSS_VALIDATION_TYPE,
                'expectedHandles' => false,
            ],
            TaskTypeService::URL_DISCOVERY_TYPE => [
                'taskTypeName' => TaskTypeService::URL_DISCOVERY_TYPE,
                'expectedHandles' => true,
            ],
            TaskTypeService::LINK_INTEGRITY_TYPE => [
                'taskTypeName' => TaskTypeService::LINK_INTEGRITY_TYPE,
                'expectedHandles' => false,
            ],
        ];
    }

    /**
     * @dataProvider processFailureDataProvider
     *
     * @param string $taskStateName
     * @param array $taskOutputValues
     */
    public function testProcessFailure($taskStateName, $taskOutputValues)
    {
        $stateService = self::$container->get(StateService::class);
        $crawlJobContainerService = self::$container->get(CrawlJobContainerService::class);

        $userFactory = self::$container->get(UserFactory::class);
        $user = $userFactory->createAndActivateUser();

        $jobFactory = self::$container->get(JobFactory::class);
        $job = $jobFactory->createResolveAndPrepareStandardCrawlJob([
            JobFactory::KEY_USER => $user,
        ]);
        $crawlJobContainer = $crawlJobContainerService->getForJob($job);
        $crawlJob = $crawlJobContainer->getCrawlJob();

        /* @var Task $task */
        $task = $crawlJob->getTasks()->first();

        $task->setState($stateService->get($taskStateName));

        if (!empty($taskOutputValues)) {
            $taskOutputFactory = self::$container->get(TaskOutputFactory::class);
            $taskOutputFactory->create($task, $taskOutputValues);
        }

        $this->assertFalse($this->urlDiscoveryTaskPostProcessor->process($task));
    }

    /**
     * @return array
     */
    public function processFailureDataProvider()
    {
        return [
            'incorrect state' => [
                'taskStateName' => Task::STATE_QUEUED,
                'taskOutputValues' => [],
            ],
            'empty output' => [
                'taskStateName' => Task::STATE_COMPLETED,
                'taskOutputValues' => [],
            ],
            'errored output' => [
                'taskStateName' => Task::STATE_COMPLETED,
                'taskOutputValues' => [
                    TaskOutputFactory::KEY_ERROR_COUNT => 1,
                ],
            ],
        ];
    }

    /**
     * @dataProvider processSuccessPlanUrlLimitReachedDataProvider
     *
     * @param array $taskValuesCollection
     * @param string $expectedAmmendmentReason
     * @param string[] $expectedTaskStateNames
     */
    public function testProcessSuccessPlanUrlLimitReached(
        $taskValuesCollection,
        $expectedAmmendmentReason,
        $expectedTaskStateNames
    ) {
        $stateService = self::$container->get(StateService::class);
        $crawlJobContainerService = self::$container->get(CrawlJobContainerService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $taskTypeService = self::$container->get(TaskTypeService::class);

        $userFactory = self::$container->get(UserFactory::class);
        $taskOutputFactory = self::$container->get(TaskOutputFactory::class);

        $user = $userFactory->createAndActivateUser();
        $urlDiscoveryTaskType = $taskTypeService->getUrlDiscoveryTaskType();

        $jobFactory = self::$container->get(JobFactory::class);
        $job = $jobFactory->createResolveAndPrepareStandardCrawlJob([
            JobFactory::KEY_USER => $user,
        ]);
        $crawlJobContainer = $crawlJobContainerService->getForJob($job);
        $crawlJob = $crawlJobContainer->getCrawlJob();

        $initialCrawlJobTask = $crawlJob->getTasks()->first();

        $crawlJob->removeTask($initialCrawlJobTask);

        $entityManager->persist($crawlJob);
        $entityManager->remove($initialCrawlJobTask);
        $entityManager->flush();

        foreach ($taskValuesCollection as $taskValues) {
            $task = new Task();
            $task->setJob($crawlJob);
            $task->setType($urlDiscoveryTaskType);
            $task->setUrl($taskValues['url']);
            $task->setState($stateService->get($taskValues['stateName']));

            $entityManager->persist($crawlJob);
            $entityManager->flush();

            $entityManager->persist($task);
            $entityManager->flush();

            if (isset($taskValues['output'])) {
                $taskOutputFactory->create($task, [
                    TaskOutputFactory::KEY_OUTPUT => $taskValues['output'],
                ]);
            }

            $crawlJob->addTask($task);
        }

        /* @var Task $task */
        $task = $crawlJob->getTasks()->first();

        $returnValue = $this->urlDiscoveryTaskPostProcessor->process($task);

        $this->assertTrue($returnValue);
        $this->assertCount(1, $crawlJob->getAmmendments());

        /* @var Ammendment $ammendment */
        $ammendment = $crawlJob->getAmmendments()->first();

        $this->assertEquals($expectedAmmendmentReason, $ammendment->getReason());

        $taskIndex = 0;
        foreach ($crawlJob->getTasks() as $task) {
            $expectedTaskStateName = $expectedTaskStateNames[$taskIndex];
            $this->assertEquals($expectedTaskStateName, (string) $task->getState());

            $taskIndex++;
        }
    }

    /**
     * @return array
     */
    public function processSuccessPlanUrlLimitReachedDataProvider()
    {
        return [
            '11 discovered urls' => [
                'taskValuesCollection' => [
                    [
                        'url' => 'http://example.com/',
                        'stateName' => Task::STATE_COMPLETED,
                        'output' => json_encode([
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
                        ])
                    ],
                    [
                        'url' => 'http://example.com/one',
                        'stateName' => Task::STATE_QUEUED,
                        'output' => null,
                    ],
                ],
                'expectedAmmendmentReason' => 'plan-url-limit-reached:discovered-url-count-11',
                'expectedTaskStateNames' => [
                    Task::STATE_COMPLETED,
                    Task::STATE_CANCELLED,
                ],
            ],
            '12 discovered urls' => [
                'taskValuesCollection' => [
                    [
                        'url' => 'http://example.com/',
                        'stateName' => Task::STATE_COMPLETED,
                        'output' => json_encode([
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
                            'http://example.com/elevent',
                        ])
                    ]
                ],
                'expectedAmmendmentReason' => 'plan-url-limit-reached:discovered-url-count-12',
                'expectedTaskStateNames' => [
                    Task::STATE_COMPLETED,
                ],
            ],
        ];
    }

    /**
     * @dataProvider processSuccessPlanUrlLimitNotReachedDataProvider
     *
     * @param array $taskValuesCollection
     * @param array $expectedTaskValuesCollection
     */
    public function testProcessSuccessPlanUrlLimitNotReached(
        $taskValuesCollection,
        $expectedTaskValuesCollection
    ) {
        $stateService = self::$container->get(StateService::class);
        $crawlJobContainerService = self::$container->get(CrawlJobContainerService::class);
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $taskTypeService = self::$container->get(TaskTypeService::class);

        $userFactory = self::$container->get(UserFactory::class);
        $taskOutputFactory = self::$container->get(TaskOutputFactory::class);

        $user = $userFactory->createAndActivateUser();
        $urlDiscoveryTaskType = $taskTypeService->getUrlDiscoveryTaskType();

        $jobFactory = self::$container->get(JobFactory::class);
        $job = $jobFactory->createResolveAndPrepareStandardCrawlJob([
            JobFactory::KEY_USER => $user,
        ]);
        $crawlJobContainer = $crawlJobContainerService->getForJob($job);
        $crawlJob = $crawlJobContainer->getCrawlJob();

        $initialCrawlJobTask = $crawlJob->getTasks()->first();

        $crawlJob->removeTask($initialCrawlJobTask);

        $entityManager->persist($crawlJob);
        $entityManager->remove($initialCrawlJobTask);
        $entityManager->flush();

        foreach ($taskValuesCollection as $taskValues) {
            $task = new Task();
            $task->setJob($crawlJob);
            $task->setType($urlDiscoveryTaskType);
            $task->setUrl($taskValues['url']);
            $task->setState($stateService->get($taskValues['stateName']));

            $entityManager->persist($crawlJob);
            $entityManager->flush();

            $entityManager->persist($task);
            $entityManager->flush();

            if (isset($taskValues['output'])) {
                $taskOutputFactory->create($task, [
                    TaskOutputFactory::KEY_OUTPUT => $taskValues['output'],
                ]);
            }

            $crawlJob->addTask($task);
        }

        /* @var Task $task */
        $task = $crawlJob->getTasks()->first();

        $returnValue = $this->urlDiscoveryTaskPostProcessor->process($task);

        $this->assertTrue($returnValue);

        $tasks = $crawlJob->getTasks()->toArray();

        $this->assertCount(count($expectedTaskValuesCollection), $tasks);

        $taskIndex = 0;
        foreach ($tasks as $task) {
            $expectedTaskValues = $expectedTaskValuesCollection[$taskIndex];

            $this->assertEquals($expectedTaskValues['url'], $task->getUrl());
            $this->assertEquals($expectedTaskValues['stateName'], (string) $task->getState());

            $taskIndex++;
        }
    }

    /**
     * @return array
     */
    public function processSuccessPlanUrlLimitNotReachedDataProvider()
    {
        return [
            'no discovered urls due to bad output' => [
                'taskValuesCollection' => [
                    [
                        'url' => 'http://example.com/',
                        'stateName' => Task::STATE_COMPLETED,
                        'output' => json_encode(1)
                    ],
                ],
                'expectedTaskValuesCollection' => [
                    [
                        'url' => 'http://example.com/',
                        'stateName' => Task::STATE_COMPLETED,
                    ],
                ],
            ],
            'one discovered urls, none in use by existing tasks' => [
                'taskValuesCollection' => [
                    [
                        'url' => 'http://example.com/',
                        'stateName' => Task::STATE_COMPLETED,
                        'output' => json_encode([
                            'http://example.com/one',
                        ])
                    ],
                ],
                'expectedTaskValuesCollection' => [
                    [
                        'url' => 'http://example.com/',
                        'stateName' => Task::STATE_COMPLETED,
                    ],
                    [
                        'url' => 'http://example.com/one',
                        'stateName' => Task::STATE_QUEUED,
                    ],
                ],
            ],
            'many discovered urls, some in use by existing tasks' => [
                'taskValuesCollection' => [
                    [
                        'url' => 'http://example.com/',
                        'stateName' => Task::STATE_COMPLETED,
                        'output' => json_encode([
                            'http://example.com/one',
                            'http://example.com/two',
                        ])
                    ],
                    [
                        'url' => 'http://example.com/one',
                        'stateName' => Task::STATE_QUEUED,
                        'output' => null,
                    ],
                ],
                'expectedTaskValuesCollection' => [
                    [
                        'url' => 'http://example.com/',
                        'stateName' => Task::STATE_COMPLETED,
                    ],
                    [
                        'url' => 'http://example.com/one',
                        'stateName' => Task::STATE_QUEUED,
                    ],
                    [
                        'url' => 'http://example.com/two',
                        'stateName' => Task::STATE_QUEUED,
                    ],
                ],
            ],

        ];
    }
}
