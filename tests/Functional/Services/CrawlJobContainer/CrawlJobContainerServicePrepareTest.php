<?php

namespace App\Tests\Functional\Services\CrawlJobContainer;

use App\Entity\CrawlJobContainer;
use App\Entity\Job\Job;
use App\Entity\State;
use App\Entity\Task\Task;
use App\Services\CrawlJobContainerService;
use App\Services\JobTypeService;
use App\Services\StateService;
use App\Services\TaskTypeService;
use App\Services\WebSiteService;
use App\Tests\Factory\ModelFactory;

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
        $state = State::create($stateName);

        $crawlJob = ModelFactory::createJob([
            ModelFactory::JOB_STATE => $state,
        ]);

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
        $crawlJob = ModelFactory::createJob();
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
        $jobTypeService = self::$container->get(JobTypeService::class);

        $user = $this->userFactory->create();
        $website = $websiteService->get('http://example.com/');

        $crawlJob = Job::create(
            $user,
            $website,
            $jobTypeService->getCrawlType(),
            $stateService->get(Job::STATE_STARTING),
            ''
        );

        $parentJob = Job::create(
            $user,
            $website,
            $jobTypeService->getFullSiteType(),
            $stateService->get(Job::STATE_STARTING),
            ''
        );

        $crawlJobContainer = new CrawlJobContainer();
        $crawlJobContainer->setCrawlJob($crawlJob);
        $crawlJobContainer->setParentJob($parentJob);

        $this->assertNotEquals(Job::STATE_QUEUED, (string) $crawlJob->getState());
        $this->assertNull($crawlJob->getTimePeriod());
        $this->assertCount(0, $crawlJob->getTasks());

        // Call prepare more than once to verify operation is idempotent
        $this->crawlJobContainerService->prepare($crawlJobContainer);
        $this->crawlJobContainerService->prepare($crawlJobContainer);

        $this->assertEquals(Job::STATE_QUEUED, (string) $crawlJob->getState());
        $this->assertNotNull($crawlJob->getTimePeriod());
        $this->assertCount(1, $crawlJob->getTasks());

        /* @var Task $task */
        $urlDiscoveryTaskType = $taskTypeService->getUrlDiscoveryTaskType();

        $task = $crawlJob->getTasks()->first();
        $this->assertEquals($urlDiscoveryTaskType, $task->getType());
    }
}
