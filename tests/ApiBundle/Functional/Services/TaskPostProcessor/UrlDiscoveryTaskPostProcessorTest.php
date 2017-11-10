<?php

namespace Tests\ApiBundle\Functional\Services\TaskPostProcessor;

use SimplyTestable\ApiBundle\Entity\Job\Ammendment;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskPostProcessor\UrlDiscoveryTaskPostProcessor;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\TaskOutputFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

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

        $this->urlDiscoveryTaskPostProcessor = $this->container->get(
            'simplytestable.services.taskpostprocessor.urldiscovery'
        );
    }

    /**
     * @dataProvider handlesDataProvider
     *
     * @param string $taskTypeName
     * @param bool $expectedHandles
     */
    public function testHandles($taskTypeName, $expectedHandles)
    {
        $taskType = new Type();
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
            TaskTypeService::JS_STATIC_ANALYSIS_TYPE => [
                'taskTypeName' => TaskTypeService::JS_STATIC_ANALYSIS_TYPE,
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
        $stateService = $this->container->get(StateService::class);
        $crawlJobContainerService = $this->container->get('simplytestable.services.crawljobcontainerservice');

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->createAndActivateUser();

        $jobFactory = new JobFactory($this->container);
        $job = $jobFactory->createResolveAndPrepareStandardCrawlJob([
            JobFactory::KEY_USER => $user,
        ]);
        $crawlJobContainer = $crawlJobContainerService->getForJob($job);
        $crawlJob = $crawlJobContainer->getCrawlJob();

        /* @var Task $task */
        $task = $crawlJob->getTasks()->first();

        $task->setState($stateService->get($taskStateName));

        if (!empty($taskOutputValues)) {
            $taskOutputFactory = new TaskOutputFactory($this->container);
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
                'taskStateName' => TaskService::QUEUED_STATE,
                'taskOutputValues' => [],
            ],
            'empty output' => [
                'taskStateName' => TaskService::COMPLETED_STATE,
                'taskOutputValues' => [],
            ],
            'errored output' => [
                'taskStateName' => TaskService::COMPLETED_STATE,
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
        $stateService = $this->container->get(StateService::class);
        $crawlJobContainerService = $this->container->get('simplytestable.services.crawljobcontainerservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $taskTypeService = $this->container->get(TaskTypeService::class);

        $userFactory = new UserFactory($this->container);
        $taskOutputFactory = new TaskOutputFactory($this->container);

        $user = $userFactory->createAndActivateUser();
        $urlDiscoveryTaskType = $taskTypeService->getUrlDiscoveryTaskType();

        $jobFactory = new JobFactory($this->container);
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
            $this->assertEquals($expectedTaskStateName, $task->getState()->getName());

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
                        'stateName' => TaskService::COMPLETED_STATE,
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
                        'stateName' => TaskService::QUEUED_STATE,
                        'output' => null,
                    ],
                ],
                'expectedAmmendmentReason' => 'plan-url-limit-reached:discovered-url-count-11',
                'expectedTaskStateNames' => [
                    TaskService::COMPLETED_STATE,
                    TaskService::CANCELLED_STATE,
                ],
            ],
            '12 discovered urls' => [
                'taskValuesCollection' => [
                    [
                        'url' => 'http://example.com/',
                        'stateName' => TaskService::COMPLETED_STATE,
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
                    TaskService::COMPLETED_STATE,
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
        $stateService = $this->container->get(StateService::class);
        $crawlJobContainerService = $this->container->get('simplytestable.services.crawljobcontainerservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $taskTypeService = $this->container->get(TaskTypeService::class);

        $userFactory = new UserFactory($this->container);
        $taskOutputFactory = new TaskOutputFactory($this->container);

        $user = $userFactory->createAndActivateUser();
        $urlDiscoveryTaskType = $taskTypeService->getUrlDiscoveryTaskType();

        $jobFactory = new JobFactory($this->container);
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
            $this->assertEquals($expectedTaskValues['stateName'], $task->getState()->getName());

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
                        'stateName' => TaskService::COMPLETED_STATE,
                        'output' => json_encode(1)
                    ],
                ],
                'expectedTaskValuesCollection' => [
                    [
                        'url' => 'http://example.com/',
                        'stateName' => TaskService::COMPLETED_STATE,
                    ],
                ],
            ],
            'one discovered urls, none in use by existing tasks' => [
                'taskValuesCollection' => [
                    [
                        'url' => 'http://example.com/',
                        'stateName' => TaskService::COMPLETED_STATE,
                        'output' => json_encode([
                            'http://example.com/one',
                        ])
                    ],
                ],
                'expectedTaskValuesCollection' => [
                    [
                        'url' => 'http://example.com/',
                        'stateName' => TaskService::COMPLETED_STATE,
                    ],
                    [
                        'url' => 'http://example.com/one',
                        'stateName' => TaskService::QUEUED_STATE,
                    ],
                ],
            ],
            'many discovered urls, some in use by existing tasks' => [
                'taskValuesCollection' => [
                    [
                        'url' => 'http://example.com/',
                        'stateName' => TaskService::COMPLETED_STATE,
                        'output' => json_encode([
                            'http://example.com/one',
                            'http://example.com/two',
                        ])
                    ],
                    [
                        'url' => 'http://example.com/one',
                        'stateName' => TaskService::QUEUED_STATE,
                        'output' => null,
                    ],
                ],
                'expectedTaskValuesCollection' => [
                    [
                        'url' => 'http://example.com/',
                        'stateName' => TaskService::COMPLETED_STATE,
                    ],
                    [
                        'url' => 'http://example.com/one',
                        'stateName' => TaskService::QUEUED_STATE,
                    ],
                    [
                        'url' => 'http://example.com/two',
                        'stateName' => TaskService::QUEUED_STATE,
                    ],
                ],
            ],

        ];
    }
}
