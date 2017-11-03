<?php

namespace Tests\ApiBundle\Unit\Model;

use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Model\ApplicationStatus;
use Tests\ApiBundle\Factory\ModelFactory;

class ApplicationStatusTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider jsonSerializeDataProvider
     *
     * @param string $state
     * @param Worker[] $workers
     * @param string $version
     * @param int $taskThroughputPerMinute
     * @param int $inProgressJobCount
     * @param array $expectedReturnValue
     */
    public function testJsonSerialize(
        $state,
        $workers,
        $version,
        $taskThroughputPerMinute,
        $inProgressJobCount,
        $expectedReturnValue
    ) {
        $applicationStatus = new ApplicationStatus(
            $state,
            $workers,
            $version,
            $taskThroughputPerMinute,
            $inProgressJobCount
        );

        $this->assertEquals($expectedReturnValue, $applicationStatus->jsonSerialize());
    }

    /**
     * @return array
     */
    public function jsonSerializeDataProvider()
    {
        return [
            'no workers' => [
                'state' => 'active',
                'workers' => [],
                'version' => 'a0092bfe2c7ae35996deb4bc2b1c57fc91a0c5e4',
                'taskThroughputPerMinute' => 0,
                'inProgressJobCount' => 0,
                'expectedReturnValue' => [
                    'state' => 'active',
                    'workers' => [],
                    'version' => 'a0092bfe2c7ae35996deb4bc2b1c57fc91a0c5e4',
                    'task_throughput_per_minute' => 0,
                    'in_progress_job_count' => 0,
                ],
            ],
            'with workers' => [
                'state' => 'active',
                'workers' => [
                    ModelFactory::createWorker(
                        'worker1.example.com',
                        ModelFactory::createState('worker-active'),
                        'worker1token'
                    ),
                ],
                'version' => 'a0092bfe2c7ae35996deb4bc2b1c57fc91a0c5e4',
                'taskThroughputPerMinute' => 0,
                'inProgressJobCount' => 0,
                'expectedReturnValue' => [
                    'state' => 'active',
                    'workers' => [
                        [
                            'hostname' => 'worker1.example.com',
                            'state' => 'active',
                        ],
                    ],
                    'version' => 'a0092bfe2c7ae35996deb4bc2b1c57fc91a0c5e4',
                    'task_throughput_per_minute' => 0,
                    'in_progress_job_count' => 0,
                ],
            ],
        ];
    }
}
