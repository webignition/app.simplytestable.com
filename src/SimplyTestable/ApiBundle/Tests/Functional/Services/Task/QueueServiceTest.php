<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\Task;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\Task\QueueService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Services\TaskTypeService;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class QueueServiceTest extends BaseSimplyTestableTestCase
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

        $this->taskQueueService = $this->container->get('simplytestable.services.task.queueservice');
        $this->jobFactory = new JobFactory($this->container);
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
                        HttpFixtureFactory::createStandardSitemapResponse($domain),
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
                        JobFactory::KEY_STATE => JobService::REJECTED_STATE,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://1.example.com/',
                        JobFactory::KEY_STATE => JobService::CANCELLED_STATE,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://2.example.com/',
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://3.example.com/',
                        JobFactory::KEY_STATE => JobService::FAILED_NO_SITEMAP_STATE,
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
                        JobFactory::KEY_TASK_STATES => [
                            TaskService::CANCELLED_STATE,
                            TaskService::COMPLETED_STATE,
                            TaskService::AWAITING_CANCELLATION_STATE,
                            TaskService::TASK_FAILED_NO_RETRY_AVAILABLE_STATE,
                            TaskService::TASK_FAILED_RETRY_AVAILABLE_STATE,
                            TaskService::TASK_FAILED_RETRY_LIMIT_REACHED_STATE,
                            TaskService::TASK_SKIPPED_STATE,
                            TaskService::TASK_SKIPPED_STATE,
                            TaskService::TASK_SKIPPED_STATE,
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
                        JobFactory::KEY_TASK_STATES => [
                            TaskService::CANCELLED_STATE,
                            TaskService::COMPLETED_STATE,
                        ],
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://1.example.com/',
                        JobFactory::KEY_TASK_STATES => [
                            TaskService::AWAITING_CANCELLATION_STATE,
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
