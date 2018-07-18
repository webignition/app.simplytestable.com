<?php

namespace App\Tests\Functional\Services;

use App\Entity\CrawlJobContainer;
use App\Entity\Job\Job;
use App\Entity\State;
use App\Entity\Task\Task;
use App\Entity\Task\Type\Type;
use App\Services\CrawlJobContainerService;
use App\Services\CrawlJobUrlCollector;
use App\Services\StateService;
use App\Services\TaskTypeService;
use App\Tests\Factory\JobFactory;
use App\Tests\Factory\TaskOutputFactory;
use App\Tests\Factory\UserFactory;
use App\Tests\Functional\AbstractBaseTestCase;

class CrawlJobUrlCollectorTest extends AbstractBaseTestCase
{
    /**
     * @var CrawlJobUrlCollector
     */
    private $crawlJobUrlCollector;

    /**
     * @var Type
     */
    private $urlDiscoveryTaskType;

    /**
     * @var State
     */
    private $taskCompletedState;

    /**
     * @var CrawlJobContainer
     */
    private $crawlJobContainer;

    /**
     * @var Job
     */
    private $crawlJob;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->crawlJobUrlCollector = self::$container->get(CrawlJobUrlCollector::class);

        $taskTypeService = self::$container->get(TaskTypeService::class);
        $this->urlDiscoveryTaskType = $taskTypeService->getUrlDiscoveryTaskType();

        $stateService = self::$container->get(StateService::class);
        $this->taskCompletedState = $stateService->get(Task::STATE_COMPLETED);

        $this->crawlJobContainer = $this->createCrawlJobContainer();
        $this->crawlJob = $this->crawlJobContainer->getCrawlJob();
    }

    /**
     * @dataProvider getDiscoveredUrlsDataProvider
     *
     * @param bool $constraintToAccountPlan
     * @param string[] $taskUrlsCollection
     * @param array $taskOutputValuesCollection
     * @param string[] $expectedDiscoveredUrls
     */
    public function testGetDiscoveredUrls(
        $constraintToAccountPlan,
        $taskUrlsCollection,
        $taskOutputValuesCollection,
        $expectedDiscoveredUrls
    ) {
        $entityManager = self::$container->get('doctrine.orm.entity_manager');
        $taskOutputFactory = new TaskOutputFactory(self::$container);

        $initialCrawlJobTask = $this->crawlJob->getTasks()->first();

        $this->crawlJob->removeTask($initialCrawlJobTask);

        $entityManager->persist($this->crawlJob);
        $entityManager->remove($initialCrawlJobTask);
        $entityManager->flush();

        foreach ($taskUrlsCollection as $taskIndex => $taskUrl) {
            $taskOutputValues = $taskOutputValuesCollection[$taskIndex];

            $task = $this->createTask($taskUrl);
            $task->setState($this->taskCompletedState);

            $entityManager->persist($this->crawlJob);
            $entityManager->flush();

            $entityManager->persist($task);
            $entityManager->flush();

            $taskOutputFactory->create($task, $taskOutputValues);
        }

        $this->crawlJobUrlCollector->setConstrainToAccountPlan($constraintToAccountPlan);

        $discoveredUrls = $this->crawlJobUrlCollector->getDiscoveredUrls($this->crawlJobContainer);

        $this->assertEquals($expectedDiscoveredUrls, $discoveredUrls);
    }

    /**
     * @return array
     */
    public function getDiscoveredUrlsDataProvider()
    {
        return [
            'no task output' => [
                'constraintToAccountPlan' => false,
                'taskUrlsCollection' => [],
                'taskOutputValuesCollection' => [],
                'expectedDiscoveredUrls' => [
                    'http://example.com/',
                ],
            ],
            'single output, no duplicates' => [
                'constraintToAccountPlan' => false,
                'taskUrlsCollection' => [
                    'http://example.com/',
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => json_encode([
                            'http://example.com/one',
                            'http://example.com/two',
                            'http://example.com/three',
                        ]),
                    ],
                ],
                'expectedDiscoveredUrls' => [
                    'http://example.com/',
                    'http://example.com/one',
                    'http://example.com/two',
                    'http://example.com/three',
                ],
            ],
            'multiple outputs, has duplicates' => [
                'constraintToAccountPlan' => false,
                'taskUrlsCollection' => [
                    'http://example.com/',
                    'http://example.com/one',
                    'http://example.com/two',
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => json_encode([
                            'http://example.com/one',
                            'http://example.com/two',
                            'http://example.com/three',
                        ]),
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => json_encode([
                            'http://example.com/one',
                            'http://example.com/four',
                        ]),
                    ],
                    [
                        TaskOutputFactory::KEY_OUTPUT => json_encode([
                            'http://example.com/two',
                            'http://example.com/three',
                            'http://example.com/five',
                        ]),
                    ],
                ],
                'expectedDiscoveredUrls' => [
                    'http://example.com/',
                    'http://example.com/one',
                    'http://example.com/two',
                    'http://example.com/three',
                    'http://example.com/four',
                    'http://example.com/five',
                ],
            ],
            'constraint to account plan' => [
                'constraintToAccountPlan' => true,
                'taskUrlsCollection' => [
                    'http://example.com/',
                ],
                'taskOutputValuesCollection' => [
                    [
                        TaskOutputFactory::KEY_OUTPUT => json_encode([
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
                        ]),
                    ],
                ],
                'expectedDiscoveredUrls' => [
                    'http://example.com/',
                    'http://example.com/one',
                    'http://example.com/two',
                    'http://example.com/three',
                    'http://example.com/four',
                    'http://example.com/five',
                    'http://example.com/six',
                    'http://example.com/seven',
                    'http://example.com/eight',
                    'http://example.com/nine',
                ],
            ],
        ];
    }

    /**
     * @return CrawlJobContainer
     */
    private function createCrawlJobContainer()
    {
        $crawlJobContainerService = self::$container->get(CrawlJobContainerService::class);

        $userFactory = new UserFactory(self::$container);
        $user = $userFactory->createAndActivateUser();

        $jobFactory = new JobFactory(self::$container);
        $job = $jobFactory->createResolveAndPrepareStandardCrawlJob([
            JobFactory::KEY_USER => $user,
        ]);
        $crawlJobContainer = $crawlJobContainerService->getForJob($job);

        return $crawlJobContainer;
    }

    /**
     * @param string $url
     *
     * @return Task
     */
    private function createTask($url)
    {
        $task = new Task();
        $task->setJob($this->crawlJob);
        $task->setType($this->urlDiscoveryTaskType);
        $task->setUrl($url);
        $task->setState($this->taskCompletedState);

        return $task;
    }
}
