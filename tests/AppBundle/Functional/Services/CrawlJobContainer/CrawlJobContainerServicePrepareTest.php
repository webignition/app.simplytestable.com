<?php

namespace Tests\AppBundle\Functional\Services\CrawlJobContainer;

use AppBundle\Entity\CrawlJobContainer;
use AppBundle\Entity\Job\Job;
use AppBundle\Entity\Task\Task;
use AppBundle\Services\CrawlJobContainerService;
use AppBundle\Services\StateService;
use AppBundle\Services\TaskTypeService;
use AppBundle\Services\WebSiteService;
use Tests\AppBundle\Factory\StateFactory;

class CrawlJobContainerServicePrepareTest extends AbstractCrawlJobContainerServiceTest
{
    /**
     * @dataProvider prepareInWrongStateDataProvider
     *
     * @param string $stateName
     */
    public function testPrepareInWrongState($stateName)
    {
        $crawlJobContainerService = self::$container->get(CrawlJobContainerService::class);

        $state = StateFactory::create($stateName);

        $crawlJob = new Job();
        $crawlJob->setState($state);

        $crawlJobContainer = new CrawlJobContainer();
        $crawlJobContainer->setCrawlJob($crawlJob);

        $this->assertFalse($crawlJobContainerService->prepare($crawlJobContainer));
    }

    /**
     * @return array
     */
    public function prepareInWrongStateDataProvider()
    {
        return [
            Job::STATE_CANCELLED => [
                'stateName' => Job::STATE_CANCELLED,
            ],
            Job::STATE_COMPLETED => [
                'stateName' => Job::STATE_COMPLETED,
            ],
            Job::STATE_IN_PROGRESS => [
                'stateName' => Job::STATE_IN_PROGRESS,
            ],
            Job::STATE_PREPARING => [
                'stateName' => Job::STATE_PREPARING,
            ],
            Job::STATE_QUEUED => [
                'stateName' => Job::STATE_QUEUED,
            ],
            Job::STATE_FAILED_NO_SITEMAP => [
                'stateName' => Job::STATE_FAILED_NO_SITEMAP,
            ],
            Job::STATE_REJECTED => [
                'stateName' => Job::STATE_REJECTED,
            ],
            Job::STATE_RESOLVING => [
                'stateName' => Job::STATE_RESOLVING,
            ],
            Job::STATE_RESOLVED => [
                'stateName' => Job::STATE_RESOLVED,
            ],
        ];
    }

    /**
     * @dataProvider prepareWhenCrawlJobHasTasksDataProvider
     *
     * @param int $taskCount
     * @param bool $expectedReturnValue
     */
    public function testPrepareWhenCrawlJobHasTasks($taskCount, $expectedReturnValue)
    {
        $crawlJob = new Job();
        $crawlJobTasks = $crawlJob->getTasks();

        for ($taskIndex = 0; $taskIndex < $taskCount; $taskIndex++) {
            $crawlJobTasks->add(new Task());
        }

        $crawlJobContainer = new CrawlJobContainer();
        $crawlJobContainer->setCrawlJob($crawlJob);

        $this->assertEquals($expectedReturnValue, $this->crawlJobContainerService->prepare($crawlJobContainer));
    }

    /**
     * @return array
     */
    public function prepareWhenCrawlJobHasTasksDataProvider()
    {
        return [
            'one' => [
                'taskCount' => 1,
                'expectedReturnValue' => true,
            ],
            'two' => [
                'taskCount' => 2,
                'expectedReturnValue' => false,
            ],
        ];
    }

    public function testPrepare()
    {
        $stateService = self::$container->get(StateService::class);
        $websiteService = self::$container->get(WebSiteService::class);
        $taskTypeService = self::$container->get(TaskTypeService::class);

        $user = $this->userFactory->create();
        $website = $websiteService->get('http://example.com/');

        $crawlJob = new Job();
        $crawlJob->setState($stateService->get(Job::STATE_STARTING));
        $crawlJob->setUser($user);
        $crawlJob->setWebsite($website);

        $parentJob = new Job();
        $parentJob->setUser($user);
        $parentJob->setWebsite($website);

        $crawlJobContainer = new CrawlJobContainer();
        $crawlJobContainer->setCrawlJob($crawlJob);
        $crawlJobContainer->setParentJob($parentJob);

        $this->assertNotEquals(Job::STATE_QUEUED, $crawlJob->getState()->getName());
        $this->assertNull($crawlJob->getTimePeriod());
        $this->assertCount(0, $crawlJob->getTasks());

        // Call prepare more than once to verify operation is idempotent
        $this->crawlJobContainerService->prepare($crawlJobContainer);
        $this->crawlJobContainerService->prepare($crawlJobContainer);

        $this->assertEquals(Job::STATE_QUEUED, $crawlJob->getState()->getName());
        $this->assertNotNull($crawlJob->getTimePeriod());
        $this->assertCount(1, $crawlJob->getTasks());

        /* @var Task $task */
        $urlDiscoveryTaskType = $taskTypeService->getUrlDiscoveryTaskType();

        $task = $crawlJob->getTasks()->first();
        $this->assertEquals($urlDiscoveryTaskType, $task->getType());
    }
}
