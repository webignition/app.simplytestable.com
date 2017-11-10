<?php

namespace Tests\ApiBundle\Functional\Services;

use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type;
use SimplyTestable\ApiBundle\Services\CrawlJobContainerService;
use SimplyTestable\ApiBundle\Services\CrawlJobUrlCollector;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Tests\ApiBundle\Factory\JobFactory;
use Tests\ApiBundle\Factory\TaskOutputFactory;
use Tests\ApiBundle\Factory\UserFactory;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

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

        $this->crawlJobUrlCollector = $this->container->get(CrawlJobUrlCollector::class);

        $taskTypeService = $this->container->get(TaskTypeService::class);
        $this->urlDiscoveryTaskType = $taskTypeService->getUrlDiscoveryTaskType();

        $stateService = $this->container->get(StateService::class);
        $this->taskCompletedState = $stateService->get(TaskService::COMPLETED_STATE);

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
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $taskOutputFactory = new TaskOutputFactory($this->container);

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
        $crawlJobContainerService = $this->container->get(CrawlJobContainerService::class);

        $userFactory = new UserFactory($this->container);
        $user = $userFactory->createAndActivateUser();

        $jobFactory = new JobFactory($this->container);
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
