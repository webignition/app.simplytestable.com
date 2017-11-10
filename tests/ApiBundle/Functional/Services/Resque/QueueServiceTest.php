<?php

namespace Tests\ApiBundle\Functional\Services\Resque;

use ResqueBundle\Resque\Resque;
use SimplyTestable\ApiBundle\Services\Resque\JobFactory;
use SimplyTestable\ApiBundle\Services\Resque\QueueService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class QueueServiceTest extends AbstractBaseTestCase
{
    /**
     * @var QueueService
     */
    private $queueService;

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

        $this->queueService = $this->container->get(QueueService::class);
        $this->jobFactory = $this->container->get('simplytestable.services.resque.jobfactory');
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

        $resque = \Mockery::mock(Resque::class);
        $resque
            ->shouldReceive('getQueue')
            ->with($queue)
            ->andThrow($credisException);

        $queueService = new QueueService(
            $resque,
            'test',
            $this->container->get('logger'),
            $this->container->get('simplytestable.services.resque.jobfactory')
        );

        $queueService->contains($queue);
    }

    public function testEnqueueSuccess()
    {
        $queue = 'tasks-notify';

        $this->assertTrue($this->queueService->isEmpty($queue));

        $job = $this->jobFactory->create($queue);

        $this->queueService->enqueue($job);

        $this->assertFalse($this->queueService->isEmpty($queue));
    }

    public function testEnqueueFailure()
    {
        $credisException = \Mockery::mock(\CredisException::class);

        $resque = \Mockery::mock(Resque::class);
        $resque
            ->shouldReceive('enqueue')
            ->andThrow($credisException);

        $queueService = new QueueService(
            $resque,
            'test',
            $this->container->get('logger'),
            $this->container->get('simplytestable.services.resque.jobfactory')
        );

        $queue = 'tasks-notify';
        $job = $this->jobFactory->create($queue);

        $queueService->enqueue($job);
    }

    public function testIsEmptyFailure()
    {
        $credisException = \Mockery::mock(\CredisException::class);

        $resque = \Mockery::mock(Resque::class);
        $resque
            ->shouldReceive('enqueue')
            ->andThrow($credisException);

        $queueService = new QueueService(
            $resque,
            'test',
            $this->container->get('logger'),
            $this->container->get('simplytestable.services.resque.jobfactory')
        );

        $queue = 'tasks-notify';
        $queueService->isEmpty($queue);
    }

    public function testGetResque()
    {
        $resque = $this->queueService->getResque();

        $this->assertInstanceOf(Resque::class, $resque);
    }
}
