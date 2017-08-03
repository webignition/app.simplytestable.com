<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\Ammendment;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\State;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Entity\Task\Type\Type;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\StateService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\UserFactory;

class CrawlJobContainerServiceGetDiscoveredUrlsTest extends AbstractCrawlJobContainerServiceTest
{
//    /**
//     * @var TaskTypeService
//     */
//    private $taskTypeService;
//
//    /**
//     * @var StateService
//     */
//    private $stateService;
//
//    /**
//     * @var Type
//     */
//    private $urlDiscoveryTaskType;
//
//    /**
//     * @var State
//     */
//    private $taskCompletedState;
//
//    /**
//     * {@inheritdoc}
//     */
//    protected function setUp()
//    {
//        parent::setUp();
//
//        $this->taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
//        $this->stateService = $this->container->get('simplytestable.services.stateservice');
//
//        $this->urlDiscoveryTaskType = $this->taskTypeService->getByName(TaskTypeService::URL_DISCOVERY_TYPE);
//        $this->taskCompletedState = $this->stateService->fetch(TaskService::COMPLETED_STATE);
//    }

    /**
     * @dataProvider discoveredUrlsDataProvider
     *
     * @param array $discoveredUrlSets
     * @param bool $constraintToAccountPlan
     * @param array $expectedDiscoveredUrls
     */
    public function testGetDiscoveredUrls($discoveredUrlSets, $constraintToAccountPlan, $expectedDiscoveredUrls)
    {
        $websiteService = $this->container->get('simplytestable.services.websiteservice');
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobService = $this->container->get('simplytestable.services.jobservice');
        $taskService = $this->container->get('simplytestable.services.taskservice');
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $userFactory = new UserFactory($this->container);

        $user = $userFactory->create();
        $website = $websiteService->fetch('http://example.com');

        $parentJob = new Job();
        $parentJob->setUser($user);
        $parentJob->setState($stateService->fetch(JobService::FAILED_NO_SITEMAP_STATE));
        $parentJob->setWebsite($website);

        $jobService->persistAndFlush($parentJob);

        $crawlJob = new Job();
        $crawlJob->setUser($user);
        $crawlJob->setState($stateService->fetch(JobService::COMPLETED_STATE));
        $crawlJob->setWebsite($website);

        $jobService->persistAndFlush($crawlJob);

        $crawlJobContainer = new CrawlJobContainer();
        $crawlJobContainer->setParentJob($parentJob);
        $crawlJobContainer->setCrawlJob($crawlJob);
        $this->crawlJobContainerService->persistAndFlush($crawlJobContainer);

        $crawlJobTasks = $crawlJob->getTasks();

        foreach ($discoveredUrlSets as $taskUrl => $discoveredUrlSet) {
            $task = new Task();
            $task->setState($stateService->fetch(TaskService::COMPLETED_STATE));
            $task->setUrl($taskUrl);
            $task->setJob($crawlJob);
            $task->setType($taskTypeService->getByName(TaskTypeService::URL_DISCOVERY_TYPE));

            $output = new Output();
            $output->setOutput(json_encode($discoveredUrlSet));

            $task->setOutput($output);

            $taskService->persistAndFlush($task);

            $crawlJobTasks->add($task);
            $jobService->persistAndFlush($crawlJob);
        }

        $this->assertEquals(
            $expectedDiscoveredUrls,
            $this->crawlJobContainerService->getDiscoveredUrls($crawlJobContainer, $constraintToAccountPlan)
        );
    }

    /**
     * @return array
     */
    public function discoveredUrlsDataProvider()
    {
        return [
            'single set' => [
                'discoveredUrlSets' => [
                    'http://example.com/' => [
                        'http://example.com/one',
                        'http://example.com/two',
                        'http://example.com/three',
                    ],
                ],
                'constraintToAccountPlan' => false,
                'expectedDiscoveredUrls' => [
                    'http://example.com/',
                    'http://example.com/one',
                    'http://example.com/two',
                    'http://example.com/three',
                ],
            ],
            'two sets' => [
                'discoveredUrlSets' => [
                    'http://example.com/' => [
                        'http://example.com/one',
                        'http://example.com/two',
                    ],
                    'http://example.com/one' => [
                        'http://example.com/three',
                    ],
                ],
                'constraintToAccountPlan' => false,
                'expectedDiscoveredUrls' => [
                    'http://example.com/',
                    'http://example.com/one',
                    'http://example.com/two',
                    'http://example.com/three',
                ],
            ],
            'many sets' => [
                'discoveredUrlSets' => [
                    'http://example.com/' => [
                        'http://example.com/',
                        'http://example.com/one',
                        'http://example.com/two',
                        'http://example.com/three',
                    ],
                    'http://example.com/one' => [
                        'http://example.com/',
                        'http://example.com/four',
                        'http://example.com/five',
                        'http://example.com/six',
                    ],
                    'http://example.com/two' => [
                        'http://example.com/',
                        'http://example.com/seven',
                        'http://example.com/eight',
                        'http://example.com/nine',
                    ],
                    'http://example.com/three' => [
                        'http://example.com/',
                        'http://example.com/ten',
                        'http://example.com/eleven',
                        'http://example.com/twelve',
                    ],
                ],
                'constraintToAccountPlan' => false,
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
                    'http://example.com/ten',
                    'http://example.com/eleven',
                    'http://example.com/twelve',
                ],
            ],
            'many sets, constrain to account plan' => [
                'discoveredUrlSets' => [
                    'http://example.com/' => [
                        'http://example.com/',
                        'http://example.com/one',
                        'http://example.com/two',
                        'http://example.com/three',
                    ],
                    'http://example.com/one' => [
                        'http://example.com/',
                        'http://example.com/four',
                        'http://example.com/five',
                        'http://example.com/six',
                    ],
                    'http://example.com/two' => [
                        'http://example.com/',
                        'http://example.com/seven',
                        'http://example.com/eight',
                        'http://example.com/nine',
                    ],
                    'http://example.com/three' => [
                        'http://example.com/',
                        'http://example.com/ten',
                        'http://example.com/eleven',
                        'http://example.com/twelve',
                    ],
                ],
                'constraintToAccountPlan' => true,
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
}
