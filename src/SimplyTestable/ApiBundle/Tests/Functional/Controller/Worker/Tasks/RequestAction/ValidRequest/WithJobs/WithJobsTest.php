<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Controller\Worker\Tasks\RequestAction\ValidRequest\WithJobs;

use SimplyTestable\ApiBundle\Entity\Job\Job;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Tests\Functional\Controller\Worker\Tasks\RequestAction\ValidRequest\ValidRequestTest;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

abstract class WithJobsTest extends ValidRequestTest
{
    /**
     * @var Job[]
     */
    private $jobs;

    /**
     * @var int[]
     */
    private $taskIds = [];

    public function preCall()
    {
        $this->createWorker(self::WORKER_HOSTNAME, self::WORKER_TOKEN);

        $this->getUserService()->setUser($this->getUserService()->getPublicUser());

        $jobFactory = $this->createJobFactory();

        for ($jobLimitIndex = 0; $jobLimitIndex < $this->getJobLimit(); $jobLimitIndex++) {
            $this->jobs[] = $jobFactory->createResolveAndPrepare([
                JobFactory::KEY_SITE_ROOT_URL => 'http://' . $jobLimitIndex . '.example.com/',
            ], [
                'prepare' => [
                    HttpFixtureFactory::createStandardRobotsTxtResponse(),
                    HttpFixtureFactory::createStandardSitemapResponse($jobLimitIndex . '.example.com/'),
                ],
            ]);
        }

        $this->getTaskQueueService()->setLimit($this->getTaskLimit());
        $this->taskIds = $this->getTaskQueueService()->getNext();
    }

    public function testSelectedTasksAreQueuedForAssignment()
    {
        foreach ($this->getSortedExpectedTasks() as $task) {
            $this->assertEquals('task-queued-for-assignment', $task->getState()->getName());
        }
    }

    public function testTaskAssignCollectionResqueJob()
    {
        if ($this->getTaskLimit() === 0) {
            $this->assertFalse($this->getResqueQueueService()->contains(
                'task-assign-collection',
                [
                    'ids' => implode(',', $this->taskIds),
                    'worker' => self::WORKER_HOSTNAME
                ]
            ));
        } else {
            $this->assertTrue($this->getResqueQueueService()->contains(
                'task-assign-collection',
                [
                    'ids' => implode(',', $this->taskIds),
                    'worker' => self::WORKER_HOSTNAME
                ]
            ));
        }
    }

    public function testRemainingTasksAreQueued()
    {
        foreach ($this->jobs as $job) {
            foreach ($job->getTasks() as $task) {
                if (!in_array($task->getId(), $this->taskIds)) {
                    $this->assertEquals('task-queued', $task->getState()->getName());
                }
            }
        }
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
     * @return Task[]
     */
    private function getSortedExpectedTasks()
    {
        $rawTaskCollection = $this->getTaskService()->getEntityRepository()->getCollectionById($this->taskIds);

        $taskIdIndex = $this->taskIds;

        $tasks = [];

        while (count($taskIdIndex)) {
            foreach ($taskIdIndex as $indexIndex => $taskId) {
                foreach ($rawTaskCollection as $taskIndex => $task) {
                    if ($task->getId() == $taskId) {
                        $tasks[] = $task;
                        unset($rawTaskCollection[$taskIndex]);
                    }
                }

                unset($taskIdIndex[$indexIndex]);
            }
        }

        return $tasks;
    }

    /**
     * @return int
     */
    private function getJobLimit()
    {
        $classNameParts = explode('\\', get_class($this));
        $localClassName = array_pop($classNameParts);

        $matches = [];
        preg_match('/Job[0-9]+/', $localClassName, $matches);

        return (int)str_replace('Job', '', $matches[0]);
    }

    /**
     * @return int
     */
    private function getTaskLimit()
    {
        $classNameParts = explode('\\', get_class($this));
        $localClassName = array_pop($classNameParts);

        $matches = [];
        preg_match('/Task[0-9]+/', $localClassName, $matches);

        return (int)str_replace('Task', '', $matches[0]);
    }
}
