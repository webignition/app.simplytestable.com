<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\WorkerTaskAssignmentService\AssignCollection\HasWorkers\Success;

use SimplyTestable\ApiBundle\Services\WorkerTaskAssignmentService;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Services\WorkerTaskAssignmentService\AssignCollection\HasWorkers\ServiceTest;
use SimplyTestable\ApiBundle\Entity\Worker;
use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;

abstract class SuccessTest extends ServiceTest
{
    /**
     * @var Worker[]
     */
    private $workers = null;

    /**
     * @var Job
     */
    private $job;

    /**
     * @var Task[]
     */
    private $tasks;

    protected function setUp()
    {
        parent::setUp();

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $jobFactory = new JobFactory($this->container);
        $this->job = $jobFactory->createResolveAndPrepare();

        $this->queueHttpFixtures($this->buildHttpFixtureSet($this->getAssignCollectionHttpResponseFixtures()));
    }

    public function testTasksAreAssigned()
    {
        $this->getService()->assignCollection($this->getTasks(), $this->getWorkers());

        foreach ($this->getTasks() as $task) {
            $this->assertEquals($this->getTaskService()->getInProgressState(), $task->getState());
        }
    }

    public function testTaskAssignmentDistribution()
    {
        $this->getService()->assignCollection($this->getTasks(), $this->getWorkers());

        if (count($this->getTasks()) === 0) {
            return;
        }

        foreach ($this->getTaskGroups() as $workerIndex => $tasks) {
            $worker = $this->getWorkers()[$workerIndex];

            foreach ($tasks as $task) {
                /* @var $task Task */
                $this->assertEquals($worker->getHostname(), $task->getWorker()->getHostname());
            }
        }
    }

    protected function getWorkerCount()
    {
        $classNameParts = explode('\\', get_class($this));
        foreach ($classNameParts as $classNamePart) {
            if (preg_match('/^Worker[0-9]+$/', $classNamePart)) {
                return (int)str_replace('Worker', '', $classNamePart);
            }
        }

        return 0;
    }

    private function getTaskCount()
    {
        $classNameParts = explode('\\', get_class($this));

        foreach ($classNameParts as $classNamePart) {
            if (preg_match('/^Task[0-9]+Test$/', $classNamePart)) {
                return (int)str_replace(['Task', 'Test'], '', $classNamePart);
            }
        }

        return 0;
    }

    protected function getExpectedReturnCode()
    {
        return WorkerTaskAssignmentService::ASSIGN_COLLECTION_OK_STATUS_CODE;
    }

    /**
     * @return Worker[]
     */
    protected function getWorkers()
    {
        if (is_null($this->workers)) {
            $this->workers = $this->createWorkers($this->getWorkerCount());
        }

        return $this->workers;
    }

    protected function getTasks()
    {
        if (is_null($this->tasks)) {
            $taskRemoveCount = count($this->job->getTasks()) - $this->getTaskCount();

            $taskToRemove = $this->job->getTasks()->slice(count($this->job->getTasks()) - $taskRemoveCount);

            foreach ($taskToRemove as $task) {
                $this->container->get('doctrine')->getManager()->remove($task);
                $this->container->get('doctrine')->getManager()->flush();
            }

            $this->tasks = $this->job->getTasks()->toArray();
        }

        return $this->tasks;
    }

    private function getTaskGroups()
    {
        $taskGroups = [];

        foreach ($this->getTasks() as $taskIndex => $task) {
            $groupIndex = $taskIndex % $this->getWorkerCount();

            if (!isset($taskGroups[$groupIndex])) {
                $taskGroups[$groupIndex] = [];
            }

            $taskGroups[$groupIndex][] = $task;
        }

        return $taskGroups;
    }

    private function getAssignCollectionHttpResponseFixtures()
    {
        $fixtures = [];

        for ($index = 0; $index < $this->getWorkerCount(); $index++) {
            $fixtures[] = $this->getAssignCollectionHttpResponseFixture();
        }

        return $fixtures;
    }

    private function getAssignCollectionHttpResponseFixture()
    {
        return <<<'EOD'
HTTP/1.1 200 OK
Content-Type: application/json

[{"id":1,"url":"http://example.com/","state":"queued","type":"HTML validation","parameters":""}]
EOD;
    }
}
