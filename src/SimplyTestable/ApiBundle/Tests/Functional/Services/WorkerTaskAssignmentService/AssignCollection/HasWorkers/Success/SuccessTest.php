<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Services\WorkerTaskAssignmentService\AssignCollection\HasWorkers\Success;

use SimplyTestable\ApiBundle\Services\WorkerTaskAssignmentService;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;
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

        $userService = $this->container->get('simplytestable.services.userservice');
        $this->setUser($userService->getPublicUser());
        $jobFactory = new JobFactory($this->container);
        $this->job = $jobFactory->createResolveAndPrepare();

        $httpFixture = HttpFixtureFactory::createSuccessResponse('application/json', json_encode([]));
        $httpFixtures = [];

        for ($index = 0; $index < $this->getWorkerCount(); $index++) {
            $httpFixtures[] = $httpFixture;
        }

        $this->queueHttpFixtures($httpFixtures);
    }

    public function testTasksAreAssigned()
    {
        $taskService = $this->container->get('simplytestable.services.taskservice');

        $this->getService()->assignCollection($this->getTasks(), $this->getWorkers());

        foreach ($this->getTasks() as $task) {
            $this->assertEquals($taskService->getInProgressState(), $task->getState());
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
            $workerFactory = new WorkerFactory($this->container);
            $this->workers = $workerFactory->createCollection($this->getWorkerCount());
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
}
