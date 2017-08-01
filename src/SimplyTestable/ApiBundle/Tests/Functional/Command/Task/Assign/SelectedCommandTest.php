<?php

namespace SimplyTestable\ApiBundle\Tests\Functional\Command\Task\Assign;

use SimplyTestable\ApiBundle\Command\Task\Assign\SelectedCommand;
use SimplyTestable\ApiBundle\Controller\Job\JobController;
use SimplyTestable\ApiBundle\Controller\MaintenanceController;
use SimplyTestable\ApiBundle\Entity\Task\Task;
use SimplyTestable\ApiBundle\Services\JobService;
use SimplyTestable\ApiBundle\Services\TaskService;
use SimplyTestable\ApiBundle\Tests\Factory\HttpFixtureFactory;
use SimplyTestable\ApiBundle\Tests\Factory\JobFactory;
use SimplyTestable\ApiBundle\Tests\Factory\WorkerFactory;
use SimplyTestable\ApiBundle\Tests\Functional\BaseSimplyTestableTestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class SelectedCommandTest extends BaseSimplyTestableTestCase
{
    /**
     * @var SelectedCommand
     */
    private $command;

    /**
     * @var WorkerFactory
     */
    private $workerFactory;

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

        $this->command = new SelectedCommand();
        $this->command->setContainer($this->container);

        $this->workerFactory = new WorkerFactory($this->container);
        $this->jobFactory = new JobFactory($this->container);
    }

    public function testRunInMaintenanceReadOnlyMode()
    {
        $maintenanceController = new MaintenanceController();
        $maintenanceController->setContainer($this->container);
        $maintenanceController->enableReadOnlyAction();

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(SelectedCommand::RETURN_CODE_IN_MAINTENANCE_READ_ONLY_MODE, $returnCode);
    }

    public function testRunWithNoWorkers()
    {
        $taskService = $this->container->get('simplytestable.services.taskservice');

        $job = $this->jobFactory->createResolveAndPrepare();

        $task = $job->getTasks()->first();
        $task->setState($taskService->getQueuedForAssignmentState());
        $taskService->getManager()->persist($task);
        $taskService->getManager()->flush();

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(SelectedCommand::RETURN_CODE_FAILED_NO_WORKERS, $returnCode);
    }

    public function testRunWithNoWorkersAvailable()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $taskService = $this->container->get('simplytestable.services.taskservice');

        $job = $this->jobFactory->createResolveAndPrepare();

        $task = $job->getTasks()->first();
        $task->setState($taskService->getQueuedForAssignmentState());
        $taskService->getManager()->persist($task);
        $taskService->getManager()->flush();

        $userService->setUser($userService->getPublicUser());

        $this->queueHttpFixtures([
            HttpFixtureFactory::createNotFoundResponse(),
            HttpFixtureFactory::createNotFoundResponse(),
            HttpFixtureFactory::createNotFoundResponse(),
        ]);

        $this->workerFactory->create('hydrogen.worker.simplytestable.com');
        $this->workerFactory->create('lithium.worker.simplytestable.com');
        $this->workerFactory->create('helium.worker.simplytestable.com');

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(2, $returnCode);
    }

    public function testRunSuccess()
    {
        $userService = $this->container->get('simplytestable.services.userservice');
        $taskService = $this->container->get('simplytestable.services.taskservice');

        $userService->setUser($userService->getPublicUser());

        $job = $this->jobFactory->createResolveAndPrepare();

        /* @var Task $task */
        $task = $job->getTasks()->first();
        $task->setState($taskService->getQueuedForAssignmentState());
        $taskService->getManager()->persist($task);
        $taskService->getManager()->flush();

        $jobController = new JobController();
        $jobController->setContainer($this->container);

        $jobStatusResponse = $jobController->statusAction($job->getWebsite()->getCanonicalUrl(), $job->getId());
        $jobStatusData = json_decode($jobStatusResponse->getContent(), true);

        $this->assertEquals(
            1,
            $jobStatusData['task_count_by_state']['queued-for-assignment']
        );

        $this->workerFactory->create();

        $this->queueHttpFixtures([
            HttpFixtureFactory::createSuccessResponse('application/json', json_encode([
                [
                    'id' => 1,
                    'url' => $task->getUrl(),
                    'type' => $task->getType()->getName(),
                ],
            ])),
        ]);

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(SelectedCommand::RETURN_CODE_OK, $returnCode);

        $jobStatusResponse = $jobController->statusAction($job->getWebsite()->getCanonicalUrl(), $job->getId());
        $jobStatusData = json_decode($jobStatusResponse->getContent(), true);

        $this->assertEquals(
            0,
            $jobStatusData['task_count_by_state']['queued-for-assignment']
        );

        $this->assertEquals(
            1,
            $jobStatusData['task_count_by_state']['in-progress']
        );
    }

    public function testMarkEquivalentTasksAsInProgress()
    {
        $userService = $this->container->get('simplytestable.services.userservice');

        $userService->setUser($userService->getPublicUser());

        $this->setJobTypeConstraintLimits();
        $this->workerFactory->create();

         $job1 = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TEST_TYPES => ['css validation'],
            JobFactory::KEY_TEST_TYPE_OPTIONS => [
                'css validation' => [
                    'ignore-warnings' => 1,
                    'ignore-common-cdns' => 1,
                    'vendor-extensions' => 'warn'
                ],
            ],
        ]);

        $job2 = $this->jobFactory->createResolveAndPrepare([
            JobFactory::KEY_TEST_TYPES => ['html validation', 'css validation'],
            JobFactory::KEY_TEST_TYPE_OPTIONS => [
                'css validation' => [
                    'ignore-warnings' => 1,
                    'ignore-common-cdns' => 1,
                    'vendor-extensions' => 'warn'
                ],
            ]
        ]);

        /* @var Task $task */
        $task = $job1->getTasks()->first();
        $task->setState($this->getTaskService()->getQueuedForAssignmentState());
        $this->getTaskService()->getManager()->persist($task);
        $this->getTaskService()->getManager()->flush();

        $this->queueHttpFixtures([
            HttpFixtureFactory::createSuccessResponse('application/json', json_encode([
                [
                    'id' => 1,
                    'url' => $task->getUrl(),
                    'type' => $task->getType()->getName(),
                ],
            ])),
        ]);

        $returnCode = $this->command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertEquals(SelectedCommand::RETURN_CODE_OK, $returnCode);

        $this->assertEquals(TaskService::IN_PROGRESS_STATE, $job1->getTasks()->get(0)->getState()->getName());
        $this->assertEquals(TaskService::IN_PROGRESS_STATE, $job2->getTasks()->get(1)->getState()->getName());
        $this->assertEquals(JobService::IN_PROGRESS_STATE, $job1->getState()->getName());
        $this->assertEquals(JobService::IN_PROGRESS_STATE, $job2->getState()->getName());
    }

    private function setJobTypeConstraintLimits()
    {
        $jobService = $this->container->get('simplytestable.services.jobservice');
        $jobUserAccountPlanEnforcementService = $this->container->get(
            'simplytestable.services.jobuseraccountplanenforcementservice'
        );
        $userService = $this->container->get('simplytestable.services.userservice');

        $jobUserAccountPlanEnforcementService->setUser($userService->getPublicUser());

        $fullSiteJobsPerSiteConstraint = $jobUserAccountPlanEnforcementService->getFullSiteJobLimitConstraint();

        $singleUrlJobsPerUrlConstraint = $jobUserAccountPlanEnforcementService->getSingleUrlJobLimitConstraint();

        $fullSiteJobsPerSiteConstraint->setLimit(2);
        $singleUrlJobsPerUrlConstraint->setLimit(2);

        $jobService->getManager()->persist($fullSiteJobsPerSiteConstraint);
        $jobService->getManager()->persist($singleUrlJobsPerUrlConstraint);
    }
}
