<?php

namespace Tests\ApiBundle\Functional\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Entity\Job\Ammendment;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Factory\JobFactory;

class CrawlJobContainerServiceProcessTaskResultsTest extends AbstractCrawlJobContainerServiceTest
{
    /**
     * @var TaskTypeService
     */
    private $taskTypeService;

    /**
     * @var StateService
     */
    private $stateService;

    /**
     * @var Type
     */
    private $urlDiscoveryTaskType;

    /**
     * @var State
     */
    private $taskCompletedState;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $this->stateService = $this->container->get('simplytestable.services.stateservice');

        $this->urlDiscoveryTaskType = $this->taskTypeService->getByName(TaskTypeService::URL_DISCOVERY_TYPE);
        $this->taskCompletedState = $this->stateService->fetch(TaskService::COMPLETED_STATE);
    }

    /**
     * @dataProvider taskOfWrongTypeDataProvider
     *
     * @param string $taskTypeName
     */
    public function testTaskOfWrongType($taskTypeName)
    {
        $taskType = $this->taskTypeService->getByName($taskTypeName);

        $task = new Task();
        $task->setType($taskType);

        $this->assertFalse($this->crawlJobContainerService->processTaskResults($task));
    }

    /**
     * @return array
     */
    public function taskOfWrongTypeDataProvider()
    {
        return [
            TaskTypeService::HTML_VALIDATION_TYPE => [
                'taskTypeName' => TaskTypeService::HTML_VALIDATION_TYPE,
            ],
            TaskTypeService::CSS_VALIDATION_TYPE => [
                'taskTypeName' => TaskTypeService::CSS_VALIDATION_TYPE,
            ],
            TaskTypeService::JS_STATIC_ANALYSIS_TYPE => [
                'taskTypeName' => TaskTypeService::JS_STATIC_ANALYSIS_TYPE,
            ],
            TaskTypeService::LINK_INTEGRITY_TYPE => [
                'taskTypeName' => TaskTypeService::LINK_INTEGRITY_TYPE,
            ],
        ];
    }

    /**
     * @dataProvider taskInWrongStateDataProvider
     *
     * @param string $stateName
     */
    public function testTaskInWrongState($stateName)
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $state = $stateService->fetch($stateName);

        $task = new Task();
        $task->setType($this->urlDiscoveryTaskType);
        $task->setState($state);

        $this->assertFalse($this->crawlJobContainerService->processTaskResults($task));
    }

    /**
     * @return array
     */
    public function taskInWrongStateDataProvider()
    {
        return [
            TaskService::CANCELLED_STATE => [
                'stateName' => TaskService::CANCELLED_STATE,
            ],
            TaskService::QUEUED_STATE => [
                'stateName' => TaskService::QUEUED_STATE,
            ],
            TaskService::IN_PROGRESS_STATE => [
                'stateName' => TaskService::IN_PROGRESS_STATE,
            ],
            TaskService::COMPLETED_STATE => [
                'stateName' => TaskService::COMPLETED_STATE,
            ],
            TaskService::AWAITING_CANCELLATION_STATE => [
                'stateName' => TaskService::AWAITING_CANCELLATION_STATE,
            ],
            TaskService::QUEUED_FOR_ASSIGNMENT_STATE => [
                'stateName' => TaskService::QUEUED_FOR_ASSIGNMENT_STATE,
            ],
            TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE => [
                'stateName' => TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
            ],
            TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE => [
                'stateName' => TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
            ],
            TaskService::TASK_FAILED_RETRY_LIMIT_REACHED_STATE => [
                'stateName' => TaskService::TASK_FAILED_RETRY_LIMIT_REACHED_STATE,
            ],
            TaskService::TASK_SKIPPED_STATE => [
                'stateName' => TaskService::TASK_SKIPPED_STATE,
            ],
        ];
    }

    public function testTaskHasNoOutput()
    {
        $task = new Task();
        $task->setType($this->urlDiscoveryTaskType);
        $task->setState($this->taskCompletedState);

        $this->assertFalse($this->crawlJobContainerService->processTaskResults($task));
    }

    public function testTaskHasErrors()
    {
        $output = new Output();
        $output->setErrorCount(1);

        $task = new Task();
        $task->setType($this->urlDiscoveryTaskType);
        $task->setState($this->taskCompletedState);
        $task->setOutput($output);

        $this->assertFalse($this->crawlJobContainerService->processTaskResults($task));
    }

    /**
     * @dataProvider processTaskResultsDataProvider
     *
     * @param array $discoveredUrlSets
     * @param array $expectedCrawlJobTasks
     * @param array $expectedCrawlJobAmmendments
     */
    public function testProcessTaskResults(
        $discoveredUrlSets,
        $expectedCrawlJobTasks,
        $expectedCrawlJobAmmendments
    ) {
        $taskService = $this->container->get('simplytestable.services.taskservice');
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $crawlJobContainerService = $this->container->get('simplytestable.services.crawljobcontainerservice');
        $entityManager = $this->container->get('doctrine.orm.entity_manager');


        $user = $this->userFactory->create();
        $this->setUser($user);

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_USER => $user,
        ], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $crawlJobContainer = $crawlJobContainerService->getForJob($job);
        $crawlJobContainerService->prepare($crawlJobContainer);

        $crawlJob = $crawlJobContainer->getCrawlJob();
        $crawlJob->setState($stateService->fetch(JobService::IN_PROGRESS_STATE));

        $entityManager->persist($crawlJob);
        $entityManager->flush();

        $crawlJobTasks = $crawlJob->getTasks();

        $taskCompletedState = $this->stateService->fetch(TaskService::COMPLETED_STATE);

        foreach ($discoveredUrlSets as $urlSetIndex => $discoveredUrlSet) {
            $task = $crawlJobTasks->get($urlSetIndex);
            $task->setState($taskCompletedState);

            $output = new Output();
            $output->setOutput(json_encode($discoveredUrlSet));

            $task->setOutput($output);
            $taskService->persistAndFlush($task);

            $this->crawlJobContainerService->processTaskResults($task);
        }

        $this->assertCount(count($expectedCrawlJobTasks), $crawlJobTasks);

        /* @var Task $crawlJobTask */
        foreach ($crawlJobTasks as $crawlJobTaskIndex => $crawlJobTask) {
            $expectedCrawlJobTask = $expectedCrawlJobTasks[$crawlJobTaskIndex];

            $this->assertEquals(
                $expectedCrawlJobTask,
                [
                    'url' => $crawlJobTask->getUrl(),
                    'state' => $crawlJobTask->getState()->getName(),
                ]
            );
        }

        /* @var Ammendment[] $crawlJobAmmendments*/
        $crawlJobAmmendments = $crawlJob->getAmmendments();

        $this->assertCount(count($expectedCrawlJobAmmendments), $crawlJob->getAmmendments());

        foreach ($crawlJobAmmendments as $ammendmentIndex => $ammendment) {
            $expectedAmmendment = $expectedCrawlJobAmmendments[$ammendmentIndex];

            $this->assertEquals($expectedAmmendment, [
                'reason' => $ammendment->getReason(),
                'constraint' => [
                    'name' => $ammendment->getConstraint()->getName(),
                ],
            ]);
        }
    }

    /**
     * @return array
     */
    public function processTaskResultsDataProvider()
    {
        return [
            'single set, crawl incomplete' => [
                'discoveredUrlSets' => [
                    [
                        'http://example.com/one',
                        'http://example.com/two',
                        'http://example.com/three',
                    ],
                ],
                'expectedCrawlJobTasks' => [
                    [
                        'url' => 'http://example.com/',
                        'state' => TaskService::COMPLETED_STATE,
                    ],
                    [
                        'url' => 'http://example.com/one',
                        'state' => TaskService::QUEUED_STATE,
                    ],
                    [
                        'url' => 'http://example.com/two',
                        'state' => TaskService::QUEUED_STATE,
                    ],
                    [
                        'url' => 'http://example.com/three',
                        'state' => TaskService::QUEUED_STATE,
                    ],
                ],
                'expectedCrawlJobAmmendments' => [],
            ],
            'single set, urls_per_job limit reached' => [
                'discoveredUrlSets' => [
                    [
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
                    ],
                ],
                'expectedCrawlJobTasks' => [
                    [
                        'url' => 'http://example.com/',
                        'state' => TaskService::COMPLETED_STATE,
                    ],
                ],
                'expectedCrawlJobAmmendments' => [
                    [
                        'reason' => 'plan-url-limit-reached:discovered-url-count-11',
                        'constraint' => [
                            'name' => 'urls_per_job',
                        ],
                    ],
                ],
            ],
            'two sets, crawl incomplete' => [
                'discoveredUrlSets' => [
                    [
                        'http://example.com/one',
                        'http://example.com/two',
                    ],
                    [
                        'http://example.com/three',
                    ],
                ],
                'expectedCrawlJobTasks' => [
                    [
                        'url' => 'http://example.com/',
                        'state' => TaskService::COMPLETED_STATE,
                    ],
                    [
                        'url' => 'http://example.com/one',
                        'state' => TaskService::COMPLETED_STATE,
                    ],
                    [
                        'url' => 'http://example.com/two',
                        'state' => TaskService::QUEUED_STATE,
                    ],
                    [
                        'url' => 'http://example.com/three',
                        'state' => TaskService::QUEUED_STATE,
                    ],
                ],
                'expectedCrawlJobAmmendments' => [],
            ],
            'two sets, urls_per_job limit reached' => [
                'discoveredUrlSets' => [
                    [
                        'http://example.com/one',
                        'http://example.com/two',
                        'http://example.com/three',
                    ],
                    [
                        'http://example.com/four',
                        'http://example.com/five',
                        'http://example.com/six',
                        'http://example.com/seven',
                        'http://example.com/eight',
                        'http://example.com/nine',
                        'http://example.com/ten',
                    ],
                ],
                'expectedCrawlJobTasks' => [
                    [
                        'url' => 'http://example.com/',
                        'state' => TaskService::COMPLETED_STATE,
                    ],
                    [
                        'url' => 'http://example.com/one',
                        'state' => TaskService::COMPLETED_STATE,
                    ],
                    [
                        'url' => 'http://example.com/two',
                        'state' => TaskService::CANCELLED_STATE,
                    ],
                    [
                        'url' => 'http://example.com/three',
                        'state' => TaskService::CANCELLED_STATE,
                    ],
                ],
                'expectedCrawlJobAmmendments' => [
                    [
                        'reason' => 'plan-url-limit-reached:discovered-url-count-11',
                        'constraint' => [
                            'name' => 'urls_per_job',
                        ],
                    ],
                ],
            ],
        ];
    }
}
