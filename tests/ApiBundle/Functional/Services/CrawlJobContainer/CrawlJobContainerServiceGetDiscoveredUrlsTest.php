<?php

namespace Tests\ApiBundle\Functional\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use Tests\ApiBundle\Factory\UserFactory;

class CrawlJobContainerServiceGetDiscoveredUrlsTest extends AbstractCrawlJobContainerServiceTest
{
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
        $taskTypeService = $this->container->get('simplytestable.services.tasktypeservice');
        $userFactory = new UserFactory($this->container);
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        $user = $userFactory->create();
        $website = $websiteService->get('http://example.com');

        $parentJob = new Job();
        $parentJob->setUser($user);
        $parentJob->setState($stateService->get(JobService::FAILED_NO_SITEMAP_STATE));
        $parentJob->setWebsite($website);

        $entityManager->persist($parentJob);
        $entityManager->flush();

        $crawlJob = new Job();
        $crawlJob->setUser($user);
        $crawlJob->setState($stateService->get(JobService::COMPLETED_STATE));
        $crawlJob->setWebsite($website);

        $entityManager->persist($crawlJob);
        $entityManager->flush();

        $crawlJobContainer = new CrawlJobContainer();
        $crawlJobContainer->setParentJob($parentJob);
        $crawlJobContainer->setCrawlJob($crawlJob);

        $entityManager->persist($crawlJobContainer);
        $entityManager->flush();

        $crawlJobTasks = $crawlJob->getTasks();

        foreach ($discoveredUrlSets as $taskUrl => $discoveredUrlSet) {
            $task = new Task();
            $task->setState($stateService->get(TaskService::COMPLETED_STATE));
            $task->setUrl($taskUrl);
            $task->setJob($crawlJob);
            $task->setType($taskTypeService->getUrlDiscoveryTaskType());

            $output = new Output();
            $output->setOutput(json_encode($discoveredUrlSet));

            $task->setOutput($output);

            $entityManager->persist($task);
            $entityManager->flush();

            $crawlJobTasks->add($task);

            $entityManager->persist($crawlJob);
            $entityManager->flush();
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
