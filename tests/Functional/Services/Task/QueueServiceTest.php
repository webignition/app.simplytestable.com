<?php

namespace App\Tests\Functional\Services\Task;

use GuzzleHttp\Psr7\Response;
use App\Entity\Job\Job;
use App\Entity\Task\Task;
use App\Services\Task\QueueService;
use App\Services\TaskTypeService;
use App\Tests\Factory\HttpFixtureFactory;
use App\Tests\Factory\JobFactory;
use App\Tests\Factory\SitemapFixtureFactory;
use App\Tests\Functional\AbstractBaseTestCase;

class QueueServiceTest extends AbstractBaseTestCase
{
    /**
     * @var QueueService
     */
    private $taskQueueService;

    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->taskQueueService = self::$container->get(QueueService::class);
        $this->jobFactory = new JobFactory(self::$container);
    }

    public function testGetNextWithNoJobs()
    {
        $this->assertEquals([], $this->taskQueueService->getNext());
    }

    /**
     * @dataProvider getNextDataProvider
     *
     * @param array $jobValuesCollection
     * @param int $limit
     * @param int[] $expectedTaskIdIndices
     */
    public function testGetNext($jobValuesCollection, $limit, $expectedTaskIdIndices)
    {
        /* @var Job[] $jobs */
        $jobs = [];

        foreach ($jobValuesCollection as $jobValues) {
            $domain = $this->getDomainFromUrl($jobValues[JobFactory::KEY_SITE_ROOT_URL]);

            $jobs[] = $this->jobFactory->createResolveAndPrepare(
                $jobValues,
                [
                    'prepare' => [
                        HttpFixtureFactory::createStandardRobotsTxtResponse(),
                        new Response(
                            200,
                            ['content-type' => 'text/plain'],
                            SitemapFixtureFactory::load('example.com-three-urls', $domain)
                        ),
                    ],
                ]
            );
        }

        $taskIdIndex = [];

        foreach ($jobs as $job) {
            foreach ($job->getTasks() as $task) {
                $taskIdIndex[] = $task->getId();
            }
        }

        if (!empty($limit)) {
            $this->taskQueueService->setLimit($limit);
        }

        $expectedTaskIds = [];

        foreach ($expectedTaskIdIndices as $expectedTaskIdIndex) {
            $expectedTaskIds[] = $taskIdIndex[$expectedTaskIdIndex];
        }

        $this->assertEquals(
            $expectedTaskIds,
            $this->taskQueueService->getNext()
        );
    }

    /**
     * @return array
     */
    public function getNextDataProvider()
    {
        return [
            'no jobs' => [
                'jobValuesCollection' => [],
                'limit' => null,
                'expectedTaskIdIndices' => [],
            ],
            'finished jobs only' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://0.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_REJECTED,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://1.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_CANCELLED,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://2.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_COMPLETED,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://3.example.com/',
                        JobFactory::KEY_STATE => Job::STATE_FAILED_NO_SITEMAP,
                    ],
                ],
                'limit' => null,
                'expectedTaskIdIndices' => [],
            ],
            'incomplete jobs, no queued tasks' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                        JobFactory::KEY_TEST_TYPES => [
                            TaskTypeService::HTML_VALIDATION_TYPE,
                            TaskTypeService::CSS_VALIDATION_TYPE,
                            TaskTypeService::JS_STATIC_ANALYSIS_TYPE,
                        ],
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_AWAITING_CANCELLATION,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_FAILED_NO_RETRY_AVAILABLE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_FAILED_RETRY_AVAILABLE,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_FAILED_RETRY_LIMIT_REACHED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_SKIPPED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_SKIPPED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_SKIPPED,
                            ],
                        ],
                    ],
                ],
                'limit' => null,
                'expectedTaskIdIndices' => [],
            ],
            'single job with incomplete tasks; default limit' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                    ],
                ],
                'limit' => null,
                'expectedTaskIdIndices' => [
                    0,
                ],
            ],
            'single job with incomplete tasks; limit 2' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://example.com/',
                    ],
                ],
                'limit' => 2,
                'expectedTaskIdIndices' => [
                    0,
                    1,
                ],
            ],
            'two jobs with incomplete tasks; default limit' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://0.example.com/',
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://1.example.com/',
                    ],
                ],
                'limit' => null,
                'expectedTaskIdIndices' => [
                    0,
                ],
            ],
            'two jobs with incomplete tasks; limit 2' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://0.example.com/',
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://1.example.com/',
                    ],
                ],
                'limit' => 2,
                'expectedTaskIdIndices' => [
                    0,
                    3,
                ],
            ],
            'two jobs with incomplete tasks; limit 4' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://0.example.com/',
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://1.example.com/',
                    ],
                ],
                'limit' => 4,
                'expectedTaskIdIndices' => [
                    0,
                    3,
                    1,
                    4,
                ],
            ],
            'two jobs with incomplete tasks; not all tasks incomplete, limit 4' => [
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://0.example.com/',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_CANCELLED,
                            ],
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_COMPLETED,
                            ],
                        ],
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://1.example.com/',
                        JobFactory::KEY_TASKS => [
                            [
                                JobFactory::KEY_TASK_STATE => Task::STATE_AWAITING_CANCELLATION,
                            ],
                        ],
                    ],
                ],
                'limit' => 4,
                'expectedTaskIdIndices' => [
                    2,
                    4,
                    5,
                ],
            ],
        ];
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function getDomainFromUrl($url)
    {
        $urlParts = parse_url($url);

        return $urlParts['host'];
    }
}
