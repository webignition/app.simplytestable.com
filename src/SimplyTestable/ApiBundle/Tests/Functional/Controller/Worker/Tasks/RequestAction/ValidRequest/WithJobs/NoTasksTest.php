<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Worker\Tasks\RequestAction\ValidRequest\WithJobs;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\Worker\Tasks\RequestAction\ValidRequest\ValidRequestTest;

class NoTasksTest extends ValidRequestTest
{
    /**
     * @var Job
     */
    private $job;

    /**
     * @var int[]
     */
    private $taskIds = [];

    public function preCall()
    {
        $workerFactory = new WorkerFactory($this->container);
        $workerFactory->create(self::WORKER_HOSTNAME, self::WORKER_TOKEN);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $jobFactory = new JobFactory($this->container);

        $this->job = $jobFactory->createResolveAndPrepare();

        foreach ($this->job->getTasks() as $task) {
            $task->setState($this->getTaskService()->getCompletedState());
            $this->getTaskService()->persistAndFlush($task);
        }

        $this->getTaskQueueService()->setLimit($this->getTaskLimit());
        $this->taskIds = $this->getTaskQueueService()->getNext();
    }

    public function testNoTasksAreSelected()
    {
        $this->assertEquals([], $this->taskIds);
    }

    public function testNoResqueJobIsCreated()
    {
        $this->assertTrue($this->getResqueQueueService()->isEmpty('task-assign-collection'));
    }

    /**
     * @return array
     */
    protected function getRequestPostData()
    {
        return [
            'worker_hostname' => self::WORKER_HOSTNAME,
            'worker_token' => self::WORKER_TOKEN,
            'limit' => $this->getTaskLimit()
        ];
    }

    /**
     * @return int
     */
    private function getTaskLimit()
    {
        return 10;
    }
}
