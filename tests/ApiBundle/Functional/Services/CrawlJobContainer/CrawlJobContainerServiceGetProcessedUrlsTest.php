<?php

namespace Tests\ApiBundle\Functional\Services\CrawlJobContainer;

use SimplyTestable\ApiBundle\Entity\Task\Output;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\TaskService;
use Tests\ApiBundle\Factory\HttpFixtureFactory;
use Tests\ApiBundle\Factory\JobFactory;

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
        $taskService = $this->container->get('simplytestable.services.taskservice');
        $stateService = $this->container->get('simplytestable.services.stateservice');
        $jobService = $this->container->get('simplytestable.services.jobservice');
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
        $crawlJob->setState($stateService->get(JobService::IN_PROGRESS_STATE));

        $entityManager->persist($crawlJob);
        $entityManager->flush();

        $crawlJobTasks = $crawlJob->getTasks();

        $taskCompletedState = $stateService->get(TaskService::COMPLETED_STATE);

        foreach ($discoveredUrlSets as $urlSetIndex => $discoveredUrlSet) {
            $task = $crawlJobTasks->get($urlSetIndex);
            $task->setState($taskCompletedState);

            $output = new Output();
            $output->setOutput(json_encode($discoveredUrlSet));

            $task->setOutput($output);

            $entityManager->persist($task);
            $entityManager->flush();

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
