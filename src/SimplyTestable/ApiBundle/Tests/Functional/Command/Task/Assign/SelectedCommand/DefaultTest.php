<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Task\Assign\SelectedCommand;

use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;

class DefaultTest extends CommandTest
{
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

        $this->jobFactory = new JobFactory($this->container);
    }

    public function testAssignValidTaskReturnsStatusCode0()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->jobFactory->createResolveAndPrepare();
        $this->createWorker();

        $this->queueTaskAssignCollectionResponseHttpFixture();

        $task = $job->getTasks()->first();
        $task->setState($this->getTaskService()->getQueuedForAssignmentState());
        $this->getTaskService()->getManager()->persist($task);
        $this->getTaskService()->getManager()->flush();

        $this->assertEquals(
            1,
            json_decode($this->fetchJobResponse($job)->getContent())->task_count_by_state->{'queued-for-assignment'}
        );

        $this->assertReturnCode(0);

        $postAssignJobObject = json_decode($this->fetchJobResponse($job)->getContent());

        $this->assertEquals(0, $postAssignJobObject->task_count_by_state->{'queued-for-assignment'});
        $this->assertEquals(1, $postAssignJobObject->task_count_by_state->{'in-progress'});
    }

    public function testAssignTaskWhenNoWorkersReturnsStatusCode1()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->jobFactory->createResolveAndPrepare();

        $task = $job->getTasks()->first();
        $task->setState($this->getTaskService()->getQueuedForAssignmentState());
        $this->getTaskService()->getManager()->persist($task);
        $this->getTaskService()->getManager()->flush();

        $this->assertReturnCode(1);
    }

    public function testAssignTaskWhenNoWorkersAreAvailableReturnsStatusCode2()
    {
        $this->getUserService()->setUser($this->getUserService()->getPublicUser());
        $job = $this->jobFactory->createResolveAndPrepare();

        $this->queueHttpFixtures($this->buildHttpFixtureSet(array(
            'HTTP/1.0 404',
            'HTTP/1.0 404',
            'HTTP/1.0 404',
        )));

        $this->createWorker('hydrogen.worker.simplytestable.com');

        $task = $job->getTasks()->first();
        $task->setState($this->getTaskService()->getQueuedForAssignmentState());
        $this->getTaskService()->getManager()->persist($task);
        $this->getTaskService()->getManager()->flush();

        $this->assertReturnCode(2);
    }

    public function testExecutekInMaintenanceReadOnlyModeReturnsStatusCodeMinus1()
    {
        $this->executeCommand('simplytestable:maintenance:enable-read-only');
        $this->assertReturnCode(-1);
    }
}
