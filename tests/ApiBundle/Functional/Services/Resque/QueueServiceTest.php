<?php

namespace Tests\ApiBundle\Functional\Services\Resque;

use Mockery\Mock;
use Psr\Log\LoggerInterface;
use ResqueBundle\Resque\Job;
use ResqueBundle\Resque\Resque;
use SimplyTestable\ApiBundle\Resque\Job\Task\AssignCollectionJob;
use SimplyTestable\ApiBundle\Resque\Job\Worker\Tasks\NotifyJob;
use SimplyTestable\ApiBundle\Services\Resque\QueueService;
use Tests\ApiBundle\Functional\AbstractBaseTestCase;

class QueueServiceTest extends AbstractBaseTestCase
{
    /**
     * @var QueueService
     */
    private $queueService;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->queueService = $this->container->get(QueueService::class);
    }

    /**
     * @dataProvider containsSuccessDataProvider
     *
     * @param Job[] $jobs
     * @param string $queue
     * @param array $args
     * @param bool $expectedContains
     */
    public function testContainsSuccess(array $jobs, $queue, $args, $expectedContains)
    {
        $this->queueService->getResque()->getQueue($queue)->clear();

        foreach ($jobs as $job) {
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
                'jobs' => [
                    new AssignCollectionJob([
                        'ids' => 1,
                        'worker' => 'worker.simplytestable.com',
                    ]),
                ],
                'queue' => 'job-prepare',
                'args' => [],
                'expectedContains' => false,
            ],
            'non-matching args (no keys)' => [
                'jobs' => [
                    new AssignCollectionJob([
                        'ids' => 1,
                        'worker' => 'worker.simplytestable.com',
                    ]),
                ],
                'queue' => 'task-assign-collection',
                'args' => [
                    'foo' => 'bar',
                ],
                'expectedContains' => false,
            ],
            'non-matching args (no matching values)' => [
                'jobs' => [
                    new AssignCollectionJob([
                        'ids' => 1,
                        'worker' => 'worker.simplytestable.com',
                    ]),
                ],
                'queue' => 'task-assign-collection',
                'args' => [
                    'ids' => 2,
                    'worker' => 'worker.simplytestable.com',
                ],
                'expectedContains' => false,
            ],
            'matching args' => [
                'jobs' => [
                    new AssignCollectionJob([
                        'ids' => 1,
                        'worker' => 'worker.simplytestable.com',
                    ]),
                ],
                'queue' => 'task-assign-collection',
                'args' => [
                    'ids' => 1,
                    'worker' => 'worker.simplytestable.com',
                ],
                'expectedContains' => true,
            ],
            'matching args (empty)' => [
                'jobs' => [
                    new AssignCollectionJob([
                        'ids' => 1,
                        'worker' => 'worker.simplytestable.com',
                    ]),
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

        /* @var LoggerInterface|Mock $logger */
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('warning')
            ->with('ResqueQueueService::contains: Redis error []');

        $queueService = $this->createQueueService($resque, $logger);

        $this->assertFalse($queueService->contains($queue));
    }

    public function testEnqueueSuccess()
    {
        $queueName = 'tasks-notify';

        $this->queueService->getResque()->getQueue($queueName)->clear();
        $this->assertTrue($this->queueService->isEmpty($queueName));

        $this->queueService->enqueue(new NotifyJob());
        $this->assertFalse($this->queueService->isEmpty($queueName));
    }

    public function testEnqueueFailure()
    {
        $credisException = \Mockery::mock(\CredisException::class);

        /* @var Mock|Resque $resque */
        $resque = \Mockery::mock(Resque::class);
        $resque
            ->shouldReceive('enqueue')
            ->andThrow($credisException);

        /* @var LoggerInterface|Mock $logger */
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('warning')
            ->with('ResqueQueueService::enqueue: Redis error []');

        $queueService = $this->createQueueService($resque, $logger);
        $this->assertNull($queueService->enqueue(new NotifyJob()));
    }

    public function testIsEmptyFailure()
    {
        $queue = 'tasks-notify';
        $credisException = \Mockery::mock(\CredisException::class);

        /* @var Mock|Resque $resque */
        $resque = \Mockery::mock(Resque::class);
        $resque
            ->shouldReceive('getQueue')
            ->with($queue)
            ->andThrow($credisException);

        /* @var LoggerInterface|Mock $logger */
        $logger = \Mockery::mock(LoggerInterface::class);
        $logger
            ->shouldReceive('warning')
            ->with('ResqueQueueService::isEmpty: Redis error []');

        $queueService = $this->createQueueService($resque, $logger);
        $this->assertFalse($queueService->isEmpty($queue));
    }

    public function testGetResque()
    {
        $resque = $this->queueService->getResque();

        $this->assertInstanceOf(Resque::class, $resque);
    }

    /**
     * @param Resque $resque
     *
     * @param LoggerInterface $logger
     * @return QueueService
     */
    private function createQueueService(Resque $resque, LoggerInterface $logger)
    {
        return new QueueService(
            $resque,
            $logger,
            'test'
        );
    }
}
