<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Tests\Factory\StateFactory;

class CrawlJobContainerServicePrepareTest extends AbstractCrawlJobContainerServiceTest
{
    /**
     * @dataProvider prepareInWrongStateDataProvider
     *
     * @param string $stateName
     */
    public function testPrepareInWrongState($stateName)
    {
        $crawlJobContainerService = $this->container->get('simplytestable.services.crawljobcontainerservice');

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
            JobService::CANCELLED_STATE => [
                'stateName' => JobService::CANCELLED_STATE,
            ],
            JobService::COMPLETED_STATE => [
                'stateName' => JobService::COMPLETED_STATE,
            ],
            JobService::IN_PROGRESS_STATE => [
                'stateName' => JobService::IN_PROGRESS_STATE,
            ],
            JobService::PREPARING_STATE => [
                'stateName' => JobService::PREPARING_STATE,
            ],
            JobService::QUEUED_STATE => [
                'stateName' => JobService::QUEUED_STATE,
            ],
            JobService::FAILED_NO_SITEMAP_STATE => [
                'stateName' => JobService::FAILED_NO_SITEMAP_STATE,
            ],
            JobService::REJECTED_STATE => [
                'stateName' => JobService::REJECTED_STATE,
            ],
            JobService::RESOLVING_STATE => [
                'stateName' => JobService::RESOLVING_STATE,
            ],
            JobService::RESOLVED_STATE => [
                'stateName' => JobService::RESOLVED_STATE,
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

    /**
     * @dataProvider prepareDataProvider
     *
     * @param string $url
     * @param string $jobParameters
     * @param string[] $expectedUrlDiscoveryTaskParameters
     */
    public function testPrepare($url, $jobParameters, $expectedUrlDiscoveryTaskParameters)
    {
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $websiteService = $this->container->get('simplytestable.services.websiteservice');

        $user = $this->userFactory->create();
        $website = $websiteService->fetch($url);

        $crawlJob = new Job();
        $crawlJob->setState($stateService->fetch(JobService::STARTING_STATE));
        $crawlJob->setUser($user);
        $crawlJob->setWebsite($website);
        $crawlJob->setParameters($jobParameters);

        $parentJob = new Job();
        $parentJob->setUser($user);
        $parentJob->setWebsite($website);

        $crawlJobContainer = new CrawlJobContainer();
        $crawlJobContainer->setCrawlJob($crawlJob);
        $crawlJobContainer->setParentJob($parentJob);

        $this->assertNotEquals(JobService::QUEUED_STATE, $crawlJob->getState()->getName());
        $this->assertNull($crawlJob->getTimePeriod());
        $this->assertCount(0, $crawlJob->getTasks());

        // Call prepare more than once to verify operation is idempotent
        $this->crawlJobContainerService->prepare($crawlJobContainer);
        $this->crawlJobContainerService->prepare($crawlJobContainer);

        $this->assertEquals(JobService::QUEUED_STATE, $crawlJob->getState()->getName());
        $this->assertNotNull($crawlJob->getTimePeriod());
        $this->assertCount(1, $crawlJob->getTasks());

        /* @var Task $task */
        $task = $crawlJob->getTasks()->first();
        $this->assertEquals('URL discovery', $task->getType()->getName());
        $this->assertEquals($crawlJob, $task->getJob());
        $this->assertEquals(TaskService::QUEUED_STATE, $task->getState()->getName());
        $this->assertEquals(
            json_encode($expectedUrlDiscoveryTaskParameters),
            $task->getParameters()
        );
    }

    public function prepareDataProvider()
    {
        return [
            'non-www' => [
                'url' => 'http://example.com/',
                'jobParameters' => null,
                'expectedUrlDiscoveryTaskParameters' => [
                    'scope' => [
                        'http://example.com/',
                        'http://www.example.com/',
                    ],
                ],
            ],
            'www' => [
                'url' => 'http://www.example.com/',
                'jobParameters' => json_encode([
                    'foo' => 'bar',
                ]),
                'expectedUrlDiscoveryTaskParameters' => [
                    'scope' => [
                        'http://www.example.com/',
                        'http://example.com/',
                    ],
                    'foo' => 'bar',
                ],
            ],
        ];
    }
}
