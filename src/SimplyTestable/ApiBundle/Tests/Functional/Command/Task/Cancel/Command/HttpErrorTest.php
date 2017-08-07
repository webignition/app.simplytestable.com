<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Task\Cancel\Command;

use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;

class HttpErrorTest extends BaseTest
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $jobFactory = new JobFactory($this->container);

        $job = $jobFactory->createResolveAndPrepare();

        $this->queueHttpFixtures([
            HttpFixtureFactory::createResponse($this->getStatusCode(), ''),
            HttpFixtureFactory::createResponse($this->getStatusCode(), ''),
            HttpFixtureFactory::createResponse($this->getStatusCode(), ''),
            HttpFixtureFactory::createResponse($this->getStatusCode(), ''),
        ]);

        $workerFactory = new WorkerFactory($this->container);
        $worker = $workerFactory->create();

        $task = $job->getTasks()->first();
        $cancellableStates = array(
            $this->getTaskService()->getAwaitingCancellationState(),
            $this->getTaskService()->getInProgressState(),
            $this->getTaskService()->getQueuedState(),
            $this->getTaskService()->getQueuedForAssignmentState()
        );

        foreach ($cancellableStates as $state) {
            $task->setWorker($worker);
            $task->setState($state);
            $this->getTaskService()->getManager()->persist($task);
            $this->getTaskService()->getManager()->flush();

            $this->assertReturnCode($this->getStatusCode(), array(
                'id' => $task->getId()
            ));
            $this->assertEquals($this->getTaskService()->getAwaitingCancellationState(), $task->getState());
        }
    }

    public function test400()
    {
    }

    public function test404()
    {
    }

    public function test500()
    {
    }

    public function test503()
    {
    }

    /**
     * @return int
     */
    private function getStatusCode()
    {
        return (int)  str_replace('test', '', $this->getName());
    }
}
