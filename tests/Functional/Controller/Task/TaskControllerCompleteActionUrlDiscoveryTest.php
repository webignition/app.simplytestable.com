<?php

namespace App\Tests\Functional\Controller\Task;

use GuzzleHttp\Psr7\Response;
use App\Entity\CrawlJobContainer;
use App\Entity\Job\Job;
use App\Entity\Task\Task;
use App\Services\CrawlJobContainerService;
use App\Services\JobTypeService;
use App\Services\Request\Factory\Task\CompleteRequestFactory;
use App\Services\StateService;
use App\Services\UserService;
use App\Tests\Factory\JobFactory;
use App\Tests\Factory\TaskControllerCompleteActionRequestFactory;

/**
 * @group Controller/TaskController
 */
class TaskControllerCompleteActionUrlDiscoveryTest extends AbstractTaskControllerTest
{
    /**
     * @var CrawlJobContainer
     */
    private $crawlJobContainer;

    /**
     * @var Job
     */
    private $crawlJob;

    /**
     * @var Job
     */
    private $parentJob;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $jobFactory = new JobFactory(self::$container);
        $userService = self::$container->get(UserService::class);

        $notFoundResponse = new Response(404);

        $job = $jobFactory->createResolveAndPrepare([
            'type' => JobTypeService::FULL_SITE_NAME,
            'siteRootUrl' => 'http://example.com',
            'testTypes' => ['css validation',],
            'testTypeOptions' => [],
            'parameters' => [],
            'user' => $userService->getPublicUser()
        ], [
            'resolve' => [
                new Response(),
            ],
            'prepare' => [
                $notFoundResponse,
                $notFoundResponse,
                $notFoundResponse,
                $notFoundResponse,
                $notFoundResponse,
                $notFoundResponse,
                $notFoundResponse,
            ],
        ]);

        $crawlJobContainerService = self::$container->get(CrawlJobContainerService::class);

        $this->crawlJobContainer = $crawlJobContainerService->getForJob($job);
        $this->crawlJob = $this->crawlJobContainer->getCrawlJob();
        $this->parentJob = $this->crawlJobContainer->getParentJob();

        $crawlJobContainerService->prepare($this->crawlJobContainer);

        $entityManager = self::$container->get('doctrine.orm.entity_manager');

        $stateService = self::$container->get(StateService::class);
        $jobInProgressState = $stateService->get(Job::STATE_IN_PROGRESS);

        $this->crawlJob->setState($jobInProgressState);

        $entityManager->persist($this->crawlJob);
        $entityManager->flush();

