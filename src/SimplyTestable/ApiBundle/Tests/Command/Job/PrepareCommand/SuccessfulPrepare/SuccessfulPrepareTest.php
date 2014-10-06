<?php

namespace SimplyTestable\ApiBundle\Tests\Command\Job\PrepareCommand\SuccessfulPrepare;

use SimplyTestable\ApiBundle\Tests\Command\Job\PrepareCommand\CommandTest;

abstract class SuccessfulPrepareTest extends CommandTest {

    protected function preCall() {
        $this->queuePrepareHttpFixturesForJob($this->job->getWebsite()->getCanonicalUrl());
        $this->createWorkers($this->getWorkerCount());
    }

    protected function getJob() {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        return $this->getJobService()->getById($this->createAndResolveDefaultJob());
    }

    protected function getExpectedReturnCode() {
        return 0;
    }

    public function testSelectedTaskIdsAreQueuedForAssignment() {
        $taskIds = [];
        foreach ($this->job->getTasks() as $task) {
            $taskIds[] = $task->getId();
        }

        $length = $this->container->getParameter('tasks_per_job_per_worker_count') * count($this->getWorkerService()->getActiveCollection());

        $taskIds = array_slice($taskIds, 0, $length);

        $this->assertTrue($this->getResqueQueueService()->contains(
            'task-assign-collection',
            ['ids' => implode(',', $taskIds)]
        ));
    }


    private function getWorkerCount() {
        $classNameParts = explode('\\', get_class($this));
        return (int)str_replace(['Worker', 'Test'], '', $classNameParts[count($classNameParts) - 1]);
    }
}
