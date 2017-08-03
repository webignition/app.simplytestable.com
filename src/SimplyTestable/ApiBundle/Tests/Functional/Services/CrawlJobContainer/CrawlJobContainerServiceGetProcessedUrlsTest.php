<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class CrawlJobContainerServiceGetProcessedUrlsTest extends AbstractCrawlJobContainerServiceTest
{
    /**
     * @dataProvider getProcessedUrlsDataProvider
     *
     * @param array $discoveredUrlSets
     * @param string[] $expectedProcessedUrls
     */
    public function testGetProcessedUrls($discoveredUrlSets, $expectedProcessedUrls)
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $taskService = $this->container->get('simplytestable.services.taskservice');
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobService = $this->container->get('simplytestable.services.jobservice');

        $user = $this->userFactory->create();
        $userService->setUser($user);

        $job = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_USER => $user,
        ], [
            'prepare' => HttpFixtureFactory::createStandardCrawlPrepareResponses(),
        ]);

        $crawlJobContainer = $this->getCrawlJobContainerService()->getForJob($job);
        $this->getCrawlJobContainerService()->prepare($crawlJobContainer);

        $crawlJob = $crawlJobContainer->getCrawlJob();
        $crawlJob->setState($stateService->fetch(JobService::IN_PROGRESS_STATE));
        $jobService->persistAndFlush($crawlJob);

        $crawlJobTasks = $crawlJob->getTasks();

        $taskCompletedState = $stateService->fetch(TaskService::COMPLETED_STATE);

        foreach ($discoveredUrlSets as $urlSetIndex => $discoveredUrlSet) {
            $task = $crawlJobTasks->get($urlSetIndex);
            $task->setState($taskCompletedState);

            $output = new Output();
            $output->setOutput(json_encode($discoveredUrlSet));

            $task->setOutput($output);
            $taskService->persistAndFlush($task);

            $this->crawlJobContainerService->processTaskResults($task);
        }

        $this->assertEquals(
            $expectedProcessedUrls,
            $this->crawlJobContainerService->getProcessedUrls($crawlJobContainer)
        );
    }

    /**
     * @return array
     */
    public function getProcessedUrlsDataProvider()
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
                'expectedProcessedUrls' => [
                    'http://example.com/',
                ],
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
                'expectedProcessedUrls' => [
                    'http://example.com/',
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
                'expectedProcessedUrls' => [
                    'http://example.com/',
                    'http://example.com/one',
                ],
            ],
            'many sets, urls_per_job limit reached' => [
                'discoveredUrlSets' => [
                    [
                        'http://example.com/one',
                    ],
                    [
                        'http://example.com/two',
                    ],
                    [
                        'http://example.com/three',
                    ],
                    [
                        'http://example.com/four',
                    ],
                    [
                        'http://example.com/five',
                        'http://example.com/six',
                        'http://example.com/seven',
                        'http://example.com/eight',
                        'http://example.com/nine',
                        'http://example.com/ten',
                    ],
                ],
                'expectedProcessedUrls' => [
                    'http://example.com/',
                    'http://example.com/one',
                    'http://example.com/two',
                    'http://example.com/three',
                    'http://example.com/four',
                ],
            ],
        ];
    }
}
