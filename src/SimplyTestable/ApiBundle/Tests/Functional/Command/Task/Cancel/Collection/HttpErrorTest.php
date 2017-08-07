<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Task\Cancel\Collection;

use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;

class HttpErrorTest extends BaseTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $jobFactory = new JobFactory($this->container);

        $job = $jobFactory->createResolveAndPrepare();
        $this->queueHttpFixtures([
            HttpFixtureFactory::createResponse($this->getStatusCode(), ''),
        ]);

        $workerFactory = new WorkerFactory($this->container);
        $worker = $workerFactory->create();

        foreach ($job->getTasks() as $task) {
            $task->setState($this->getTaskService()->getQueuedState());
            $task->setWorker($worker);
            $this->getTaskService()->getManager()->persist($task);
        }

        $this->getTaskService()->getManager()->flush();

        $this->assertReturnCode(0, array(
            'ids' => implode(',', $job->getTaskIds())
        ));

        foreach ($job->getTasks() as $task) {
            $this->assertEquals($this->getTaskService()->getCancelledState(), $task->getState());
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
    private function getStatusCode() {
        return (int)  str_replace('test', '', $this->getName());
    }
}