        $this->assertEquals(Job::STATE_IN_PROGRESS, $this->crawlJob->getState()->getName());
        $this->assertEquals(Job::STATE_FAILED_NO_SITEMAP, $this->parentJob->getState()->getName());
    }

    /**
     * @dataProvider urlDiscoveryTaskCompletionDataProvider
     *
     * @param array $completeActionCalls
     */
    public function testUrlDiscoveryTaskCompletion($completeActionCalls)
    {
        $defaultRouteParams = [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => 'url discovery',
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => '8ffe6fe0d3ad5707d2d89f845727e75a',
        ];

        foreach ($completeActionCalls as $callIndex => $completeActionCall) {
            $postData = $completeActionCall['postData'];
            $routeParams = $completeActionCall['routeParams'];
            $expectedCrawlJobState = $completeActionCall['expectedCrawlJobState'];
            $expectedParentJobState = $completeActionCall['expectedParentJobState'];
            $expectedTaskStates = $completeActionCall['expectedTaskStates'];

            $request = TaskControllerCompleteActionRequestFactory::create(
                $postData,
                array_merge($defaultRouteParams, $routeParams)
            );

            self::$container->get('request_stack')->push($request);
            self::$container->get(CompleteRequestFactory::class)->init($request);

            $this->callCompleteAction();

            $this->assertEquals($expectedCrawlJobState, $this->crawlJob->getState());
            $this->assertEquals($expectedParentJobState, $this->parentJob->getState());

            foreach ($this->crawlJob->getTasks() as $task) {
                /* @var Task $task */
                $this->assertEquals($expectedTaskStates[$task->getUrl()], $task->getState());
            }
        }
    }

    /**
     * @return array
     */
    public function urlDiscoveryTaskCompletionDataProvider()
    {
        $now = new \DateTime();

        return [
            'first task finds no urls, crawl ends' => [
                'completeActionCalls' => [
                    [
                        'postData' => [
                            CompleteRequestFactory::PARAMETER_END_DATE_TIME => $now->format('c'),
                            CompleteRequestFactory::PARAMETER_CONTENT_TYPE => 'application/json',
                            CompleteRequestFactory::PARAMETER_STATE => 'completed',
                            CompleteRequestFactory::PARAMETER_ERROR_COUNT => 0,
                            CompleteRequestFactory::PARAMETER_WARNING_COUNT => 0,
                            CompleteRequestFactory::PARAMETER_OUTPUT => json_encode([]),
                        ],
                        'routeParams' => [
                            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => base64_encode('http://example.com/'),
                        ],
                        'expectedCrawlJobState' => Job::STATE_COMPLETED,
                        'expectedParentJobState' => Job::STATE_QUEUED,
                        'expectedTaskStates' => [
                            'http://example.com/' => Task::STATE_COMPLETED,
                        ],
                    ],
                ],
            ],
            'first task output is not a list of urls, crawl ends' => [
                'completeActionCalls' => [
                    [
                        'postData' => [
                            CompleteRequestFactory::PARAMETER_END_DATE_TIME => $now->format('c'),
                            CompleteRequestFactory::PARAMETER_CONTENT_TYPE => 'application/json',
                            CompleteRequestFactory::PARAMETER_STATE => 'completed',
                            CompleteRequestFactory::PARAMETER_ERROR_COUNT => 0,
                            CompleteRequestFactory::PARAMETER_WARNING_COUNT => 0,
                            CompleteRequestFactory::PARAMETER_OUTPUT => json_encode([
                                'messages' => [
                                    [
                                        'message' => 'Unauthorized',
                                        'messageId' => 'http-retrieval-401',
                                        'type' => 'error',
                                    ],
                                ],
                            ]),
                        ],
                        'routeParams' => [
                            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => base64_encode('http://example.com/'),
                        ],
                        'expectedCrawlJobState' => Job::STATE_COMPLETED,
                        'expectedParentJobState' => Job::STATE_QUEUED,
                        'expectedTaskStates' => [
                            'http://example.com/' => Task::STATE_COMPLETED,
                        ],
                    ],
                ],
            ],
            'first task finds urls, crawl continues' => [
                'completeActionCalls' => [
                    [
                        'postData' => [
                            CompleteRequestFactory::PARAMETER_END_DATE_TIME => $now->format('c'),
                            CompleteRequestFactory::PARAMETER_CONTENT_TYPE => 'application/json',
                            CompleteRequestFactory::PARAMETER_STATE => 'completed',
                            CompleteRequestFactory::PARAMETER_ERROR_COUNT => 0,
                            CompleteRequestFactory::PARAMETER_WARNING_COUNT => 0,
                            CompleteRequestFactory::PARAMETER_OUTPUT => json_encode([
                                'http://example.com/1',
                            ]),
                        ],
                        'routeParams' => [
                            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => base64_encode('http://example.com/'),
                        ],
                        'expectedCrawlJobState' => Job::STATE_IN_PROGRESS,
                        'expectedParentJobState' => Job::STATE_FAILED_NO_SITEMAP,
                        'expectedTaskStates' => [
                            'http://example.com/' => Task::STATE_COMPLETED,
                            'http://example.com/1' => Task::STATE_QUEUED,
                        ],
                    ],
                ],
            ],
            'first task finds urls, second task finds no urls, crawl ends' => [
                'completeActionCalls' => [
                    [
                        'postData' => [
                            CompleteRequestFactory::PARAMETER_END_DATE_TIME => $now->format('c'),
                            CompleteRequestFactory::PARAMETER_CONTENT_TYPE => 'application/json',
                            CompleteRequestFactory::PARAMETER_STATE => 'completed',
                            CompleteRequestFactory::PARAMETER_ERROR_COUNT => 0,
                            CompleteRequestFactory::PARAMETER_WARNING_COUNT => 0,
                            CompleteRequestFactory::PARAMETER_OUTPUT => json_encode([
                                'http://example.com/1',
                            ]),
                        ],
                        'routeParams' => [
                            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => base64_encode('http://example.com/'),
                        ],
                        'expectedCrawlJobState' => Job::STATE_IN_PROGRESS,
                        'expectedParentJobState' => Job::STATE_FAILED_NO_SITEMAP,
                        'expectedTaskStates' => [
                            'http://example.com/' => Task::STATE_COMPLETED,
                            'http://example.com/1' => Task::STATE_QUEUED,
                        ],
                    ],
                    [
                        'postData' => [
                            CompleteRequestFactory::PARAMETER_END_DATE_TIME => $now->format('c'),
                            CompleteRequestFactory::PARAMETER_CONTENT_TYPE => 'application/json',
                            CompleteRequestFactory::PARAMETER_STATE => 'completed',
                            CompleteRequestFactory::PARAMETER_ERROR_COUNT => 0,
                            CompleteRequestFactory::PARAMETER_WARNING_COUNT => 0,
                            CompleteRequestFactory::PARAMETER_OUTPUT => json_encode([]),
                        ],
                        'routeParams' => [
                            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => base64_encode('http://example.com/1'),
                        ],
                        'expectedCrawlJobState' => Job::STATE_COMPLETED,
                        'expectedParentJobState' => Job::STATE_QUEUED,
                        'expectedTaskStates' => [
                            'http://example.com/' => Task::STATE_COMPLETED,
                            'http://example.com/1' => Task::STATE_COMPLETED,
                        ],
                    ],
                ],
            ],
            'first task finds urls that exceed limit, crawl ends' => [
                'completeActionCalls' => [
                    [
                        'postData' => [
                            CompleteRequestFactory::PARAMETER_END_DATE_TIME => $now->format('c'),
                            CompleteRequestFactory::PARAMETER_CONTENT_TYPE => 'application/json',
                            CompleteRequestFactory::PARAMETER_STATE => 'completed',
                            CompleteRequestFactory::PARAMETER_ERROR_COUNT => 0,
                            CompleteRequestFactory::PARAMETER_WARNING_COUNT => 0,
                            CompleteRequestFactory::PARAMETER_OUTPUT => json_encode([
                                'http://example.com/1',
                                'http://example.com/2',
                                'http://example.com/3',
                                'http://example.com/4',
                                'http://example.com/5',
                                'http://example.com/6',
                                'http://example.com/7',
                                'http://example.com/8',
                                'http://example.com/9',
                                'http://example.com/10',
                                'http://example.com/11',
                            ]),
                        ],
                        'routeParams' => [
                            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => base64_encode('http://example.com/'),
                        ],
                        'expectedCrawlJobState' => Job::STATE_COMPLETED,
                        'expectedParentJobState' => Job::STATE_QUEUED,
                        'expectedTaskStates' => [
                            'http://example.com/' => Task::STATE_COMPLETED,
                        ],
                    ],
                ],
            ],
        ];
    }
}
