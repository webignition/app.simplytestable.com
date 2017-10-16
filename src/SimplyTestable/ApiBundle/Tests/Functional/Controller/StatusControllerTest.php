<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller;

use SimplyTestable\ApiBundle\Controller\StatusController;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;

class StatusControllerTest extends BaseSimplyTestableTestCase
{
    /**
     * @var StatusController
     */
    private $statusController;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->statusController = new StatusController();
        $this->statusController->setContainer($this->container);
    }

    /**
     * @dataProvider indexActionDataProvider
     *
     * @param string[] $workerHostnames
     * @param array $jobValuesCollection
     * @param array $expectedResponseData
     */
    public function testIndexAction($workerHostnames, $jobValuesCollection, $expectedResponseData)
    {
        $workerFactory = new WorkerFactory($this->container);
        $jobFactory = new JobFactory($this->container);

        foreach ($workerHostnames as $workerHostname) {
            $workerFactory->create([
                WorkerFactory::KEY_HOSTNAME => $workerHostname,
            ]);
        }

        foreach ($jobValuesCollection as $jobValues) {
            $jobFactory->create($jobValues);
        }

        $response = $this->statusController->indexAction();

        $this->assertTrue($response->isSuccessful());
        $this->assertEquals('application/json', $response->headers->get('content-type'));

        $responseData = json_decode($response->getContent(), true);

        unset($responseData['version']);

        $this->assertEquals($expectedResponseData, $responseData);
    }

    public function indexActionDataProvider()
    {
        return [
            'no workers, no jobs' => [
                'workerHostnames' => [],
                'jobValuesCollection' => [],
                'expectedResponseData' => [
                    'state' => 'active',
                    'workers' => [],
                    'task_throughput_per_minute' => 0,
                    'in_progress_job_count' => 0,
                ],
            ],
            'one worker, no jobs' => [
                'workerHostnames' => [
                    'worker1',
                ],
                'jobValuesCollection' => [],
                'expectedResponseData' => [
                    'state' => 'active',
                    'workers' => [
                        [
                            'hostname' => 'worker1',
                            'state' => 'active',
                        ],
                    ],
                    'task_throughput_per_minute' => 0,
                    'in_progress_job_count' => 0,
                ],
            ],
            'one worker, one in-progress job' => [
                'workerHostnames' => [
                    'worker1',
                ],
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://completed.example.com/',
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://in-progress.example.com/',
                        JobFactory::KEY_STATE => JobService::IN_PROGRESS_STATE,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://cancelled.example.com/',
                        JobFactory::KEY_STATE => JobService::CANCELLED_STATE,
                    ],
                ],
                'expectedResponseData' => [
                    'state' => 'active',
                    'workers' => [
                        [
                            'hostname' => 'worker1',
                            'state' => 'active',
                        ],
                    ],
                    'task_throughput_per_minute' => 0,
                    'in_progress_job_count' => 1,
                ],
            ],
            'many workers, many in-progress jobs' => [
                'workerHostnames' => [
                    'worker1',
                    'worker2',
                ],
                'jobValuesCollection' => [
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://completed.example.com/',
                        JobFactory::KEY_STATE => JobService::COMPLETED_STATE,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://foo.in-progress.example.com/',
                        JobFactory::KEY_STATE => JobService::IN_PROGRESS_STATE,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://cancelled.example.com/',
                        JobFactory::KEY_STATE => JobService::CANCELLED_STATE,
                    ],
                    [
                        JobFactory::KEY_SITE_ROOT_URL => 'http://bar.in-progress.example.com/',
                        JobFactory::KEY_STATE => JobService::IN_PROGRESS_STATE,
                    ],
                ],
                'expectedResponseData' => [
                    'state' => 'active',
                    'workers' => [
                        [
                            'hostname' => 'worker1',
                            'state' => 'active',
                        ],
                        [
                            'hostname' => 'worker2',
                            'state' => 'active',
                        ],
                    ],
                    'task_throughput_per_minute' => 0,
                    'in_progress_job_count' => 2,
                ],
            ],
        ];
    }
}
