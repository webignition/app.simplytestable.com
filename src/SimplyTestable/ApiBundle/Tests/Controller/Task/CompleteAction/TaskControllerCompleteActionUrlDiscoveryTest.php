<?php

namespace SimplyTestable\ApiBundle\Tests\Controller\Task\CompleteAction;

use Guzzle\Http\Message\Response;
use SimplyTestable\ApiBundle\Entity\CrawlJobContainer;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\Request\Factory\Task\CompleteRequestFactory;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Tests\Controller\Task\AbstractTaskControllerTest;
use Symfony\Component\HttpFoundation\Request;

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

    public function setUp()
    {
        parent::setUp();
        $this->queueCrawlJobHttpFixtures();
        $job = $this->createJobFactory()->createResolveAndPrepare(
            'html validation',
            'http://example.com/',
            ['html validation',],
            [],
            [],
            $this->container->get('simplytestable.services.userservice')->getPublicUser()
        );

        $crawlJobContainerService = $this->container->get('simplytestable.services.crawljobcontainerservice');

        $this->crawlJobContainer = $crawlJobContainerService->getForJob($job);
        $this->crawlJob = $this->crawlJobContainer->getCrawlJob();
        $this->parentJob = $this->crawlJobContainer->getParentJob();

        $crawlJobContainerService->prepare($this->crawlJobContainer);

        $jobService = $this->container->get('simplytestable.services.jobservice');
        $entityManager = $this->container->get('doctrine')->getManager();

        $this->crawlJob->setState($jobService->getInProgressState());

        $entityManager->persist($this->crawlJob);
        $entityManager->flush();

        $this->assertEquals(JobService::IN_PROGRESS_STATE, $this->crawlJob->getState());
        $this->assertEquals(JobService::FAILED_NO_SITEMAP_STATE, $this->parentJob->getState());
    }

    /**
     * @dataProvider fooDataProvider
     *
     * @param array $completeActionCalls
     */
    public function testFoo($completeActionCalls)
    {
        $defaultRouteParams = [
            CompleteRequestFactory::ROUTE_PARAM_TASK_TYPE => 'url discovery',
            CompleteRequestFactory::ROUTE_PARAM_PARAMETER_HASH => '8ffe6fe0d3ad5707d2d89f845727e75a',
        ];

        foreach ($completeActionCalls as $completeActionCall) {
            $postData = $completeActionCall['postData'];
            $routeParams = $completeActionCall['routeParams'];
            $expectedCrawlJobState = $completeActionCall['expectedCrawlJobState'];
            $expectedParentJobState = $completeActionCall['expectedParentJobState'];
            $expectedTaskStates = $completeActionCall['expectedTaskStates'];

            $request = new Request([], $postData, [
                CompleteRequestFactory::ATTRIBUTE_ROUTE_PARAMS => array_merge($defaultRouteParams, $routeParams)
            ]);
            $this->addRequestToContainer($request);

            $this->createTaskController()->completeAction();

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
    public function fooDataProvider()
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
                            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/',
                        ],
                        'expectedCrawlJobState' => JobService::COMPLETED_STATE,
                        'expectedParentJobState' => JobService::QUEUED_STATE,
                        'expectedTaskStates' => [
                            'http://example.com/' => TaskService::COMPLETED_STATE,
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
                            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/',
                        ],
                        'expectedCrawlJobState' => JobService::COMPLETED_STATE,
                        'expectedParentJobState' => JobService::QUEUED_STATE,
                        'expectedTaskStates' => [
                            'http://example.com/' => TaskService::COMPLETED_STATE,
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
                            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/',
                        ],
                        'expectedCrawlJobState' => JobService::IN_PROGRESS_STATE,
                        'expectedParentJobState' => JobService::FAILED_NO_SITEMAP_STATE,
                        'expectedTaskStates' => [
                            'http://example.com/' => TaskService::COMPLETED_STATE,
                            'http://example.com/1' => TaskService::QUEUED_STATE,
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
                            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/',
                        ],
                        'expectedCrawlJobState' => JobService::IN_PROGRESS_STATE,
                        'expectedParentJobState' => JobService::FAILED_NO_SITEMAP_STATE,
                        'expectedTaskStates' => [
                            'http://example.com/' => TaskService::COMPLETED_STATE,
                            'http://example.com/1' => TaskService::QUEUED_STATE,
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
                            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/1',
                        ],
                        'expectedCrawlJobState' => JobService::COMPLETED_STATE,
                        'expectedParentJobState' => JobService::QUEUED_STATE,
                        'expectedTaskStates' => [
                            'http://example.com/' => TaskService::COMPLETED_STATE,
                            'http://example.com/1' => TaskService::COMPLETED_STATE,
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
                            CompleteRequestFactory::ROUTE_PARAM_CANONICAL_URL => 'http://example.com/',
                        ],
                        'expectedCrawlJobState' => JobService::COMPLETED_STATE,
                        'expectedParentJobState' => JobService::QUEUED_STATE,
                        'expectedTaskStates' => [
                            'http://example.com/' => TaskService::COMPLETED_STATE,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function queueCrawlJobHttpFixtures()
    {
        $this->queueHttpFixtures([
            Response::fromMessage('HTTP/1.1 200 OK'),
            Response::fromMessage('HTTP/1.1 404'),
            Response::fromMessage('HTTP/1.1 404'),
            Response::fromMessage('HTTP/1.1 404'),
            Response::fromMessage('HTTP/1.1 404'),
        ]);
    }
}
