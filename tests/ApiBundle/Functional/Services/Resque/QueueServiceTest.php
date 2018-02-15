<?php

namespace Tests\ApiBundle\Functional\Services\Resque;

use Mockery\Mock;
use ResqueBundle\Resque\Resque;
use webignition\ResqueJobFactory\ResqueJobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class QueueServiceTest extends AbstractBaseTestCase
{
    /**
     * @var QueueService
     */
    private $queueService;

    /**
     * @var ResqueJobFactory
     */
    private $jobFactory;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->queueService = $this->container->get(QueueService::class);
        $this->jobFactory = $this->container->get(ResqueJobFactory::class);
    }

    /**
     * @dataProvider containsSuccessDataProvider
     *
     * @param array $jobValuesCollection
     * @param string $queue
     * @param array $args
     * @param bool $expectedContains
     */
    public function testContainsSuccess($jobValuesCollection, $queue, $args, $expectedContains)
    {
        $this->queueService->getResque()->getQueue($queue)->clear();

        foreach ($jobValuesCollection as $jobValues) {
            $job = $this->jobFactory->create($jobValues['queue'], $jobValues['args']);
            $this->queueService->enqueue($job);
        }

        $this->assertEquals($expectedContains, $this->queueService->contains($queue, $args));
    }

    /**
     * @return array
     */
    public function containsSuccessDataProvider()
    {
        return [
            'empty queue' => [
                'jobValuesCollection' => [
                    [
                        'queue' => 'task-assign-collection',
                        'args' => [
                            'ids' => 1,
                            'worker' => 'worker.simplytestable.com',
                        ],
                    ],
                ],
                'queue' => 'job-prepare',
                'args' => [],
                'expectedContains' => false,
            ],
            'non-matching args (no keys)' => [
                'jobValuesCollection' => [
                    [
                        'queue' => 'task-assign-collection',
                        'args' => [
                            'ids' => 1,
                            'worker' => 'worker.simplytestable.com',
                        ],
                    ],
                ],
                'queue' => 'task-assign-collection',
                'args' => [
                    'foo' => 'bar',
                ],
                'expectedContains' => false,
            ],
            'non-matching args (no matching values)' => [
                'jobValuesCollection' => [
                    [
                        'queue' => 'task-assign-collection',
                        'args' => [
                            'ids' => 1,
                            'worker' => 'worker.simplytestable.com',
                        ],
                    ],
                ],
                'queue' => 'task-assign-collection',
                'args' => [
                    'ids' => 2,
                    'worker' => 'worker.simplytestable.com',
                ],
                'expectedContains' => false,
            ],
            'matching args' => [
                'jobValuesCollection' => [
                    [
                        'queue' => 'task-assign-collection',
                        'args' => [
                            'ids' => 1,
                            'worker' => 'worker.simplytestable.com',
                        ],
                    ]
                ],
                'queue' => 'task-assign-collection',
                'args' => [
                    'ids' => 1,
                    'worker' => 'worker.simplytestable.com',
                ],
                'expectedContains' => true,
            ],
            'matching args (empty)' => [
                'jobValuesCollection' => [
                    [
                        'queue' => 'task-assign-collection',
                        'args' => [
                            'ids' => 1,
                            'worker' => 'worker.simplytestable.com',
                        ],
                    ]
                ],
                'queue' => 'task-assign-collection',
                'args' => [],
                'expectedContains' => true,
            ],
        ];
    }


    public function testContainsFailure()
    {
        $credisException = \Mockery::mock(\CredisException::class);

        $queue = 'tasks-notify';

        /* @var Mock|Resque $resque */
        $resque = \Mockery::mock(Resque::class);
        $resque
            ->shouldReceive('getQueue')
            ->with($queue)
            ->andThrow($credisException);

        $queueService = $this->createQueueService($resque);

        $queueService->contains($queue);
    }

    public function testEnqueueSuccess()
    {
        $this->queueService->getResque()->getQueue('tasks-notify')->clear();
        $queue = 'tasks-notify';

        $this->assertTrue($this->queueService->isEmpty($queue));

        $job = $this->jobFactory->create($queue);

        $this->queueService->enqueue($job);

        $this->assertFalse($this->queueService->isEmpty($queue));
    }

    public function testEnqueueFailure()
    {
        $credisException = \Mockery::mock(\CredisException::class);

        /* @var Mock|Resque $resque */
        $resque = \Mockery::mock(Resque::class);
        $resque
            ->shouldReceive('enqueue')
            ->andThrow($credisException);

        $queueService = $this->createQueueService($resque);

        $queue = 'tasks-notify';
        $job = $this->jobFactory->create($queue);

        $queueService->enqueue($job);
    }

    public function testIsEmptyFailure()
    {
        $credisException = \Mockery::mock(\CredisException::class);

        /* @var Mock|Resque $resque */
        $resque = \Mockery::mock(Resque::class);
        $resque
            ->shouldReceive('enqueue')
            ->andThrow($credisException);

        $queueService = $this->createQueueService($resque);

        $queue = 'tasks-notify';
        $queueService->isEmpty($queue);
    }

    public function testGetResque()
    {
        $resque = $this->queueService->getResque();

        $this->assertInstanceOf(Resque::class, $resque);
    }

    /**
     * @param Resque $resque
     *
     * @return QueueService
     */
    private function createQueueService(Resque $resque)
    {
        return new QueueService(
            $resque,
            $this->container->get('logger'),
            $this->container->get(ResqueJobFactory::class),
            'test'
        );
    }
}
