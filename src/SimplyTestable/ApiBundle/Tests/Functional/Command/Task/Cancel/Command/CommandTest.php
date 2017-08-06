<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Task\Cancel\Command;

use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;

class CancelCommandTest extends BaseTest
{
    /**
     * @var JobFactory
     */
    private $jobFactory;

    /**
     * @var Worker
     */
    private $worker;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->jobFactory = new JobFactory($this->container);

        $workerFactory = new WorkerFactory($this->container);
        $this->worker = $workerFactory->create();
    }

    public function testCancelTaskThatDoesNotExistReturnsStatusCodeMinus1()
    {
        $this->assertReturnCode(-1, array(
            'id' => -1
        ));
    }

    public function testCancelInReadOnlyModeReturnsStatusCodeMinus3()
    {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertReturnCode(-3, array(
            'id' => 1
        ));
    }

    public function testCancelValidTaskReturnsStatusCode0()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->jobFactory->createResolveAndPrepare();

        $this->queueHttpFixtures([
            HttpFixtureFactory::createSuccessResponse(),
            HttpFixtureFactory::createSuccessResponse(),
            HttpFixtureFactory::createSuccessResponse(),
            HttpFixtureFactory::createSuccessResponse(),
        ]);

        $task = $job->getTasks()->first();
        $cancellableStates = array(
            $this->getTaskService()->getAwaitingCancellationState(),
            $this->getTaskService()->getInProgressState(),
            $this->getTaskService()->getQueuedState(),
            $this->getTaskService()->getQueuedForAssignmentState()
        );

        foreach ($cancellableStates as $state) {
            $task->setWorker($this->worker);
            $task->setState($state);
            $this->getTaskService()->getManager()->persist($task);
            $this->getTaskService()->getManager()->flush();

            $this->assertReturnCode(0, array(
                'id' => $task->getId()
            ));

            $this->assertEquals('task-cancelled', $task->getState()->getName());
        }
    }

    public function testCancelTaskInWrongStateReturnsStatusCodeMinus2()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->jobFactory->createResolveAndPrepare();

        $task = $job->getTasks()->first();

        $uncancellableStates = array(
            $this->getTaskService()->getCancelledState(),
            $this->getTaskService()->getCompletedState(),
            $this->getTaskService()->getFailedNoRetryAvailableState(),
            $this->getTaskService()->getFailedRetryAvailableState(),
            $this->getTaskService()->getFailedRetryLimitReachedState()

        );

        foreach ($uncancellableStates as $state) {
            $task->setWorker($this->worker);
            $task->setState($state);
            $this->getTaskService()->getManager()->persist($task);
            $this->getTaskService()->getManager()->flush();

            $this->assertReturnCode(-2, array(
                'id' => $task->getId()
            ));

            $this->assertEquals($task->getState()->getName(), $task->getState()->getName());
        }
    }

    public function testCancelTaskWhenWorkerIsInReadOnlyModeReturnsStatusCode503()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->jobFactory->createResolveAndPrepare();

        $this->queueHttpFixtures([
            HttpFixtureFactory::createServiceUnavailableResponse(),
        ]);

        $task = $job->getTasks()->first();
        $task->setState($this->getTaskService()->getQueuedState());
        $task->setWorker($this->worker);
        $this->getTaskService()->getManager()->persist($task);
        $this->getTaskService()->getManager()->flush();

        $this->assertReturnCode(503, array(
            'id' => $task->getId()
        ));

        $this->assertEquals('task-awaiting-cancellation', $task->getState()->getName());
    }
}
